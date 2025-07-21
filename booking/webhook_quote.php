<?php
// 1. Debug em dev
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Conexão com o banco de referrals
require_once __DIR__ . '/../bluereferralclub/conexao.php';

// 3. Usa o $event já criado no webhook.php
if (!isset($event)) {
    error_log("Webhook Stripe: variável \$event não definida.");
    http_response_code(400);
    exit();
}

// 4. Filtra só checkout.session.completed
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    $quoteId = $session->metadata->quote_id ?? null;

    $gstAmount = $session->total_details->amount_tax ?? 0; // em centavos
    $gstAmountDecimal = $gstAmount / 100; // convertendo pra decimal
    $paymentIntent = $session->payment_intent ?? null;

    if ($quoteId) {
        // Atualiza apenas os campos necessários
        $stmt = $conn->prepare("
            UPDATE referrals
            SET
                status = 'Paid',
                paid = 1,
                stripe_paid = 1
            WHERE id = ?
        ");
        $stmt->bind_param("i", $quoteId); // "i" para inteiro
        $stmt->execute();

        error_log("Webhook Stripe: Linhas afetadas = " . $stmt->affected_rows);
        $stmt->close();
    } else {
        error_log("Webhook Stripe: quote_id ausente na sessão.");
    }
} else {
    error_log("Webhook Stripe: Evento não suportado: " . $event->type);
}

// 5. Responde 200 para Stripe
http_response_code(200);
