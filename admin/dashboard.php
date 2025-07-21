<?php
session_start();

// ✅ Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once(__DIR__ . '/../bluereferralclub/conexao.php');

// Buscar todas as views que terminam com 'paid'
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
</head>
<body>

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

    <!-- Outras seções omitidas por brevidade... -->

    <!-- 🔷 Referral Club Commissions Section -->
    <div class="full-width-card">
        <div class="bento-wrapper">
            <h5>Referral Club Commissions</h5>
            <div class="mini-cards-container">
                <?php foreach ($cards as $card): ?>
                    <div class="mini-card mini-red open-modal"
                        data-view="<?= $card['view_name'] ?>"
                         style="cursor:pointer;">
                         <span><?= htmlspecialchars($card['user_name']) ?><br>
                               <small>$<?= number_format($card['total_commission'], 2) ?></small>
                         </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<div class="modal fade" id="commissionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Comissão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Esta parte ainda é carregada por AJAX -->
            <div class="modal-body" id="modalContent">Carregando...</div>

            <!-- Esta parte FICA FIXA no DOM e não é alterada -->
            <div class="modal-footer d-flex flex-column align-items-stretch gap-2">
                <div class="form-group w-100">
                    <label for="paymentReference">Número de Identificação do Pagamento</label>
                    <input type="text" id="paymentReference" class="form-control" placeholder="Ex: 1234567890">
                </div>
                <button id="markPaidBtn" class="btn btn-success align-self-end">Mark as Paid</button>
            </div>
        </div>
    </div>
</div>



<!-- Apenas Bootstrap básico mantido -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/dashboard.js"></script>
<script src="js/menu.js"></script>


</body>
</html>
