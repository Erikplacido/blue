<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT referral_code, commission_amount, first_name, referral_club_level_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($referral_code, $commission_amount, $first_name, $referral_club_level_name);
$stmt->fetch();
$stmt->close();

// 🔢 Busca ranking por commission_amount (maior valor = 1º lugar)
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
    SUM(status = 'Successes') AS successes,
    SUM(status = 'Unsuccessful') AS unsuccessful,
    SUM(status = 'Pending') AS pending,
    SUM(status = 'Negotiating') AS negotiating
  FROM referrals
  WHERE referral_code = ?
");
$stats->bind_param("s", $referral_code);
$stats->execute();
$stats->bind_result($total, $successes, $unsuccessful, $pending, $negotiating);
$stats->fetch();
$stats->close();

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

$upcoming_payment = 0.00;
$referrals = [];

while ($row = $res->fetch_assoc()) {
    $referrals[] = $row;
    $upcoming_payment += (float)($row['commission_amount'] ?? 0);
}
$query->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Minhas Indicações</title>
</head>
<body>

  <p>
    Hello, <strong><?= htmlspecialchars($first_name) ?>!<br></strong>
    Your referral code is <strong><?= htmlspecialchars($referral_code) ?><br></strong>
    Category in the club is <strong><?= htmlspecialchars($referral_club_level_name) ?><br></strong>
    Your position in the referral ranking is <strong>Top <?= $position ?><br></strong>
    
    
    
 Ranking:
<?php if (count($top_users) > 0): ?>
  <?php foreach ($top_users as $i => $top): ?>
    Top <?= $i + 1 ?> (<?= htmlspecialchars($top['first_name']) ?> $<?= number_format($top['commission_amount'], 2, ',', '.') ?>)
    <?= $i < count($top_users) - 1 ? ' | ' : '' ?>
  <?php endforeach; ?>
<?php else: ?>
  No ranking data available.
<?php endif; ?>

    
    
    
  </p>

  <div>
    <p>Total earnings: $ <strong><?= number_format($commission_amount, 2, ',', '.') ?></strong></p>
    <p>Upcoming payment: $ <strong><?= number_format($upcoming_payment, 2, ',', '.') ?></strong></p>
  </div>

  <div>
    <p>
      Total: <strong><?= (int)$total ?></strong> |
      Successes: <strong><?= (int)$successes ?></strong> |
      Unsuccessful: <strong><?= (int)$unsuccessful ?></strong> |
      Pending: <strong><?= (int)$pending ?></strong> |
      Negotiating: <strong><?= (int)$negotiating ?></strong>
    </p>
  </div>

  <table border="1">
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
        <tr>
          <td><input type="text" value="<?= htmlspecialchars($row['referred'] ?? '') ?>" readonly></td>
          <td>
            <select disabled>
              <?php foreach (['Pending','Successes','Unsuccessful','Negotiating'] as $opt): ?>
                <option value="<?= $opt ?>" <?= ($row['status'] === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input type="text" value="<?= htmlspecialchars($row['commission_amount'] ?? '') ?>" readonly></td>
          <td><input type="text" value="<?= htmlspecialchars($row['city'] ?? '') ?>" readonly></td>
          <td><input type="text" value="<?= htmlspecialchars($row['created_at'] ?? '') ?>" readonly></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

</body>
</html>
