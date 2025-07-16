<?php



// notify_quote_success.php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
header('Content-Type: application/json');

require_once __DIR__ . '/../conexao.php';

// 1. Recebe o ID da cotação (ou referral_code) e o novo status
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$quoteId      = $input['id']            ?? null;
$newStatus    = $input['status']        ?? '';
$emailCliente = $input['email']         ?? '';
$referred     = $input['referred']      ?? '';
$service_name = $input['service_name']  ?? '';

// 1.1. Obter valor da reserva: do payload ou do banco
$booking_value = $input['booking_value'] ?? '';
if (empty($booking_value)) {
    try {
        $valStmt = $conn->prepare("SELECT booking_value FROM referrals WHERE id = ?");
        $valStmt->bind_param("i", $quoteId);
        $valStmt->execute();
        $valStmt->bind_result($booking_value);
        $valStmt->fetch();
        $valStmt->close();
    } catch (Exception $e) {
        error_log("Error fetching booking_value: " . $e->getMessage());
        $booking_value = 0;
    }
}
// 1.2. Normalizar vírgula para ponto e transformar em float
$numericValue   = floatval(str_replace(',', '.', $booking_value));
// 1.3. Formatar com duas casas decimais
$formatted_value = number_format($numericValue, 2);

if (!$quoteId || strtolower($newStatus) !== 'success' || !filter_var($emailCliente, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['error' => 'Parâmetros inválidos.']);
    exit;
}

// 2. (Opcional) Atualizar status no banco, se quiser centralizar aqui
try {
    $upd = $conn->prepare("UPDATE quote_admin SET status = ? WHERE id = ?");
    $upd->bind_param("si", $newStatus, $quoteId);
    $upd->execute();
    $upd->close();
} catch (Exception $e) {
    error_log("Falha ao atualizar status: " . $e->getMessage());
    // seguir adiante para envio de e-mail mesmo se essa atualização falhar
}

// 3. Monta e-mail para o cliente
$userMessage = <<<HTML
<html>
  <body style="font-family: Arial, sans-serif; color: #333; text-align: center; line-height:1.6;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr><td align="center">
        <table width="600" cellpadding="20" cellspacing="0" border="0" style="border:1px solid #e0e0e0; border-radius:8px;">
          <tr>
            <td align="center" style="padding-bottom:20px;">
              <img src="https://bluefacilityservices.com.au/bluereferralclub/assest/images/logo_blue.png" alt="Blue Facility Services logo" style="max-width:200px; height:auto; display:block; margin:0 auto;">
            </td>
          </tr>
          <tr>
            <td align="center">
              <h2 style="color:#004aad;">Hello, {$referred}!</h2>
              <p>Your request for <strong>{$service_name}</strong> has been approved.</p>
              <p>Your service value is: <strong>AU$ {$formatted_value}</strong></p>
              <p>To proceed with payment and scheduling your service, please visit:</p>
              <p><a href="https://bluefacilityservices.com.au/booking/checkout_quote.php?quote_id={$quoteId}" style="color:#004aad; text-decoration:none;">Complete Payment</a></p>
              <p style="margin-top:30px;">If you have any questions, simply reply to this email.<br><strong>Blue Facility Services</strong></p>
            </td>
          </tr>
        </table>
      </td></tr>
    </table>
  </body>
</html>
HTML;

// 4. Cabeçalhos e envio
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: no-reply@bluefacilityservices.com.au\r\n";
$headers .= "Reply-To: no-reply@bluefacilityservices.com.au\r\n";

@mail(
    $emailCliente,
    "Your request has been approved! Here are the next steps.",
    $userMessage,
    $headers
);

echo json_encode(['success' => true]);