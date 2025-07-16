<?php
require_once(__DIR__ . '/../conexao.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Buscar todos os usuários com pelo menos uma indicação com status 'Successes'
$sql = "
    SELECT u.id, u.name, u.referral_code
    FROM users u
    JOIN referrals r ON r.user_id = u.id
    WHERE r.status = 'Successes'
    GROUP BY u.id
";

$users = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $userId   = $user['id'];
    $firstName = preg_replace('/\s+/', '', strtok($user['name'], ' '));
    $refCode   = $user['referral_code'];
    $month = strtolower(date('F'));
    $year = date('Y');
    $viewName = strtolower($firstName . $refCode . $month . '_' . $year. '_paid');

    // 🔎 Verifica se realmente há registros com status 'Successes' para este usuário
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE user_id = :user_id AND status = 'Successes'");
    $stmt->execute([':user_id' => $userId]);
    $successCount = $stmt->fetchColumn();

    if ((int)$successCount === 0) {
        echo "🔕 Usuário {$user['name']} ({$refCode}) não possui referrals com status 'Successes'. Ignorado.\n";
        continue;
    }

    // 🎯 Criar ou atualizar a VIEW
    $viewSql = "
        CREATE OR REPLACE VIEW `$viewName` AS
        SELECT 
            r.id,
            r.consumer_name,
            r.referred,
            r.referred_last_name,
            r.service_name,
            r.commission_amount,
            r.created_at,
            (
                SELECT SUM(r2.commission_amount)
                FROM referrals r2
                WHERE r2.user_id = u.id AND r2.status = 'Successes'
            ) AS total_commission,
            u.name AS user_name,
            u.referral_code,
            u.referral_club_level_name,
            u.bankName,
            u.agency,
            u.bsb,
            u.accountNumber,
            u.abnNumber
        FROM referrals r
        JOIN users u ON r.user_id = u.id
        WHERE r.user_id = {$userId} AND r.status = 'Successes'
    ";

    try {
        $pdo->exec($viewSql);
        echo "✅ View '$viewName' criada com sucesso!\n";
    } catch (PDOException $e) {
        echo "❌ Erro ao criar view '$viewName': " . $e->getMessage() . "\n";
    }
}
