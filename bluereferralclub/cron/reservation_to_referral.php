<?php
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
                (user_id, consumer_name, referred, referred_last_name, referral_code, email, mobile, postcode, address, suburb, city, territory, service_id, service_name, commission_amount, created_at) 
                VALUES 
                (:user_id, :consumer_name, :referred, :referred_last_name, :referral_code, :email, :mobile, :postcode, :address, :suburb, :city, :territory, :service_id, :service_name, :commission_amount, NOW())");

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
                ':commission_amount'   => $reservation['commission_amount']
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
?>
