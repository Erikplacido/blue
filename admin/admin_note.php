<?php
session_start();
date_default_timezone_set('Australia/Melbourne'); // 🇦🇺 Fuso horário de Melbourne

require_once('../bluereferralclub/conexao.php');

// 🔧 Forçar o fuso horário na sessão do MySQL:
$pdo->exec("SET time_zone = '+10:00'"); // ou 'Australia/Melbourne' se suportado

// Proteção de acesso
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$currentUserRole = $_SESSION['role'];
$currentUserName = $_SESSION['name'];

// Valida ID de forma segura
$referralId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$referralId) {
  die('ID inválido');
}

// Buscar dados do indicado
$stmt = $pdo->prepare("SELECT referred, referred_last_name FROM referrals WHERE id = ?");
$stmt->execute([$referralId]);
$referral = $stmt->fetch();

// Buscar notas existentes
$stmt = $pdo->prepare("SELECT * FROM referral_notes WHERE referral_id = ? ORDER BY created_at DESC");
$stmt->execute([$referralId]);
$notes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Note - <?= htmlspecialchars($referral['referred'] ?? 'Referral') ?></title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <style>
    .note-box { padding: 12px; border-left: 5px solid #ccc; margin-bottom: 20px; background: #f9f9f9; border-radius: 4px; }
    .note-box.admin { border-color: #0d6efd; background: #e7f1ff; }
    .note-box.cleaner { border-color: #198754; background: #e6f4ea; }
    .note-box.consumer { border-color: #ffc107; background: #fff8e1; }
    .note-meta { font-size: 0.9em; color: #555; margin-bottom: 5px; font-style: italic; }
  </style>
</head>
<body class="container mt-4">

  <h3>📝 Admin Notes for <?= htmlspecialchars($referral['referred'] . ' ' . $referral['referred_last_name']) ?></h3>
  <a href="reservations.php" class="btn btn-secondary mb-3">← Back</a>

  <?php if (!empty($_SESSION['note_success'])): ?>
    <div class="alert alert-success">✅ Note added successfully!</div>
    <?php unset($_SESSION['note_success']); ?>
  <?php endif; ?>

  <form method="POST" action="admin_note_save.php">
    <input type="hidden" name="referral_id" value="<?= htmlspecialchars($referralId) ?>">
    <textarea name="note" class="form-control" rows="4" required></textarea>
    <button type="submit" class="btn btn-primary mt-2">Add Note</button>
  </form>

  <?php foreach ($notes as $note): ?>
    <div class="note-box <?= htmlspecialchars($note['role']) ?>">
      <div class="note-meta">
        <?php
          $createdAt = new DateTime($note['created_at'], new DateTimeZone('UTC'));
          $createdAt->setTimezone(new DateTimeZone('Australia/Melbourne'));
        ?>
        <?= ucfirst(htmlspecialchars($note['role'])) ?>: <?= htmlspecialchars($note['created_by']) ?> — <?= $createdAt->format('d/m/Y H:i') ?>
      </div>
      <div><?= nl2br(htmlspecialchars($note['note'])) ?></div>
    </div>
  <?php endforeach; ?>

  <?php if (empty($notes)): ?>
    <p class="text-muted">No notes yet.</p>
  <?php endif; ?>

</body>
</html>
