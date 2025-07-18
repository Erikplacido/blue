<?php
date_default_timezone_set('Australia/Sydney');
$logFile = __DIR__ . '/cron_unificado.log';

function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $message . "\n", FILE_APPEND);
}

logMessage("===== Início da execução do CRON unificado =====");


// ======================= cron_sync_service_name.php =======================
logMessage("Executando: cron_sync_service_name.php");
try {

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

    logMessage("✔️ Concluído com sucesso: cron_sync_service_name.php");
} catch (Throwable $e) {
    logMessage("❌ Erro em cron_sync_service_name.php: " . $e->getMessage());
}

// ============================================================

// ======================= cron_sync_user_club_levels.php =======================
logMessage("Executando: cron_sync_user_club_levels.php");
try {

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


    logMessage("✔️ Concluído com sucesso: cron_sync_user_club_levels.php");
} catch (Throwable $e) {
    logMessage("❌ Erro em cron_sync_user_club_levels.php: " . $e->getMessage());
}

// ============================================================





// ======================= cron_update_commission.php =======================
logMessage("Executando: cron_update_commission.php");
try {

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

    logMessage("✔️ Concluído com sucesso: cron_update_commission.php");
} catch (Throwable $e) {
    logMessage("❌ Erro em cron_update_commission.php: " . $e->getMessage());
}

// ============================================================

