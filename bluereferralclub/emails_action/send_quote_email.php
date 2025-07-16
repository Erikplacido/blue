<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once('../PHPMailer/src/Exception.php');
require_once('../PHPMailer/src/PHPMailer.php');
require_once('../PHPMailer/src/SMTP.php');
require_once('../conexao.php');

header('Content-Type: application/json');

// Secure status update function
function atualizarStatus(mysqli $conn, int $referral_id, string $novo_status): bool {
    $allowed_statuses = ['Pending', 'Successes', 'Unsuccessful', 'Negotiating', 'Paid'];

    if (!in_array($novo_status, $allowed_statuses)) {
        error_log("❌ Invalid status: $novo_status");
        echo json_encode(['success' => false, 'message' => 'Invalid status.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE referrals SET email_notified = ? WHERE id = ?");
    $stmt->bind_param("si", $novo_status, $referral_id);
    $stmt->execute();

    return $stmt->affected_rows > 0;
}

// Start log
error_log("⚙️ Email script started");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ Invalid request method");
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Collect data
$first_name  = $_POST['referred'] ?? '';
$last_name   = $_POST['referred_last_name'] ?? '';
$email       = $_POST['email'] ?? '';
$mobile      = $_POST['mobile'] ?? '';

error_log("📥 Received data: $first_name $last_name | $email | $mobile");

if (!$first_name || !$email || !$mobile) {
    error_log("❌ Required fields missing");
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Search for referral
$stmt = $conn->prepare("SELECT id, email_notified FROM referrals WHERE referred = ? AND referred_last_name = ? AND email = ? AND mobile = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("ssss", $first_name, $last_name, $email, $mobile);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("❌ No referral found");
    echo json_encode(['success' => false, 'message' => 'Referral not found in database.']);
    exit;
}

$row = $result->fetch_assoc();
$referral_id = $row['id'];
$email_notified = $row['email_notified'];

error_log("🔍 ID: $referral_id | email_notified: $email_notified");

// Check if email has already been sent
if ($email_notified !== 'Pending') {
    error_log("🔒 Email has already been sent or status already updated.");
    echo json_encode(['success' => false, 'message' => 'Email has already been sent or status already updated.']);
    exit;
}

// Define email content
$subject = "Booking Request Received - Blue Facility Services";
$body = "
    <p>Hi <strong>$first_name</strong>,</p>
    <p>We've received your booking request through Blue Facility Services.</p>
    <p><a href='https://bluefacilityservices.com.au/register'>Create an account</a> to track your referral status and access more features.</p>
";

$mail = new PHPMailer(true);

try {
    // SMTP configuration
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'contact@bluefacilityservices.com.au';
    $mail->Password = 'BlueM@rketing33';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom('contact@bluefacilityservices.com.au', 'Blue Referral Club');
    $mail->addAddress($email, "$first_name $last_name");
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->CharSet = 'UTF-8';

    $mail->send();
    error_log("✅ Email successfully sent to $email");

    // Define new status after email is sent
    $new_status = 'Negotiating';

    if (atualizarStatus($conn, $referral_id, $new_status)) {
        error_log("✅ UPDATE succeeded. Row updated. ID: $referral_id");
        echo json_encode(['success' => true, 'message' => 'Email sent and status updated.']);
    } else {
        error_log("⚠️ UPDATE executed, but no row was affected. ID: $referral_id");
        echo json_encode(['success' => true, 'message' => 'Email sent, but the status was already updated or no changes were made.']);
    }

} catch (Exception $e) {
    error_log("❌ Failed to send email: {$mail->ErrorInfo}");
    echo json_encode(['success' => false, 'message' => "Failed to send email: {$mail->ErrorInfo}"]);
}