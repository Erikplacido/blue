<?php
require_once __DIR__ . '/../conexao.php'; // Inclui a conexão com o banco

try {
    // Buscar todos os usuários
    $sql = "SELECT id, first_name, referral_code FROM users";
    $users = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        $userId = (int) $user['id'];
        $firstName = preg_replace('/[^a-zA-Z0-9]/', '', $user['first_name']);
        $refCode = preg_replace('/[^a-zA-Z0-9]/', '', $user['referral_code']);

        $viewName = strtolower("{$firstName}{$refCode}total_earnings");

        // Apaga a view se já existir
        $dropViewSQL = "DROP VIEW IF EXISTS `$viewName`";
        $pdo->exec($dropViewSQL);

        // Cria nova view com total de comissões pagas
        $createViewSQL = "
            CREATE VIEW `$viewName` AS
            SELECT 
                $userId AS user_id,
                COALESCE(SUM(commission_amount), 0) AS total_earnings
            FROM referrals
            WHERE user_id = $userId AND status = 'Paid';
        ";
        $pdo->exec($createViewSQL);

        echo "✔️ View criada/atualizada: $viewName\n";
    }

} catch (PDOException $e) {
    die("❌ Erro: " . $e->getMessage());
}
?>
