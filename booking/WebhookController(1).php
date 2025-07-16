<?php
namespace Src\Controllers;

use PDO;

class WebhookController
{
    protected $pdo;

    public function __construct()
    {
        require_once __DIR__ . '/../config.php';
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Marcar reserva como paga após checkout inicial
     */
    public function checkoutSessionCompleted($session)
    {
        $bookingId = $session->metadata->booking_id ?? null;
        $subscriptionId = $session->subscription ?? null;

        if (!$bookingId) {
            error_log("❌ No booking_id found in session metadata.");
            return;
        }

        $stmt = $this->pdo->prepare("
            UPDATE bookings 
            SET status = 'paid', stripe_subscription_id = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$subscriptionId, $bookingId]);

        error_log("✅ Booking #$bookingId marked as PAID via checkout.session.completed. Subscription ID: $subscriptionId");
    }

    /**
     * Marcar pagamento de fatura de recorrência como sucesso
     */
    public function invoicePaymentSucceeded($invoice)
    {
        $subscriptionId = $invoice->subscription ?? null;

        if (!$subscriptionId) {
            error_log("❌ No subscription ID found in invoice.");
            return;
        }

        $stmt = $this->pdo->prepare("SELECT id FROM bookings WHERE stripe_subscription_id = ? LIMIT 1");
        $stmt->execute([$subscriptionId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            error_log("❌ No booking found for subscription ID: $subscriptionId");
            return;
        }

        $bookingId = $booking['id'];

        $stmt = $this->pdo->prepare("UPDATE bookings SET status = 'paid', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$bookingId]);

        error_log("✅ Recurring payment succeeded. Booking #$bookingId updated to PAID.");
    }

    /**
     * Marcar falha de pagamento da fatura
     */
    public function invoicePaymentFailed($invoice)
    {
        $subscriptionId = $invoice->subscription ?? null;

        if (!$subscriptionId) {
            error_log("❌ No subscription ID found in failed invoice.");
            return;
        }

        $stmt = $this->pdo->prepare("SELECT id FROM bookings WHERE stripe_subscription_id = ? LIMIT 1");
        $stmt->execute([$subscriptionId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            error_log("❌ No booking found for failed subscription: $subscriptionId");
            return;
        }

        $bookingId = $booking['id'];

        $stmt = $this->pdo->prepare("UPDATE bookings SET status = 'pending', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$bookingId]);

        error_log("⚠️ Payment FAILED for booking #$bookingId. Status reverted to PENDING.");
    }
}