<?php
require __DIR__ . '/../src/config.php';

use Src\Models\Customer;

ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
$password   = trim($_POST['password'] ?? '');

if (!$customerId || $password === '') {
    http_response_code(400);
    echo 'Invalid data';
    exit;
}

$customerModel = new Customer();
$hash = password_hash($password, PASSWORD_DEFAULT);
$customerModel->updatePassword($customerId, $hash);

echo 'Registration completed successfully.';