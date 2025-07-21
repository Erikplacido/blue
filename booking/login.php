<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Carrega variáveis de ambiente e autoload/configurações
$env    = require __DIR__ . '/../src/.env.php';
require __DIR__ . '/../src/config.php';

use Src\Models\Customer;

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitização e validação
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email inválido.';
    }
    if (empty($password)) {
        $errors['password'] = 'Senha obrigatória.';
    }

    // Autenticação
    if (empty($errors)) {
        $customerModel = new Customer();
        $customer      = $customerModel->getByEmail($email);

        $hash = $customer['password_hash'] ?? $customer['password'] ?? '';
        if ($customer && $hash && password_verify($password, $hash)) {
            $_SESSION['customer_id'] = $customer['id'];
            header('Location: profile.php');
            exit;
        } else {
            $errors['general'] = 'Email ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <!-- Fonte Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;800&display=swap" rel="stylesheet">
  <!-- CSS do template -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <section class="hero">
    <div class="hero__left">
      <div class="hero__card">
        <span class="brand-tag">Nu Apresenta</span>
        <h1>Login</h1>

        <?php if (! empty($errors['general'])): ?>
          <p class="error-text mb-4"><?= htmlspecialchars($errors['general']) ?></p>
        <?php endif; ?>

        <form method="POST" novalidate class="info-form">
          <h2>Entre com sua conta</h2>

          <input
            type="email"
            name="email"
            placeholder="E-mail"
            value="<?= htmlspecialchars($email) ?>"
            class="input-field <?= isset($errors['email']) ? 'input-error' : '' ?>"
            required
          >
          <small class="error-text"><?= $errors['email'] ?? '' ?></small>

          <input
            type="password"
            name="password"
            placeholder="Senha"
            class="input-field <?= isset($errors['password']) ? 'input-error' : '' ?>"
            required
          >
          <small class="error-text"><?= $errors['password'] ?? '' ?></small>

          <button type="submit" class="btn btn--full">Entrar</button>
        </form>

        <p class="text-center mt-4">
          Não tem conta? <a href="register.php" class="text-primary">Cadastre-se</a>
        </p>
      </div>
    </div>

    <div class="hero__right">
      <img
        src="assets/uploads/home_cleaning_banner.webp"
        alt="Ilustração de login"
        class="hero-image"
      >
    </div>
  </section>
</body>
</html>