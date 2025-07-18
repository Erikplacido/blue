<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
date_default_timezone_set('Australia/Melbourne'); // 🇦🇺 Fuso horário de Melbourne

require_once('../bluereferralclub/conexao.php');

// 🔧 Forçar o fuso horário na sessão do MySQL
$pdo->exec("SET time_zone = '+10:00'");

// Proteção de acesso (somente usuários logados)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Atualiza a mensagem, se enviada
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newMessage = $_POST['message'] ?? '';

    $stmt = $pdo->prepare("
        INSERT INTO settings (name, value)
        VALUES ('share_message', :val)
        ON DUPLICATE KEY UPDATE value = :val2
    ");
    $stmt->execute([
        'val' => $newMessage,
        'val2' => $newMessage
    ]);

    $successMessage = "Mensagem atualizada com sucesso!";
}


// Busca a mensagem atual
$stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'share_message' LIMIT 1");
$stmt->execute();
$currentMessage = $stmt->fetchColumn() ?: '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Editar Mensagem de Compartilhamento</title>
  <style>
    body { font-family: Arial; padding: 30px; background: #f9f9f9; }
    textarea { width: 100%; max-width: 600px; font-size: 16px; }
    .success { color: green; margin-bottom: 15px; }
    button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
  </style>
</head>
<body>

<h2>Editar mensagem de compartilhamento</h2>

<?php if (!empty($successMessage)): ?>
  <div class="success"><?= htmlspecialchars($successMessage) ?></div>
<?php endif; ?>

<form method="POST">
  <textarea name="message" rows="5"><?= htmlspecialchars($currentMessage) ?></textarea><br><br>
  <button type="submit">Salvar</button>
</form>

</body>
</html>
