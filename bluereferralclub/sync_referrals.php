<?php
// sync_referrals.php
require_once 'conexao.php';

// Lista dos status a atualizar
$statuses = ['Successes', 'Unsuccessful', 'Pending', 'Negotiating'];

foreach ($statuses as $status) {
    $sql = "
        UPDATE users u
        SET $status = (
            SELECT COUNT(*)
            FROM referrals r
            WHERE r.referred_by = u.referral_code
              AND r.status = ?
        )
        WHERE u.referral_code IS NOT NULL AND u.referral_code != ''
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $stmt->close();
}

// Atualiza o total de indicações
$sqlTotal = "
    UPDATE users u
    SET TotalReferrals = (
        SELECT COUNT(*)
        FROM referrals r
        WHERE r.referred_by = u.referral_code
    )
    WHERE u.referral_code IS NOT NULL AND u.referral_code != ''
";
$conn->query($sqlTotal);

echo "✅ Métricas sincronizadas com sucesso!";
?>
