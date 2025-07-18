<?php
// validar_login.php

// 1) Sessão mais segura (mesmo setup do login.php)
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $_SERVER['HTTP_HOST'],
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// 2) Conexão com o banco (caminho absoluto via __DIR__)
require_once __DIR__ . '/bluereferralclub/conexao.php';

// 3) Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// 4) Validação CSRF
if (!hash_equals(
    $_SESSION['csrf_token'] ?? '',
    $_POST['csrf_token'] ?? ''
)) {
    $_SESSION['login_error'] = true;
    header('Location: login.php');
    exit;
}

// 5) Sanitização de e-mail e leitura de senha
$email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    $_SESSION['login_error'] = true;
    header('Location: login.php');
    exit;
}

try {
    // 6) Busca dados do usuário
    $stmt = $pdo->prepare(
      'SELECT id, password_hash 
       FROM users 
       WHERE email = ?'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 7) Verifica se existe e se a senha confere
    if (
      !$user ||
      !password_verify($password, $user['password_hash'])
    ) {
        $_SESSION['login_error'] = true;
        header('Location: login.php');
        exit;
    }

    // 8) Login ok: renova ID e armazena user_id
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];

    // 9) Redireciona para área restrita
    header('Location: dashboard.php');
    exit;

} catch (Exception $e) {
    // Em produção, registre em log em vez de exibir
    error_log('Login error: ' . $e->getMessage());
    $_SESSION['login_error'] = true;
    header('Location: login.php');
    exit;
}