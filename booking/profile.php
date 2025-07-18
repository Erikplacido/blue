<?php
// booking/profile.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require __DIR__ . '/../src/config.php';
require_once __DIR__ . '/vendor/autoload.php';  // Stripe e outros

use Src\Models\Customer;
use Src\Models\Address;
use Src\Models\Booking;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Checkout\Session;
use Src\Services\BreakFeeCalculator;

// 0) Verifica se veio do Stripe após pagamento de multa
$errors  = [];
$success = '';

$customerId = $_SESSION['customer_id'] ?? null;
if (!$customerId) {
    header('Location: login.php');
    exit;
}

$customerModel = new Customer();
$bookingModel  = new Booking();

// 0.1) Tratar redirect do Stripe após pagamento da multa
if (isset($_GET['paid'], $_GET['booking_id']) && $_GET['paid'] === '1') {
    Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
    $paidBookingId = (int) $_GET['booking_id'];
    $paidBooking   = $bookingModel->getById($paidBookingId);
    if ($paidBooking && !empty($paidBooking['stripe_subscription_id'])) {
        // cancela ao fim do período atual
        Subscription::update($paidBooking['stripe_subscription_id'], [
            'cancel_at_period_end' => true,
        ]);
        
        // --- salvar valor da multa ---
        $breakFee = \Src\Services\BreakFeeCalculator::calculate($paidBooking);
        $bookingModel->updateBreakFee($paidBookingId, $breakFee);
        
        // atualiza status no banco
        $bookingModel->updateRecurrenceStatus(
            $paidBookingId,
            Booking::RECURRENCE_STATUS_CANCELLED
        );
        $success = 'Multa paga com sucesso! Sua assinatura será cancelada ao fim do período atual.';
    }
}

// 1) Busca dados de cliente e endereços
$customer  = $customerModel->getById($customerId);
$addresses = (new Address())->getAllByCustomer($customerId);

// fallback: se não houver endereços, usa o da primeira reserva
if (empty($addresses)) {
    $firstBooking = $bookingModel->getFirstByCustomer($customerId);
    if ($firstBooking) {
        $addresses[] = [
            'id'            => null,
            'label'         => 'Endereço da primeira reserva',
            'address_line1' => $firstBooking['address'],
            'address_line2' => '',
            'city'          => '',
            'state'         => '',
            'postcode'      => $firstBooking['postcode'],
            'latitude'      => $firstBooking['latitude'],
            'longitude'     => $firstBooking['longitude'],
        ];
    }
}

// ───────────────
// 2) Cancelar assinatura (confirmação + pagamento de multa)
// ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_subscription'])) {
    $bookingId = (int) $_POST['booking_id'];
    $booking   = $bookingModel->getById($bookingId);

    // valida booking
    if (! $booking || $booking['customer_id'] != $customerId || empty($booking['stripe_subscription_id'])) {
        $errors['general'] = 'Assinatura inválida ou não encontrada.';
    } else {
        // calcula multa
        $breakFee    = BreakFeeCalculator::calculate($booking);
        $breakFeeFmt = number_format($breakFee, 2, ',', '.');

        // 2.1) exibe página de confirmação com valor da multa
        if (! isset($_POST['confirm_break_fee'])) {
            echo <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Pagamento de Multa</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="card">
    <h2>Multa por quebra de contrato</h2>
    <p>O valor da multa é <strong>R\$ {$breakFeeFmt}</strong>.</p>
    <form method="POST">
      <input type="hidden" name="cancel_subscription" value="1">
      <input type="hidden" name="booking_id" value="{$bookingId}">
      <input type="hidden" name="confirm_break_fee" value="1">
      <button type="submit" class="btn btn-primary">Pagar multa e cancelar assinatura</button>
      <a href="profile.php" class="btn btn-outline">Voltar</a>
    </form>
  </div>
</body>
</html>
HTML;
            exit;
        }

        // 2.2) usuário confirmou: cria Stripe Checkout Session para pagamento da multa
        $appUrl   = env('APP_URL') ?: 'https://bluefacilityservices.com.au';
        $baseUrl  = rtrim($appUrl, '/');

        
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $successUrl = $baseUrl . "/booking/profile.php?paid=1&booking_id={$bookingId}";
        $cancelUrl  = $baseUrl . "/booking/profile.php";

        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode'                 => 'payment',
            'line_items'           => [[
                'price_data' => [
                    'currency'     => DEFAULT_CURRENCY,
                    'unit_amount'  => intval($breakFee * 100),
                    'product_data' => [
                        'name' => "Multa quebra booking #{$bookingId}"
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata'    => [
                'booking_id' => $bookingId,
                'action'     => 'cancel_break_fee',
            ],
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
        ]);

        header('Location: ' . $session->url);
        exit;
    }
}

