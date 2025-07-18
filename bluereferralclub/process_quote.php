<?php
require 'conexao.php';

$first = $_POST['first_name'] ?? '';
$last = $_POST['last_name'] ?? '';
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$mobile = $_POST['mobile'] ?? '';
$postcode = $_POST['postcode'] ?? '';
$referral_code = $_POST['referral_code'] ?? '';
$service_id = $_POST['service_id'] ?? 0;
$service_name = $_POST['service_name'] ?? '';
$more_details = $_POST['more_details'] ?? '';

if (!$first || !$last || !$email || !$mobile || !$postcode || !$service_id || !$service_name) {
  die("Missing fields.");
}

$stmt = $conn->prepare("INSERT INTO referrals (referral_code, first_name, last_name, email, mobile, postcode, service_id, service_name, more_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssiss", $referral_code, $first, $last, $email, $mobile, $postcode, $service_id, $service_name, $more_details);
$stmt->execute();

$admin_email = (stripos($postcode, '3') === 0) ? 'email@melbourne.com' : 'email@sydney.com';
$subject = "New Quote Request from $first $last";

$message = "Service: $service_name\nName: $first $last\nEmail: $email\nMobile: $mobile\nPostcode: $postcode\nDetails: $more_details\nReferral: $referral_code";

mail($admin_email, $subject, $message);
mail($email, "Your quote request was received", "Thanks $first! Our team will contact you soon.");

echo "Success!";
?>
