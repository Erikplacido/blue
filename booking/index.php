<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../src/config.php';
use Src\Controllers\ServiceController;

$controller = new ServiceController();
$serviceData = $controller->getServiceWithInclusionsAndExtras('house-cleaning');

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Configure Your Booking</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/css/blue.css">
  <link rel="stylesheet" href="assets/css/mobile.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.css">
</head>
<body>

<div class="booking__container">
  <h1>Arrange your service: <?= htmlspecialchars($serviceData['service']['name']) ?></h1>

  <form id="bookingForm" class="booking" method="post" action="process_booking.php">

    <div class="booking-bar">
      <div class="booking-bar__item">
        <label for="address">Enter your Address</label>
        <input type="text" id="address" name="address" required placeholder="Search address…" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
        <input type="hidden" name="postcode" value="<?= htmlspecialchars($_POST['postcode'] ?? '') ?>">
        <input type="hidden" name="latitude" value="<?= htmlspecialchars($_POST['latitude'] ?? '') ?>">
        <input type="hidden" name="longitude" value="<?= htmlspecialchars($_POST['longitude'] ?? '') ?>">
      </div>

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
    <div class="calendar-header">
      <button type="button" id="prevMonth" aria-label="Previous Month">&lt;</button>
      <span id="calendarMonth">Month Year</span>
      <button type="button" id="nextMonth" aria-label="Next Month">&gt;</button>
    </div>
    <div class="calendar-grid" id="calendarGrid"></div>
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
        <div class="item-card inclusion-item" data-price="<?= $inclusion['price'] ?>">
          <?= renderMedia($inclusion['image'], $inclusion['name']) ?>
          <div class="item-card__info">
            <h4><?= htmlspecialchars($inclusion['name']) ?></h4>
            <p class="item-card__price">+ $ <?= number_format($inclusion['price'], 2) ?></p>
          </div>
          <div class="item-card__counter">
            <button type="button" class="minus">−</button>
            <span class="qty">1</span>
            <button type="button" class="plus">+</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Extras -->
    <div class="extras-group-box">
      <?php foreach ($serviceData['extras'] as $extra): ?>
        <div class="extra-item" data-price="<?= $extra['price'] ?>" data-type="extra">
          <span class="extra-name"><?= htmlspecialchars($extra['name']) ?></span>
          <span class="extra-price">+ $ <?= number_format($extra['price'], 2) ?></span>
          <div class="extra-actions">
            <div class="extra-counter">
              <button type="button" class="minus">−</button>
              <span class="qty">0</span>
              <button type="button" class="plus">+</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Preferences -->
    <div class="booking__section">
      <label class="booking__label">Preferences</label>
      <div class="preferences-wrapper">
        <div class="preferences-column">
          <label><input type="checkbox" name="present"> I will be at the property</label>
          <input type="text" name="access_info" placeholder="Access Info (if you're not home)">
        </div>
        <div class="preferences-column">
          <label><input type="checkbox" name="pets"> I have pets</label>
          <input type="text" name="allergies" placeholder="Allergies or notes">
        </div>
      </div>
    </div>

    <!-- Personal Info -->
    <div class="booking__section dados_pessoais-wrapper">
      <label class="booking__label">Your Info</label>
      <div class="dados_pessoais-container">
        <input type="text" name="first_name" placeholder="First name" required class="dados_pessoais-input" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
        <input type="text" name="last_name" placeholder="Last name" required class="dados_pessoais-input" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
        <input type="email" name="email" placeholder="Email" required class="dados_pessoais-input" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <input type="text" name="phone" placeholder="Mobile number" required class="dados_pessoais-input" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        <input type="text" name="abn_or_tfn" placeholder="ABN or TFN" class="dados_pessoais-input" value="<?= htmlspecialchars($_POST['abn_or_tfn'] ?? '') ?>">
      </div>
    </div>

    <!-- Submit -->
    <div class="booking__section">
      <button type="submit" class="btn btn--full">Proceed to Checkout</button>
    </div>

  </form>
</div>

<!-- Summary Panel -->
<div id="summaryBar">
  <div class="summary-label">Review your booking</div>
  <div class="summary-total">$0.00</div>
  <button id="openSummaryBtn">Proceed to Checkout</button>
</div>

<div class="summary-discount-wrapper">
  <div id="discountLine">You saved $0.00</div>
</div>

<div id="summaryPanelOverlay"></div>
<div id="summaryPanel">
  <button class="close-btn">&times;</button>
  <h2>Booking Summary</h2>
  
    <!-- ✅ ADICIONE AQUI -->
  <div class="summary-section" id="summaryInfoPanel"></div>
  
  <div class="summary-section">
    <!-- detalhes aqui -->
    <p><?= htmlspecialchars($serviceData['service']['name']) ?> – <span id="summaryRecurrence">One-time</span> – <span id="summaryTime">06:00</span></p>
  </div>
  <div class="summary-section">
    <label for="coupon">Apply Coupon</label>
    <input type="text" id="coupon" placeholder="Enter coupon code">
  </div>
  <div class="summary-section">
    <label for="recurrencePanel">Set recurrence (in months)</label>
    <select id="recurrencePanel">
      <option value="3">Every 3 months</option>
      <option value="6">Every 6 months</option>
      <option value="12">Every 12 months</option>
    </select>
  </div>
  <div class="booking__section">
    <button type="submit" form="bookingForm" class="btn btn--full">Checkout</button>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/script2.js"></script>
<script src="assets/js/script3.js"></script>


</body>
</html>
