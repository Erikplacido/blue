<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(__DIR__ . '/../PHPMailer/src/PHPMailer.php');
require_once(__DIR__ . '/../PHPMailer/src/Exception.php');
require_once(__DIR__ . '/../PHPMailer/src/SMTP.php');
require_once 'email_templates.php';
require_once __DIR__ . '/../email_dispatcher.php'; // ajuste o caminho se necessário
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);



function sendEmail($to, $name, $subject, $body) {
        $mail = new PHPMailer(true);
        try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'contact@bluefacilityservices.com.au'; // Substituir se necessário
        $mail->Password = 'BlueM@rketing33'; // Nunca compartilhe em público 😅
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('contact@bluefacilityservices.com.au', 'Blue Referral Club');
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->CharSet = 'UTF-8';

        $mail->send();
    } catch (Exception $e) {
        error_log("Erro no envio de e-mail: {$e->getMessage()}");
    }
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// ✅ 1. Novas reservas
$res = $db->query("SELECT r.*, u.first_name, u.email FROM referrals r JOIN users u ON r.user_id = u.id WHERE r.email_notified = 'Pending'");
while ($row = $res->fetch_assoc()) {
    $template = emailTemplate('new_referral', $row);
    sendEmail($row['email'], $row['first_name'], $template['subject'], $template['body']);
    $db->query("UPDATE referrals SET email_notified = 'created' WHERE id = " . $row['id']);
}

// ✅ 2. Reservas concluídas
$res = $db->query("SELECT r.*, u.first_name, u.email FROM referrals r JOIN users u ON r.user_id = u.id WHERE r.status = 'Successes' AND r.email_notified != 'success'");
while ($row = $res->fetch_assoc()) {
    $template = emailTemplate('referral_success', ['first_name' => $row['first_name'], 'id_reserva' => $row['id']]);
    sendEmail($row['email'], $row['first_name'], $template['subject'], $template['body']);
    $db->query("UPDATE referrals SET email_notified = 'success' WHERE id = " . $row['id']);
}

// ✅ 3. Reservas fracassadas
$res = $db->query("SELECT r.*, u.first_name, u.email FROM referrals r JOIN users u ON r.user_id = u.id WHERE r.status = 'Unsuccessful' AND r.email_notified != 'fail'");
while ($row = $res->fetch_assoc()) {
    $template = emailTemplate('referral_fail', ['first_name' => $row['first_name'], 'id_reserva' => $row['id']]);
    sendEmail($row['email'], $row['first_name'], $template['subject'], $template['body']);
    $db->query("UPDATE referrals SET email_notified = 'fail' WHERE id = " . $row['id']);
}

// ✅ 4. Nível Tanzanite
$res = $db->query("SELECT * FROM users WHERE referral_club_level_name = 'Blue Tanzanite' AND level_notified != 'tanzanite'");
while ($row = $res->fetch_assoc()) {
    $template = emailTemplate('level_tanzanite', $row);
    sendEmail($row['email'], $row['first_name'], $template['subject'], $template['body']);
    $db->query("UPDATE users SET level_notified = 'tanzanite' WHERE id = " . $row['id']);
}

// ✅ 5. Nível Sapphire
$res = $db->query("SELECT * FROM users WHERE referral_club_level_name = 'Blue Sapphire' AND level_notified != 'sapphire'");
while ($row = $res->fetch_assoc()) {
    $template = emailTemplate('level_sapphire', $row);
    sendEmail($row['email'], $row['first_name'], $template['subject'], $template['body']);
    $db->query("UPDATE users SET level_notified = 'sapphire' WHERE id = " . $row['id']);
}

// ✅ 6. Pagamentos recebidos
$res = $db->query("
    SELECT r.*, u.first_name, u.email 
    FROM referrals r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.status = 'Paid' 
      AND r.email_notified != 'paid'
");
while ($row = $res->fetch_assoc()) {
    // monta e envia o e-mail de confirmação de pagamento
    $template = emailTemplate(
        'payment_received',
        ['first_name' => $row['first_name'], 'id_reserva' => $row['id']]
    );
    sendEmail(
        $row['email'],
        $row['first_name'],
        $template['subject'],
        $template['body']
    );
    // marca como enviado
    $db->query("UPDATE referrals SET email_notified = 'paid' WHERE id = " . $row['id']);
}
