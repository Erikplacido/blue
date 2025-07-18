<?php
// stripe_success.php
require __DIR__ . '/../src/config.php';

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Src\Models\Booking;
use Src\Models\Customer;

require_once __DIR__ . '/../booking/vendor/autoload.php';

// Debug – remova em produção
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ① Recupera Session ID recebido via GET
$sessionId = $_GET['session_id'] ?? null;
if (!$sessionId) {
    die('Invalid request: session ID missing.');
}

// ② Consulta Stripe
Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

$session        = Session::retrieve($sessionId);
$customerEmail  = $session->customer_email ?? 'Not provided';
$bookingId      = $session->metadata->booking_id ?? 'Unknown';
$bookingModel   = new Booking();
$customerModel  = new Customer();
$booking        = $bookingModel->getById((int)$bookingId);
$customer       = $booking ? $customerModel->getById((int)$booking['customer_id']) : null;
$needsPassword  = $customer && empty($customer['password']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pagamento Concluído</title>

  <!-- Fonte Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;800&display=swap" rel="stylesheet">

  <!-- Design-System Global -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <section class="hero">
    <!-- Main content -->
    <div class="hero__left">
      <div class="hero__card">

        <!-- Success card -->
        <section class="dados_pessoais-wrapper fade-in">
          <h2 class="section-title">Payment successful!</h2>

          <p>Thanks! Your payment has been processed.</p><br>
          <p><strong>Booking&nbsp;ID:</strong> <?= htmlspecialchars($bookingId) ?></p>
          <p><strong>Email:</strong> <?= htmlspecialchars($customerEmail) ?></p>
        </section>

        <!-- Password-creation card (if needed) -->
        <?php if ($needsPassword): ?>
<!-- Password-creation card (JS-only) -->
<section class="dados_pessoais-wrapper fade-in mt-4">
  <h3 class="section-title">Finish your registration</h3>

  <form id="registrationForm" class="info-form">
    <input type="hidden" name="customer_id"
           value="<?= htmlspecialchars($customer['id']) ?>">

    <input type="password"
           name="password"
           placeholder="Create a password"
           required>

    <button type="submit" class="btn btn--full">Create&nbsp;Account</button>
  </form>
</section>
</section>
        <?php endif; ?>

        <a href="index.php" class="btn mt-4">< Back to the home page</a>
      </div>
    </div>

    <!-- Side image for visual consistency -->
    <div class="hero__right">
      <img src="assets/uploads/home_cleaning_banner.webp"
           alt="Illustration of payment completion"
           class="hero-image">
    </div>
  </section>
      <script src="assets/js/aviso.js"></script>
</body>
</html>
