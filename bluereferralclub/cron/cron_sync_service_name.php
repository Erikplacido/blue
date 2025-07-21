<?php
// cron_sync_service_name.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclua o caminho correto para sua conexão
require_once __DIR__ . '/../conexao.php'; // ajuste o caminho se necessário

$sql = "
    UPDATE referrals r
    JOIN services s ON r.service_id = s.id
    SET r.service_name = s.service_name
    WHERE r.service_name IS NULL AND r.service_id IS NOT NULL
";

if ($conn->query($sql)) {
    echo "[" . date("Y-m-d H:i:s") . "] ✅ service_name sincronizado com sucesso.\n";
} else {
    echo "[" . date("Y-m-d H:i:s") . "] ❌ Erro: " . $conn->error . "\n";
}
?>