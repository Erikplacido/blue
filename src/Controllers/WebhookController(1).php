<?php
namespace Src\Controllers;

use Src\Models\Booking;
use Stripe\Webhook;
use Stripe\Subscription;

class WebhookController
{
    /**
     * Handler para o evento checkout.session.completed
     *
     * @param \Stripe\Checkout\Session $session
     */
    public function checkoutSessionCompleted($session)
    {
        // captura metadata
        $action         = $session->metadata->action ?? null;
        $bookingId      = $session->metadata->booking_id ?? null;
        $subscriptionId = $session->subscription       ?? null;

        // se for o fluxo de pagamento de multa, cancela a assinatura
        if ($action === 'cancel_break_fee') {
            if ($bookingId && $subscriptionId) {
                Subscription::update($subscriptionId, [
                    'cancel_at_period_end' => true,
                ]);
                (new Booking())->updateRecurrenceStatus($bookingId, 'canceled');
            }
            return;
        }

        // para fluxo normal de criação de assinatura, grava o subscription_id
        if ($bookingId && $subscriptionId) {
            (new Booking())->updateSubscriptionId($bookingId, $subscriptionId);
        }

        // ... aqui você pode adicionar notificações, envio de e-mail, etc. ...
    }

    /**
     * Handler para o evento invoice.payment_succeeded
     *
     * @param \Stripe\Invoice $invoice
     */
    public function invoicePaymentSucceeded($invoice)
    {
        $subscriptionId = $invoice->subscription;
        // se o invoice carregar metadata da booking (primeira sessão)
        $bookingId = $invoice->lines->data[0]->metadata->booking_id ?? null;

        if ($bookingId && $subscriptionId) {
            // assegura que o subscription_id esteja gravado
            (new Booking())->updateSubscriptionId($bookingId, $subscriptionId);
            // decrementa o contador de execuções restantes
            (new Booking())->decrementRemainingExecutions($bookingId);
        }
    }

    /**
     * Rota única para lidar com todos os webhooks Stripe
     */
    public function handle()
    {
        $payload        = @file_get_contents('php://input');
        $sigHeader      = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            exit();
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->checkoutSessionCompleted($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                $this->invoicePaymentSucceeded($event->data->object);
                break;

            // ... outros eventos se necessário ...
        }

        http_response_code(200);
    }
}