<?php
session_start();
require_once('../bluereferralclub/conexao.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

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

    header("Location: index.php?msg=1");
    exit;
}
?>
