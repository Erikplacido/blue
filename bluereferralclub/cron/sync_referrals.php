<?php
// sync_referrals.php
// 1) CONFIGURAÇÃO
$dbA_conf = [
    'dsn'    => 'mysql:host=localhost;dbname=u979853733_bluefc_bd;charset=utf8mb4',
    'user'   => 'userA',   // usuário com SELECT em bluefc_bd
    'pass'   => 'passA',
];
$dbB_conf = [
    'dsn'    => 'mysql:host=localhost;dbname=u979853733_BFS;charset=utf8mb4',
    'user'   => 'userB',   // usuário com INSERT em BFS.referrals
    'pass'   => 'passB',
];

try {
    $dbA = new PDO($dbA_conf['dsn'], $dbA_conf['user'], $dbA_conf['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $dbB = new PDO($dbB_conf['dsn'], $dbB_conf['user'], $dbB_conf['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    error_log("Erro de conexão: " . $e->getMessage());
    exit(1);
}

// 2) BUSCA BOOKINGS PAGAS COM CUPOM E AINDA NÃO EM referrals
$sql = "
SELECT
  b.id            AS booking_id,
  b.coupon_code,
  b.address,
  b.postcode,
  b.total_price,
  c.first_name,
  c.last_name,
  c.email,
  c.phone
FROM `bookings` AS b
JOIN `customers` AS c
  ON c.id = b.customer_id
WHERE
  b.coupon_code IS NOT NULL
  AND b.status = 'paid'
  AND NOT EXISTS (
    SELECT 1
    FROM `referrals` AS r
    WHERE r.referral_code = b.coupon_code
  )
";
$stmt = $dbA->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) === 0) {
    // nada a fazer
    exit(0);
}

// 3) INSERE CADA UM EM referrals
$ins = $dbB->prepare("
INSERT INTO `referrals` (
  referral_code,
  referred,
  referred_last_name,
  email,
  mobile,
  address,
  postcode,
  booking_value,
  created_at
) VALUES (
  :code, :first, :last, :email, :mobile,
  :address, :postcode, :value, NOW()
)
");

foreach ($rows as $r) {
    try {
        $ins->execute([
            ':code'     => $r['coupon_code'],
            ':first'    => $r['first_name'],
            ':last'     => $r['last_name'],
            ':email'    => $r['email'],
            ':mobile'   => $r['phone'],
            ':address'  => $r['address'],
            ':postcode' => $r['postcode'],
            ':value'    => $r['total_price'],
        ]);
        echo "[" . date('Y-m-d H:i:s') . "] Inserido: {$r['coupon_code']}\n";
    } catch (PDOException $e) {
        // Se for duplicado, ignora; senão, loga o erro
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            error_log("Erro insert referral {$r['coupon_code']}: " . $e->getMessage());
        }
    }
}

