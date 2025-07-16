<?php
session_start();
require_once '../conexao.php';

if (!isset($_SESSION['user_id'])) {
    exit('Not logged in');
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, referral_code FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $referral_code);
$stmt->fetch();
$stmt->close();
?>

<h2>Give a Referral</h2>

<?php if (isset($_GET['success'])): ?>
  <p>Referral submitted successfully!</p>
<?php endif; ?>

<form action="referral/referral_process.php" method="POST">
  <div>
    <label>Referred by</label><br>
    <input type="text" name="referred_by" value="<?= htmlspecialchars($name) ?>" readonly>
  </div>

  <div>
    <label>Your referral code</label><br>
    <input type="text" value="<?= htmlspecialchars($referral_code) ?>" readonly>
    <input type="hidden" name="referral_code" value="<?= htmlspecialchars($referral_code) ?>">
  </div>

  <div>
    <label>Name</label><br>
    <input type="text" name="referred" required>
  </div>

  <div>
    <label>Last name</label><br>
    <input type="text" name="last_name">
  </div>

  <div>
    <label>Email</label><br>
    <input type="email" name="email" required>
  </div>

  <div>
    <label>Mobile</label><br>
    <input type="text" name="mobile" required>
  </div>

  <div>
    <label>Postcode</label><br>
    <input type="text" name="postcode" required>
  </div>

  <div>
    <label>More details (optional)</label><br>
    <input type="text" name="more_details">
  </div>

  <div>
    <button type="submit">Submit</button>
  </div>
</form>
