<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../src/config.php';

use Src\Models\Customer;
use Src\Models\Booking;
use Src\Models\Service;
use Src\Controllers\StripeController;
use Src\Services\ContractCalculator;

// 1. Validar entrada
$input = filter_input_array(INPUT_POST, [
    'address'         => FILTER_DEFAULT,
    'postcode'        => FILTER_DEFAULT,
    'latitude'        => FILTER_VALIDATE_FLOAT,
    'longitude'       => FILTER_VALIDATE_FLOAT,
    'recurrence'      => FILTER_DEFAULT,
    'execution_date'  => FILTER_DEFAULT,
    'time_window'     => FILTER_DEFAULT,
    'first_name'      => FILTER_DEFAULT,
    'last_name'       => FILTER_DEFAULT,
    'email'           => FILTER_VALIDATE_EMAIL,
    'phone'           => FILTER_DEFAULT,
    'abn_or_tfn'      => FILTER_DEFAULT,
    'discountCode'    => FILTER_DEFAULT,
    'pointsApplied'   => FILTER_VALIDATE_INT,
]);

if (!$input || in_array(null, $input, true)) {
    die('All required fields must be filled out correctly.');
}

// 2. Fallback e extração do time_window (define start_time e end_time)
$timeWindow = trim($input['time_window'] ?? '') ?: '00:00 - 23:59';
$timeParts  = explode(' - ', $timeWindow);
$startTime  = $timeParts[0] ?? '00:00';
$endTime    = $timeParts[1] ?? '23:59';

// 3. Verificar tempo mínimo de 48h
$date = new DateTime($input['execution_date'] . ' ' . $startTime);
$now  = new DateTime();
$diff = $now->diff($date)->h + $now->diff($date)->days * 24;

if ($diff < 48) {
    die('Please choose a slot at least 48 hours in advance.');
}

// 4. Tratar recorrência
$validRecurrences = ['one-time', 'weekly', 'fortnightly', 'monthly'];
$recurrence = in_array($input['recurrence'], $validRecurrences)
    ? $input['recurrence']
    : 'one-time';

// 4.1. Validar e capturar duração do contrato
$months = filter_input(INPUT_POST, 'contractDuration', FILTER_VALIDATE_INT);
if ($recurrence !== 'one-time') {
    if (! in_array($months, [3, 6, 12], true)) {
        die('Please choose a valid contract duration (3, 6 or 12 months).');
    }
} else {
    // one-time: ignora duration e considera 1 execução
    $months = 1;
}

// 4.2. Traduz intervalos ISO
switch ($recurrence) {
    case 'weekly':
        $interval = 'P7D';
        break;
    case 'fortnightly':
        $interval = 'P15D';
        break;
    case 'monthly':
        $interval = 'P30D';
        break;
    default:
        $interval = null;
        break;
}

// 5. Obter serviço
$serviceModel = new Service();
$service = $serviceModel->getBySlug('house-cleaning');
if (!$service) {
    die('Service not found.');
}

// 6. Criar ou localizar cliente
$customerModel = new Customer();
$customer = $customerModel->findOrCreate([
    'first_name' => $input['first_name'],
    'last_name'  => $input['last_name'],
    'email'      => $input['email'],
    'phone'      => $input['phone'],
    'abn_or_tfn' => $input['abn_or_tfn'],
]);

// 7. Calcular preço total (prefere o baseTotal vindo do form, senão recalc)
$baseTotal = isset($_POST['baseTotal'])
    ? floatval($_POST['baseTotal'])
    : Booking::calculateTotal($_POST, $service);
$totalPrice = $baseTotal;

// 8. Calcular taxas de preferência desmarcadas (extra_fee)
$db = \Src\Database\Connection::getInstance()->getPDO();
$stmt = $db->query("SELECT id, is_checked_default, extra_fee FROM preference_fields WHERE field_type = 'checkbox'");
$defaults = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($defaults as $pref) {
    if ($pref['is_checked_default'] && ($_POST['preferences'][$pref['id']] ?? null) !== '1') {
        $totalPrice += floatval($pref['extra_fee']);
    }
}

// 9. Aplicar desconto de cupom
$discountAmount  = 0;
$couponCodeInput = trim($input['discountCode'] ?? '');
$validCouponCode = null; // valor final a ser salvo no banco

if ($couponCodeInput !== '') {
    $stmt  = $db->prepare("SELECT * FROM discount_coupons WHERE code = :code AND is_active = 1 LIMIT 1");
    $stmt->execute([':code' => $couponCodeInput]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($coupon) {
        if ($coupon['discount_type'] === 'percent') {
            $discountAmount = $totalPrice * floatval($coupon['amount']) / 100;
        } else {
            $discountAmount = floatval($coupon['amount']);
        }
        $totalPrice -= $discountAmount;
        $validCouponCode = $couponCodeInput; // apenas define se for válido
    } else {
        $validCouponCode = $couponCodeInput; // ainda salva o valor digitado mesmo se inválido
        error_log('Invalid or inactive coupon used: ' . $couponCodeInput);
    }
}

// 10. Aplicar pontos do usuário
$pointsApplied = $input['pointsApplied'] ?? 0;
if ($pointsApplied > 0) {
    $totalPrice -= $pointsApplied;
}

// Garantir que o total não fique negativo
if ($totalPrice < 0) {
    $totalPrice = 0;
}

// 10.1. Calcular número total de execuções (contract_length)
$contractLength = ContractCalculator::calculate($recurrence, $months);

// 11. Criar agendamento
$bookingModel = new Booking();
$booking = $bookingModel->create([
    'customer_id'         => $customer['id'],
    'service_id'          => $service['id'],
    'recurrence'          => $recurrence,
    'contract_length'     => $contractLength,
    'execution_date'      => $input['execution_date'],
    'num_days'            => $_POST['num_days'] ?? 1,
    'start_time'          => $startTime,
    'end_time'            => $endTime,
    'recurrence_interval' => $interval,
    'address'             => $input['address'],
    'postcode'            => $input['postcode'],
    'latitude'            => $input['latitude'],
    'longitude'           => $input['longitude'],
    'total_price'         => $totalPrice,
    'coupon_code'         => $validCouponCode,
    'points_used'        => $pointsApplied,
    'status'              => 'pending',
]);

// 12. Inclusões
if (!empty($_POST['included_qty'])) {
    foreach ($_POST['included_qty'] as $inclusionId => $qty) {
        $bookingModel->addInclusion($booking['id'], $inclusionId, $qty);
    }
}

// 13. Extras
if (!empty($_POST['extra_qty'])) {
    foreach ($_POST['extra_qty'] as $extraId => $qty) {
        $bookingModel->addExtra($booking['id'], $extraId, $qty);
    }
}

// 14. Preferências
if (!empty($_POST['preferences'])) {
    foreach ($_POST['preferences'] as $prefId => $value) {
        $bookingModel->addPreference($booking['id'], $prefId, $value);
    }
}

// 15. Criar sessão Stripe
$stripeController = new StripeController();
$session = $stripeController->createCheckoutSession($booking, $interval, 48);

if (!$session || !isset($session->url)) {
    die('Stripe session creation failed.');
}

// 16. Atualização imediata do stripe_subscription_id (opcional)
// Grava já a subscription_id se for assinatura
if ($interval && !empty($session->subscription)) {
    $bookingModel->updateSubscriptionId($booking['id'], $session->subscription);
}

// 17. Redirecionar para o Stripe Checkout
header('Location: ' . $session->url);
exit;