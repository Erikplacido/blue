<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/vendor/stripe/stripe-php/init.php';

global $pdo; // 👈 ESSENCIAL para evitar o erro prepare() on null

use Stripe\Stripe;
use Stripe\Webhook;
use Src\Controllers\WebhookController;

Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

try {
    $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\UnexpectedValueException $e) {
    error_log('Stripe Webhook Error: Invalid payload - ' . $e->getMessage());
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    error_log('Stripe Webhook Error: Invalid signature - ' . $e->getMessage());
    http_response_code(400);
    exit();
}

$webhookController = new WebhookController();
$eventId = $event->id;

// Log para qualquer evento recebido
error_log("✅ Stripe webhook received: {$event->type}");

if (eventAlreadyProcessed($eventId)) {
    error_log("⏩ Duplicate event skipped: $eventId");
    http_response_code(200);
    exit();
}

switch ($event->type) {
    case 'checkout.session.completed':
        error_log("🔔 Evento checkout.session.completed recebido. Processando...");
        $webhookController->checkoutSessionCompleted($event->data->object);
        break;
    case 'invoice.payment_succeeded':
        $webhookController->invoicePaymentSucceeded($event->data->object);
        break;
    case 'invoice.payment_failed':
        $webhookController->invoicePaymentFailed($event->data->object);
        break;
    case 'customer.subscription.deleted':
        $webhookController->subscriptionCancelled($event->data->object->id);
        break;
    default:
        error_log("⚠️ Unhandled Stripe event: {$event->type}");
        break;
}

markEventAsProcessed($eventId, $event->type);
http_response_code(200);

// Funções auxiliares
function eventAlreadyProcessed(string $eventId): bool {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stripe_events WHERE id = ?");
    $stmt->execute([$eventId]);
    return $stmt->fetchColumn() > 0;
}

function markEventAsProcessed(string $eventId, string $eventType): void {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO stripe_events (id, event_type, processed_at) VALUES (?, ?, NOW())");
    $stmt->execute([$eventId, $eventType]);
}