<?php
// cron_update_referral_level.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexão
require_once __DIR__ . '/../conexao.php'; // ajuste o caminho se necessário

$logDir = __DIR__;
$logFile = $logDir . '/cron_update_referral_level.log';

try {
    // Primeiro: buscar todos os referral_codes e club_level_name dos USERS
    $sqlSelect = "SELECT referral_code, referral_club_level_name FROM users WHERE referral_code IS NOT NULL";
    $stmtSelect = $pdo->prepare($sqlSelect);
    $stmtSelect->execute();
    $users = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

    $totalAtualizados = 0;

    foreach ($users as $user) {
        $referralCode = $user['referral_code'];
        $clubLevelName = $user['referral_club_level_name'];

        // Atualizar SOMENTE se o valor for diferente
        $sqlUpdate = "
            UPDATE referrals 
            SET referral_club_level_name = :club_level_name 
            WHERE referral_code = :referral_code
            AND (referral_club_level_name IS NULL OR referral_club_level_name != :club_level_name_check)
        ";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':club_level_name' => $clubLevelName,
            ':referral_code' => $referralCode,
            ':club_level_name_check' => $clubLevelName
        ]);

        $totalAtualizados += $stmtUpdate->rowCount();
    }

    $mensagem = "Atualização finalizada às " . date('Y-m-d H:i:s') . " - Total linhas alteradas: $totalAtualizados\n";
    echo $mensagem;
    file_put_contents($logFile, $mensagem, FILE_APPEND);

} catch (PDOException $e) {
    $erroMensagem = "Erro ao conectar ou atualizar: " . $e->getMessage() . "\n";
    echo $erroMensagem;
    file_put_contents($logFile, $erroMensagem, FILE_APPEND);
    exit(1);
}
?>
