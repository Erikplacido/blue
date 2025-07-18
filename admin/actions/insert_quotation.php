<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../../bluereferralclub/conexao.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    try {
$stmt = $pdo->prepare("INSERT INTO quote_admin 
    (referred, referred_last_name, referral_code, email, mobile, postcode, address, number, suburb, city, territory, service_id, service_name, more_details, client_type) 
    VALUES 
    (:referred, :referred_last_name, :referral_code, :email, :mobile, :postcode, :address, :number, :suburb, :city, :territory, :service_id, :service_name, :more_details, :client_type)");

$stmt->execute([
    ':referred'           => $input['referred'],
    ':referred_last_name' => $input['referred_last_name'],
    ':referral_code'      => $input['referral_code'],
    ':email'              => $input['email'],
    ':mobile'             => $input['mobile'],
    ':postcode'           => $input['postcode'],
    ':address'            => $input['address'],
    ':number'             => $input['number'],
    ':suburb'             => $input['suburb'],
    ':city'               => $input['city'],
    ':territory'          => $input['territory'],
    ':service_id'         => $input['service_id'],
    ':service_name'       => $input['service_name'],
    ':more_details'       => $input['more_details'],
    ':client_type'        => $input['client_type']       // ✅ NOVO
]);


        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
}
?>
