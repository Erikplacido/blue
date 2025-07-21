<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once('../bluereferralclub/conexao.php');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Buscar usuários
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, email, user_type, mobile FROM users ORDER BY id DESC LIMIT 100");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("<strong>Erro ao buscar usuários:</strong> " . $e->getMessage());
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
    <title>Referral Club | Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/reservations.css">

    
    <style>
        
        .custom-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0; top: 0; width: 100%; height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}

.custom-modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border-radius: 8px;
    max-width: 700px;
    width: 90%;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    position: relative;
}

.custom-modal-content input,
.custom-modal-content textarea,
.custom-modal-content select {
    width: 100%;
    margin-bottom: 10px;
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.custom-modal-content .close {
    position: absolute;
    right: 20px;
    top: 15px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}
2. ✅ Ce
        
        
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
<div class="ps-panel__content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="dashboard-title">Users</h2>
        <button class="btn btn-success" onclick="openModal('newUserModal')">Add New User</button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th><th>First Name</th><th>Last Name</th><th>Email</th><th>User Type</th><th>Mobile</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)) : ?>
                    <?php foreach ($users as $user) : ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['first_name']) ?></td>
                            <td><?= htmlspecialchars($user['last_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['user_type']) ?></td>
                            <td><?= htmlspecialchars($user['mobile']) ?></td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="delete_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="7" class="text-center">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal - New User -->
<div id="newUserModal" class="custom-modal">
    <div class="custom-modal-content" onclick="event.stopPropagation()">
        <span class="close" id="closeModalBtn">&times;</span>
        <h3>Add New Referral User</h3>

        <form action="insert_user.php" method="POST">
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="tel" id="mobile" name="mobile" placeholder="+61 405 102 254" required />
            <input type="text" name="referral_code" id="referral_code" placeholder="Referral Code" style="text-transform: uppercase;">
            <input type="text" name="tfn" id="tfn" placeholder="TFN 123456789" inputmode="numeric" maxlength="9">
            <input type="text" name="abn" id="abn" placeholder="ABN 12345678910" inputmode="numeric" maxlength="11">

            <select name="user_type" required>
                <option value="">Select User Type</option>
                <option value="super admin">Super Admin</option>
                <option value="admin">Admin</option>
                <option value="cleaner">Cleaner</option>
                <option value="consumer">Consumer</option>
                <option value="referral member">Referral Member</option>
            </select>

            <button type="submit" class="btn btn-success mt-3">Save User</button>
        </form>
    </div>
</div>

    <!-- Modal: Share Message -->
    <div class="modal fade" id="shareMessageModal" tabindex="-1" aria-labelledby="shareMessageModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-md modal-dialog-centered">
        <form class="modal-content shadow-lg rounded-3 border-0" method="POST" action="update_share_message.php">
          <div class="modal-header bg-light border-bottom">
            <h4 class="modal-title fw-bold text-dark" id="shareMessageModalLabel">Edit Share Message</h4>
          </div>
          <div class="modal-body bg-white">
            <textarea name="message" id="message" class="form-control form-control-lg" rows="6" required><?= htmlspecialchars($currentMessage) ?></textarea>
          </div>
          <div class="modal-footer bg-light border-top d-flex justify-content-between">
            <button type="submit" class="btn btn-success px-4">Save Message</button>
            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Edit Referral Commissions -->
    <div class="modal fade" id="referralValuesModal" tabindex="-1" aria-labelledby="referralValuesModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="update_commissions.php" class="modal-content shadow-lg rounded-3 border-0">
          <div class="modal-header bg-light border-bottom">
            <h4 class="modal-title fw-bold text-dark" id="referralValuesModalLabel">Edit Referral Club Commissions</h4>
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
            <button type="submit" class="btn btn-success px-4">Save Changes</button>
            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
</div>

<script src="js/get_commission_view.js"></script>
<script src="js/user_script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="js/referral_club.js"></script>
<script src="js/input.js"></script>
<script src="js/menu.js"></script>


<script>
    const modal = document.getElementById('newUserModal');
    const closeBtn = document.getElementById('closeModalBtn');

    // Abre o modal
    function openModal(id) {
        document.getElementById(id).style.display = "block";
    }

    // Fecha o modal
    function closeModal(id) {
        document.getElementById(id).style.display = "none";
    }

    // Fecha ao clicar no botão ×
    closeBtn.addEventListener('click', () => {
        closeModal('newUserModal');
    });

    // Fecha ao clicar fora da área de conteúdo
    modal.addEventListener('click', () => {
        closeModal('newUserModal');
    });

    // Evita que clique dentro da caixa feche o modal
    document.querySelector('.custom-modal-content').addEventListener('click', function (e) {
        e.stopPropagation();
    });
</script>


</body>
</html>