<?php
session_start();
require_once('../bluereferralclub/conexao.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fixeds = $_POST['commission_fixed'] ?? [];
    $percentages = $_POST['commission_percentage'] ?? [];

    foreach ($fixeds as $id => $fixed) {
        $percentage = $percentages[$id] ?? 0;
        $stmt = $pdo->prepare("UPDATE referral_club_data SET commission_fixed = :fixed, commission_percentage = :percentage WHERE id = :id");
        $stmt->execute([
            'fixed' => $fixed,
            'percentage' => $percentage,
            'id' => $id
        ]);
    }

    header("Location: index.php?msg=commission_updated");
    exit;
}
