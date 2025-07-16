<?php
namespace Src\Controllers;

require_once __DIR__ . '/../../booking/vendor/autoload.php';

use Stripe\Checkout\Session;
use Stripe\Stripe;
use Src\Models\Booking;

class StripeController {
    public function __construct() {
        $secretKey = env('STRIPE_SECRET_KEY');
        if (!$secretKey) {
            throw new \Exception("Stripe secret key not set in .env.php");
        }

        Stripe::setApiKey($secretKey);
    }

    public function createCheckoutSession(array $booking, ?string $interval, int $minHours = 48) {
        $baseUrl   = env('APP_URL', 'https://bluefacilityservices.com.au');
        $successUrl = rtrim($baseUrl, '/') . "/booking/success.php?session_id={CHECKOUT_SESSION_ID}";
        $cancelUrl  = rtrim($baseUrl, '/') . "/booking/cancel.php";

        // Dados do produto
        $priceData = [
            'currency'     => 'usd',
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

        // Se for assinatura, garanta metadata na subscription_data também
        if ($interval) {
            $sessionParams['subscription_data'] = [
                'metadata' => [
                    'booking_id' => $booking['id'],
                ],
            ];
        }

        // Cria a sessão no Stripe
        $session = Session::create($sessionParams);

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