<?php
/* ----------------------------------------------
   forgot-password.php  
   ---------------------------------------------- */

// 1) Sessão mais segura
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $_SERVER['HTTP_HOST'],
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// 2) Conexão com o banco
require_once 'bluereferralclub/conexao.php';

// 3) Mensagem flash (se houver)
$msg       = $_SESSION['forgot_msg']    ?? '';
$success   = $_SESSION['forgot_success'] ?? false;
unset($_SESSION['forgot_msg'], $_SESSION['forgot_success']);

// 4) Gerar/recuperar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en-AU">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password – Blue Referral Club</title>

  <!-- Mantém o mesmo CSS do login -->
  <link rel="stylesheet" href="bluereferralclub/css/login-style.css">

  <!-- Pequenos ajustes visuais -->
  <style>
    .input-icon-wrapper { position: relative; }
    .input-icon-wrapper input { padding-right: 40px; }
    .toggle-password {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
    }
    .toggle-password img { width: 20px; opacity: 0.6; }

    /* Especial para o formulário único */
    .login-box h2 { text-align: center; margin-bottom: 20px; }
  </style>
</head>
<body>

<div class="login-container">
  <div class="login-box">

    <h2>Forgot Your Password?</h2>

    <?php if ($msg): ?>
      <p class="message <?= $success ? 'success' : 'error' ?>">
        <?= htmlspecialchars($msg) ?>
      </p>
    <?php endif; ?>

    <!-- Apenas o formulário de reset -->
    <form id="forgotForm" method="POST" action="/bluereferralclub/forgot-password-process.php">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <div class="form-group">
        <label for="resetEmail">Enter your email to reset</label>
        <input type="email"
               id="resetEmail"
               name="resetEmail"
               required
               placeholder="you@example.com">
      </div>
      <button type="submit" class="btn-gold">Send Reset Link</button>
    </form>

    <p style="margin-top:15px; text-align:center;">
      <a href="login.php" style="color:#11284B; text-decoration:none;">
        ← Back to Login
      </a>
    </p>

  </div>
</div>

</body>
</html>
