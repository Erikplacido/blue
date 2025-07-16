<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. CÓDIGO PHP no topo preparando sessão, conexões e dados
session_start();
require_once 'conexao.php';

// Proteção: redireciona se não estiver logado
if (!isset($_SESSION['user_id'])) {
    header("Location: /../login.php");
    exit;
}




// Buscar mensagem de compartilhamento do banco
$shareMessage = 'Check out Blue Facility Services and get XX% off!'; // fallback

$stmt = $conn->prepare("SELECT value FROM settings WHERE name = 'share_message' LIMIT 1");
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($dbMessage);
    if ($stmt->fetch() && $dbMessage) {
        $shareMessage = $dbMessage;
    }
    $stmt->close();
}








// Dados do usuário e suas estatísticas
$user_id = $_SESSION['user_id'];

// 1. Pega os dados principais do usuário, sem o commission_amount
$stmt = $conn->prepare("SELECT referral_code, first_name, referral_club_level_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($referral_code, $first_name, $referral_club_level_name);
$stmt->fetch();
$stmt->close();

// 2. Define a tabela de earnings dinamicamente com base no nome
$firstNameSanitized = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $first_name));
$referralCodeSanitized = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $referral_code));
$upcoming_view_name = $firstNameSanitized . $referralCodeSanitized . 'upcoming_payment_view';
$earnings_view_name = $firstNameSanitized . $referralCodeSanitized . 'total_earnings';

