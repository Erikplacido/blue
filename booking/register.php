<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// autoload / config
require __DIR__ . '/../src/config.php';
use Src\Models\Customer;

$errors   = [];
$name     = '';
$emailRaw = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // capturar e sanitizar
    $name       = trim($_POST['name'] ?? '');
    $emailRaw   = trim($_POST['email'] ?? '');
    $email      = filter_var($emailRaw, FILTER_VALIDATE_EMAIL) ? $emailRaw : '';
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    // validações
    if (!$name)            $errors['name']             = 'Nome é obrigatório.';
    if (!$email)           $errors['email']            = 'Email inválido.';
    if (strlen($password) < 6)       $errors['password']         = 'Senha precisa ter ≥ 6 caracteres.';
    if ($password !== $confirm)      $errors['confirm_password'] = 'Senhas não conferem.';

    // tentativa de criação
    if (empty($errors)) {
        $customerModel = new Customer();
        $hash          = password_hash($password, PASSWORD_DEFAULT);
        $created       = $customerModel->create([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => $hash,
        ]);

        if ($created) {
            header('Location: login.php?registered=1');
            exit;
        } else {
            $errors['general'] = 'Erro ao criar conta. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cadastro</title>
  <!-- Fonte Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;800&display=swap" rel="stylesheet">
  <!-- CSS do template -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <section class="hero">
    <!-- Lado esquerdo: card de cadastro -->
    <div class="hero__left">
      <div class="hero__card">
        <span class="brand-tag">Nu Apresenta</span>
        <h1>Crie sua conta</h1>

        <!-- erro geral -->
        <?php if (! empty($errors['general'])): ?>
          <p class="error-text"><?= htmlspecialchars($errors['general']) ?></p>
        <?php endif; ?>

        <form method="POST" novalidate class="info-form">
          <h2>Preencha seus dados</h2>

          <!-- Nome -->
          <input
            type="text"
            name="name"
            placeholder="Nome completo"
            value="<?= htmlspecialchars($name) ?>"
            class="input-field <?= isset($errors['name']) ? 'input-error' : '' ?>"
            required
          >
          <small class="error-text"><?= $errors['name'] ?? '' ?></small>

          <!-- E-mail -->
          <input
            type="email"
            name="email"
            placeholder="E-mail"
            value="<?= htmlspecialchars($emailRaw) ?>"
            class="input-field <?= isset($errors['email']) ? 'input-error' : '' ?>"
            required
          >
          <small class="error-text"><?= $errors['email'] ?? '' ?></small>

          <!-- Senha -->
          <input
            type="password"
            name="password"
            placeholder="Senha"
            class="input-field <?= isset($errors['password']) ? 'input-error' : '' ?>"
            required
          >
          <small class="error-text"><?= $errors['password'] ?? '' ?></small>

          <!-- Confirmar senha -->
          <input
            type="password"
            name="confirm_password"
            placeholder="Confirme a senha"
            class="input-field <?= isset($errors['confirm_password']) ? 'input-error' : '' ?>"
            required
          >
          <small class="error-text"><?= $errors['confirm_password'] ?? '' ?></small>

          <!-- Botão -->
          <button type="submit" class="btn btn--full">Cadastrar</button>
        </form>

        <p class="text-center mt-4">
          Já tem conta? <a href="login.php" class="text-primary">Faça login</a>
        </p>
      </div>
    </div>

    <!-- Lado direito: imagem -->
    <div class="hero__right">
      <img
        src="assets/uploads/home_cleaning_banner.webp"
        alt="Ilustração de cadastro"
        class="hero-image"
      >
    </div>
  </section>
</body>
</html>