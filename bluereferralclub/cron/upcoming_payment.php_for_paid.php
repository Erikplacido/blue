<?php
require_once __DIR__ . '/../conexao.php';

// Função para traduzir número do mês para nome
function nomeMes($mes) {
    $meses = [
        '01' => 'January', '02' => 'February', '03' => 'March',
        '04' => 'April',   '05' => 'May',      '06' => 'June',
        '07' => 'July',    '08' => 'August',   '09' => 'September',
        '10' => 'October', '11' => 'November', '12' => 'December'
    ];
    return $meses[$mes] ?? 'Unknown';
}

// Data atual para nome da view
$mesAtual = date('m');
$nomeMes = nomeMes($mesAtual);

// Busca todos os usuários com referral_code válido
$users = $conn->query("SELECT first_name, referral_code FROM users WHERE referral_code IS NOT NULL AND referral_code != ''");

while ($user = $users->fetch_assoc()) {
    $firstNameSanitized = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($user['first_name']));
    $referralCodeSanitized = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($user['referral_code']));
    $viewName = "view_{$firstNameSanitized}_{$referralCodeSanitized}_{$nomeMes}";

    // Remove a view se já existir
    $conn->query("DROP VIEW IF EXISTS `$viewName`");

    // Cria nova view com dados filtrados
    $createViewSQL = "
        CREATE VIEW `$viewName` AS
        SELECT 
            created_at,
            id,
            consumer_name,
            referred,
            referred_last_name,
            client_type,
            service_name,
            commission_amount
        FROM referrals
        WHERE referral_code = '{$conn->real_escape_string($user['referral_code'])}'
          AND status = 'Successes'
    ";

    if ($conn->query($createViewSQL)) {
        echo "✅ View `$viewName` criada com sucesso.<br>";
    } else {
        echo "❌ Erro ao criar view `$viewName`: " . $conn->error . "<br>";
    }
}

$conn->close();
?>