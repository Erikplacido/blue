<?php
require_once 'conexao.php';

try {
    $stmt = $conn->prepare("CALL update_all_user_referral_stats()");
    $stmt->execute();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}