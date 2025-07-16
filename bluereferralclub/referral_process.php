<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

// Conexão banco
require_once 'conexao.php';
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Recebe os dados do formulário
$referred      = trim($_POST['referred']      ?? '');
$referred_by   = trim($_POST['referred_by']   ?? '');
$referral_code = trim($_POST['referral_code'] ?? '');
$email         = trim($_POST['email']         ?? '');
$mobile        = trim($_POST['mobile']        ?? '');
$postcode      = trim($_POST['postcode']      ?? '');
$more_details  = trim($_POST['more_details']  ?? '');
$status        = 'Pending';

// Validação mínima
if (empty($referred) || empty($referral_code) || empty($email) || empty($mobile) || empty($postcode)) {
    http_response_code(400);
    exit('❌ Campos obrigatórios não preenchidos.');
}

// Prepara o INSERT
$stmt = $conn->prepare("
    INSERT INTO referrals (
        referred,
        referred_by,
        referral_code,
        email,
        mobile,
        postcode,
        more_details,
        status,
        user_id,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

if (!$stmt) {
    die("❌ Erro na preparação da query: " . $conn->error);
}

// Liga os parâmetros
$stmt->bind_param(
    "ssssssssi",
    $referred,
    $referred_by,
    $referral_code,
    $email,
    $mobile,
    $postcode,
    $more_details,
    $status,
    $user_id
);

// Executa
if ($stmt->execute()) {
    // ✉️ Após salvar, envia o e-mail

    // Determina destinatário baseado em alguma lógica de negócio
    // Exemplo: postcode começando com 3 = Melbourne
    $destinatario = (strpos($postcode, '3') === 0)
        ? "mayza.mota@bluefacilityservices.com.au"
        : "lucas.garcia@bluefacilityservices.com.au";

    $assunto = "📬 Nova indicação - Código Postal $postcode";
    $mensagem = "
Indicação feita por: $referred_by
Referral code: $referral_code

Nome do indicado: $referred
Email: $email
Mobile: $mobile
Código Postal: $postcode
Mais detalhes: $more_details
";

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'contact@bluefacilityservices.com.au';
        $mail->Password = 'BlueM@rketing33';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('contact@bluefacilityservices.com.au', 'Blue Referral Club');
        $mail->addAddress($destinatario);
        $mail->addReplyTo($email, $referred);

        $mail->Subject = $assunto;
        $mail->Body    = $mensagem;
        $mail->CharSet = 'UTF-8';

        $mail->send();

        header('Location: referral_form.php?success=1');
        exit;
    } catch (Exception $e) {
        echo "<script>alert('Erro ao enviar o e-mail: {$mail->ErrorInfo}'); window.location.href='referral_form.php';</script>";
        exit;
    }

} else {
    echo "❌ Erro ao salvar no banco de dados: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
