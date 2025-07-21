<?php
session_start();

// ✅ Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once(__DIR__ . '/../bluereferralclub/conexao.php');

// 🔍 Buscar todas as views que terminam com 'paid'
$sql = "SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_u979853733_BFS LIKE '%paid'";
$views = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);

$cards = [];

foreach ($views as $view) {
    $query = $pdo->prepare("SELECT id, user_name, total_commission FROM `$view` LIMIT 1");
    $query->execute();
    $row = $query->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $row['view_name'] = $view;
        $cards[] = $row;
    }
}

// 🔄 Recuperar a mensagem atual do Referral Club
$stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'share_message' LIMIT 1");
$stmt->execute();
$currentMessage = $stmt->fetchColumn() ?: '';

// 🔄 Buscar níveis de comissão (ID 1 a 3)
$levels = [];
$stmt = $pdo->query("SELECT id, level, commission_fixed, commission_percentage FROM referral_club_data WHERE id IN (1, 2, 3) ORDER BY id");
$levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Cleaning Services Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS Imports -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/demo.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cards.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/modal.css">

    <style>
      .modal-title { font-size: 1.5rem; }
      .modal-body textarea, .modal-body input {
        font-size: 1rem;
        padding: 10px;
        border-radius: 6px;
      }
      .modal-footer .btn {
        padding: 8px 20px;
        font-size: 1rem;
      }
      .fw-semibold { font-weight: 600; }
    </style>
</head>
<body>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'ok'): ?>
<script>alert('✅ Share message updated successfully!');</script>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'commission_updated'): ?>
<script>alert('✅ Commission values updated successfully!');</script>
<?php endif; ?>

<!-- Sidebar -->
<nav class="ps-panel__sidebar">
    <div class="ps-panel__top">
        <img src="https://bluefacilityservices.com.au/wp-content/uploads/2020/09/logo_novo.svg" alt="Admin Panel Logo" style="height:40px;">
    </div>
    <ul class="ps-panel__menu">
        <li><a href="reservations.php"><i class="fa fa-calendar"></i> Reservations</a></li>
        <li><a href="clients.php"><i class="fa fa-users"></i> Clients</a></li>
        <li><a href="services.php"><i class="fa fa-bars"></i> Services</a></li>
        <li><a href="add_user.php"><i class="fa fa-user-plus"></i> User</a></li>
        <li><a href="cleaner.php"><i class="fa fa-user"></i> Cleaner</a></li>
        <li><a href="schedule.php"><i class="fa fa-calendar-check-o"></i> Schedule</a></li>
        <li><a href="referral.php"><i class="fa fa-share-alt"></i> Referral Club</a></li>
        <li><a href="https://bluefacilityservices.com.au/training"><i class="fa fa-graduation-cap"></i> Training</a></li>
        <li>
          <a href="javascript:void(0)" class="toggle-submenu">
            <i class="fa fa-cog"></i> Settings <i class="fa fa-angle-down float-end"></i>
          </a>
          <ul class="submenu">
            <li><a href="#" data-bs-toggle="modal" data-bs-target="#shareMessageModal"><i class="fa fa-comment"></i> Message</a></li>
            <li><a href="#" data-bs-toggle="modal" data-bs-target="#referralValuesModal"><i class="fa fa-money"></i> Commissions</a></li>
          </ul>
        </li>
        <li><a href="../logout.php" class="btn btn-danger">Logout</a></li>
    </ul>
</nav>

<!-- Main content -->
<div class="ps-panel__content" style="margin-left: 250px; padding: 2rem;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="dashboard-title">Dashboard Overview</h2>
    </div>

    <!-- Modal: Share Message -->
    <div class="modal fade" id="shareMessageModal" tabindex="-1" aria-labelledby="shareMessageModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-md modal-dialog-centered">
        <form class="modal-content shadow-lg rounded-3 border-0" method="POST" action="update_share_message.php">
          <div class="modal-header bg-light border-bottom">
            <h4 class="modal-title fw-bold text-dark" id="shareMessageModalLabel">📤 Edit Share Message</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body bg-white">
            <label for="message" class="form-label fw-semibold">Message to Share:</label>
            <textarea name="message" id="message" class="form-control form-control-lg" rows="6" required><?= htmlspecialchars($currentMessage) ?></textarea>
          </div>
          <div class="modal-footer bg-light border-top d-flex justify-content-between">
            <button type="submit" class="btn btn-success px-4">💾 Save Message</button>
            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">❌ Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Edit Referral Commissions -->
    <div class="modal fade" id="referralValuesModal" tabindex="-1" aria-labelledby="referralValuesModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="update_commissions.php" class="modal-content shadow-lg rounded-3 border-0">
          <div class="modal-header bg-light border-bottom">
            <h4 class="modal-title fw-bold text-dark" id="referralValuesModalLabel">💰 Edit Referral Club Commissions</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body bg-white">
            <?php foreach ($levels as $level): ?>
              <div class="mb-4 border-bottom pb-3">
                <h5 class="fw-semibold mb-3"><?= htmlspecialchars($level['level']) ?> (ID <?= $level['id'] ?>)</h5>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="fixed_<?= $level['id'] ?>" class="form-label">Fixed Commission ($)</label>
                    <input type="number" step="0.01" min="0" name="commission_fixed[<?= $level['id'] ?>]" id="fixed_<?= $level['id'] ?>" class="form-control form-control-lg" value="<?= $level['commission_fixed'] ?>">
                  </div>
                  <div class="col-md-6">
                    <label for="percentage_<?= $level['id'] ?>" class="form-label">Percentage Commission (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="commission_percentage[<?= $level['id'] ?>]" id="percentage_<?= $level['id'] ?>" class="form-control form-control-lg" value="<?= $level['commission_percentage'] ?>">
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="modal-footer bg-light border-top d-flex justify-content-between">
            <button type="submit" class="btn btn-success px-4">💾 Save Changes</button>
            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">❌ Cancel</button>
          </div>
        </form>
      </div>
    </div>
</div>

<!-- Scripts -->
<script src="js/get_commission_view.js"></script>
<script src="js/user_script.js"></script>
<script src="js/menu.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
