<?php
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

// Recebe os dados do formulário
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

// Sanitização extra
$referred           = htmlspecialchars($referred, ENT_QUOTES);
$referred_last_name = htmlspecialchars($referred_last_name, ENT_QUOTES);
$mobile             = preg_replace('/[^\d+]/', '', $mobile);
$address            = htmlspecialchars($address, ENT_QUOTES);
$suburb             = htmlspecialchars($suburb, ENT_QUOTES);
$city               = htmlspecialchars($city, ENT_QUOTES);
$territory          = htmlspecialchars($territory, ENT_QUOTES);
$number             = htmlspecialchars($number, ENT_QUOTES);

// Nome completo apenas para exibição no e-mail
$full_name = $referred;
if (!empty($referred_last_name)) {
    $full_name .= ' ' . $referred_last_name;
}

// Validação de campos obrigatórios e ao menos Email ou Mobile
$errors = [];
if ($referred === '')             $errors[] = 'First name is required.';
if ($referred_last_name === '')   $errors[] = 'Last name is required.';
if ($client_type === '')          $errors[] = 'Client type is required.';
if ($email === '' && $mobile === '') {
    $errors[] = 'Please provide at least Email or Mobile.';
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format.';
}
if (!empty($errors)) {
    $msg = urlencode(implode(' ', $errors));
    header("Location: index_user.php?error={$msg}");
    exit;
}

// Prepara o INSERT
$stmt = $conn->prepare("
    INSERT INTO referrals (
        referred, referred_last_name, referred_by, referral_code,
        email, mobile, postcode, client_type, more_details,
        number, address, suburb, city, territory, user_id, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

if (!$stmt) {
    die("❌ Erro na preparação da query: " . $conn->error);
}

$stmt->bind_param(
    "ssssssssssssssi",
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

if ($stmt->execute()) {
    // Exibe confirmação antes de tentar o envio de e-mail
    echo "<p style='color:#F0D71A; font-weight:bold;'>Referral submitted successfully!</p>";

    // Define destinatário, assunto e corpo da mensagem
    $destinatario = (strpos($postcode, '3') === 0)
        ? "mayza.mota@bluefacilityservices.com.au"
        : "lucas.garcia@bluefacilityservices.com.au";

    $assunto = "📬 Nova indicação - Código Postal $postcode";
    $mensagem = "
Indicação feita por: $referred_by
Referral code: $referral_code

Nome do indicado: $full_name
Email: $email
Mobile: $mobile
Código Postal: $postcode
Endereço: $address, $number, $suburb, $city, $territory
Tipo de Cliente: $client_type
Mais detalhes: " . ($more_details ?: 'Nenhum') . "
";

    // Tenta enviar o e-mail sem interromper a experiência do usuário em caso de falha
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

        // Só adiciona Reply-To se houver e-mail válido
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($email, $full_name);
        }

        $mail->Subject = $assunto;
        $mail->Body    = $mensagem;
        $mail->CharSet = 'UTF-8';

        $mail->send();
        // opcional: error_log("E-mail enviado com sucesso para $destinatario");
    } catch (Exception $e) {
        // Registra falha sem quebrar a UX
        error_log("Falha ao enviar e-mail: " . $mail->ErrorInfo);
    }

    exit;
} else {
    echo "❌ Erro ao salvar no banco de dados: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
