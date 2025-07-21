<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once(__DIR__ . '/../bluereferralclub/conexao.php');

// ✅ Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Acesso negado');
}

// ✅ Valida e sanitiza a view
$view = $_GET['view'] ?? '';
if (!$view || !preg_match('/^[a-z0-9_]+$/i', $view)) {
    http_response_code(400);
    exit('View inválida');
}

// ✅ Consulta agregada via view para total de comissões
try {
    $stmtAgg = $pdo->prepare("SELECT user_id AS id, total_earnings AS total_commission FROM `$view`");
    $stmtAgg->execute();
    $agg = $stmtAgg->fetch(PDO::FETCH_ASSOC);
    if (!$agg) {
        http_response_code(404);
        exit('Nenhum dado encontrado.');
    }
    $userId = (int) $agg['id'];
    $totalCommission = $agg['total_commission'];
} catch (PDOException $e) {
    http_response_code(500);
    exit('Erro ao obter total: ' . $e->getMessage());
}

// ✅ Dados bancários do usuário
try {
    $stmtUser = $pdo->prepare("
        SELECT name AS user_name, bankName, agency, bsb, accountNumber, abnNumber
        FROM users
        WHERE id = ?
    ");
    $stmtUser->execute([$userId]);
    $bankData = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if (!$bankData) {
        http_response_code(404);
        exit('Dados bancários não encontrados.');
    }
} catch (PDOException $e) {
    http_response_code(500);
    exit('Erro ao obter dados bancários: ' . $e->getMessage());
}

// ✅ Detalhes individuais das comissões
try {
    $stmtDetails = $pdo->prepare("
        SELECT
            r.id,
            r.consumer_name,
            CONCAT(r.referred, ' ', r.referred_last_name) AS referred,
            r.more_details AS description,
            r.commission_amount AS amount,
            r.created_at
        FROM referrals AS r
        WHERE r.user_id = ?
          AND r.status = 'Successes'
    ");
    $stmtDetails->execute([$userId]);
    $details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Erro ao obter detalhes: ' . $e->getMessage());
}
?>

<!-- 🔹 Bank Details -->
<h5>Bank Details</h5>
<p><strong>User:</strong> <?= htmlspecialchars($bankData['user_name'] ?? '') ?></p>
<ul>
    <li><strong>Bank:</strong> <?= htmlspecialchars($bankData['bankName'] ?? '') ?></li>
    <li><strong>Agency:</strong> <?= htmlspecialchars($bankData['agency'] ?? '') ?></li>
    <li><strong>BSB:</strong> <?= htmlspecialchars($bankData['bsb'] ?? '') ?></li>
    <li><strong>Account:</strong> <?= htmlspecialchars($bankData['accountNumber'] ?? '') ?></li>
    <li><strong>ABN:</strong> <?= htmlspecialchars($bankData['abnNumber'] ?? '') ?></li>
</ul>

<!-- 🔹 Commission Details -->
<h5 class="mt-4">Commission Details</h5>
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Consumer Name</th>
            <th>Referred</th>
            <th>Service</th>
            <th>Amount</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($details as $row): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['consumer_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['referred'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['description'] ?? '') ?></td>
            <td>$<?= number_format($row['amount'] ?? 0, 2) ?></td>
            <td><?= isset($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : '' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- 🔹 TOTAL GERAL -->
<div class="text-end mt-3">
    <p><strong>Total Amount:</strong> $<?= number_format($totalCommission, 2) ?></p>
</div>