// 3. Busca os earnings da view apropriada, se existir
$commission_amount = 0.00;
$checkEarnings = $conn->prepare("SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
$checkEarnings->bind_param("s", $earnings_view_name);
$checkEarnings->execute();
$checkEarnings->store_result();
if ($checkEarnings->num_rows > 0) {
    $stmt = $conn->prepare("SELECT total_earnings FROM $earnings_view_name WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($commission_amount);
    $stmt->fetch();
    $stmt->close();
}
$checkEarnings->close();

// Busca posição no ranking
$ranking_result = $conn->query("SELECT id FROM users WHERE commission_amount IS NOT NULL ORDER BY commission_amount DESC");
$position = 0;
$rank = 1;
while ($row = $ranking_result->fetch_assoc()) {
    if ($row['id'] == $user_id) {
        $position = $rank;
        break;
    }
    $rank++;
}

// Top 3 do ranking geral
$top_query = $conn->query("
  SELECT first_name, commission_amount 
  FROM users 
  WHERE commission_amount IS NOT NULL 
  ORDER BY commission_amount DESC 
  LIMIT 3
");

$top_users = [];
while ($row = $top_query->fetch_assoc()) {
    $top_users[] = $row;
}

// Estatísticas de indicações
$stats = $conn->prepare("
  SELECT
    COUNT(*) AS total,
    SUM(status = 'Paid') AS paid,
    SUM(status = 'Successes') AS successes,
    SUM(status = 'Unsuccessful') AS unsuccessful,
    SUM(status = 'Pending') AS pending,
    SUM(status = 'Negotiating') AS negotiating
  FROM referrals
  WHERE referral_code = ?
");
$stats->bind_param("s", $referral_code);
$stats->execute();
$stats->bind_result($total, $paid, $successes, $unsuccessful, $pending, $negotiating);
$stats->fetch();
$stats->close();


// Referrals ainda não pagos
$query = $conn->prepare("
  SELECT 
    referred,
    status,
    commission_amount,
    city,
    DATE_FORMAT(created_at, '%d/%m/%Y') AS created_at
  FROM referrals 
  WHERE referral_code = ? AND (paid IS NULL OR paid = 0)
  ORDER BY created_at DESC
");
$query->bind_param("s", $referral_code);
$query->execute();
$res = $query->get_result();

$referrals = [];
while ($row = $res->fetch_assoc()) {
    $referrals[] = $row;
}
$query->close();

// Nome da view de upcoming payment
$upcoming_view_name = $firstNameSanitized . $referralCodeSanitized . 'upcoming_payment_view';

// Busca do total_earnings da view de upcoming payment, se existir
$upcoming_payment = 0.00;
$check = $conn->prepare("
    SELECT TABLE_NAME 
    FROM information_schema.VIEWS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
");
$check->bind_param("s", $upcoming_view_name);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $stmt = $conn->prepare("SELECT total_earnings FROM $upcoming_view_name WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $stmt->bind_result($upcoming_payment);
        $stmt->fetch();
    }
    $stmt->close();
}
$check->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Elegante</title>
<!-- No <head> do seu HTML -->
<link rel="stylesheet" href="css/dashboard.css">


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</head>
<style>
    
    
    .button-loading {
  position: relative;
  pointer-events: none;
  opacity: 0.6;
}

.button-loading .spinner {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
}
    
    
</style>
<body>

  <!-- HEADER substituindo a div .top -->
<header class="main-header">
  <div class="header-banner">
    <img src="https://bluefacilityservices.com.au/bluereferralclub/image/header_referral.webp"
         alt="Header Banner"
         class="banner-image" />
  </div>

  <nav class="header-nav">
    <div class="logo-area">
      <span class="logo-link">Hello, <strong><?= htmlspecialchars($first_name) ?>!</strong></span>
    </div>

    <!-- BOTÃO HAMBURGUER (aparece só no mobile) -->
    <button class="hamburger" id="menuToggle" aria-label="Toggle menu">
      &#9776;
    </button>

    <!-- AQUI: damos um id para o JS encontrar -->
    <div class="header-actions" id="navMenu">
      <button class="btn-referral-share" id="btnShareReferral">
        Share your Referral Code
      </button>
      <input type="hidden" id="referral_code" value="<?= htmlspecialchars($referral_code) ?>">

      <button type="button"
              class="btn btn-referral-give menu-item"
              id="btnGiveReferral"
              data-modal="/bluereferralclub/referral/index_user.php">
        Give a Referral
      </button>

      <div class="account-menu" id="accountMenu">
        <button class="account-icon-text" id="accountToggle"
                aria-label="Account Menu">Account</button>
<div class="account-dropdown" id="accountDropdown">
  <ul class="account-dropdown-menu">
    <li><a href="#" data-modal="header_component/profile.php" class="menu-item">Profile</a></li>
    <li><a href="#" data-modal="header_component/password.php" class="menu-item">Password</a></li>
    <li><a href="#" data-modal="header_component/bank.php" class="menu-item">Bank Details</a></li>
    <!-- Aqui: sem class="menu-item", para não entrar no handler de modal -->
    <li><a href="payment_history.php" target="_blank" rel="noopener">Payment History</a></li>
    <li><a href="referral_history.php">Referrals</a></li>
  </ul>
</div>
      </div>

      <button class="btn-logout" id="btnLogout">Logout</button>
    </div>
  </nav>
</header>

<!-- ÁREA DO MEIO -->
<div class="middle">
<div style="display: flex; align-items: flex-start;">
  <!-- Imagem do nível -->
  <div style="margin-right: 20px;">
    <?php
      $referralLevel = $referral_club_level_name ?? '';
      $imageSrc = '';

      if ($referralLevel === 'Blue Topaz') {
        $imageSrc = 'https://bluefacilityservices.com.au/wp-content/uploads/2024/10/topaz_icon-1-150x150.png';
      } elseif ($referralLevel === 'Blue Tanzanite') {
        $imageSrc = 'https://bluefacilityservices.com.au/wp-content/uploads/2024/10/tanzanite_icon-1-150x150.png';
      } elseif ($referralLevel === 'Blue Sapphire') {
        $imageSrc = 'https://bluefacilityservices.com.au/wp-content/uploads/2024/10/sapphire_icon-1-150x150.png';
      }

      if ($imageSrc !== '') {
        echo '<img src="' . htmlspecialchars($imageSrc) . '" width="150" height="150" alt="' . htmlspecialchars($referralLevel) . ' Badge">';
      }
    ?>
  </div>

  <!-- Texto de informações -->
  <div>
    Level: <strong><?= htmlspecialchars($referral_club_level_name ?? '') ?></strong><br>
    Your referral code is <strong><?= htmlspecialchars($referral_code ?? '') ?></strong><br>
    <?php if (($successes ?? 0) > 0): ?>
      Category in the club is <strong><?= htmlspecialchars($referral_club_level_name ?? '') ?></strong><br>
    <?php endif; ?>
    Your position in the referral ranking is <strong>Top <?= $position ?? '-' ?></strong><br><br>

    <strong>Ranking:</strong><br>
    <?php if (!empty($top_users)): ?>
      <?php foreach ($top_users as $i => $top): ?>
        Top <?= $i + 1 ?> (<?= htmlspecialchars($top['first_name']) ?> — $<?= number_format($top['commission_amount'], 2, ',', '.') ?>)
        <?= $i < count($top_users) - 1 ? ' | ' : '' ?>
      <?php endforeach; ?>
    <?php else: ?>
      No ranking data available.
    <?php endif; ?>
  </div>
</div>

  <div>
    <p>Total earnings: $ <strong><?= number_format($commission_amount, 2, ',', '.') ?></strong></p>
    <p>Upcoming payment: $ <strong><?= number_format($upcoming_payment, 2, ',', '.') ?></strong></p>

<p>
  Total: <strong><?= (int)$total ?></strong> |
  Paid: <strong><?= (int)$paid ?></strong> |
  Successes: <strong><?= (int)$successes ?></strong> |
  Unsuccessful: <strong><?= (int)$unsuccessful ?></strong> |
  Pending: <strong><?= (int)$pending ?></strong> |
  Negotiating: <strong><?= (int)$negotiating ?></strong>
</p>

  </div>
</div>

<!-- ÁREA INFERIOR -->
<div class="bottom">
  <h3>Referrals Overview</h3>
  <div class="table-responsive">
    <table class="referrals-table">
      <thead>
        <tr>
          <th>Referred</th>
          <th>Status</th>
          <th>Commission Amount</th>
          <th>City</th>
          <th>Created At</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($referrals as $row): ?>
          <?php 
            $status = strtolower($row['status']);
            if ($status === 'paid' || $status === 'unsuccessful') continue;
          ?>
          <tr>
            <td data-label="Referred"><?= htmlspecialchars($row['referred'] ?? '') ?></td>
            <td data-label="Status"><?= htmlspecialchars($row['status'] ?? '') ?></td>
            <td data-label="Commission"><?= htmlspecialchars($row['commission_amount'] ?? '') ?></td>
            <td data-label="City"><?= htmlspecialchars($row['city'] ?? '') ?></td>
            <td data-label="Created At"><?= htmlspecialchars($row['created_at'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>


<!-- MODAL -->
<div id="formModal" class="modal">
  <div class="modal-content">
    <span class="close-button">&times;</span>
    <div id="modalBody"></div>
  </div>
</div>
  
<!-- Modal para compartilhar -->
<div id="shareModal" class="modal">
  <div class="modal-content">
    <button class="close-modal" id="closeShareModal" aria-label="Close modal">&times;</button>
    <h3>Share your referral link!</h3>
    <input type="text" id="referralLink" readonly style="width:100%; margin-bottom: 10px;">
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
      <a id="whatsappShare" class="btn btn-submit" target="_blank">WhatsApp</a>
      <a id="instagramShare" class="btn btn-submit" href="#">Instagram</a> <!-- ✅ href="#" e não target -->
      <a id="facebookShare" class="btn btn-submit" target="_blank">Facebook</a>
      <a id="linkedinShare" class="btn btn-submit" target="_blank">LinkedIn</a>
      <button id="copyLink" class="btn btn-orange">Copy Link</button>
    </div>
  </div>
</div>



<!-- SCRIPT para interações com modal -->
<script>
  const shareMessage = <?= json_encode($shareMessage) ?>;
</script>
<script src="js/user_script.js"></script>
<script src="js/user_hamburber.js"></script>
<!-- Awesomplete -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js" defer></script>

<!-- Seu script de autocomplete -->
<script src="js/address.js" defer></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="/bluereferralclub/js/load.js" defer></script>


<!-- Google Places API com callback global -->
<script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA6dqOPMiDLe29otXTfltxkrnNyUPYCo9s&libraries=places&callback=initGooglePlaces" defer></script>
  
  


</body>
</html>