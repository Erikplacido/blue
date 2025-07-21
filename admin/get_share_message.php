<?php
session_start();
require_once('../bluereferralclub/conexao.php');

// Apenas verificação simples
if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">Unauthorized access</div>';
    exit;
}

date_default_timezone_set('Australia/Melbourne');
$pdo->exec("SET time_zone = '+10:00'");

// Processa mensagem apenas se via POST
$successMessage = '';
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

    $successMessage = "Message updated successfully!";
}

// Busca a mensagem atual
$stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'share_message' LIMIT 1");
$stmt->execute();
$currentMessage = $stmt->fetchColumn() ?: '';
?>

<form method="POST">
    <?php if (!empty($successMessage)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <textarea name="message" class="form-control" rows="5" required><?= htmlspecialchars($currentMessage) ?></textarea><br>
    <button type="submit" class="btn btn-success">Save</button>
</form>