// ======================= quote_to_referral.php =======================
logMessage("Executando: quote_to_referral.php");
try {

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php'; // ajuste o caminho se necessário

$logFile = __DIR__ . '/cron_sync_quotes.log';

try {
    $query = $pdo->prepare("SELECT * FROM quote_admin WHERE referral_code IS NOT NULL AND referral_code != '' AND processed = 0");
    $query->execute();
    $quotes = $query->fetchAll(PDO::FETCH_ASSOC);

    echo "Encontrados " . count($quotes) . " registros pendentes na tabela quote_admin.\n";

    $inserted = 0;

    foreach ($quotes as $quote) {
        try {
            $userStmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = :referral_code LIMIT 1");
            $userStmt->execute([':referral_code' => $quote['referral_code']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            $user_id = $user ? $user['id'] : 1;

            // Pega client_type se existir, senão define como NULL
            $client_type = $quote['client_type'] ?? null;

$stmt = $pdo->prepare("INSERT INTO referrals 
    (user_id, referred, referred_last_name, referral_code, email, mobile, postcode, number, address, suburb, city, territory, service_id, service_name, more_details, client_type, created_at) 
    VALUES 
    (:user_id, :referred, :referred_last_name, :referral_code, :email, :mobile, :postcode, :number, :address, :suburb, :city, :territory, :service_id, :service_name, :more_details, :client_type, NOW())");

$stmt->execute([
    ':user_id'             => $user_id,
    ':referred'            => $quote['referred'],
    ':referred_last_name'  => $quote['referred_last_name'],
    ':referral_code'       => $quote['referral_code'],
    ':email'               => $quote['email'],
    ':mobile'              => $quote['mobile'],
    ':postcode'            => $quote['postcode'],
    ':number'              => $quote['number'] ?? null,
    ':address'             => $quote['address'],
    ':suburb'              => $quote['suburb'] ?? null,
    ':city'                => $quote['city'] ?? null,
    ':territory'           => $quote['territory'] ?? null,
    ':service_id'          => $quote['service_id'],
    ':service_name'        => $quote['service_name'],
    ':more_details'        => $quote['more_details'],
    ':client_type'         => $client_type
]);
            $updateStmt = $pdo->prepare("UPDATE quote_admin SET processed = 1 WHERE id = :id");
            $updateStmt->execute([':id' => $quote['id']]);

            $inserted++;

        } catch (PDOException $e) {
            file_put_contents(
                $logFile,
                date('Y-m-d H:i:s') . " - Erro ao inserir quote ID " . ($quote['id'] ?? 'desconhecido') . ": " . $e->getMessage() . "\n",
                FILE_APPEND
            );
        }
    }

    echo "Processo concluído. {$inserted} registros inseridos com sucesso.\n";

} catch (PDOException $e) {
    file_put_contents(
        $logFile,
        date('Y-m-d H:i:s') . " - Erro no processo principal: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
    echo "Erro no processo principal: " . $e->getMessage();
}

logMessage("✔️ Concluído com sucesso: quote_to_referral.php");

} catch (Throwable $e) {
    logMessage("❌ Erro em quote_to_referral.php: " . $e->getMessage());
}


// ============================================================

// ======================= reservation_to_referral.php =======================
logMessage("Executando: reservation_to_referral.php");
try {

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php'; // ajuste o caminho se necessário

$logFile = __DIR__ . '/cron_sync_reservations.log';

try {
    // Só buscamos registros que ainda NÃO foram processados
    $query = $pdo->prepare("SELECT * FROM reservation_admin WHERE referral_code IS NOT NULL AND referral_code != '' AND processed = 0");
    $query->execute();
    $reservations = $query->fetchAll(PDO::FETCH_ASSOC);

    echo "Encontrados " . count($reservations) . " registros pendentes na tabela reservation_admin.\n";

    $inserted = 0;

    foreach ($reservations as $reservation) {
        try {
            // Buscar user_id baseado no referral_code
            $userStmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = :referral_code LIMIT 1");
            $userStmt->execute([':referral_code' => $reservation['referral_code']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            $user_id = $user ? $user['id'] : 1; // Se não achar, fallback para user_id = 1

            // Inserir na tabela referrals
$stmt = $pdo->prepare("INSERT INTO referrals 
    (user_id, consumer_name, referred, referred_last_name, referral_code, email, mobile, postcode, address, suburb, city, territory, service_id, service_name, commission_amount, client_type, created_at) 
    VALUES 
    (:user_id, :consumer_name, :referred, :referred_last_name, :referral_code, :email, :mobile, :postcode, :address, :suburb, :city, :territory, :service_id, :service_name, :commission_amount, :client_type, NOW())");

$stmt->execute([
    ':user_id'             => $user_id,
    ':consumer_name'       => $reservation['consumer_name'],
    ':referred'            => $reservation['referred'],
    ':referred_last_name'  => $reservation['referred_last_name'],
    ':referral_code'       => $reservation['referral_code'],
    ':email'               => $reservation['email'],
    ':mobile'              => $reservation['mobile'],
    ':postcode'            => $reservation['postcode'],
    ':address'             => $reservation['address'],
    ':suburb'              => $reservation['suburb'],
    ':city'                => $reservation['city'],
    ':territory'           => $reservation['territory'],
    ':service_id'          => $reservation['service_id'],
    ':service_name'        => $reservation['service_name'],
    ':commission_amount'   => $reservation['commission_amount'],
    ':client_type'         => $reservation['client_type'] // ✅ Adicionado
]);


            // ✅ Marcar como processado
            $updateStmt = $pdo->prepare("UPDATE reservation_admin SET processed = 1 WHERE id = :id");
            $updateStmt->execute([':id' => $reservation['id']]);

            $inserted++;

        } catch (PDOException $e) {
            file_put_contents(
                $logFile,
                date('Y-m-d H:i:s') . " - Erro ao inserir reservation ID " . ($reservation['id'] ?? 'desconhecido') . ": " . $e->getMessage() . "\n",
                FILE_APPEND
            );
        }
    }

    echo "Processo concluído. {$inserted} registros inseridos com sucesso.\n";

} catch (PDOException $e) {
    file_put_contents(
        $logFile,
        date('Y-m-d H:i:s') . " - Erro no processo principal: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
    echo "Erro no processo principal: " . $e->getMessage();
}


    logMessage("✔️ Concluído com sucesso: reservation_to_referral.php");
} catch (Throwable $e) {
    logMessage("❌ Erro em reservation_to_referral.php: " . $e->getMessage());
}

// ============================================================

// ======================= referral_club_name.php =======================
logMessage("Executando: referral_club_name.php");
try {

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


    logMessage("✔️ Concluído com sucesso: referral_club_name.php");
} catch (Throwable $e) {
    logMessage("❌ Erro em referral_club_name.php: " . $e->getMessage());
}

// ============================================================

logMessage("===== Fim da execução do CRON unificado =====");