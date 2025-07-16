<?php
/* -------------------------------------------------
   referral/referral_process_user.php
   ------------------------------------------------- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';
require_once '../conexao.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

/* ----------- Função auxiliar para redirecionar com erro ----------- */
function backWithError(string $msg): void
{
    header('Location: give_referral.php?err_msg=' . urlencode($msg));
    exit;
}

/* ----------------- 1. Recolha e sanitização ---------------------- */
$referred           = trim($_POST['referred'] ?? '');
$referred_last_name = trim($_POST['referred_last_name'] ?? '');
$client_type        = trim($_POST['client_type'] ?? '');
$referred_by        = trim($_POST['referred_by'] ?? '');
$referral_code      = trim($_POST['referral_code'] ?? '');
$email              = trim($_POST['email'] ?? '');
$mobile             = trim($_POST['mobile'] ?? '');
$postcode           = trim($_POST['postcode'] ?? '');
$more_details       = trim($_POST['more_details'] ?? '');

$number             = trim($_POST['number'] ?? '');
$address            = trim($_POST['address'] ?? '');
$suburb             = trim($_POST['suburb'] ?? '');
$city               = trim($_POST['city'] ?? '');
$territory          = trim($_POST['territory'] ?? '');

/* Sanitização extra */
$referred           = htmlspecialchars($referred, ENT_QUOTES);
$referred_last_name = htmlspecialchars($referred_last_name, ENT_QUOTES);
$address            = htmlspecialchars($address, ENT_QUOTES);
$suburb             = htmlspecialchars($suburb, ENT_QUOTES);
$city               = htmlspecialchars($city, ENT_QUOTES);
$territory          = htmlspecialchars($territory, ENT_QUOTES);
$number             = htmlspecialchars($number, ENT_QUOTES);
$mobile             = preg_replace('/[^\d+]/', '', $mobile);   // mantêm só dígitos e '+'

/* --------------- 2. Validações de negócio ------------------------ */
/* Pelo menos um contacto */
if ($email === '' && $mobile === '') {
    backWithError('Please provide at least Email or Mobile.');
}
/* Formato de email (quando existe) */
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    backWithError('Invalid email address.');
}

/* Nome completo apenas para exposição no email */
$full_name = $referred . ($referred_last_name ? ' ' . $referred_last_name : '');

/* --------------- 3. Inserção na base de dados -------------------- */
$stmt = $conn->prepare("
    INSERT INTO referrals (
        referred, referred_last_name, referred_by, referral_code,
        email, mobile, postcode, client_type, more_details,
        number, address, suburb, city, territory, user_id, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
if (!$stmt) {
    die('❌ Query preparation failed: ' . $conn->error);
}

$stmt->bind_param(
    'ssssssssssssssi',
    $referred,
    $referred_last_name,
    $referred_by,
    $referral_code,
    $email,
    $mobile,
    $postcode,
    $client_type,
    $more_details,
    $number,
    $address,
    $suburb,
    $city,
    $territory,
    $user_id
);

if (!$stmt->execute()) {
    die('❌ DB error: ' . $stmt->error);
}

/* --------------- 4. Envio de email ------------------------------- */
$destinatario = (strpos($postcode, '3') === 0)
    ? 'mayza.mota@bluefacilityservices.com.au'
    : 'lucas.garcia@bluefacilityservices.com.au';

$assunto  = "📬 New referral – Postcode $postcode";
$mensagem = <<<EOT
Referral by: $referred_by
Referral code: $referral_code

Referred customer: $full_name
Email: $email
Mobile: $mobile
Postcode: $postcode
Address: $address, $number, $suburb, $city, $territory
Client type: $client_type
More details: {$more_details ?: 'None'}
EOT;

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'contact@bluefacilityservices.com.au';
    $mail->Password   = 'BlueM@rketing33';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom('contact@bluefacilityservices.com.au', 'Blue Referral Club');
    $mail->addAddress($destinatario);
    if ($email !== '') {
        $mail->addReplyTo($email, $full_name);
    }

    $mail->Subject = $assunto;
    $mail->Body    = $mensagem;
    $mail->CharSet = 'UTF-8';

    $mail->send();

    /* sucesso → devolve e fecha */
    header('Location: give_referral.php?success=1');
    exit;
} catch (Exception $e) {
    backWithError('Email could not be sent: ' . $mail->ErrorInfo);
}

/* --------------- 5. Fim -------------------- */
$stmt->close();
$conn->close();
?>
