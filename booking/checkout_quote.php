<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Carrega config e Stripe
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/vendor/stripe/stripe-php/init.php';
\Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

// 2. Conexão com o banco de referrals
require_once __DIR__ . '/../bluereferralclub/conexao.php';

// 3. Recebe o quote_id
$quoteId = isset($_GET['quote_id']) ? intval($_GET['quote_id']) : 0;
if (!$quoteId) {
    die('Cotação inválida.');
}

// 4. Busca detalhes da cotação
$stmt = $conn->prepare("
    SELECT service_name, booking_value, referred, email
    FROM referrals
    WHERE id = ?
");
$stmt->bind_param("i", $quoteId);
$stmt->execute();
$stmt->bind_result($serviceName, $bookingValue, $referred, $customerEmail);
if (!$stmt->fetch()) {
    die('Cotação não encontrada.');
}
$stmt->close();

// 5. Define o Tax Rate do GST (adicione essa chave no seu .env ou config.php)
$gstTaxRateId = env('STRIPE_GST_TAX_RATE_ID'); // Ex: 'txr_1JXXXXXXXXXXXX'

// 6. Cria a sessão de Checkout com metadata e GST
try {
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency'    => DEFAULT_CURRENCY,
                'unit_amount' => intval(round($bookingValue * 100)), // em centavos
                'product_data' => [
                    'name'        => $serviceName,
                    'description' => "Serviço solicitado por $referred",
                ],
            ],
            'quantity'  => 1,
            // Aplica 10% de GST
            'tax_rates' => [$gstTaxRateId],
        ]],
        'mode'           => 'payment',
        'customer_email' => $customerEmail,
        'metadata'       => ['quote_id' => $quoteId],
        'success_url'    => 'https://bluefacilityservices.com.au/booking/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'     => 'https://bluefacilityservices.com.au/booking/cancel.php?quote_id=' . $quoteId,
    ]);

    // 7. Redireciona para o Stripe Checkout
    header("Location: " . $session->url);
    exit;
} catch (\Exception $e) {
    error_log("Stripe error: " . $e->getMessage());
    die('Erro ao iniciar pagamento. Por favor, tente novamente mais tarde.');
}
