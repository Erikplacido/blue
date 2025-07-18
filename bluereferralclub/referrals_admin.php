 <?php
// referrals_admin.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'conexao.php';
$conn->query("CALL update_all_user_referral_stats()");
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Buscar nome do admin
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($adminName);
$stmt->fetch();
$stmt->close();

$success_message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['single_update'])) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo 'ID inválido';
        exit;
    }

$campos = [
    "service_consumer_id", "user_id", "referred_by", "referral_code", "referral_club_level_name",
    "first_name", "last_name", "email", "mobile", "client_type", "consumer_name", "service_name",
    "status", "commission_fixed", "commission_percentage", "commission_amount", "commission_type",
    "address", "suburb", "city", "territory", "paid"
];

    $params = [];
    $types = "";

    foreach ($campos as $campo) {
        $valor = $_POST[$campo] ?? null;
        if ($valor === "") $valor = null;
        $params[] = $valor;

        if (is_null($valor)) $types .= "s";
        elseif (is_numeric($valor) && strpos((string)$valor, '.') !== false) $types .= "d";
        elseif (is_numeric($valor)) $types .= "i";
        else $types .= "s";
    }

    $params[] = $id;
    $types .= "i";

    $sql = "UPDATE referrals SET " . implode(", ", array_map(fn($c) => "$c = ?", $campos)) . " WHERE id = ?";
    $upd = $conn->prepare($sql);
    $upd->bind_param($types, ...$params);
    $upd->execute();
    $upd->close();

    http_response_code(200);
    exit;
}

// Métricas
$metrics = $conn->prepare(
    "SELECT COUNT(*) AS Total,
            SUM(status = 'Successes'),
            SUM(status = 'Unsuccessful'),
            SUM(status = 'Pending'),
            SUM(status = 'Negotiating')
     FROM referrals"
);
$metrics->execute();
$metrics->bind_result($Total, $Successes, $Unsuccessful, $Pending, $Negotiating);
$metrics->fetch();
$metrics->close();

// Listagem de registros
$list = $conn->prepare("SELECT * FROM referrals ORDER BY created_at DESC");
$list->execute();
$res = $list->get_result();
$list->close();

// Buscar todos os serviços
$servicesQuery = $conn->query("SELECT id, service_name FROM services ORDER BY service_name ASC");
$services = [];
while ($service = $servicesQuery->fetch_assoc()) {
    $services[] = $service;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Admin – Referrals</title>
  <link rel="stylesheet" href="referrals_admin.css">
</head>
<body>
  <h3>Total de Indicações: <?= htmlspecialchars((string)$Total) ?></h3>
  <div class="metrics">
    <span>Successes: <?= htmlspecialchars((string)$Successes) ?></span>
    <span>Unsuccessful: <?= htmlspecialchars((string)$Unsuccessful) ?></span>
    <span>Pending: <?= htmlspecialchars((string)$Pending) ?></span>
    <span>Negotiating: <?= htmlspecialchars((string)$Negotiating) ?></span>
  </div>

  <table>
<thead>
  <tr>
    <th>Referred</th> <!-- 👈 NOVA COLUNA VISÍVEL -->
    <th>Referred by</th>
    <th>Code</th>
    <th>Level</th>
    <th>Email</th>
    <th>Mobile</th>
    <th>Type</th>
    <th>Consumer</th>
    <th>Service</th>
    <th>Status</th>
    <th>Amount</th>
    <th>Commission</th>
    <th>City</th>
    <th>Paid</th>
    <th>Action</th>
  </tr>
</thead>
<tbody>
<?php while ($row = $res->fetch_assoc()): ?>
  <tr data-original='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'>
    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
    <?php foreach (["service_consumer_id", "user_id", "referral_club_level_name", "first_name", "last_name",
                    "commission_percentage", "address", "suburb", "territory", "created_at", "service_id"] as $hidden): ?>
      <input type="hidden" name="<?= $hidden ?>" value="<?= htmlspecialchars($row[$hidden] ?? '') ?>">
    <?php endforeach; ?>

    <td><input type="text" name="referred" value="<?= htmlspecialchars($row['referred'] ?? '') ?>" readonly></td>
    <td><input type="text" name="referred_by" value="<?= htmlspecialchars($row['referred_by'] ?? '') ?>" readonly></td>
    <td><input type="text" name="referral_code" value="<?= htmlspecialchars($row['referral_code'] ?? '') ?>" readonly></td>
    <td><input type="text" name="referral_club_level" value="<?= htmlspecialchars($row['referral_club_level_name'] ?? '') ?>" readonly></td>
    <td><input type="email" name="email" value="<?= htmlspecialchars($row['email'] ?? '') ?>" readonly></td>
    <td><input type="text" name="mobile" value="<?= htmlspecialchars($row['mobile'] ?? '') ?>" readonly></td>
<td>
  <select name="client_type" disabled>
    <option value="">Select</option>
    <option value="Company" <?= ($row['client_type'] === 'Company') ? 'selected' : '' ?>>Company</option>
    <option value="Home" <?= ($row['client_type'] === 'Home') ? 'selected' : '' ?>>Home</option>
  </select>
</td>
    <td><input type="text" name="consumer_name" value="<?= htmlspecialchars($row['consumer_name'] ?? '') ?>" readonly></td>
    <td>
<select name="service_name" disabled>
    <option value="">Select</option>
    <?php foreach ($services as $service): ?>
        <option value="<?= htmlspecialchars($service['service_name']) ?>" 
            <?= ($row['service_name'] === $service['service_name']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($service['service_name']) ?>
        </option>
    <?php endforeach; ?>
</select>

</td>
    <td>
      <select name="status" disabled>
        <?php foreach (['Pending','Successes','Unsuccessful','Negotiating'] as $opt): ?>
          <option value="<?= $opt ?>" <?= ($row['status'] === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
    </td>
    <td data-field="commission_fixed">
      <input type="text" name="commission_fixed" class="commission_fixed_field" value="<?= htmlspecialchars($row['commission_fixed'] ?? '') ?>" readonly>
    </td>
    <td data-field="commission_amount">
      <input type="text" name="commission_amount" class="commission_amount_field" value="<?= htmlspecialchars($row['commission_amount'] ?? '') ?>" readonly>
    </td>
    <td>
      <select name="commission_type" class="commission_type_select" disabled>
        <?php foreach (['fixed','percentage'] as $opt): ?>
          <option value="<?= $opt ?>" <?= ($row['commission_type'] === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
    </td>
    <td><input type="text" name="city" value="<?= htmlspecialchars($row['city'] ?? '') ?>" readonly></td>

    <!-- ✅ Nova Coluna "Paid" -->
    <td>
      <input type="checkbox" name="paid" value="1" <?= !empty($row['paid']) ? 'checked' : '' ?> disabled>
    </td>

    <td>
      <button type="button" class="edit-btn">Editar</button>
      <button type="button" class="save-line-btn" style="display:none;">Salvar</button>
    </td>
  </tr>
<?php endwhile; ?>
</tbody>
  </table>

  <script src="referrals_admin.js"></script>
  <script src="atualizar_stats_referrals.js"></script>
</body>
</html>