<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/vendor/stripe/stripe-php/init.php';

use Stripe\Stripe;
use Stripe\Webhook;

Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

// 1) Monta e valida o evento
$payload        = @file_get_contents('php://input');
$sig_header     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

try {
    $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\UnexpectedValueException $e) {
    error_log('❌ Invalid payload: ' . $e->getMessage());
    http_response_code(401);
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    error_log('❌ Invalid signature: ' . $e->getMessage());
    http_response_code(402);
    exit;
}

// 2) Verifica metadata e direciona
$metadata = $event->data->object->metadata ?? null;

if ($metadata && isset($metadata->quote_id)) {
    require_once __DIR__ . '/webhook_quote.php';
    error_log("✅ quote_id encontrado: {$metadata->quote_id} — encaminhado para webhook_quote.php");
    http_response_code(200);
    exit;
} elseif ($metadata && isset($metadata->booking_id)) {
    require_once __DIR__ . '/webhook_booking.php';
    error_log("✅ booking_id encontrado: {$metadata->booking_id} — encaminhado para webhook_booking.php");
    http_response_code(200);
    exit;
} else {
    error_log("⚠️ Nenhum quote_id ou booking_id encontrado na metadata do evento Stripe");
    http_response_code(200);
    exit;
}