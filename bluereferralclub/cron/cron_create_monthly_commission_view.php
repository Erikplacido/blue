<?php
// cron_create_monthly_commission_views_by_user.php

ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../conexao.php';

function sanitize_username($name) {
    $name = strtolower($name);
    $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name); // Remove acentos
    $name = preg_replace('/[^a-z0-9]/', '_', $name); // Apenas letras e números
    $name = preg_replace('/_+/', '_', $name); // Múltiplos _ viram um só
    return trim($name, '_');
}

// 📅 Parâmetros
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? str_pad((int)$_GET['month'], 2, '0', STR_PAD_LEFT) : date('m');
$monthYear = "$year-$month";

// 🔍 Buscar usuários com dados no mês
$sqlUsers = "
  SELECT u.id, u.first_name, u.last_name
  FROM users u
  JOIN referrals r ON r.user_id = u.id
  WHERE r.referral_code IS NOT NULL 
    AND DATE_FORMAT(r.created_at, '%Y-%m') = '$monthYear'
  GROUP BY u.id
";

$result = $conn->query($sqlUsers);
if (!$result) {
    die("Erro ao buscar usuários: " . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    $userId = (int)$row['id'];
    $first = sanitize_username($row['first_name']);
    $last  = sanitize_username($row['last_name']);
    $username = $first . '_' . $last;

    $viewName = "user_commission_summary_{$username}_" . str_replace('-', '_', $monthYear);

    $sqlView = "
    CREATE OR REPLACE VIEW `$viewName` AS
    SELECT 
        u.id AS user_id,
        u.first_name,
        u.last_name,
        DATE_FORMAT(r.created_at, '%Y-%m') AS month_year,
        SUM(
            CASE 
                WHEN r.commission_type = 'fixed' THEN r.commission_fixed
                WHEN r.commission_type = 'percentage' THEN r.commission_amount
                ELSE 0
            END
        ) AS total_commission
    FROM users u
    LEFT JOIN referrals r ON r.user_id = u.id
    WHERE 
        u.id = $userId
        AND r.referral_code IS NOT NULL
        AND DATE_FORMAT(r.created_at, '%Y-%m') = '$monthYear'
    GROUP BY u.id
    ";

    if ($conn->query($sqlView)) {
        echo "[" . date("Y-m-d H:i:s") . "] ✅ View `$viewName` criada com sucesso.<br>";
    } else {
        echo "[" . date("Y-m-d H:i:s") . "] ❌ Erro ao criar view `$viewName`: " . $conn->error . "<br>";
    }
}
?>