// 3) Mensagem de pausa (se veio ?paused=1)
$subscriptionId = $_SESSION['subscription_id'] ?? '';
$pausedMessage  = '';
if (isset($_GET['paused']) && $_GET['paused'] === '1') {
    $pausedMessage = 'Assinatura pausada até ' . date('d/m/Y', strtotime('+1 month'));
}

// 4) Atualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['cancel_subscription'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $email      = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $phone      = trim($_POST['phone'] ?? '');
    $abn_or_tfn = trim($_POST['abn_or_tfn'] ?? '');

    if (!$first_name)  $errors['first_name'] = 'Preencha seu nome.';
    if (!$last_name)   $errors['last_name']  = 'Preencha seu sobrenome.';
    if (!$email)       $errors['email']      = 'E-mail inválido.';
    if (!$phone)       $errors['phone']      = 'Preencha seu telefone.';
    if (!$abn_or_tfn)  $errors['abn_or_tfn'] = 'Preencha o ABN ou TFN.';

    if (empty($errors)) {
        $updated = $customerModel->updateProfile(
            $customerId,
            $first_name,
            $last_name,
            $email,
            $phone,
            $abn_or_tfn
        );
        if ($updated) {
            $success  = 'Perfil atualizado com sucesso.';
            $customer = $customerModel->getById($customerId);
        } else {
            $errors['general'] = 'Erro ao salvar alterações.';
        }
    }
}

