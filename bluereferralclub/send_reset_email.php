<?php
// bluereferralclub/send_reset_email.php

session_start();

// 1) Força saída JSON e desliga display_errors
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);   // NÃO mostrar erros na tela
ini_set('log_errors',    1);    // registrar no log de erros
error_reporting(E_ALL);

// 2) Inclui a conexão usando caminho absoluto
require_once __DIR__ . '/conexao.php';

// 3) Validação CSRF
if (!hash_equals(
        $_SESSION['csrf_token'] ?? '',
        $_POST['csrf_token']  ?? ''
    )) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSRF token.']);
    exit;
}

// 4) Só POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

// 5) Sanitiza e valida e-mail
$email = filter_input(INPUT_POST, 'resetEmail', FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Please provide a valid email address.']);
    exit;
}

// ** DEBUG MODE: quando true, devolve mensagem completa da exceção em JSON **
$debug = true;

try {
    // 6) Verifica se $pdo existe e é PDO
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Database connection ($pdo) not found.');
    }

    // 7) Busca usuário
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 8) Responde “success” sempre, para não vazar informação
    if (!$user) {
        echo json_encode([
            'success' => 'If that email is registered, you will receive a reset link shortly.'
        ]);
        exit;
    }

    // 9) Gera token e salva
    $token   = bin2hex(random_bytes(16));
    $expires = date('Y-m-d H:i:s', time() + 3600);
    $stmt = $pdo->prepare(
      'INSERT INTO password_resets (user_id, token, expires_at)
       VALUES (?, ?, ?)'
    );
    $stmt->execute([$user['id'], $token, $expires]);

    // 10) Envia o e-mail
    $resetLink = 'https://bluefacilityservices.com.au/bluereferralclub/reset_password.php?token='.$token;
    $subject   = 'Your Password Reset Link';
    $message   = "Hello,\n\nClick here to reset your password:\n\n{$resetLink}\n\nThis link expires in one hour.";
    $headers   = [
        'From'     => 'no-reply@bluefacilityservices.com.au',
        'Reply-To' => 'no-reply@bluefacilityservices.com.au',
        'X-Mailer' => 'PHP/'.phpversion()
    ];
    $hdrString = '';
    foreach ($headers as $k => $v) {
        $hdrString .= "$k: $v\r\n";
    }

    if (!mail($email, $subject, $message, $hdrString)) {
        throw new Exception('mail() returned false.');
    }

    echo json_encode(['success' => 'Reset link sent! Check your inbox.']);

} catch (Throwable $e) {
    // Grava a mensagem completa no log
    error_log('send_reset_email ERROR: '.$e->getMessage());

    http_response_code(500);
    if ($debug) {
        // Em dev, envia a mensagem de volta para inspecionar
        echo json_encode(['error' => 'Server error: '.$e->getMessage()]);
    } else {
        echo json_encode(['error' => 'Server error.']);
    }
    exit;
}