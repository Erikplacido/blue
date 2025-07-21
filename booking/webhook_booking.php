<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/config.php';

// Usa o $pdo e $event já definidos no webhook.php
if (!isset($event)) {
    error_log("Webhook Stripe: variável \$event não definida.");
    http_response_code(400);
    exit;
}

// 2) Garante diretório de logs e grava evento bruto
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
@file_put_contents(
    "$logDir/stripe_webhook.log",
    date('Y-m-d H:i:s') . " Event: {$event->type}\n" . json_encode($event, JSON_PRETTY_PRINT) . "\n\n",
    FILE_APPEND
);
error_log("✅ Stripe webhook received: {$event->type}");

// 3) Deduplicação
$eventId = $event->id;
if (eventAlreadyProcessed($eventId)) {
    error_log("⏩ Duplicate event: $eventId");
    http_response_code(200);
    exit;
}

// 4) Processamento principal do evento
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    $bookingId = $session->metadata->booking_id ?? null;

    if ($bookingId) {
        $stripeSessionId = $session->id;

        $stmt = $pdo->prepare("
            UPDATE bookings
            SET status = 'paid',
                stripe_session_id = ?,
                updated_at = NOW()
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$stripeSessionId, $bookingId]);

        if ($stmt->rowCount()) {
            error_log("✅ Booking $bookingId atualizado para 'paid' via Stripe");
        } else {
            error_log("⚠️ Nenhuma linha afetada — booking_id inexistente ou já não está mais em 'pending'");
        }
    } else {
        error_log("❌ booking_id não encontrado na metadata da sessão Stripe");
    }
}

// 5) Marca como processado e responde OK
markEventAsProcessed($eventId, $event->type);
http_response_code(200);
exit;

// —————————————————————————————  
function eventAlreadyProcessed(string $eventId): bool {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stripe_events WHERE id = ?");
    $stmt->execute([$eventId]);
    return $stmt->fetchColumn() > 0;
}

function markEventAsProcessed(string $eventId, string $eventType): void {
    global $pdo;
    $stmt = $pdo->prepare(
        "INSERT INTO stripe_events (id, event_type, processed_at) VALUES (?, ?, NOW())"
    );
    $stmt->execute([$eventId, $eventType]);
}
