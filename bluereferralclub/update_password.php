<?php
require_once 'conexao.php';
session_start();

// Habilitar exibição de erros para depuração (desative em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['currentPassword'], $_POST['newPassword'], $_POST['confirmPassword'])) {
    $user_id = $_SESSION['user_id'];
    $currentPassword = $_POST['currentPassword'];
    $newPassword     = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Verificar se a nova senha e a confirmação coincidem
    if ($newPassword !== $confirmPassword) {
        $_SESSION['error_message'] = "New passwords do not match.";
        header("Location: index.php");
        exit;
    }
    
    // Recupera a hash atual da senha do banco
    $query = "SELECT password FROM users WHERE id = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $hash = $row['password'];
            
            // Verifica se a senha atual informada confere com o hash armazenado
            if (password_verify($currentPassword, $hash)) {
                // Gera o hash da nova senha
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $updateQuery = "UPDATE users SET password = ? WHERE id = ?";
                if ($updateStmt = $conn->prepare($updateQuery)) {
                    $updateStmt->bind_param("si", $newHash, $user_id);
                    if ($updateStmt->execute()) {
                        $_SESSION['success_message'] = "Password changed successfully";
                        header("Location: index.php");
                        exit;
                    } else {
                        $_SESSION['error_message'] = "Failed to update password.";
                        header("Location: index.php");
                        exit;
                    }
                } else {
                    $_SESSION['error_message'] = "Error preparing update query.";
                    header("Location: index.php");
                    exit;
                }
            } else {
                $_SESSION['error_message'] = "Incorrect current password.";
                header("Location: index.php");
                exit;
            }
        } else {
            $_SESSION['error_message'] = "User not found.";
            header("Location: index.php");
            exit;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Error preparing select query.";
        header("Location: index.php");
        exit;
    }
} else {
    $_SESSION['error_message'] = "Insufficient data to update password.";
    header("Location: index.php");
    exit;
}
?>