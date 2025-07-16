<?php
namespace Src\Controllers;

use Stripe\Webhook;
use Stripe\Subscription;
use Stripe\Invoice;
use Src\Models\Booking;
use Src\Database\Connection;

class WebhookController
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance()->getPDO();
    }

    /**
     * Handler para o evento checkout.session.completed
     *
     * @param \Stripe\Checkout\Session $session
     */
    public function checkoutSessionCompleted($session)
    {
        error_log("🔍 [checkoutSessionCompleted] payload: " . json_encode($session, JSON_PRETTY_PRINT));

        $action         = $session->metadata->action ?? null;
        $bookingId      = $session->metadata->booking_id ?? null;
        $subscriptionId = $session->subscription ?? null;

        error_log("🔍 [checkoutSessionCompleted] booking_id={$bookingId}, subscription_id={$subscriptionId}, action={$action}");

        // 1) Fluxo de cancelamento de multa
        if ($action === 'cancel_break_fee' && $bookingId && $subscriptionId) {
            Subscription::update($subscriptionId, ['cancel_at_period_end' => true]);
            (new Booking())->updateRecurrenceStatus($bookingId, 'cancelled');
            return;
        }

        // 2) Fluxo de assinatura
        if ($bookingId && $subscriptionId) {
            $b = new Booking();
            $b->updateSubscriptionId($bookingId, $subscriptionId);
            $b->updateStatus($bookingId, 'scheduled');
            return;
        }

        // 3) Pagamento único (one-time)
        if ($bookingId) {
            $stripeSessionId = $session->id;

            $stmt = $this->pdo->prepare("
                UPDATE bookings
                SET status = 'paid',
                    paid = 'ok',
                    stripe_session_id = ?,
                    updated_at = NOW()
                WHERE id = ? AND status != 'paid'
            ");
            $stmt->execute([$stripeSessionId, $bookingId]);

            if ($stmt->rowCount()) {
                error_log("✅ Booking $bookingId atualizado: status='paid', paid='ok'");
            } else {
                $stmtInsert = $this->pdo->prepare("
                    INSERT INTO bookings (id, status, paid, stripe_session_id, created_at, updated_at)
                    VALUES (?, 'paid', 'ok', ?, NOW(), NOW())
                ");
                $stmtInsert->execute([$bookingId, $stripeSessionId]);

                error_log("🆕 Booking $bookingId criado com status='paid' e paid='ok'");
            }

            return;
        }

        error_log("❌ [checkoutSessionCompleted] sem metadata.booking_id");
    }

    /**
     * Handler para o evento invoice.payment_succeeded
     *
     * @param \Stripe\Invoice $invoice
     */
    public function invoicePaymentSucceeded($invoice)
    {
        error_log("🔍 [invoicePaymentSucceeded] payload: " . json_encode($invoice, JSON_PRETTY_PRINT));

        $subscriptionId = $invoice->subscription ?? null;
        $line0          = $invoice->lines->data[0] ?? null;
        $bookingId      = $line0->metadata->booking_id ?? null;

        error_log("🔍 [invoicePaymentSucceeded] booking_id={$bookingId}, subscription_id={$subscriptionId}");

        if (! $bookingId || ! $subscriptionId) {
            error_log("❌ [invoicePaymentSucceeded] dados insuficientes");
            return;
        }

        $b = new Booking();
        $b->updateSubscriptionId($bookingId, $subscriptionId);
        $b->decrementRemainingExecutions($bookingId);

        $remaining = $b->getRemainingExecutions($bookingId);
        if ($remaining > 0) {
            $b->updateStatus($bookingId, 'scheduled');
        } else {
            $b->updateStatus($bookingId, 'completed');
        }
    }

    /**
     * Handler para o evento invoice.payment_failed
     *
     * @param \Stripe\Invoice $invoice
     */
    public function invoicePaymentFailed($invoice)
    {
        error_log("🔍 [invoicePaymentFailed] payload: " . json_encode($invoice, JSON_PRETTY_PRINT));

        $subscriptionId = $invoice->subscription ?? null;
        $line0          = $invoice->lines->data[0] ?? null;
        $bookingId      = $line0->metadata->booking_id ?? null;

        error_log("🔍 [invoicePaymentFailed] booking_id={$bookingId}, subscription_id={$subscriptionId}");

        if (! $bookingId || ! $subscriptionId) {
            error_log("❌ [invoicePaymentFailed] dados insuficientes");
            return;
        }

        $b = new Booking();
        $b->updateStatus($bookingId, 'pending');
    }

    /**
     * Handler para o evento customer.subscription.deleted
     *
     * @param string $subscriptionId
     */
    public function subscriptionCancelled($subscriptionId)
    {
        error_log("🔍 [subscriptionCancelled] subscription_id={$subscriptionId}");
        $b = new Booking();
        $b->updateRecurrenceStatusBySubscription($subscriptionId, 'cancelled');
    }

    /**
     * Entry-point único para todos os webhooks Stripe
     */
    public function handle()
    {
        $payload        = @file_get_contents('php://input');
        $sigHeader      = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            error_log('❌ [webhook.handle] Invalid payload: ' . $e->getMessage());
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            error_log('❌ [webhook.handle] Invalid signature: ' . $e->getMessage());
            http_response_code(400);
            exit();
        }

        error_log("✅ [webhook.handle] Event: {$event->type}");

        $this->processEvent($event);

        http_response_code(200);
    }

    /**
     * Roteia cada evento Stripe para o handler correto.
     *
     * @param \Stripe\Event $event
     */
    public function processEvent($event): void
    {
        switch ($event->type) {
            case 'checkout.session.completed':
                error_log("🔔 [processEvent] checkout.session.completed");
                $this->checkoutSessionCompleted($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                error_log("💰 [processEvent] invoice.payment_succeeded");
                $this->invoicePaymentSucceeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                error_log("❗ [processEvent] invoice.payment_failed");
                $this->invoicePaymentFailed($event->data->object);
                break;

            case 'customer.subscription.deleted':
                error_log("🧨 [processEvent] customer.subscription.deleted");
                $this->subscriptionCancelled($event->data->object->id);
                break;

            default:
                error_log("⚠️ [processEvent] Unhandled event: {$event->type}");
                break;
        }
    }
}
