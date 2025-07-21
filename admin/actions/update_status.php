<?php
// actions/update_status.php

require_once('../../bluereferralclub/conexao.php');
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'], $data['status'])) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

$id = filter_var($data['id'], FILTER_VALIDATE_INT);
$status = trim($data['status']);

$validStatuses = ['Pending', 'Successes', 'Unsuccessful', 'Negotiating', 'Paid'];
if (!$id || !in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE referrals SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro no banco: ' . $e->getMessage()]);
}
