<?php
/* ----------------------------------------------
   reset_password.php
   ---------------------------------------------- */

// 0) DEBUG: exibe erros em tela (remova em produção)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1) Sessão segura
session_set_cookie_params([
    'lifetime'=>0,'path'=>'/','domain'=>$_SERVER['HTTP_HOST'],
    'secure'=>true,'httponly'=>true,'samesite'=>'Lax'
]);
session_start();

// 2) Conexão
require_once __DIR__.'/conexao.php';

// 3) Captura token
$token = trim($_REQUEST['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token           = trim($_POST['token'] ?? '');
    $newPassword     = $_POST['newPassword']     ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Validações
    if ($token === '') die('Missing token.');
    if ($newPassword === '' || $confirmPassword === '') die('Both password fields are required.');
    if ($newPassword !== $confirmPassword)    die('Passwords do not match.');

    try {
        // Busca token válido (última 1h)
        $stmt = $pdo->prepare("
          SELECT user_id
            FROM password_resets
           WHERE token = ?
             AND created_at >= NOW() - INTERVAL 1 HOUR
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new Exception('Invalid or expired token.');

        // Atualiza coluna `password` em vez de `password_hash`
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
          UPDATE users
             SET password = ?
           WHERE id = ?
        ");
        $stmt->execute([$hash, $row['user_id']]);

        // Remove o reset usado
        $stmt = $pdo->prepare("
          DELETE FROM password_resets
            WHERE token = ?
        ");
        $stmt->execute([$token]);

        // Redireciona ao login
        header('Location: /login.php');
        exit;

    } catch (Throwable $e) {
        die('Error: '.$e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en-AU">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password – Blue Referral Club</title>
  <!-- Como este arquivo está em /bluereferralclub/, o CSS fica em css/ -->
  <link rel="stylesheet" href="css/login-style.css">
  <style>
    .input-icon-wrapper { position: relative; }
    .input-icon-wrapper input { padding-right: 40px; }
    .toggle-password {
      position: absolute; right: 10px; top: 50%; 
      transform: translateY(-50%); cursor: pointer;
    }
    .toggle-password img { width: 20px; opacity: 0.6; }
  </style>
  <!-- Reusa o mesmo JS de toggle e validação -->
  <script src="js/login.js" defer></script>
</head>
<body>

<div class="login-container">
  <div class="login-box">
    <h2>Reset Your Password</h2>
    <form method="POST" id="resetForm" onsubmit="return validatePasswords();">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

      <div class="form-group">
        <label for="newPassword">New Password</label>
        <div class="input-icon-wrapper">
          <input type="password" name="newPassword" id="newPassword" required autocomplete="new-password">
          <span class="toggle-password" onclick="togglePassword(this,'newPassword')">
            <img src="assest/img/eye.svg" alt="Show password">
          </span>
        </div>
      </div>

      <div class="form-group">
        <label for="confirmPassword">Confirm New Password</label>
        <div class="input-icon-wrapper">
          <input type="password" name="confirmPassword" id="confirmPassword" required autocomplete="new-password">
          <span class="toggle-password" onclick="togglePassword(this,'confirmPassword')">
            <img src="assest/img/eye.svg" alt="Show password">
          </span>
        </div>
      </div>

      <button type="submit" class="btn-gold">Save New Password</button>
    </form>
  </div>
</div>

<script>
  // Validação simples no cliente (já existe em login.js, mas repetimos aqui p/ autossuficiência)
  function validatePasswords() {
    const a = document.getElementById('newPassword').value;
    const b = document.getElementById('confirmPassword').value;
    if (a !== b) {
      alert('Passwords do not match.');
      return false;
    }
    return true;
  }
</script>
</body>
</html>