<?php
// cron_update_commission.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ Caminho relativo corrigido para conexão
require_once __DIR__ . '/../conexao.php';

// 🔄 Importante: sempre refletir mudanças em tempo real
// Este script deve ser executado periodicamente (ex: cron) para garantir
// que quaisquer alterações nas referências/comissões reflitam no usuário final

$sql = "
    UPDATE users u
    JOIN user_commission_summary v ON v.user_id = u.id
    SET u.commission_amount = v.total_commission
";

// 🛡️ Execução com checagem
if ($conn->query($sql)) {
    echo "[" . date("Y-m-d H:i:s") . "] ✅ Comissão dos usuários atualizada com sucesso. Os dados foram sincronizados com base nas informações atuais da view.\n";
} else {
    echo "[" . date("Y-m-d H:i:s") . "] ❌ Erro ao atualizar comissão: " . $conn->error . "\n";
}
?>