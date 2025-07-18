<?php
session_start();
require_once __DIR__ . '/../conexao.php';

$msg = "";
$success = false;

if (!isset($_SESSION['user_id'])) {
    exit('Not logged in');
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current_password'])) {
    $current = trim($_POST['current_password']);
    $new = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($db_password);
        $stmt->fetch();    // Primeiro faz o fetch!
        $stmt->close();    // Depois fecha!

        if (empty($db_password)) {
            $msg = "User not found.";
        } elseif (!password_verify($current, $db_password)) {
            $msg = "Current password is incorrect.";
        } elseif ($new !== $confirm) {
            $msg = "New passwords do not match.";
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update) {
                $update->bind_param("si", $hash, $user_id);
                if ($update->execute()) {
                    $msg = "✅ Password updated successfully.";
                    $success = true;
                } else {
                    $msg = "❌ Error updating password: " . $update->error;
                }
                $update->close();
            } else {
                $msg = "❌ Error preparing update: " . $conn->error;
            }
        }
    } else {
        $msg = "❌ Error retrieving user: " . $conn->error;
    }
}
?>

<style>
  .input-icon-wrapper {
    position: relative;
  }
  .input-icon-wrapper input[type="password"],
  .input-icon-wrapper input[type="text"] {
    padding-right: 40px;
  }
  .toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
  }
  .toggle-password img {
    width: 20px;
    opacity: 0.6;
  }
</style>

<div class="modal-body">
  <h2 class="modal-title">Update Your Password</h2>

  <?php if (!empty($msg)): ?>
    <p style="color: <?= $success ? '#4CAF50' : '#FFC107' ?>; font-weight: bold; margin-bottom: 15px;">
      <?= htmlspecialchars($msg) ?>
    </p>
  <?php endif; ?>

  <form method="POST" action="header_component/password.php">
    <div class="form-group">
      <label>Current Password</label>
      <div class="input-icon-wrapper">
        <input type="password" name="current_password" id="current_password" required>
        <span class="toggle-password" onclick="togglePassword(this, 'current_password')">
          <img src="/bluereferralclub/assest/img/eye.svg" alt="Show/Hide Password">
        </span>
      </div>
    </div>
    <br>
    <div class="form-group">
      <label>New Password</label>
      <div class="input-icon-wrapper">
        <input type="password" name="new_password" id="new_password" required>
        <span class="toggle-password" onclick="togglePassword(this, 'new_password')">
          <img src="/bluereferralclub/assest/img/eye.svg" alt="Show/Hide Password">
        </span>
      </div>
    </div>
    <br>
    <div class="form-group">
      <label>Confirm Password</label>
      <div class="input-icon-wrapper">
        <input type="password" name="confirm_password" id="confirm_password" required>
        <span class="toggle-password" onclick="togglePassword(this, 'confirm_password')">
          <img src="/bluereferralclub/assest/img/eye.svg" alt="Show/Hide Password">
        </span>
      </div>
    </div>
    <br>
    <div class="form-actions">
      <button type="submit" class="btn-submit">Update Password</button>
      <button 
        type="button" 
        class="forgot_password" 
        id="forgotPwdBtn"
        onclick="window.open('https://bluefacilityservices.com.au/reset-password.php','_blank')"
      >
        Forgot Password?
      </button>
    </div>
  </form>
</div>















<script>
  document.getElementById('forgotPwdBtn')
    .addEventListener('click', () => {
      // usa o login.php com query param pra abrir direto o form de reset
      window.location.href = '/../login.php?forgot=1';
    });
</script>


<script>
function togglePassword(el, inputId) {
  const input = document.getElementById(inputId);
  const icon = el.querySelector("img");
  if (input.type === "password") {
    input.type = "text";
    icon.src = "/bluereferralclub/assest/img/eye-off.svg";
  } else {
    input.type = "password";
    icon.src = "/bluereferralclub/assest/img/eye.svg";
  }
}
</script>