<?php
// index.php — carrega o seu conexao.php e renderiza serviços e modal
require 'conexao.php';

$referralCode = '';
if (!empty($_GET['ref'])) {
    $referralCode = preg_replace('/[^A-Za-z0-9]/', '', $_GET['ref']);
}

$services = [
    [
        'id'       => 1,
        'name'     => 'Home Cleaning',
        'subtitle' => 'Cleaning Services',
        'desc'     => 'Transform your space with professional home care.',
        'year'     => '2025',
        'location' => 'Sydney',
        'duration' => '2h',
        'price'    => '100',
        'img'      => 'home_cleaning.jpg'
    ],
    [
        'id'       => 2,
        'name'     => 'Commercial Cleaning',
        'subtitle' => 'Cleaning Services',
        'desc'     => 'Tailored commercial solutions for your business.',
        'year'     => '2025',
        'location' => 'Melbourne',
        'duration' => '3h',
        'price'    => '100',
        'img'      => 'commercial_cleaning.jpg'
    ],
    [
        'id'       => 3,
        'name'     => 'Short Rental Services',
        'subtitle' => 'Rental Services',
        'desc'     => 'Efficient cleaning for Airbnb and short-term stays.',
        'year'     => '2025',
        'location' => 'Brisbane',
        'duration' => '1.5h',
        'price'    => '100',
        'img'      => 'short_rental_services.jpg'
    ],
    [
        'id'       => 4,
        'name'     => 'Strata Services',
        'subtitle' => 'Strata Services',
        'desc'     => 'Shared space cleaning with a community focus.',
        'year'     => '2025',
        'location' => 'Adelaide',
        'duration' => '2.5h',
        'price'    => '100',
        'img'      => 'strata_services.jpg'
    ],
    [
        'id'       => 5,
        'name'     => 'Support Services',
        'subtitle' => 'Support Services',
        'desc'     => 'Tailored assistance for any part of your property.',
        'year'     => '2025',
        'location' => 'Perth',
        'duration' => '2h',
        'price'    => '100',
        'img'      => 'support_services.jpg'
    ],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Blue Facility Services</title>
  <link rel="stylesheet" href="style_quote.css">
</head>
<body>

  <!-- Services Grid -->
  <section class="services">
    <?php foreach ($services as $s): ?>
      <div class="card" data-service-id="<?= $s['id'] ?>" data-service-name="<?= htmlspecialchars($s['name']) ?>">
        <img src="images/<?= htmlspecialchars($s['img']) ?>" alt="<?= htmlspecialchars($s['name']) ?>">
        <h3><?= htmlspecialchars($s['name']) ?></h3>
        <button class="booking-btn"><?= 'Book Now' ?></button>
      </div>
    <?php endforeach; ?>
  </section>

  <!-- Quote Modal -->
  <div id="quoteModal" class="modal">
    <div class="modal-content">
      <button class="close-modal" aria-label="Fechar">&times;</button>
      <div class="modal-left" id="modalLeft">
        <div class="modal-left-overlay"></div>
        <div class="modal-left-text">
          <h2 id="modalServiceName">Service Name</h2>
          <h4 id="modalServiceSubtitle">Service Subtitle</h4>
          <p id="modalServiceDesc">Mini description goes here.</p>
        </div>
      </div>
      <div class="modal-right">
        <h2>Enter Your Details</h2>
        <form id="quoteForm">
          <div class="form-row">
            <input type="text" name="referral_code" id="referral_code" placeholder="Referral Code (if any)" <?= $referralCode ? 'value="'.$referralCode.'" readonly' : '' ?>>
          </div>
          <div class="form-row">
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
          </div>
          <input type="email" name="email" placeholder="Email" required>
          <input type="tel" name="mobile" placeholder="Mobile" required>
          <input type="text" name="postcode" placeholder="Postal Code" required>
          <textarea name="more_details" placeholder="Additional Comments" rows="3"></textarea>

          <input type="hidden" name="service_id" id="service_id">
          <input type="hidden" name="service_name" id="service_name">

          <button type="submit" class="btn-submit">Submit</button>
        </form>
      </div>
    </div>
  </div>

  <script src="main.js"></script>
</body>
</html>
