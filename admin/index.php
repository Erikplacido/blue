<?php
session_start();

// ✅ Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once(__DIR__ . '/../bluereferralclub/conexao.php');

$sql = "SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_u979853733_BFS LIKE '%upcoming_payment_view'";
$views = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);

$cards = [];

foreach ($views as $view) {
    $query = $pdo->prepare("
        SELECT 
            v.user_id AS id, 
            v.total_earnings AS total_commission, 
            u.name AS user_name
        FROM `$view` v
        JOIN users u ON u.id = v.user_id
        LIMIT 1
    ");
    $query->execute();
    $row = $query->fetch(PDO::FETCH_ASSOC);

    if ($row && (float)$row['total_commission'] > 0) {
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
    <style>
      .btn-close.custom-close {
        background-image: none;
        background-color: transparent;
        border: none;
        width: auto;
        height: auto;
        padding: 0;
      }
    </style>
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

    <!-- Linha solo para Total Completed Services -->
    <div class="full-width-card" hidden>
        <div class="bento-card text-center">
            <h5>Total Completed Services</h5>
            <p class="display-4">120</p>
        </div>
    </div>

<!-- Dashboard Columns Verticais -->
    <div class="dashboard-columns" hidden>
        <!-- New Reservations -->
        <div class="dashboard-column">
            <div class="bento-wrapper">
                <h5>New Reservations</h5>
                <div class="mini-cards-container">
                    <div class="mini-card mini-yellow"><span>#145</span></div>
                    <div class="mini-card mini-yellow"><span>#146</span></div>
                    <div class="mini-card mini-yellow"><span>#147</span></div>
                </div>
            </div>
        </div>

        <!-- Pending Reservations -->
        <div class="dashboard-column">
            <div class="bento-wrapper">
                <h5>Pending Reservations</h5>
                <div class="mini-cards-container">
                    <div class="mini-card mini-red"><span>#210</span></div>
                    <div class="mini-card mini-red"><span>#211</span></div>
                </div>
            </div>
        </div>

        <!-- Reservations Under Negotiation -->
        <div class="dashboard-column">
            <div class="bento-wrapper">
                <h5>Reservations Under Negotiation</h5>
                <div class="mini-cards-container">
                    <div class="mini-card mini-orange"><span>#320</span></div>
                    <div class="mini-card mini-orange"><span>#321</span></div>
                </div>
            </div>
        </div>

        <!-- Services In Progress -->
        <div class="dashboard-column">
            <div class="bento-wrapper">
                <h5>Services In Progress</h5>
                <div class="mini-cards-container">
                    <div class="mini-card mini-blue"><span>#425</span></div>
                    <div class="mini-card mini-blue"><span>#426</span></div>
                </div>
            </div>
        </div>

        <!-- Services Completed Today -->
        <div class="dashboard-column">
            <div class="bento-wrapper">
                <h5>Services Completed Today</h5>
                <div class="mini-cards-container">
                    <div class="mini-card mini-lightblue"><span>#520</span></div>
                    <div class="mini-card mini-lightblue"><span>#521</span></div>
                </div>
            </div>
        </div>
    </div><br>

    <!-- Linha solo para Cleaners agendados -->
    <div class="full-width-card" hidden>
        <div class="bento-wrapper">
            <h5>Cleaners Scheduled</h5>
            <div class="mini-cleaners-container">
                <div class="cleaner-mini-card">
                    <img src="https://via.placeholder.com/50" alt="Cleaner">
                    <small>John</small>
                </div>
                <div class="cleaner-mini-card">
                    <img src="https://via.placeholder.com/50" alt="Cleaner">
                    <small>Emily</small>
                </div>
                <div class="cleaner-mini-card">
                    <img src="https://via.placeholder.com/50" alt="Cleaner">
                    <small>Michael</small>
                </div>
            </div>
        </div>
    </div>

    <!-- 🔷 Referral Club Commissions Section -->
<div class="full-width-card">
    <div class="bento-wrapper">
        <h5>Referral Club Commissions</h5>
        <div class="mini-cards-container">
            <?php foreach ($cards as $card): ?>
                <div class="mini-card mini-red open-modal"
                     data-view="<?= $card['view_name'] ?>"
                     style="cursor:pointer;"
                     title="View commission details">
                    <span><?= htmlspecialchars($card['user_name'] ?? 'User #' . $card['id']) ?><br>
                        <small>$<?= number_format($card['total_commission'], 2) ?></small>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>


<!-- Modal -->
<div class="modal fade" id="commissionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Commission Details</h5>
                <button type="button" class="btn-close custom-close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="font-size: 1.5rem; color: #000;">&times;</span>
                </button>
            </div>

            <!-- Início da body ajustada -->
            <div class="modal-body">
                <!-- Conteúdo carregado via AJAX -->
                <div id="modalContent">
                    Loading...
                </div>

                <!-- Campo para número de identificação do pagamento (sempre visível) -->
                <div class="mt-4" hidden>
                    <label for="paymentReference" class="form-label">Payment Reference Number</label>
                    <input
                        type="text"
                        class="form-control"
                        id="paymentReference"
                        placeholder="Ex: 123456ABC"
                        required
                    >
                </div>
            </div>
            <!-- Fim da body ajustada -->

            <div class="modal-footer">
                <button id="markPaidBtn" class="btn btn-success">Mark as Paid</button>
            </div>
        </div>
    </div>
</div>


<!-- Scripts -->
<!-- Load Bootstrap first -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Then custom scripts -->
<script src="js/get_commission_view.js"></script>
<script src="js/user_script.js"></script>
<!-- Inline initialization (if still needed) -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".open-modal").forEach(button => {
        button.addEventListener("click", event => {
            event.preventDefault();
            const view = button.getAttribute("data-view");

            const modalContent = document.getElementById("modalContent");
            modalContent.innerHTML = "Loading...";

            fetch(`ajax_get_view_data.php?view=${view}`)
                .then(res => res.text())
                .then(html => {
                    modalContent.innerHTML = html;
                    const modal = new bootstrap.Modal(document.getElementById("commissionModal"));
                    modal.show();
                })
                .catch(error => {
                    modalContent.innerHTML = '<div class="alert alert-danger">Error loading: ' + error + '</div>';
                });
        });
    });

    const commissionModalEl = document.getElementById("commissionModal");
    commissionModalEl.addEventListener('hidden.bs.modal', () => {
      document.body.classList.remove('modal-open');
      document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    });
});
</script>
</body>
</html>
