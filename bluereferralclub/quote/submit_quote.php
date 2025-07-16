<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

header('Content-Type: application/json');
require_once __DIR__ . '/../conexao.php';

// 1. Verifica conexão
if ($conn->connect_error) {
    error_log("DB Connection Error: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    exit;
}

// 2. Captura o payload (JSON ou POST)
$raw = file_get_contents('php://input');
error_log('Raw JSON recebido: ' . $raw);
$payload = json_decode($raw, true) ?? $_POST;
error_log("Received Payload: " . print_r($payload, true));

// 3. Validação dos campos obrigatórios
$requiredFields = [
    'referred' => 'referred',
    'referred_last_name' => 'referred_last_name',
    'email' => 'Email',
    'mobile' => 'Mobile',
    'postcode' => 'Postcode',
    'client_type' => 'Client Type',
    'service_name' => 'Service'
];

$errors = [];
foreach ($requiredFields as $field => $label) {
    if (empty(trim($payload[$field] ?? ''))) {
        $errors[] = "Missing field: $label";
    }
}
if (!filter_var($payload['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}
$postcode = preg_replace('/\D/', '', $payload['postcode'] ?? '');
if (!$postcode) {
    $errors[] = "Invalid postcode";
}
if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['error' => implode(" | ", $errors)]);
    exit;
}

// 4. Prepara os dados
$referral_code = $payload['referral_code'] ?? '';
$referred = trim($payload['referred']);
$referred_last_name = trim($payload['referred_last_name']);
$email = trim($payload['email']);
$mobile = trim($payload['mobile']);
$client_type = trim($payload['client_type']);
$service_name = trim($payload['service_name']);
$more_details = trim($payload['more_details'] ?? '');

// 🆕 Novos campos
$address   = trim($payload['address'] ?? '');
$number    = trim($payload['number'] ?? '');
$suburb    = trim($payload['suburb'] ?? '');
$city      = trim($payload['city'] ?? '');
$territory = trim($payload['territory'] ?? '');

// 4.1 Sanitização
$mobile = preg_replace('/[^\d+]/', '', $mobile);
$referred = htmlspecialchars($referred, ENT_QUOTES);
$referred_last_name = htmlspecialchars($referred_last_name, ENT_QUOTES);

// 4.2 Verifica duplicidade nos últimos 2 minutos
try {
    $checkStmt = $conn->prepare("
        SELECT id FROM quote_admin 
        WHERE email = ? AND service_name = ? AND created_at >= NOW() - INTERVAL 2 MINUTE
    ");
    $checkStmt->bind_param("ss", $email, $service_name);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        error_log("Duplicate submission blocked for $email - $service_name");
        http_response_code(429);
        echo json_encode(['error' => 'Duplicate submission detected. Please wait a bit and try again.']);
        $checkStmt->close();
        $conn->close();
        exit;
    }

    $checkStmt->close();
} catch (Exception $e) {
    error_log("Duplication check failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal error']);
    exit;
}

// 5. Insere na tabela quote_admin
try {
    $stmt = $conn->prepare("
        INSERT INTO quote_admin (
            referral_code, referred, referred_last_name, email, mobile, postcode,
            client_type, service_name, more_details,
            address, number, suburb, city, territory,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    $stmt->bind_param(
        "ssssssssssssss",
        $referral_code,
        $referred,
        $referred_last_name,
        $email,
        $mobile,
        $postcode,
        $client_type,
        $service_name,
        $more_details,
        $address,
        $number,
        $suburb,
        $city,
        $territory
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process request']);
    exit;
}

// 6. E-mail para admin e usuário
$sanitizedEmail = filter_var($email, FILTER_SANITIZE_EMAIL);

$companySubject = "New Quote Request: $service_name";
$companyMessage = "
Name: $referred $referred_last_name
Service: $service_name
Client Type: $client_type
Email: $sanitizedEmail
Mobile: $mobile
Postcode: $postcode
Address: $address, $number - $suburb, $city - $territory
Details: " . ($more_details ?: 'None') . "
Referral Code: " . ($referral_code ?: 'None');

@mail(
    'office@bluefacilityservices.com.au',
    $companySubject,
    $companyMessage,
    "From: no-reply@bluefacilityservices.com.au\r\nReply-To: $sanitizedEmail"
);

// 6.1. Mensagem de usuário em HTML
$userMessage = <<<HTML
<html>
  <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td align="center">
          <table width="600" cellpadding="20" cellspacing="0" border="0" style="border:1px solid #e0e0e0; border-radius:8px;">
            <tr>
              <td align="center" style="padding-bottom:20px;">
                <img src="https://bluefacilityservices.com.au/bluereferralclub/assest/images/logo_blue.png" alt="Blue Facility Services Logo" style="max-width:200px; height:auto; display:block; margin:0 auto;">
              </td>
            </tr>
            <tr>
              <td align="center" style="text-align:center;">
                <h2 style="color:#004aad;">Thank you for your request, {$referred}!</h2>
                <p>We have received your quote request and our team will get back to you shortly.</p>
                <p style="margin-top:30px;">Regards,<br><strong>Blue Facility Services</strong></p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
HTML;

// 6.2. Enviar e-mail HTML para o usuário
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: no-reply@bluefacilityservices.com.au\r\n";
$headers .= "Reply-To: no-reply@bluefacilityservices.com.au\r\n";

@mail(
    $sanitizedEmail,
    "Thank you for your request",
    $userMessage,
    $headers
);

// 7. Sucesso 🎉
echo json_encode(['success' => true]);
