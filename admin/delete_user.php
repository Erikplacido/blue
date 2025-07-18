<?php
session_start();
require_once('../bluereferralclub/conexao.php');

// Proteção de sessão
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Validação do ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: add_user.php?error=invalid_id");
    exit();
}

try {
    // Verificar se o usuário existe antes de deletar
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: add_user.php?error=user_not_found");
        exit();
    }

    // Deletar o usuário
    $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $del->execute([$id]);

    header("Location: add_user.php?deleted=1");
    exit();

} catch (PDOException $e) {
    error_log("Erro ao deletar usuário ID $id: " . $e->getMessage());
    header("Location: add_user.php?error=delete_failed");
    exit();
}
