<?php
// 1. Debug em dev
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Carrega config e Stripe
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/vendor/stripe/stripe-php/init.php';
\Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

// 3. Conexão com o banco de referrals
require_once __DIR__ . '/../bluereferralclub/conexao.php';

// 4. Lê payload e verifica assinatura
$payload    = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$endpoint_secret = env('STRIPE_WEBHOOK_SECRET_QUOTE');

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit();
}

// 5. Filtra só checkout.session.completed
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    $quoteId = $session->metadata->quote_id ?? null;

    $gstAmount = $session->total_details->amount_tax ?? 0; // em centavos
    $gstAmountDecimal = $gstAmount / 100; // convertendo pra decimal
    $paymentIntent = $session->payment_intent ?? null;

    if ($quoteId) {
        // 🛠️ Removemos a restrição de status para garantir atualização
        $stmt = $conn->prepare("
            UPDATE referrals
            SET
                status = 'Paid',
                paid = 1,
                stripe_paid = 1,
                commission_amount = ?,
                payment_reference = ?
            WHERE id = ?
        ");
        $stmt->bind_param("dsi", $gstAmountDecimal, $paymentIntent, $quoteId);
        $stmt->execute();

        error_log(\"Webhook Stripe: Linhas afetadas = \" . $stmt->affected_rows);
        $stmt->close();
    } else {
        error_log(\"Webhook Stripe: quote_id ausente na sessão.\");
    }
}

// 7. Responde 200 para Stripe
http_response_code(200);
