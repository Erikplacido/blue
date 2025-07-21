<?php
require_once __DIR__ . '/../conexao.php'; // Ajuste o caminho conforme necessário

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Atualizar os valores de comissão com base no nível do clube
    $sql = "
        UPDATE referrals r
        JOIN referral_club_data d ON r.referral_club_level_name = d.level
        SET 
            r.commission_fixed = d.commission_fixed,
            r.commission_percentage = d.commission_percentage
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    echo "[" . date('Y-m-d H:i:s') . "] 🔁 Commissions updated successfully.\n";
} catch (PDOException $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ⚠️ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
