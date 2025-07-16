<?php
// bookingx/profile.php
require __DIR__ . '/../src/config.php';
use Src\Models\Customer;

session_start();
$customerId = $_SESSION['customer_id'] ?? null;
if (!$customerId) {
    header('Location: login.php');
    exit;
}

$customerModel = new Customer();
$customer = $customerModel->getById($customerId);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Atualização de nome e email
    $first_name  = trim($_POST['first_name'] ?? '');
    $last_name   = trim($_POST['last_name'] ?? '');
    $email       = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $phone       = trim($_POST['phone'] ?? '');
    $abn_or_tfn  = trim($_POST['abn_or_tfn'] ?? '');

    if (!$first_name)  $errors['first_name']  = 'Preencha seu nome.';
    if (!$last_name)   $errors['last_name']   = 'Preencha seu sobrenome.';
    if (!$email)       $errors['email']       = 'Email inválido.';
    if (!$phone)       $errors['phone']       = 'Preencha seu telefone.';
    if (!$abn_or_tfn)  $errors['abn_or_tfn']  = 'Preencha o ABN ou TFN.';

    if (empty($errors)) {
        $updated = $customerModel->updateProfile(
            $customerId,
            $first_name,
            $last_name,
            $email,
            $phone,
            $abn_or_tfn
        );
        if ($updated) {
            $success = 'Perfil atualizado com sucesso.';
            // Recarrega dados
            $customer = $customerModel->getById($customerId);
        } else {
            $errors['general'] = 'Erro ao salvar alterações.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Meu Perfil</title>
  <style>
:root {
  --color-primary: #11284B;
  --color-bg: #f9f9f9;
  --color-surface: #ffffff;
  --color-border: #e5e7eb;
  --color-text: #1f2937;
  --color-text-muted: #6b7280;
  --color-error: #dc2626;
  --color-success: #16a34a;
  --space-xs: 0.5rem;
  --space-sm: 1rem;
  --space-md: 1.5rem;
  --space-lg: 2rem;
  --radius-md: 0.5rem;
  --shadow-md: 0 3px 6px rgba(0,0,0,0.1);
  --font-sans: 'Inter', system-ui, sans-serif;
}

body {
  font-family: var(--font-sans);
  background-color: var(--color-bg);
  color: var(--color-text);
  margin: 0;
  padding: 0;
}

.container {
  max-width: 600px;
  margin: 0 auto;
  padding: var(--space-lg) var(--space-sm);
}

.card {
  background: var(--color-surface);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  overflow: hidden;
}

.card-header {
  padding: var(--space-md);
  background: var(--color-primary);
  color: #fff;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-body {
  padding: var(--space-md);
}

.form-group {
  margin-bottom: var(--space-md);
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: var(--space-xs);
  font-weight: 500;
}

.input-field {
  padding: var(--space-sm);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 1rem;
}

.input-error {
  border-color: var(--color-error);
}

.error-text {
  margin-top: var(--space-xs);
  color: var(--color-error);
  font-size: 0.875rem;
}

.text-success {
  color: var(--color-success);
}

.btn {
  display: inline-block;
  padding: var(--space-sm) var(--space-lg);
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  font-size: 1rem;
}

.btn-primary {
  background: var(--color-primary);
  color: #fff;
}

.btn-secondary {
  background: var(--color-border);
  color: var(--color-text);
}

.btn-outline {
  background: transparent;
  border: 1px solid #fff;
  color: #fff;
}

hr {
  border: none;
  border-top: 1px solid var(--color-border);
  margin: var(--space-md) 0;
}

.space-y-4 > * + * {
  margin-top: var(--space-md);
}

.text-center {
  text-align: center;
}

.my-8 {
  margin: calc(var(--space-lg) * 2) 0;
}
</style>
</head>
<body class="bg-light">
  <div class="container max-w-lg my-8">
    <div class="card shadow">
      <div class="card-header flex justify-between items-center">
        <h2 class="text-xl font-semibold">Meu Perfil</h2>
        <a href="logout.php" class="btn btn-outline">Sair</a>
      </div>
      <div class="card-body">
        <?php if (!empty($success)): ?>
          <p class="text-success mb-4"><?= $success ?></p>
        <?php endif; ?>
        <?php if (!empty($errors['general'])): ?>
          <p class="error-text mb-4"><?= htmlspecialchars($errors['general']) ?></p>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
          <div class="form-group">
            <label for="first_name">Nome</label>
            <input id="first_name" name="first_name" type="text" class="input-field <?= isset($errors['first_name']) ? 'input-error' : '' ?>"
                   value="<?= htmlspecialchars($customer['first_name'] ?? '') ?>" required>
            <small class="error-text"><?= $errors['first_name'] ?? '' ?></small>
          </div>
          <div class="form-group">
            <label for="last_name">Sobrenome</label>
            <input id="last_name" name="last_name" type="text"
                   class="input-field <?= isset($errors['last_name']) ? 'input-error' : '' ?>"
                   value="<?= htmlspecialchars($customer['last_name'] ?? '') ?>" required>
            <small class="error-text"><?= $errors['last_name'] ?? '' ?></small>
          </div>
          <div class="form-group">
            <label for="phone">Telefone</label>
            <input id="phone" name="phone" type="text"
                   class="input-field <?= isset($errors['phone']) ? 'input-error' : '' ?>"
                   value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" required>
            <small class="error-text"><?= $errors['phone'] ?? '' ?></small>
          </div>
          <div class="form-group">
            <label for="abn_or_tfn">ABN/TFN</label>
            <input id="abn_or_tfn" name="abn_or_tfn" type="text"
                   class="input-field <?= isset($errors['abn_or_tfn']) ? 'input-error' : '' ?>"
                   value="<?= htmlspecialchars($customer['abn_or_tfn'] ?? '') ?>" required>
            <small class="error-text"><?= $errors['abn_or_tfn'] ?? '' ?></small>
          </div>
          <div class="form-group">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" class="input-field <?= isset($errors['email']) ? 'input-error' : '' ?>"
                   value="<?= htmlspecialchars($customer['email']) ?>" required>
            <small class="error-text"><?= $errors['email'] ?? '' ?></small>
          </div>
          <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </form>

        <hr class="my-6">

        <!-- Seção de alteração de senha -->
        <h3 class="text-lg font-semibold mb-4">Alterar Senha</h3>
        <form method="POST" action="change_password.php" class="space-y-4">
          <div class="form-group">
            <label for="current_password">Senha atual</label>
            <input id="current_password" name="current_password" type="password" class="input-field" required>
          </div>
          <div class="form-group">
            <label for="new_password">Nova senha</label>
            <input id="new_password" name="new_password" type="password" class="input-field" required>
          </div>
          <div class="form-group">
            <label for="confirm_new_password">Confirme a nova senha</label>
            <input id="confirm_new_password" name="confirm_new_password" type="password" class="input-field" required>
          </div>
          <button type="submit" class="btn btn-secondary">Mudar senha</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
