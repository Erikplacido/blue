<?php
namespace Src\Controllers;

require_once __DIR__ . '/../../booking/vendor/autoload.php';

use Stripe\Checkout\Session;
use Stripe\Stripe;
use Src\Models\Booking;

class StripeController {
    protected $pdo;

    public function __construct() {
        require_once __DIR__ . '/../config.php';
        global $pdo;
        $this->pdo = $pdo;

        $secretKey = env('STRIPE_SECRET_KEY');
        if (!$secretKey) {
            throw new \Exception("Stripe secret key not set in .env.php");
        }

        Stripe::setApiKey($secretKey);
    }

    public function createCheckoutSession(array $booking, ?string $interval, int $minHours = 48) {
        $baseUrl    = env('APP_URL', 'https://bluefacilityservices.com.au');
        $successUrl = rtrim($baseUrl, '/') . "/booking/success.php?session_id={CHECKOUT_SESSION_ID}";
        $cancelUrl  = rtrim($baseUrl, '/') . "/booking/cancel.php";

        // Dados do produto
        $priceData = [
            'currency'     => 'aud',
            'product_data' => [
                'name' => 'Service Booking: ' . $booking['execution_date'],
            ],
            'unit_amount'  => (int)($booking['total_price'] * 100),
        ];

        // Se for assinatura, adiciona plano recorrente
        if ($interval) {
            $mappedInterval = $this->mapInterval($interval);
            $priceData['recurring'] = [
                'interval' => $mappedInterval['interval'],
            ];
            if (!empty($mappedInterval['interval_count'])) {
                $priceData['recurring']['interval_count'] = $mappedInterval['interval_count'];
            }
        }

        $lineItems = [[
            'price_data' => $priceData,
            'quantity'   => 1,
        ]];

        // Parâmetros base da sessão Stripe
        $sessionParams = [
            'payment_method_types' => ['card'],
            'line_items'           => $lineItems,
            'mode'                 => $interval ? 'subscription' : 'payment',
            'success_url'          => $successUrl,
            'cancel_url'           => $cancelUrl,
            'metadata'             => [
                'booking_id' => $booking['id'],
            ],
            'customer_email'       => $booking['email'] ?? null,
        ];

        // Se for assinatura, garante metadata também na subscription
        if ($interval) {
            $sessionParams['subscription_data'] = [
                'metadata' => [
                    'booking_id' => $booking['id'],
                ],
            ];
        }

        // Cria a sessão no Stripe
        $session = Session::create($sessionParams);

        // DEBUG: log completo da sessão
        error_log("🔍 Stripe Session criado: " . json_encode($session, JSON_PRETTY_PRINT));

        // Salva stripe_session_id no banco
        $stmt = $this->pdo->prepare("UPDATE bookings SET stripe_session_id = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$session->id, $booking['id']]);
        error_log("✅ stripe_session_id salvo: {$session->id} para booking #{$booking['id']}");

        // Se for assinatura, salva também stripe_subscription_id
        if (!empty($session->subscription)) {
            $stmt = $this->pdo->prepare("UPDATE bookings SET stripe_subscription_id = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$session->subscription, $booking['id']]);
            error_log("✅ stripe_subscription_id salvo: {$session->subscription} para booking #{$booking['id']}");
        }

        return $session;
    }

    private function mapInterval($interval): array {
        return match ($interval) {
            'P7D'  => ['interval' => 'week'],
            'P15D' => ['interval' => 'week', 'interval_count' => 2], // quinzenal
            'P30D' => ['interval' => 'month'],
            default => ['interval' => 'month'],
        };
    }
}
