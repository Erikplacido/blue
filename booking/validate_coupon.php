<?php
require __DIR__ . '/../src/config.php'; // ajusta conforme seu autoload

header('Content-Type: application/json');

// 1) Pega parâmetros
$code      = trim($_GET['code'] ?? '');
$baseTotal = isset($_GET['baseTotal'])
    ? floatval($_GET['baseTotal'])
    : null;

// 2) Valida parâmetros mínimos
if ($code === '') {
    echo json_encode(['valid' => false, 'message' => 'No code provided']);
    exit;
}
if ($baseTotal === null) {
    echo json_encode(['valid' => false, 'message' => 'No base total provided']);
    exit;
}

try {
    // 3) Busca o cupom no banco
    $db = \Src\Database\Connection::getInstance()->getPDO();
    $stmt = $db->prepare("
        SELECT *
          FROM discount_coupons
         WHERE code = :code
           AND is_active = 1
         LIMIT 1
    ");
    $stmt->execute([':code' => $code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        echo json_encode(['valid' => false, 'message' => 'Invalid or expired coupon']);
        exit;
    }

    // 4) Calcula o desconto em cima de baseTotal
    $discount = 0.0;
    if ($coupon['discount_type'] === 'percent') {
        $percent = floatval($coupon['amount']);
        $discount = $baseTotal * $percent / 100.0;
    } else { // assume 'fixed' ou qualquer outro tipo
        $discount = floatval($coupon['amount']);
    }

    // 5) Garante que não fique negativo
    $newTotal = max(0.0, $baseTotal - $discount);

    // 6) Retorna JSON
    echo json_encode([
        'valid'     => true,
        'discount'  => round($discount, 2),
        'new_total' => round($newTotal, 2),
    ]);
    exit;

} catch (Exception $e) {
    // opcional: logar $e->getMessage() em um arquivo de logs
    echo json_encode(['valid' => false, 'message' => 'Error validating coupon']);
    exit;
}
