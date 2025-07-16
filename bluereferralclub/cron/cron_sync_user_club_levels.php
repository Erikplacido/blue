<?php
// cron_sync_user_club_levels.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../conexao.php'; // Ajuste o caminho se necessário

$sql = "
    UPDATE users u
    JOIN referral_club_data rcd ON u.referral_club_level_id = rcd.id
    SET u.referral_club_level_name = rcd.level
";

if ($conn->query($sql)) {
    echo "[" . date("Y-m-d H:i:s") . "] ✅ Níveis dos usuários sincronizados com sucesso.\n";
} else {
    echo "[" . date("Y-m-d H:i:s") . "] ❌ Erro ao sincronizar: " . $conn->error . "\n";
}
?>
