<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../conexao.php');

$month = strtolower(date('F'));
$year  = (int) date('Y');

// 1) Buscar todos os usuários com referral_code
$users = $pdo
  ->query("SELECT id, name, referral_code FROM users WHERE referral_code IS NOT NULL AND referral_code != ''")
  ->fetchAll(PDO::FETCH_ASSOC);

$insertStmt = $pdo->prepare("
  INSERT IGNORE INTO payment_history (
    user_id, payment_id, consumer_name, referred, referred_last_name,
    description, amount, view_created_at, status, payment_reference,
    total_commission, user_name, email, referral_code, referral_club_level_name,
    bankName, agency, bsb, accountNumber, abnNumber,
    month_name, year_num, view_name
  ) VALUES (
    :user_id, :payment_id, :consumer_name, :referred, :referred_last_name,
    :description, :amount, :view_created_at, :status, :payment_reference,
    :total_commission, :user_name, :email, :referral_code, :referral_club_level_name,
    :bankName, :agency, :bsb, :accountNumber, :abnNumber,
    :month_name, :year_num, :view_name
  )
");

foreach ($users as $user) {
    $firstName = preg_replace('/[^a-zA-Z0-9]/', '', strtok($user['name'], ' '));
    $refCode   = preg_replace('/[^a-zA-Z0-9]/', '', $user['referral_code']);
    $baseName  = "{$firstName}{$refCode}{$month}_{$year}_email_ok";
    $viewName  = strtolower(substr($baseName, 0, 64));

    // 2) Verifica se a view existe
    $check = $pdo
      ->prepare("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_u979853733_BFS = ?");
    $check->execute([$viewName]);
    if (!$check->fetchColumn()) {
        continue;
    }

    // 3) Lê todos os registros da view
    $rows = $pdo
      ->query("SELECT * FROM `$viewName`")
      ->fetchAll(PDO::FETCH_ASSOC);

    // 4) Insere cada registro em payment_history
    foreach ($rows as $r) {
        $insertStmt->execute([
          ':user_id'                  => $user['id'],
          ':payment_id'               => $r['id'],
          ':consumer_name'            => $r['consumer_name'] ?? null,
          ':referred'                 => $r['referred'] ?? null,
          ':referred_last_name'       => $r['referred_last_name'] ?? null,
          ':description'              => $r['description'] ?? null,
          ':amount'                   => $r['amount'] ?? null,
          ':view_created_at'          => $r['created_at'] ?? null,
          ':status'                   => $r['status'] ?? null,
          ':payment_reference'        => $r['payment_reference'] ?? null,
          ':total_commission'         => $r['total_commission'] ?? null,
          ':user_name'                => $r['user_name'] ?? null,
          ':email'                    => $r['email'] ?? null,
          ':referral_code'            => $r['referral_code'] ?? null,
          ':referral_club_level_name' => $r['referral_club_level_name'] ?? null,
          ':bankName'                 => $r['bankName'] ?? null,
          ':agency'                   => $r['agency'] ?? null,
          ':bsb'                      => $r['bsb'] ?? null,
          ':accountNumber'            => $r['accountNumber'] ?? null,
          ':abnNumber'                => $r['abnNumber'] ?? null,
          ':month_name'               => $month,
          ':year_num'                 => $year,
          ':view_name'                => $viewName,
        ]);
    }
}

echo "✅ payment_history sincronizada em " . date('Y-m-d H:i:s') . "\n";
