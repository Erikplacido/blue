<?php
session_start();
require_once('../bluereferralclub/conexao.php');

$referral_id = $_POST['referral_id'] ?? null;
$note = trim($_POST['note'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? 'admin';
$user_name = $_SESSION['name'] ?? 'Unknown';

if (!$referral_id || !$note) {
    die('Erro: Dados incompletos');
}

$stmt = $pdo->prepare("
    INSERT INTO referral_notes (referral_id, note, role, created_by, user_id) 
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$referral_id, $note, $user_role, $user_name, $user_id]);

header("Location: admin_note.php?id=" . $referral_id);
exit;