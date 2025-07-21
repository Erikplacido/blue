<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../conexao.php';

$user_id = $_SESSION['user_id'] ?? 1;

// Atualização dos dados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['mobile'] ?? '';

    $stmt = $conn->prepare("UPDATE users SET email = ?, mobile = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssi", $email, $mobile, $user_id);

        if ($stmt->execute()) {
            echo "<p style='color:green; font-weight:bold;'>✅ Profile updated successfully!</p>";
        } else {
            echo "<p style='color:red; font-weight:bold;'>❌ Failed to update profile: {$stmt->error}</p>";
        }

        $stmt->close();
    } else {
        die("❌ Error preparing the update statement: " . $conn->error);
    }

    exit; // ⚡ Importante: impede que o restante do HTML seja enviado após salvar via AJAX
}

// Consulta dados atuais
$stmt = $conn->prepare("SELECT first_name, last_name, email, mobile FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    die("❌ Erro ao preparar a query de leitura: " . $conn->error);
}
?>


<div class="modal-body">
  <h2 class="modal-title">Edit Your Profile</h2>

  <div class="modal-form">
    <form method="POST" action="header_component/profile.php" id="profileForm">
      <div class="form-group">
        <label>First Name</label>
        <input type="text" value="<?= htmlspecialchars($result['first_name'] ?? '') ?>" disabled>
      </div>
      <br>
      <div class="form-group">
        <label>Last Name</label>
        <input type="text" value="<?= htmlspecialchars($result['last_name'] ?? '') ?>" disabled>
      </div>
      <br>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($result['email'] ?? '') ?>" disabled>
      </div>
      <br>
      <div class="form-group">
        <label>Mobile</label>
        <input type="text" name="mobile" id="mobile" value="<?= htmlspecialchars($result['mobile'] ?? '') ?>" disabled>
      </div>
      <br>
      <button type="button" id="editBtn" class="btn-gold">Edit</button>
      <button type="submit" id="saveBtn" class="btn-gold" style="display:none;">Save</button>

      <div id="responseMessage" style="margin-top: 10px;"></div> <!-- 🔥 Aqui mostra mensagens -->
    </form>
  </div>
</div>

