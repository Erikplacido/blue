<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}
// Carrega variáveis de ambiente e configurações antes de usar Connection
$env = require __DIR__ . '/../src/.env.php';
require __DIR__ . '/../src/config.php';

// Carrega manualmente o model Customer
$customerModelFile = require __DIR__ . '/../src/Models/Customer.php';
use Src\Models\Customer;
use Src\Database\Connection;
$customerModel = new Customer();

$customer = $customerModel->getById($_SESSION['customer_id']);

// Carrega endereços salvos do usuário
$db = Connection::getInstance()->getPDO();
$stmt = $db->prepare("SELECT id, address_line1 AS address_line, postcode, latitude, longitude FROM customer_addresses WHERE customer_id = ?");
$stmt->execute([$_SESSION['customer_id']]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

use Src\Controllers\ServiceController;

// Controller de serviços
$controller = new ServiceController();
$serviceData = $controller->getServiceWithInclusionsAndExtras('house-cleaning');

// Função para renderizar mídia
function renderMedia($media, $alt = '') {
    if (!$media) return '';
    if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $media)) {
        return '<img src="assets/uploads/' . htmlspecialchars($media) . '" alt="' . htmlspecialchars($alt) . '" class="item-card__thumb">';
    }
    if (str_starts_with($media, 'fa-')) {
        return '<i class="' . htmlspecialchars($media) . ' text-xl"></i>';
    }
    return '<span class="' . htmlspecialchars($media) . '"></span>';
}

// 🔍 Carregar preferências
$db = Connection::getInstance()->getPDO();
$stmt = $db->query("SELECT * FROM preference_fields ORDER BY id");
$preferenceFields = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Configure Your Booking</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/css/blue.css">
  <link rel="stylesheet" href="assets/css/summaryPanel(1).css">
  <link rel="stylesheet" href="assets/css/mobile.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.css">
    <!-- Google Places API -->
<?php $mapsKey = htmlspecialchars($env['GOOGLE_PLACES_KEY'] ?? ''); ?>
<script
  src="https://maps.googleapis.com/maps/api/js?key=<?= $mapsKey ?>&libraries=places&callback=initGooglePlaces"
  async defer>
</script>
</head>
<body>

