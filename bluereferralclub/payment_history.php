<?php
// payment_history.php

// 1) DEBUG de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/conexao.php';  // adjust as needed
$user_id = (int) $_SESSION['user_id'];

// helper to escape output safely
function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Dynamic page title
$pageTitle = 'Payment History';

?><!DOCTYPE html>
<html lang="en-AU">
<head>
  <meta charset="UTF-8">
  <title><?= e($pageTitle) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- your main CSS -->
  <link rel="stylesheet" href="css/dashboard.css">
  <style>
    /* inline styles for the invoice box */
    .invoice-container {
      max-width: 800px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border: 1px solid #ddd;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      border-radius: 4px;
    }
    .invoice-container h1,
    .invoice-container h2 {
      text-align: center;
      color: #11284B;
      margin-bottom: 24px;
    }
    .history-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .history-table th,
    .history-table td {
      padding: 12px 16px;
      border: 1px solid #e5e7eb;
      text-align: left;
      font-size: 0.95rem;
    }
    .history-table thead th {
      background-color: #f3f4f6;
      color: #374151;
      text-transform: uppercase;
      font-weight: 600;
    }
    .history-table tbody tr:nth-child(even) {
      background-color: #fafafa;
    }
  </style>
</head>
<body>

  <!-- === HEADER / NAVBAR === -->
  <header class="main-header">
    <!-- ... your banner and nav ... -->
  </header>

  <!-- === MAIN CONTENT === -->
  <div class="middle container invoice-container">
    <h1><?= e($pageTitle) ?></h1>

    <?php if (!empty($_GET['view'])):
      // DETAIL MODE
      $viewName = preg_replace('/[^a-z0-9_]/','', strtolower($_GET['view']));
      $stmt = $pdo->prepare("
        SELECT payment_id, referred, referred_last_name, amount, payment_reference,
               month_name, year_num
          FROM payment_history
         WHERE user_id = ?
           AND view_name = ?
         ORDER BY payment_id
      ");
      $stmt->execute([$user_id, $viewName]);
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if (empty($rows)): ?>

        <p>Invoice not found.</p>
        <p><a href="payment_history.php">← Back</a></p>

      <?php else:
        $month = $rows[0]['month_name'];
        $year  = $rows[0]['year_num'];
        $invoiceLabel = "paid_commission_of_{$month}_{$year}";
      ?>

        <p><a href="payment_history.php">← Back to history</a></p>
        <h2>Detail: <?= e($invoiceLabel) ?></h2>

        <div class="table-responsive">
          <table class="history-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Referred</th>
                <th>Last Name</th>
                <th>Amount</th>
                <th>Ref</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= e($r['payment_id']) ?></td>
                  <td><?= e($r['referred']) ?></td>
                  <td><?= e($r['referred_last_name']) ?></td>
                  <td><?= e(number_format($r['amount'],2)) ?></td>
                  <td><?= e($r['payment_reference']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php endif; ?>

    <?php else:
      // LIST MODE
      $stmt = $pdo->prepare("
        SELECT DISTINCT view_name, month_name, year_num
          FROM payment_history
         WHERE user_id = ?
         ORDER BY year_num DESC, month_name DESC
      ");
      $stmt->execute([$user_id]);
      $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

      <?php if (empty($invoices)): ?>
        <p>You don’t have any payment records yet.</p>
      <?php else: ?>
        <div class="bottom">
          <h3>Monthly Invoices</h3>
          <ul class="history-list">
            <?php foreach ($invoices as $inv):
              $fn    = strtok($inv['view_name'], '_');
              $label = "paid_commission_of_{$inv['month_name']}_{$inv['year_num']}";
            ?>
              <li>
                <a href="?view=<?= e($inv['view_name']) ?>">
                  <?= e($label) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>

  <!-- === FOOTER / SCRIPTS === -->
  <script src="js/user_hamburger.js" defer></script>
  <script src="js/user_script.js" defer></script>
</body>
</html>
