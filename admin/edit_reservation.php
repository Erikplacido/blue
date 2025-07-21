<?php
// edit_reservation.php

// Configurações de erro (produção segura + log)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');
error_reporting(E_ALL);

require_once '../bluereferralclub/conexao.php';
session_start();

// Proteção de login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Validação do ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die('ID inválido');
}

// Buscar a reservation
$stmt = $pdo->prepare("SELECT * FROM referrals WHERE id = ?");
$stmt->execute([$id]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    die('Reservation não encontrada.');
}

// Buscar todos os serviços
$servicesQuery = $pdo->query("SELECT id, service_name FROM services ORDER BY service_name ASC");
$services = $servicesQuery->fetchAll(PDO::FETCH_ASSOC);

// Preparar log
$logDir = __DIR__ . '/logs';
$logFile = $logDir . '/reservations_changes.log';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

$statusMsg = "";

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        // Deletar
        $del = $pdo->prepare("DELETE FROM referrals WHERE id = ?");
        $del->execute([$id]);

        file_put_contents($logFile, date('Y-m-d H:i:s') . " - User {$_SESSION['user_id']} DELETED reservation ID $id\n", FILE_APPEND);

        header('Location: reservations.php?deleted=1');
        exit;
    } else {
        // Atualizar
$fields = [
    'referred', 'referred_last_name', 'referred_by', 'referral_code', 'referral_club_level_name', 'email', 'mobile',
    'client_type', 'consumer_name', 'service_name', 'status', 'commission_fixed', 'commission_percentage',
    'commission_amount', 'commission_type', 'address', 'number', 'suburb', 'city', 'territory', 'postcode',
    'booking_value'
];

        $updateFields = [];
        $params = [];

foreach ($fields as $field) {
    $params[] = $_POST[$field] ?? null;
    $updateFields[] = "$field = ?";
}


        $params[] = $id;
        $sql = "UPDATE referrals SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $upd = $pdo->prepare($sql);
        $upd->execute($params);

        // 4.1 Notificar cliente quando status for aprovado
        if (isset($_POST['status']) && $_POST['status'] === 'Successes') {
            $notifyData = [
                'id'           => $id,
                'status'       => 'Success',
                'email'        => $_POST['email'],
                'referred'     => $_POST['referred'],
                'service_name' => $_POST['service_name'],
            ];
            $context = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\n",
                    'content' => json_encode($notifyData),
                ],
            ]);
            // Chamada ao endpoint de notificação
            @file_get_contents(
                'https://bluefacilityservices.com.au/bluereferralclub/quote/notify_quote_success.php',
                false,
                $context
            );
        }

        file_put_contents($logFile, date('Y-m-d H:i:s') . " - User {$_SESSION['user_id']} UPDATED reservation ID $id\n", FILE_APPEND);

        $statusMsg = '<div class="alert alert-success">Reservation atualizada com sucesso!</div>';

        // Recarregar dados atualizados
        $stmt = $pdo->prepare("SELECT * FROM referrals WHERE id = ?");
        $stmt->execute([$id]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Reservation</title>
    <title>Edit Reservation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS Essentials -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">

    <!-- Novo estilo específico -->
    <link rel="stylesheet" href="css/edit_reservation.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>


<div class="edit-reservation-wrapper">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="dashboard-title">Edit Reservation #<?= htmlspecialchars((string)($reservation['id'] ?? '')) ?></h2>
        <a href="referral.php" class="btn btn-secondary">← Back to Reservations</a>
    </div>

    <?= $statusMsg ?>

<form method="POST" class="form-section row g-4">
<?php  // helper p/ escapar
function safe($field) { global $reservation; return htmlspecialchars((string)($reservation[$field] ?? '')); }
?>

<div class="form-column user-data">

<!-- ********* DADOS BÁSICOS ********* -->
<div class="col-md-12 form-group">
    <label>Referred</label>
    <input type="text" name="referred" class="form-control" value="<?= safe('referred') ?>" required>
</div>

<div class="col-md-12 form-group">
    <label>Referred Last Name</label>
    <input type="text" name="referred_last_name" class="form-control" value="<?= safe('referred_last_name') ?>">
</div>

<div class="col-md-12 form-group">
    <label>Referred by</label>
    <input type="text" name="referred_by" class="form-control" value="<?= safe('referred_by') ?>">
</div>

<div class="col-md-12 form-group">
    <label>Referral Code</label>
    <input type="text" name="referral_code" class="form-control" value="<?= safe('referral_code') ?>">
</div>

<div class="col-md-12 form-group" hidden>
    <label>Referral Level</label>
    <input type="text" name="referral_club_level_name" class="form-control" value="<?= safe('referral_club_level_name') ?>">
</div>

<div class="col-md-12 form-group">
    <label>Email</label>
    <input type="email" name="email" class="form-control" value="<?= safe('email') ?>" required>
</div>

<div class="col-md-12 form-group">
    <label>Mobile</label>
    <input type="text" name="mobile" class="form-control" value="<?= safe('mobile') ?>" required>
</div>

<div class="col-md-12 form-group">
    <label>Consumer</label>
    <input type="text" name="consumer_name" class="form-control" value="<?= safe('consumer_name') ?>">
</div>

<div class="col-md-12 form-group" hidden>
    <label>City</label>
    <input type="text" name="city" class="form-control" value="<?= safe('city') ?>">
</div>

<div class="col-md-12 form-group">
    <label>Address</label>
    <input type="text" name="address" class="form-control" value="<?= safe('address') ?>">
</div>

<div class="col-md-12 form-group" hidden>
    <label>Number</label>
    <input type="text" name="number" class="form-control" value="<?= safe('number') ?>">
</div>

<div class="col-md-12 form-group" hidden>
    <label>Suburb</label>
    <input type="text" name="suburb" class="form-control" value="<?= safe('suburb') ?>">
</div>

<div class="col-md-12 form-group" hidden>
    <label>Territory</label>
    <input type="text" name="territory" class="form-control" value="<?= safe('territory') ?>">
</div>

<div class="col-md-12 form-group" hidden>
    <label>Postcode</label>
    <input type="text" name="postcode" class="form-control" value="<?= safe('postcode') ?>">
</div>

</div>

<div class="form-column order-data">

<!-- ********* COMISSIONAMENTO ********* -->
<h5>Order Commission</h5>


<div class="col-md-12 form-group">
    <label>Please enter the booking amount</label>
    <input type="number" step="0.01"
           id="booking_value" name="booking_value"
           class="form-control" value="<?= safe('booking_value') ?>">
</div>

<div class="col-md-12 form-group">
    <label>Set commission type</label>
    <select name="commission_type" id="commission_type" class="form-select">
        <option value="fixed"      <?= ($reservation['commission_type'] ?? '') === 'fixed'      ? 'selected' : '' ?>>Fixed</option>
        <option value="percentage" <?= ($reservation['commission_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage</option>
    </select>
</div>

<div class="col-md-12 form-group" id="group_fixed">
    <label>Fixed Amount</label>
    <input type="number" step="0.01"
           id="commission_fixed" name="commission_fixed"
           class="form-control" value="<?= safe('commission_fixed') ?>">
</div>

<div class="col-md-12 form-group" id="group_percentage">
    <label>Percentage (%)</label>
    <input type="number" step="0.01"
           id="commission_percentage" name="commission_percentage"
           class="form-control" value="<?= safe('commission_percentage') ?>">
</div>



<div class="col-md-12 form-group">
    <label>Amount of Commission to be Paid</label>
    <input type="number" step="0.01" readonly
           id="commission_amount" name="commission_amount"
           class="form-control" value="<?= safe('commission_amount') ?>">
</div>



<!-- ********* OUTROS ********* -->
<div class="col-md-12 form-group">
    <label>Type</label>
    <select name="client_type" class="form-select">
        <option value="">Select</option>
        <option value="Residential" <?= ($reservation['client_type'] ?? '') === 'Residential' ? 'selected' : '' ?>>Residential</option>
        <option value="Commercial" <?= ($reservation['client_type'] ?? '') === 'Commercial'    ? 'selected' : '' ?>>Commercial</option>
    </select>
</div>

<div class="col-md-12 form-group">
    <label>Service</label>
    <select name="service_name" class="form-select" required>
        <?php foreach ($services as $service): ?>
            <option value="<?= htmlspecialchars($service['service_name']) ?>"
                <?= ($reservation['service_name'] ?? '') === $service['service_name'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($service['service_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="col-md-12 form-group">
    <label>Status</label>
    <select name="status" class="form-select">
        <?php foreach (['Pending','Successes','Unsuccessful','Negotiating','Paid'] as $status): ?>
            <option value="<?= $status ?>" <?= ($reservation['status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option>
        <?php endforeach; ?>
    </select>
</div>

</div>

<!-- ********* AÇÕES ********* -->
<div class="col-12 d-flex justify-content-between mt-4">
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <button type="submit" name="delete" value="1"
            class="btn btn-outline-danger btn-sm"
            onclick="return confirm('Are you sure you want to delete this reservation?')">
        Delete
    </button>
</div>
</form>

</div>

<script src="js/commission_status.js"></script>

</body>
</html>