<div class="booking__container">
  <h1>Arrange your service: <?= htmlspecialchars($serviceData['service']['name']) ?></h1>

  <form id="bookingForm" class="booking" method="post" action="process_booking.php">

    <div class="booking-bar">
      <?php if (count($addresses) > 1): ?>
        <div class="booking-bar__item">
          <label for="savedAddress">Choose a saved address</label>
          <select id="savedAddress" name="saved_address_id" onchange="populateAddressFields(this)">
            <?php foreach ($addresses as $addr): ?>
              <option
                value="<?= $addr['id'] ?>"
                data-address="<?= htmlspecialchars($addr['address_line']) ?>"
                data-postcode="<?= htmlspecialchars($addr['postcode']) ?>"
                data-lat="<?= htmlspecialchars($addr['latitude']) ?>"
                data-lng="<?= htmlspecialchars($addr['longitude']) ?>"
                <?= (($_POST['saved_address_id'] ?? '') == $addr['id']) ? 'selected' : '' ?>
              >
                <?= htmlspecialchars($addr['address_line']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <?php $single = count($addresses) === 1 ? $addresses[0] : null; ?>

      <input 
        type="hidden" 
        id="address" 
        name="address" 
        required 
        value="<?= htmlspecialchars($_POST['address'] ?? ($single['address_line'] ?? '')) ?>"
      >
      <input 
        type="hidden" 
        id="postcode" 
        name="postcode" 
        value="<?= htmlspecialchars($_POST['postcode'] ?? ($single['postcode'] ?? '')) ?>"
      >
      <input 
        type="hidden" 
        id="latitude" 
        name="latitude" 
        value="<?= htmlspecialchars($_POST['latitude'] ?? ($single['latitude'] ?? '')) ?>"
      >
      <input 
        type="hidden" 
        id="longitude" 
        name="longitude" 
        value="<?= htmlspecialchars($_POST['longitude'] ?? ($single['longitude'] ?? '')) ?>"
      >

<div class="booking-bar__item">
  <label for="recurrence">Recurrence</label>
  <select name="recurrence" id="recurrence" required>
    <option value="one-time" <?= ($_POST['recurrence'] ?? '') === 'one-time' ? 'selected' : '' ?>>One-time</option>
    <option value="weekly" <?= ($_POST['recurrence'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
    <option value="fortnightly" <?= ($_POST['recurrence'] ?? '') === 'fortnightly' ? 'selected' : '' ?>>Fortnightly</option>
    <option value="monthly" <?= ($_POST['recurrence'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
  </select>


</div>


<div class="booking-bar__item my-calendar">
  <label for="execution_date">Date</label>

  <!-- Preview do calendário -->
  <div id="calendarPreview"
       class="calendar-preview calendar-preview--my-calendar cursor-pointer mt-1"
       aria-expanded="false">
    <?= htmlspecialchars($_POST['execution_date'] ?? 'Choose a date') ?>
  </div>

  <!-- Input escondido -->
  <input type="hidden"
         id="execution_date"
         name="execution_date"
         required
         value="<?= htmlspecialchars($_POST['execution_date'] ?? '') ?>">

  <!-- Popover do calendário -->
  <div id="calendarPopover" class="calendar-popover">
      <!-- 📘 Mensagem explicativa da recorrência -->
    <div class="calendar-header">
      <button type="button" id="prevMonth" aria-label="Previous Month">&lt;</button>
      <span id="calendarMonth">Month Year</span>
      <button type="button" id="nextMonth" aria-label="Next Month">&gt;</button>
    </div>

      <!-- Nomes dos dias da semana 🇦🇺 começa na segunda -->
  <div id="calendarWeekdays" class="calendar-weekdays"></div>
    <div class="calendar-grid" id="calendarGrid"></div>
          <small id="recurrence-message" class="text-gray message-box" style="display:none; margin-top: 0.5rem;"></small>

  </div>
</div>



      <div class="booking-bar__item">
        <label for="time_window">Time</label>
        <select name="time_window" id="time_window" required>
          <?php for ($h = 6; $h <= 17; $h++): 
            $start = sprintf('%02d:00', $h);
            $end   = sprintf('%02d:00', $h + 1);
          ?>
            <option value="<?= $start ?>" <?= ($_POST['time_window'] ?? '') === $start ? 'selected' : '' ?>>
              <?= $start ?> – <?= $end ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
    </div>

    <!-- Inclusions -->
    <div class="extras-list itens_inclusos">
      <?php foreach ($serviceData['inclusions'] as $inclusion): ?>
        <div class="item-card inclusion-item"data-price="<?= $inclusion['price'] ?>"data-min-quantity="<?= $inclusion['min_quantity'] ?>">
          <?= renderMedia($inclusion['image'], $inclusion['name']) ?>
          <div class="item-card__info">
            <h4> <?= htmlspecialchars($inclusion['name']) ?><?php if (!empty($inclusion['description'])): ?><button type="button" class="info-icon"data-title="<?= htmlspecialchars($inclusion['name']) ?>"data-description="<?= htmlspecialchars($inclusion['description']) ?>"aria-label="More info">ⓘ</button><?php endif; ?></h4>
            <p class="item-card__price">+ $ <?= number_format($inclusion['price'], 2) ?></p>
          </div>
          <div class="item-card__counter">
            <button type="button" class="minus">−</button>
            <span class="qty"><?= (int)$inclusion['min_quantity'] ?></span>
            <input type="hidden" name="included_qty[<?= $inclusion['id'] ?>]" value="<?= (int)$inclusion['min_quantity'] ?>" />
            <button type="button" class="plus">+</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

<!-- 🔄 Flex container para Extras e Preferences lado a lado -->
<div class="extras-preferences-container">
  
  <!-- Extras à esquerda -->
  <div class="extras-wrapper">
    <h3 class="section-title">Extras</h3>
    <div class="extras-group-box">
      <?php foreach ($serviceData['extras'] as $extra): ?>
        <div class="extra-item" data-price="<?= $extra['price'] ?>" data-type="extra">
          <span class="extra-name"><?= htmlspecialchars($extra['name']) ?></span>
          <span class="extra-price">+ $ <?= number_format($extra['price'], 2) ?></span>
          <div class="extra-actions">
            <div class="extra-counter">
              <button type="button" class="minus">−</button>
              <span class="qty">0</span>
              <input type="hidden" name="extra_qty[<?= $extra['id'] ?>]" value="0" />
              <button type="button" class="plus">+</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Preferences à direita -->
  <div class="preferences-wrapper-side">
    <h3 class="section-title">Preferences</h3>
    <div class="preferences-wrapper">
<?php foreach ($preferenceFields as $field): ?>
  <div class="preference-item preferences-field one-line">
    <label>
      <?= htmlspecialchars($field['name']) ?>
      <?php if ($field['field_type'] === 'checkbox'): ?>
  <input
    type="checkbox"
    class="preference-checkbox"
    name="preferences[<?= $field['id'] ?>]"
    value="1"
    data-note='<?= htmlspecialchars($field['options'] ?? '', ENT_QUOTES) ?>'
    data-extra-fee="<?= htmlspecialchars($field['extra_fee'] ?? 0, ENT_QUOTES) ?>"
    <?= $field['is_checked_default'] ? 'checked' : '' ?>
  >
      <?php elseif ($field['field_type'] === 'text'): ?>
        <input type="text" name="preferences[<?= $field['id'] ?>]">
      <?php elseif ($field['field_type'] === 'select'): ?>
        <?php $options = json_decode($field['options'], true); ?>
        <select name="preferences[<?= $field['id'] ?>]">
          <option value="">Select one</option>
          <?php foreach ($options as $opt): ?>
            <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
    </label>

    <?php if ($field['field_type'] === 'checkbox' && $field['options']): ?>
      <div class="preference-note" style="display: none; color: #cc0000; font-size: 0.85em;"></div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
    </div>
  </div>

</div>



    <!-- Personal Info -->
    <div class="booking__section dados_pessoais-wrapper">
      <label class="booking__label">Your Info</label>
      <div class="dados_pessoais-container">
        <input type="text" name="first_name" placeholder="First name" required class="dados_pessoais-input" value="<?= htmlspecialchars($_POST['first_name'] ?? $customer['first_name'] ?? '') ?>">
        <input type="text" name="last_name" placeholder="Last name" required class="dados_pessoais-input" value="<?= htmlspecialchars($_POST['last_name'] ?? $customer['last_name'] ?? '') ?>">
        <input type="email" name="email" placeholder="Email" required class="dados_pessoais-input" value="<?= htmlspecialchars($_POST['email'] ?? $customer['email'] ?? '') ?>">
        <input type="text" name="phone" placeholder="Mobile number" required class="dados_pessoais-input" value="<?= htmlspecialchars($_POST['phone'] ?? $customer['phone'] ?? '') ?>">
        <input type="text" name="abn_or_tfn" placeholder="ABN or TFN" class="dados_pessoais-input" value="<?= htmlspecialchars($_POST['abn_or_tfn'] ?? $customer['abn_or_tfn'] ?? '') ?>">
      </div>
    </div>

    <!-- 🧾 Hidden fields for modal values -->
  <input type="hidden" name="discountCode" id="hiddenDiscountCode" value="">
  <input type="hidden" name="pointsApplied" id="hiddenPointsApplied" value="">
  <input type="hidden" id="hiddenCouponCode" name="discountCode" />
  <input type="hidden" name="baseTotal" id="baseTotalInput">


  </form>
</div>

<!-- Summary Bar -->
<div id="summaryBar">
  <div class="summary-label">Review your booking</div>
  <div class="summary-total">$0.00</div>
  <button type="button" id="openSummaryBtn">Proceed to Checkout</button>
</div>

<!-- Summary Modal -->
<div id="summaryModal" class="modal-slide hidden">
  <div class="modal-content-summary">
    <button class="close-modal-btn" id="closeSummaryModal">&times;</button>
    <h2>Booking Summary</h2>

    <div id="summaryInfoContent" class="summary-content scrollable"></div>

    <!-- 🔁 Contract Duration -->
    <div class="summary-section">
      <label for="contractDuration">Contract Duration</label>
    <select id="contractDuration" name="contractDuration" required form="bookingForm">
        <option value="3">Every 3 months</option>
        <option value="6">Every 6 months</option>
        <option value="12">Every 12 months</option>
      </select>
    </div>

    <!-- 💰 Total -->
    <div class="summary-section">
      <p class="font-bold">Total Price: <span id="totalPriceLabel">$0.00</span></p>
      <p class="price-note">Note: For recurring services, this is the price per occurrence. Payment will be processed 48 hours before each scheduled service.</p>
    </div>

<!-- 🎁 Discounts and Points -->
<div class="summary-section">
  <div class="discounts-grid">
    <div class="form-group">
      <label for="discountCode">Discount Code</label>
      <div class="input-with-btn">
        <input type="text" id="discountCode" name="discountCode" placeholder="Enter code" autocomplete="off">
        <button type="button" id="applyDiscountBtn" class="apply-btn">Add</button>
      </div>
      <input type="hidden" id="hiddenCouponCode" name="hiddenCouponCode">
    </div>

    <div class="form-group">
      <label for="pointsApplied">Apply Points (1 point = $1)</label>
      <input type="number" id="pointsApplied" name="pointsApplied" min="0" value="0" class="input-field">
    </div>
  </div> <!-- / .discounts-grid -->
</div>  <!-- / .summary-section -->



<!-- ✅ Terms Agreement -->
<div class="summary-section">
  <label>
    <input type="checkbox" id="agreedToTerms">
    I agree to the <button type="button" class="terms-link" id="openTermsBtn">Terms & Conditions</button>
  </label>
</div>

<!-- 📜 Terms Modal -->
<div id="termsModal" class="modal-overlay hidden">
  <div class="modal-terms">
    <button class="close-terms-btn" id="closeTermsModal">&times;</button>
    <h3>Terms & Conditions</h3>
    <div class="terms-content" id="termsContent">
      <p><strong>Summary:</strong></p>
      <ul>
        <li>Service will be provided as configured on the selected date(s) and time window.</li>
        <li>Recurring services repeat according to selected frequency.</li>
        <li>Payment is due 48 hours before each execution.</li>
        <li>Card will be charged automatically.</li>
        <li>Changes/cancellations must be made at least 48 hours in advance.</li>
        <li>Early termination may incur a penalty.</li>
        <li>We are not responsible for pre-existing damage.</li>
        <li>Issues must be reported within 24 hours.</li>
      </ul>
      <p>By continuing, you agree to all terms stated above.</p>
    </div>
  </div>
</div>

    <!-- 🎯 Actions -->
    <div class="btn-summary">
<!-- Botões de ação com estilo do seu design system -->
<button id="confirmBtn" type="submit" form="bookingForm" class="btn-summary" disabled>Confirm & Proceed to Payment</button>
    </div>
  </div>
</div>


<!-- Modal Recurrence Info -->
<div id="recurrenceModal" class="modal-overlay" style="display: none;">
  <div class="modal-content">
    <button class="modal-close" id="closeRecurrenceModal" aria-label="Close">×</button>
    <h2 id="recurrenceModalTitle">Title</h2>
    <p id="recurrenceModalMessage">Message</p>
    <button class="modal-ack" id="ackRecurrenceModal">Acknowledge</button>
  </div>
</div>

<!-- Modal for Inclusion Info -->
<div id="inclusionInfoModal" class="modal-overlay" style="display: none;">
  <div class="modal-content">
    <button class="modal-close" id="closeInclusionModal" aria-label="Close">×</button>
    <h2 id="inclusionModalTitle">Title</h2>
    <p id="inclusionModalMessage">Message</p>
    <button class="modal-ack" id="ackInclusionModal">Acknowledge</button>
  </div>
</div>



<script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/summaryPanel.js"></script>
<script src="assets/js/script3.js"></script>
<script src="assets/js/script4.js"></script>
<script src="assets/js/script5.js"></script>
<script src="assets/js/discountformbridge.js"></script>
<script src="assets/js/preference.js"></script>
<script src="assets/js/address.js"></script>




</body>
<script>
function populateAddressFields(select) {
  var opt = select.options[select.selectedIndex];
  document.getElementById('address').value = opt.dataset.address;
  document.getElementById('postcode').value = opt.dataset.postcode;
  document.getElementById('latitude').value = opt.dataset.lat;
  document.getElementById('longitude').value = opt.dataset.lng;
}
document.addEventListener('DOMContentLoaded', function() {
  var sel = document.getElementById('savedAddress');
  if (sel) populateAddressFields(sel);
});
</script>
</html>