// 5) Busca assinatura ativa
$activeRecurringBooking = null;
$bookings = $bookingModel->getAllByCustomer($customerId);
foreach ($bookings as $b) {
    if (
        !empty($b['stripe_subscription_id']) &&
        ($b['recurrence_status'] ?? '') === Booking::RECURRENCE_STATUS_ACTIVE
    ) {
        $activeRecurringBooking = $b;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Meu Perfil</title>
  <!-- Fonte Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;800&display=swap" rel="stylesheet">
  <!-- CSS do template -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <section class="hero">
    <div class="hero__left">
      <div class="hero__card">

        <!-- Cartão de Assinatura Ativa -->
        <?php if ($activeRecurringBooking): ?>
          <div class="card mb-6">
            <div class="card-header"><h3>Assinatura Ativa</h3></div>
            <div class="card-body">
              <?php if ($pausedMessage): ?>
                <div class="text-success mb-4"><?= htmlspecialchars($pausedMessage) ?></div>
              <?php endif; ?>
              <p>
                Recorrência: <strong><?= htmlspecialchars($activeRecurringBooking['recurrence']) ?></strong><br>
                Início: <?= htmlspecialchars($activeRecurringBooking['execution_date']) ?><br>
              </p>
              <form method="POST">
                <input type="hidden" name="cancel_subscription" value="1">
                <input type="hidden" name="booking_id"         value="<?= $activeRecurringBooking['id'] ?>">
                <button type="submit" class="btn btn-outline">
                  Cancelar Assinatura
                </button>
              </form>
              <form action="pause.php" method="POST" style="display:inline">
                <input type="hidden" name="booking_id" value="<?= $activeRecurringBooking['id'] ?>">
                <button type="submit" class="btn btn-warning">
                  ⏸️ Pausar por 1 mês
                </button>
              </form>
            </div>
          </div>
        <?php endif; ?>

        <!-- Cartão de Edição de Perfil -->
        <div class="card mb-6">
          <div class="card-header">
            <h2>Meu Perfil</h2>
            <a href="logout.php" class="btn btn-outline">Sair</a>
          </div>
          <div class="card-body">
            <?php if ($success): ?>
              <div class="text-success mb-4"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors['general'])): ?>
              <div class="error-text mb-4"><?= htmlspecialchars($errors['general']) ?></div>
            <?php endif; ?>

            <form method="POST" class="info-form">
              <h2>Atualize seus dados</h2>
              <input type="text"  name="first_name" placeholder="Nome"
                     value="<?= htmlspecialchars($customer['first_name'] ?? '') ?>"
                     class="input-field <?= isset($errors['first_name']) ? 'input-error' : '' ?>"
                     required>
              <small class="error-text"><?= $errors['first_name'] ?? '' ?></small>

              <input type="text"  name="last_name" placeholder="Sobrenome"
                     value="<?= htmlspecialchars($customer['last_name'] ?? '') ?>"
                     class="input-field <?= isset($errors['last_name']) ? 'input-error' : '' ?>"
                     required>
              <small class="error-text"><?= $errors['last_name'] ?? '' ?></small>

              <input type="tel"   name="phone" placeholder="Telefone"
                     value="<?= htmlspecialchars($customer['phone'] ?? '') ?>"
                     class="input-field <?= isset($errors['phone']) ? 'input-error' : '' ?>"
                     required>
              <small class="error-text"><?= $errors['phone'] ?? '' ?></small>

              <input type="text"  name="abn_or_tfn" placeholder="ABN / TFN"
                     value="<?= htmlspecialchars($customer['abn_or_tfn'] ?? '') ?>"
                     class="input-field <?= isset($errors['abn_or_tfn']) ? 'input-error' : '' ?>"
                     required>
              <small class="error-text"><?= $errors['abn_or_tfn'] ?? '' ?></small>

              <input type="email" name="email" placeholder="E-mail"
                     value="<?= htmlspecialchars($customer['email'] ?? '') ?>"
                     class="input-field <?= isset($errors['email']) ? 'input-error' : '' ?>"
                     required>
              <small class="error-text"><?= $errors['email'] ?? '' ?></small>

              <button type="submit" class="btn btn--full">Salvar alterações</button>
            </form>
          </div>
        </div>

        <!-- Cartão de Endereços -->
        <div class="card">
          <div class="card-header"><h3>Meus Endereços</h3></div>
          <div class="card-body">
            <?php if (empty($addresses)): ?>
              <p>Nenhum endereço salvo.</p>
            <?php endif; ?>
            <?php foreach ($addresses as $addr): ?>
              <div class="form-group mb-4 p-4" style="border:1px solid var(--color-border);border-radius:var(--radius-md);">
                <strong><?= htmlspecialchars($addr['label']) ?></strong><br>
                <?= htmlspecialchars($addr['address_line1']) ?>
                <?= $addr['address_line2'] ? ', '.htmlspecialchars($addr['address_line2']) : '' ?><br>
                <?= htmlspecialchars($addr['city']) ?>, <?= htmlspecialchars($addr['state']) ?> <?= htmlspecialchars($addr['postcode']) ?><br>
                <?php if ($addr['id'] === null): ?>
                  <a href="new_address.php?address=<?= urlencode($addr['address_line1']) ?>&postcode=<?= urlencode($addr['postcode']) ?>"
                     class="btn btn-primary btn--full mt-4">Salvar este endereço</a>
                <?php else: ?>
                  <a href="edit_address.php?id=<?= $addr['id'] ?>" class="btn btn-secondary">Editar</a>
                  <a href="delete_address.php?id=<?= $addr['id'] ?>" class="btn btn-outline">Excluir</a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
            <a href="new_address.php" class="btn btn-primary btn--full">Adicionar Novo Endereço</a>
          </div>
        </div>

      </div>
    </div>

    <!-- Lado direito: imagem de apoio -->
    <div class="hero__right">
      <img src="assets/uploads/home_cleaning_banner.webp"
           alt="Ilustração de perfil" class="hero-image">
    </div>
  </section>
</body>
</html>