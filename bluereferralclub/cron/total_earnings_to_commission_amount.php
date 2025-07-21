<?php
require_once __DIR__ . '/../conexao.php';

try {
    // 1. Pega todas as views que terminam com '_total_earnings'
    $viewsStmt = $pdo->query("
        SELECT TABLE_NAME 
        FROM information_schema.VIEWS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME LIKE '%_total_earnings'
    ");

    $views = $viewsStmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($views as $viewName) {
        // 2. Consulta user_id e total_earnings da view
        $stmt = $pdo->prepare("SELECT user_id, total_earnings FROM `$viewName`");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $userId = (int)$row['user_id'];
            $totalEarnings = (float)$row['total_earnings'];

            // 3. Atualiza commission_amount na tabela users
            $update = $pdo->prepare("UPDATE users SET commission_amount = ? WHERE id = ?");
            $update->execute([$totalEarnings, $userId]);

            echo "✔️ [$viewName] Atualizado ID $userId com total_earnings = $totalEarnings\n";
        } else {
            echo "⚠️ [$viewName] Nenhum dado retornado.\n";
        }
    }

} catch (PDOException $e) {
    die("❌ Erro: " . $e->getMessage());
}
