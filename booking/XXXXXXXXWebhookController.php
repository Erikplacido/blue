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
        // DEBUG: mostra todo o payload recebido
        error_log("🔍 DEBUG checkoutSessionCompleted payload: " . json_encode($session, JSON_PRETTY_PRINT));

        $bookingId = $session->metadata->booking_id ?? null;
        error_log("🔍 DEBUG bookingId extraído: " . var_export($bookingId, true));

        if (!$bookingId) {
            error_log("❌ checkoutSessionCompleted: metadata.booking_id ausente!");
            return;
        }

        $subscriptionId = $session->subscription ?? null;

        // Atualiza status para 'paid' e salva subscription_id (se houver)
        $stmt = $this->pdo->prepare("
            UPDATE bookings 
            SET status = 'paid', 
                stripe_subscription_id = ?, 
                updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$subscriptionId, $bookingId]);

        // Quantas linhas foram afetadas?
        $affected = $stmt->rowCount();
        error_log("✅ checkoutSessionCompleted: linhas afetadas = $affected. Subscription ID: " . ($subscriptionId ?? 'N/A'));
    }

    /**
     * Marcar pagamento de fatura de recorrência como sucesso
     */
    public function invoicePaymentSucceeded($invoice)
    {
        error_log("🔍 DEBUG invoicePaymentSucceeded payload: " . json_encode($invoice, JSON_PRETTY_PRINT));

        $subscriptionId = $invoice->subscription ?? null;

        if (!$subscriptionId) {
            error_log("❌ invoicePaymentSucceeded: subscription ausente!");
            return;
        }

        $stmt = $this->pdo->prepare("SELECT id FROM bookings WHERE stripe_subscription_id = ? LIMIT 1");
        $stmt->execute([$subscriptionId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            error_log("❌ invoicePaymentSucceeded: nenhuma booking com subscription_id = $subscriptionId");
            return;
        }

        $bookingId = $booking['id'];
        $stmt = $this->pdo->prepare("UPDATE bookings SET status = 'paid', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$bookingId]);

        $affected = $stmt->rowCount();
        error_log("✅ invoicePaymentSucceeded: Booking #$bookingId atualizado para PAID (linhas afetadas = $affected)");
    }

    /**
     * Marcar falha de pagamento da fatura
     */
    public function invoicePaymentFailed($invoice)
    {
        error_log("🔍 DEBUG invoicePaymentFailed payload: " . json_encode($invoice, JSON_PRETTY_PRINT));

        $subscriptionId = $invoice->subscription ?? null;

        if (!$subscriptionId) {
            error_log("❌ invoicePaymentFailed: subscription ausente!");
            return;
        }

        $stmt = $this->pdo->prepare("SELECT id FROM bookings WHERE stripe_subscription_id = ? LIMIT 1");
        $stmt->execute([$subscriptionId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            error_log("❌ invoicePaymentFailed: nenhuma booking com subscription_id = $subscriptionId");
            return;
        }

        $bookingId = $booking['id'];
        $stmt = $this->pdo->prepare("UPDATE bookings SET status = 'pending', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$bookingId]);

        $affected = $stmt->rowCount();
        error_log("⚠️ invoicePaymentFailed: Booking #$bookingId status revertido para PENDING (linhas afetadas = $affected)");
    }
}
