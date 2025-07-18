<?php
/* ----------------------------------------------
   bluereferralclub/forgot-password-process.php  
   ---------------------------------------------- */

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $_SERVER['HTTP_HOST'],
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once __DIR__ . '/conexao.php';

// 1) Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// 2) CSRF
if (empty($_POST['csrf_token'])
  || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['forgot_msg']     = 'Invalid request.';
    $_SESSION['forgot_success'] = false;
    header('Location: ../reset-password.php');
    exit;
}

// 3) Valida e-mail
$email = trim($_POST['resetEmail'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['forgot_msg']     = 'Please enter a valid email address.';
    $_SESSION['forgot_success'] = false;
    header('Location: ../reset-password.php');
    exit;
}

// 4) Busca utilizador (e prepara mensagem genérica)
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

// Mensagem genérica para evitar enumeração de contas
$_SESSION['forgot_msg']     = 'If that email is registered, you’ll receive a reset link shortly.';
$_SESSION['forgot_success'] = true;

if ($stmt->num_rows === 1) {
    $stmt->bind_result($user_id);
    $stmt->fetch();

    // 5) Gera token e guarda
    $token   = bin2hex(random_bytes(16));
    $expires = date('Y-m-d H:i:s', time() + 3600);

    $ins = $conn->prepare(
      'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)'
    );
    $ins->bind_param('iss', $user_id, $token, $expires);
    $ins->execute();
    $ins->close();

    // 6) Envia e-mail com o link correto em /bluereferralclub/
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'contact@bluefacilityservices.com.au';
        $mail->Password   = 'BlueM@rketing33';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('contact@bluefacilityservices.com.au', 'Blue Referral Club');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset';

        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || $_SERVER['SERVER_PORT'] == 443
               ? 'https://' : 'http://';
        $host      = $_SERVER['HTTP_HOST'];
        $resetLink = "{$proto}{$host}"
                   . "/bluereferralclub/reset_password.php?token="
                   . urlencode($token);

        $mail->Body = "
          <p>You have requested a password reset.</p>
          <p>Click <a href=\"{$resetLink}\">here</a> to choose a new password.<br>
          This link expires in 1 hour.</p>
        ";
        $mail->send();
    } catch (Exception $e) {
        // opcional: error_log($mail->ErrorInfo);
    }
}

$stmt->close();

// 7) Redireciona de volta ao formulário
header('Location: ../reset-password.php');
exit;
