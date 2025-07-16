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

<link rel="stylesheet" href="/bluereferralclub/css/user_style_2.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js"></script>

<?php if (isset($_GET['success'])): ?>
  <p class="modal-success">Referral submitted successfully!</p>
<?php endif; ?>

<section class="bottom" style="max-width: 600px; margin: auto">
  <h2 class="modal-title">Give a Referral</h2>

  <form action="/bluereferralclub/referral/referral_process_user.php" method="POST" class="modal-form">

    <div class="form-group">
      <label for="referred_by">Referred by</label>
      <input type="text" id="referred_by" name="referred_by" value="<?= htmlspecialchars($name) ?>" readonly>
    </div>

    <div class="form-group">
      <label for="referral_code_display">Your referral code</label>
      <input type="text" id="referral_code_display" value="<?= htmlspecialchars($referral_code) ?>" readonly>
      <input type="hidden" id="referral_code" name="referral_code" value="<?= htmlspecialchars($referral_code) ?>">
    </div>

    <div class="form-group">
      <label for="referred">First Name</label>
      <input type="text" id="referred" name="referred" placeholder="Ex: John">
    </div>

    <div class="form-group">
      <label for="referred_last_name">Last Name</label>
      <input type="text" id="referred_last_name" name="referred_last_name" placeholder="Ex: Smith">
    </div>

    <div class="form-group">
      <label for="client_type">Client Type</label>
      <select id="client_type" name="client_type">
        <option value="">-- Select --</option>
        <option value="Home">Residential</option>
        <option value="Company">Commercial</option>
      </select>
    </div>

    <div class="form-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" placeholder="example@email.com">
    </div>

    <div class="form-group">
      <label for="mobile">Mobile</label>
      <input type="text" id="mobile" name="mobile" placeholder="+61 4XXXXXXXX">
    </div>

    <hr style="margin: 20px 0; border-color: #507dbc;">

    <div class="form-group">
      <label for="autocomplete-address-quote">Search Address</label>
      <input type="text" id="autocomplete-address-quote" placeholder="Start typing address..." autocomplete="off">
    </div>

    <!-- Hidden fields for auto-filled address -->
    <input type="hidden" name="number" id="number">
    <input type="hidden" name="address" id="address">
    <input type="hidden" name="postcode" id="postcode">
    <input type="hidden" name="suburb" id="suburb">
    <input type="hidden" name="city" id="city">
    <input type="hidden" name="territory" id="territory">

    <div class="form-group">
      <label for="more_details">More Details (Optional)</label>
      <input type="text" id="more_details" name="more_details" placeholder="E.g. Near the park, house with red gate">
    </div>
<br>
    <div class="form-actions">
      <button type="submit" class="btn-submit">Submit Referral</button>
    </div>
  </form>
</section>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="load.js"></script>