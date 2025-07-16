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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js"></script>

<?php if (isset($_GET['success'])): ?>
  <p class="modal-success">Referral submitted successfully!</p>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
  <p class="modal-error"><?= htmlspecialchars(urldecode($_GET['error'])) ?></p>
<?php endif; ?>

<div class="modal-body">
  <h2 class="modal-title">Give a Referral</h2>

  <form action="/bluereferralclub/referral/referral_process_user.php"
        method="POST"
        class="modal-form">

    <!-- Linha 1: Referred by + Referral code -->
    <div class="form-row">
      <div class="form-group">
        <label for="referred_by">Referred by</label>
        <input type="text" id="referred_by" name="referred_by"
               value="<?= htmlspecialchars($name) ?>"
               readonly>
      </div>
      <div class="form-group">
        <label for="referral_code_display">Your referral code</label>
        <input type="text" id="referral_code_display"
               value="<?= htmlspecialchars($referral_code) ?>" readonly>
        <input type="hidden" id="referral_code" name="referral_code"
               value="<?= htmlspecialchars($referral_code) ?>">
      </div>
    </div>

    <!-- Linha 2: Nome + Sobrenome -->
    <div class="form-row">
      <div class="form-group">
        <label for="referred">First Name</label>
        <input type="text" id="referred" name="referred"
               placeholder="Ex: John" required>
      </div>
      <div class="form-group">
        <label for="referred_last_name">Last Name</label>
        <input type="text" id="referred_last_name" name="referred_last_name"
               placeholder="Ex: Smith" required>
      </div>
    </div>

    <!-- Linha 3: Tipo de cliente + Email -->
    <div class="form-row">
      <div class="form-group">
        <label for="client_type">Client Type</label>
        <select id="client_type" name="client_type" required>
          <option value="">-- Select --</option>
          <option value="Home">Residential</option>
          <option value="Company">Commercial</option>
        </select>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               placeholder="example@email.com">
      </div>
    </div>

    <!-- Linha 4: Mobile + Endereço -->
    <div class="form-row">
      <div class="form-group">
        <label for="mobile">Mobile</label>
        <input type="text" id="mobile" name="mobile"
               placeholder="+61 4XXXXXXXX" required>
      </div>
      <div class="form-group">
        <label for="autocomplete-address-quote">Search Address</label>
        <input type="text" id="autocomplete-address-quote"
               placeholder="Start typing address…" autocomplete="off">
      </div>
    </div>

    <!-- Mais detalhes (full width) -->
    <div class="form-group full-width">
      <label for="more_details">More Details (Optional)</label>
      <input type="text" id="more_details" name="more_details"
             placeholder="E.g. Near the park, house with red gate">
    </div>

    <!-- Ações (botão) -->
    <div class="form-actions">
      <button type="submit" class="btn-submit">Submit Referral</button>
    </div>
  </form>
</div>





<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="load.js"></script>