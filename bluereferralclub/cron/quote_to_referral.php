<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../conexao.php'; // ajuste o caminho se necessário

$logFile = __DIR__ . '/cron_sync_quotes.log';

try {
    // Agora só buscamos registros que ainda NÃO foram processados
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

            $user_id = $user ? $user['id'] : 1; // Fallback user_id = 1 se não encontrar

            $stmt = $pdo->prepare("INSERT INTO referrals 
                (user_id, referred, referred_last_name, referral_code, email, mobile, postcode, address, service_id, service_name, more_details, created_at) 
                VALUES 
                (:user_id, :referred, :referred_last_name, :referral_code, :email, :mobile, :postcode, :address, :service_id, :service_name, :more_details, NOW())");

            $stmt->execute([
                ':user_id'             => $user_id,
                ':referred'            => $quote['referred'],
                ':referred_last_name'  => $quote['referred_last_name'],
                ':referral_code'       => $quote['referral_code'],
                ':email'               => $quote['email'],
                ':mobile'              => $quote['mobile'],
                ':postcode'            => $quote['postcode'],
                ':address'             => $quote['address'],
                ':service_id'          => $quote['service_id'],
                ':service_name'        => $quote['service_name'],
                ':more_details'        => $quote['more_details']
            ]);

            // ✅ Marcar como processado
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
?>
