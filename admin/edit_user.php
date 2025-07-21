<?php
session_start();
require_once('../bluereferralclub/conexao.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die("User ID inválido.");
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Usuário não encontrado.");
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'first_name', 'last_name', 'email', 'mobile', 'referral_code', 'tfn', 'abn',
        'commission_amount', 'referral_club_level_name', 'bankName', 'agency',
        'bsb', 'accountNumber', 'abnNumber', 'work_rights_details', 'user_type'
    ];

    $updates = [];
    $values = [];

    foreach ($fields as $field) {
        $updates[] = "$field = ?";
        $values[] = $_POST[$field] ?? null;
    }

    $values[] = $id;

    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    header("Location: add_user.php?success=1");
    exit();
}

function safe($field) {
    global $user;
    return htmlspecialchars((string)($user[$field] ?? ''));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Referral User</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<!-- Sidebar -->
<nav class="ps-panel__sidebar">
    <div class="ps-panel__top">
        <img src="https://bluefacilityservices.com.au/wp-content/uploads/2020/09/logo_novo.svg" alt="Logo" style="height:40px;">
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
        <li><a href="index.php"><i class="fa fa-cog"></i> Dashboard</a></li>
        <a href="../logout.php" class="btn btn-danger">Logout</a>
    </ul>
</nav>

<!-- Main Content -->
<div class="ps-panel__content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="dashboard-title">Edit Referral User #<?= $id ?></h2>
        <a href="add_user.php" class="btn btn-secondary">← Back to Users</a>
    </div>

<form method="POST" class="container mt-4" style="max-width: 800px;">
    <!-- Dados Pessoais -->
    <h4>Personal Info</h4>
    <div class="mb-3">
        <label class="form-label">First Name</label>
        <input type="text" name="first_name" class="form-control" value="<?= safe('first_name') ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Last Name</label>
        <input type="text" name="last_name" class="form-control" value="<?= safe('last_name') ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= safe('email') ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Mobile</label>
        <input type="text" name="mobile" class="form-control" value="<?= safe('mobile') ?>">
    </div>

    <!-- Documento e Comissão -->
    <h4>Documents</h4>
    <div class="mb-3">
        <label class="form-label">Referral Code</label>
        <input type="text" name="referral_code" class="form-control text-uppercase" value="<?= safe('referral_code') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">TFN</label>
        <input type="text" name="tfn" class="form-control" maxlength="9" value="<?= safe('tfn') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">ABN</label>
        <input type="text" name="abn" class="form-control" maxlength="11" value="<?= safe('abn') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Commission Amount</label>
        <input type="text" name="commission_amount" class="form-control" value="<?= safe('commission_amount') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Referral Club Level Name</label>
        <input type="text" name="referral_club_level_name" class="form-control" value="<?= safe('referral_club_level_name') ?>">
    </div>

    <!-- Dados Bancários -->
    <h4>Bank Info</h4>
    <div class="mb-3">
        <label class="form-label">Bank Name</label>
        <input type="text" name="bankName" class="form-control" value="<?= safe('bankName') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Agency</label>
        <input type="text" name="agency" class="form-control" value="<?= safe('agency') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">BSB</label>
        <input type="text" name="bsb" class="form-control" value="<?= safe('bsb') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Account Number</label>
        <input type="text" name="accountNumber" class="form-control" value="<?= safe('accountNumber') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">ABN Number</label>
        <input type="text" name="abnNumber" class="form-control" value="<?= safe('abnNumber') ?>">
    </div>

    <!-- Direitos de Trabalho -->
    <div class="mb-3">
        <label class="form-label">Work Rights Details</label>
        <textarea name="work_rights_details" class="form-control" rows="4"><?= safe('work_rights_details') ?></textarea>
    </div>

    <!-- Tipo de Usuário -->
    <div class="mb-3">
        <label class="form-label">User Type</label>
        <select name="user_type" class="form-select" required>
            <option value="">Select Type</option>
            <?php
            $types = ['super admin', 'admin', 'cleaner', 'consumer', 'referral member'];
            foreach ($types as $type) {
                $selected = ($user['user_type'] === $type) ? 'selected' : '';
                echo "<option value=\"$type\" $selected>$type</option>";
            }
            ?>
        </select>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
</form>

</div>

</body>
</html>
