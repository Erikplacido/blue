<?php
require_once(__DIR__ . '/../bluereferralclub/conexao.php');

// Buscar todas as views que terminam com 'paid'
$sql = "SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_u979853733_BFS LIKE '%paid'";
$views = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);

$cards = [];

foreach ($views as $view) {
    $query = $pdo->prepare("SELECT id, user_name, total_commission FROM `$view` LIMIT 1");
    $query->execute();
    $row = $query->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $row['view_name'] = $view;
        $cards[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Comissões - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Comissões por Referência</h2>
    <div class="row">
        <?php foreach ($cards as $card): ?>
            <div class="col-md-4">
                <div class="card mb-3" data-view="<?= $card['view_name'] ?>">
                    <div class="card-body">
                        <h5 class="card-title">#<?= $card['id'] ?> - <?= $card['user_name'] ?></h5>
                        <p>Total: $<?= number_format($card['total_commission'], 2) ?></p>
                        <button class="btn btn-primary open-modal" data-view="<?= $card['view_name'] ?>">Detalhes</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="commissionModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalhes da Comissão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalContent">Carregando...</div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/commissions.js"></script>
</body>
</html>
