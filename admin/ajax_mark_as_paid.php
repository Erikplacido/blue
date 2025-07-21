<?php
// /admin/ajax_mark_as_paid.php
session_start();
header('Content-Type: application/json');

// 1) Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method Not Allowed']);
    exit;
}

// 2) Conexão
require_once __DIR__ . '/../bluereferralclub/conexao.php';

// 3) Decodifica JSON
$input = json_decode(file_get_contents('php://input'), true);
$view  = $input['view']  ?? '';

// 4) Validações básicas
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}
if (!$view || !preg_match('/^[a-zA-Z0-9_]+$/', $view)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid view name']);
    exit;
}

try {
    // 5) Busca o user_id dentro da view (ela sempre retorna UMA linha)
    $stmt = $pdo->prepare("SELECT user_id FROM `$view` LIMIT 1");
    $stmt->execute();
    $userId = (int)$stmt->fetchColumn();
    if ($userId <= 0) {
        throw new Exception("Usuário não encontrado na view.");
    }

    // 6) Atualiza a tabela referrals para esse usuário
    $upd = $pdo->prepare("
        UPDATE referrals
           SET status  = 'Paid'
         WHERE user_id = :uid
           AND status  = 'Successes'
    ");
    $upd->execute([':uid' => $userId]);

    echo json_encode(['success'=>true]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    exit;
}
