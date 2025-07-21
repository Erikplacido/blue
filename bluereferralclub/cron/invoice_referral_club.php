<?php
require_once(__DIR__ . '/../conexao.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Criar diretório de extratos se não existir
$extratoDir = __DIR__ . '/../invoice';
if (!file_exists($extratoDir)) {
    mkdir($extratoDir, 0775, true);
}

// Buscar usuários com pelo menos uma indicação 'Paid'
$sql = "
    SELECT u.id, u.name, u.referral_code
    FROM users u
    JOIN referrals r ON r.user_id = u.id
    WHERE r.status = 'Paid'
    GROUP BY u.id
";

$users = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $userId    = (int) $user['id'];
    $firstName = preg_replace('/[^a-zA-Z0-9]/', '', strtok($user['name'], ' '));
    $refCode   = preg_replace('/[^a-zA-Z0-9]/', '', $user['referral_code']);
    $month     = strtolower(date('F'));
    $year      = date('Y');

    // Nome da view
    $baseName  = "{$firstName}{$refCode}{$month}_{$year}_email_ok";
    $viewName  = strtolower(substr($baseName, 0, 64));

    // Confirmar que existem indicações 'Paid'
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM referrals 
        WHERE user_id = :user_id AND status = 'Paid'
    ");
    $stmt->execute([':user_id' => $userId]);
    $paidCount = (int) $stmt->fetchColumn();

    if ($paidCount === 0) {
        echo "🔕 Usuário {$user['name']} ({$refCode}) sem referrals 'Paid'. Ignorado.\n";
        continue;
    }

    // Criar a VIEW personalizada
    $viewSql = "
        CREATE OR REPLACE VIEW `$viewName` AS
        SELECT 
            r.id,
            r.consumer_name,
            r.referred,
            r.referred_last_name,
            r.service_name AS description,
            r.commission_amount AS amount,
            r.created_at,
            r.status,
            r.payment_reference,

            (
                SELECT SUM(r2.commission_amount)
                FROM referrals r2
                WHERE r2.user_id = $userId AND r2.status = 'Paid'
            ) AS total_commission,
            u.name AS user_name,
            u.email,
            u.referral_code,
            u.referral_club_level_name,
            u.bankName,
            u.agency,
            u.bsb,
            u.accountNumber,
            u.abnNumber
        FROM referrals r
        JOIN users u ON r.user_id = u.id
        WHERE r.user_id = $userId AND r.status = 'Paid'
    ";

    try {
        $pdo->exec($viewSql);
        echo "✅ View '$viewName' criada com sucesso!\n";
    } catch (PDOException $e) {
        echo "❌ Erro ao criar view '$viewName': " . $e->getMessage() . "\n";
        continue;
    }

    // Criar o extrato HTML com base na VIEW
    try {
        $extratoPath = "$extratoDir/$viewName.html";
        $data = $pdo->query("SELECT * FROM `$viewName`")->fetchAll(PDO::FETCH_ASSOC);

        if (!$data) {
            echo "⚠️ Nenhum dado na view '$viewName'.\n";
            continue;
        }

ob_start();
?>
<!DOCTYPE html>
<html lang="en-AU">
<head>
    <meta charset="UTF-8">
    <title>Commission Statement - <?= htmlspecialchars($data[0]['user_name']) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            color: #333;
            padding: 40px;
            background-color: #fff;
        }

        .invoice-box {
            max-width: 900px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .invoice-header img {
            max-height: 60px;
        }

        .invoice-header .title {
            font-size: 28px;
            font-weight: bold;
        }

        .info-block {
            margin-bottom: 20px;
        }

        .info-block h4 {
            margin: 4px 0;
            font-weight: normal;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        table th {
            background-color: #f5f5f5;
        }

        .total {
            text-align: right;
            font-size: 16px;
            margin-top: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="invoice-header">
            <div class="title">Commission Statement</div>
            <img src="https://bluefacilityservices.com.au/wp-content/uploads/2020/09/logo_novo.svg" alt="Company Logo">
        </div>

        <div class="info-block">
            <h4><strong>Name:</strong> <?= htmlspecialchars($data[0]['user_name']) ?></h4>
            <h4><strong>Email:</strong> <?= htmlspecialchars($data[0]['email']) ?></h4>
            <h4><strong>Payment Reference:</strong> <?= htmlspecialchars($data[0]['payment_reference']) ?></h4>
            <h4><strong>Period:</strong> <?= ucfirst($month) . " / " . $year ?></h4>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client Name</th>
                    <th>Referred Person</th>
                    <th>Service</th>
                    <th>Commission</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['consumer_name']) ?></td>
                        <td><?= htmlspecialchars($row['referred'] . ' ' . $row['referred_last_name']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td>$<?= number_format($row['amount'], 2) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="total">Total Commission: $<?= number_format($data[0]['total_commission'], 2) ?></p>
    </div>
</body>
</html>
<?php



        file_put_contents($extratoPath, ob_get_clean());
        echo "📄 Extrato gerado: $extratoPath\n";
    } catch (Exception $e) {
        echo "❌ Erro ao gerar extrato HTML para '$viewName': " . $e->getMessage() . "\n";
    }
}
