<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../conexao.php');
require_once(__DIR__ . '/../PHPMailer/src/PHPMailer.php');
require_once(__DIR__ . '/../PHPMailer/src/SMTP.php');
require_once(__DIR__ . '/../PHPMailer/src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Parâmetros
$month = strtolower(date('F'));
$year  = date('Y');

// Buscar todos os usuários com referral_code
$sql = "SELECT id, name, referral_code FROM users WHERE referral_code IS NOT NULL AND referral_code != ''";
$users = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Diretório onde os flags de envio serão armazenados
$flagDir = __DIR__ . '/.sent_flags';
if (!file_exists($flagDir)) {
    mkdir($flagDir, 0775, true);
}

foreach ($users as $user) {
    $firstName = preg_replace('/[^a-zA-Z0-9]/', '', strtok($user['name'], ' '));
    $refCode   = preg_replace('/[^a-zA-Z0-9]/', '', $user['referral_code']);

    $baseName  = "{$firstName}{$refCode}{$month}_{$year}_email_ok";
    $viewName  = strtolower(substr($baseName, 0, 64));

    // Caminho do flag de controle de envio
    $sentFlagFile = "$flagDir/{$viewName}.sent";

    // Se já foi enviado este mês, pula
    if (file_exists($sentFlagFile)) {
        echo "📭 E-mail já enviado este mês para view '$viewName' (flag encontrada)\n";
        continue;
    }

    // Verifica se a view existe
    $check = $pdo->prepare("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_u979853733_BFS = ?");
    $check->execute([$viewName]);

    if (!$check->fetchColumn()) {
        echo "❌ View '$viewName' não encontrada\n";
        continue;
    }

    // Pegar o e-mail da view
    $emailQuery = $pdo->query("SELECT DISTINCT email FROM `$viewName` WHERE email IS NOT NULL AND email != '' LIMIT 1");
    $email = $emailQuery->fetchColumn();

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "❌ E-mail inválido ou não encontrado para view '$viewName'\n";
        continue;
    }

    // Enviar o e-mail
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'contact@bluefacilityservices.com.au';
        $mail->Password   = 'BlueM@rketing33';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('contact@bluefacilityservices.com.au', 'Blue Referral Club');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Blue Referral Club Payment Confirmed';
        $mail->Body = "
            <p>Hello,</p>
            <p>This is a confirmation that your referral commissions have been marked as <strong>Paid</strong> and your payment process has been completed.</p>
            <p>Thank you for being part of the Referral Club!</p>
            <p><strong>Blue Facility Services</strong></p>
        ";

        $mail->send();

        // Marca como enviado criando o flag
        file_put_contents($sentFlagFile, date('Y-m-d H:i:s') . " - enviado para $email\n");

        echo "✅ E-mail enviado com sucesso para $email (view: $viewName)\n";

    } catch (Exception $e) {
        echo "❌ Falha ao enviar para $email: " . $mail->ErrorInfo . "\n";
    }
}
