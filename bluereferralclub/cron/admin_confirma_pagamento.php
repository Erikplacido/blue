<?php
require_once(__DIR__ . '/../conexao.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Buscar usuários com pelo menos uma indicação 'Successes'
$sql = "
    SELECT u.id, u.name, u.referral_code
    FROM users u
    JOIN referrals r ON r.user_id = u.id
    WHERE r.status = 'Successes'
    GROUP BY u.id
";

$users = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $userId    = (int) $user['id'];
    $firstName = preg_replace('/[^a-zA-Z0-9]/', '', strtok($user['name'], ' '));
    $refCode   = preg_replace('/[^a-zA-Z0-9]/', '', $user['referral_code']);
    $month     = strtolower(date('F'));
    $year      = date('Y');

    // Gera nome da view (normalizado e com no máximo 64 caracteres)
    $baseName  = "{$firstName}{$refCode}{$month}_{$year}_paid";
    $viewName  = strtolower(substr($baseName, 0, 64));

    // Verifica se o usuário ainda possui indicações válidas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM referrals 
        WHERE user_id = :user_id AND status = 'Successes'
    ");
    $stmt->execute([':user_id' => $userId]);
    $successCount = (int) $stmt->fetchColumn();

    if ($successCount === 0) {
        echo "🔕 Usuário {$user['name']} ({$refCode}) sem referrals 'Successes'. Ignorado.\n";
        continue;
    }

    // SQL da view — compatível com o modelo do dump exportado
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
            (
                SELECT SUM(r2.commission_amount)
                FROM referrals r2
                WHERE r2.user_id = u.id AND r2.status = 'Successes'
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
        WHERE r.user_id = :user_id AND r.status = 'Successes'
    ";

    try {
        $stmt = $pdo->prepare($viewSql);
        $stmt->execute([':user_id' => $userId]);
        echo "✅ View '$viewName' criada com sucesso!\n";
    } catch (PDOException $e) {
        echo "❌ Erro ao criar view '$viewName': " . $e->getMessage() . "\n";
    }
}
