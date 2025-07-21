<?php
session_start(); // Inicia a sessão

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Conexão com o banco
require_once('../bluereferralclub/conexao.php');

// Ativar modo Debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Buscar reservations (incluindo email)
try {
    $stmt = $pdo->query("SELECT id, referred, referred_last_name, service_name, status, email, referral_code FROM referrals LIMIT 100");
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) {
    die("<strong>Erro ao buscar reservations:</strong> " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservations | Cleaning Services Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS Imports -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/demo.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cards.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/reservations.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js"></script>
</head>
<body>

<!-- Sidebar -->
<nav class="ps-panel__sidebar">
    <div class="ps-panel__top">
        <img src="https://bluefacilityservices.com.au/wp-content/uploads/2020/09/logo_novo.svg" alt="Admin Panel Logo" style="height:40px;">
    </div>
    <ul class="ps-panel__menu">
        <li><a href="reservations.php"><i class="fa fa-calendar"></i> Reservations</a></li>
        <li><a href="clients.php"><i class="fa fa-users"></i> Clients</a></li>
        <li><a href="services.php"><i class="fa fa-bars"></i> Services</a></li>
        <li><a href="add_user.php"><i class="fa fa-user-plus"></i> User</a></li>
        <li><a href="cleaner.php"><i class="fa fa-user"></i> Cleaner</a></li>
        <li><a href="schedule.php"><i class="fa fa-calendar-check-o"></i> Schedule</a></li>
        <li><a href="referral.php"><i class="fa fa-share-alt"></i> Referral Club</a></li>
        <li><a href="https://bluefacilityservices.com.au/training"><i class="fa fa-graduation-cap"></i> Training</a></li>
        <li><a href="index.php"><i class="fa fa-cog"></i> Dashboard</a></li>
        <a href="../logout.php" class="btn btn-danger">Logout</a>
    </ul>
</nav>

<!-- Main content -->
<div class="ps-panel__content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="dashboard-title">Reservations</h2>
        <div>
            <button class="btn btn-success me-2" onclick="openModal('newReservationModal')">New Reservation</button>
            <button class="btn btn-primary" onclick="openModal('newQuotationModal')">New Quotation</button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filters mb-4">
        <input type="text" id="filterName" placeholder="Search by Name">
        <input type="text" id="filterEmail" placeholder="Search by Email">
        <select id="filterStatus">
            <option value="">Status</option>
            <option value="Pending">Pending</option>
            <option value="Successes">Successes</option>
            <option value="Unsuccessful">Unsuccessful</option>
            <option value="Negotiating">Negotiating</option>
            <option value="Paid">Paid</option>
        </select>
        <input type="text" id="filterReferralCode" placeholder="Search by Referral Code">
        <button class="btn btn-secondary ms-2" id="clearFilters">Clear Filters</button>
    </div>

    <!-- Tabela de Reservations -->
    <div class="reservations-table">
       <table id="reservationsTable" class="table table-hover">
<thead>
    <tr>
        <th data-sort="0">ID</th>
        <th data-sort="1">Client</th>
        <th data-sort="2">Service</th>
        <th>Email</th>
        <th data-sort="3">Status</th>
        <th>Referral Code</th> <!-- NOVO -->
        <th>Actions</th>
    </tr>
</thead>
<tbody>
<?php if (!empty($reservations)) : ?>
    <?php foreach ($reservations as $reservation) : ?>
<tr 
    data-name="<?= htmlspecialchars(($reservation['referred'] ?? '') . ' ' . ($reservation['referred_last_name'] ?? '')) ?>"
    data-email="<?= htmlspecialchars($reservation['email'] ?? '') ?>" 
    data-status="<?= htmlspecialchars($reservation['status'] ?? '') ?>"
    data-referral-code="<?= htmlspecialchars($reservation['referral_code'] ?? '') ?>"
>
    <td>#<?= htmlspecialchars($reservation['id'] ?? '') ?></td>
    <td><?= htmlspecialchars(($reservation['referred'] ?? '') . ' ' . ($reservation['referred_last_name'] ?? '')) ?></td>
    <td><?= htmlspecialchars($reservation['service_name'] ?? '') ?></td>
    <td><?= htmlspecialchars($reservation['email'] ?? '') ?></td>
    <td><?= htmlspecialchars($reservation['status'] ?? '') ?></td>
    <td><?= htmlspecialchars($reservation['referral_code'] ?? '') ?></td> <!-- NOVO -->
    <td>
        <a href="edit_reservation.php?id=<?= htmlspecialchars($reservation['id'] ?? '') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
        <a href="admin_note.php?id=<?= htmlspecialchars($reservation['id'] ?? '') ?>" class="btn btn-sm btn-outline-warning">Admin Note</a>
    </td>
</tr>
    <?php endforeach; ?>
<?php else : ?>
    <tr>
        <td colspan="6" class="text-center">No reservations found.</td>
    </tr>
<?php endif; ?>
</tbody>
        </table>
    </div>
</div>

<!-- Modal - New Reservation -->
<div id="newReservationModal" class="custom-modal">
    <div class="custom-modal-content">
        <span class="close" id="closeReservationModal">&times;</span>
        <h3>New Reservation</h3>
        <form id="reservationForm">
            <input type="text" placeholder="Consumer Name">
            <input type="text" placeholder="First Name">
            <input type="text" placeholder="Last Name">
            <input type="text" placeholder="Referral Code">
            <input type="email" placeholder="Email">
            <input type="text" placeholder="Mobile">
            
            <input type="text" id="autocomplete-address-reservation" placeholder="Search address..." autocomplete="off" style="width: 100%;">
            
            <input type="hidden" name="number" placeholder="Number"readonly>
            <input type="hidden" name="postcode" placeholder="Postcode" readonly>
            <input type="hidden" name="address" placeholder="Address" readonly>
            <input type="hidden" name="suburb" placeholder="Suburb" readonly>
            <input type="hidden" name="city" placeholder="City" readonly>
            <input type="hidden" name="territory" placeholder="Territory" readonly>
            
            <select name="client_type" required>
                 <option value="">Select Client Type</option>
                 <option value="Residential">Residential</option>
                 <option value="Commercial">Commercial</option>
            </select>
            
<select name="service_name" required>
    <option value="">Select Service</option>
    <option value="Commercial Cleaning">Commercial Cleaning</option>
    <option value="Home Cleaning">Home Cleaning</option>
    <option value="Short Rental Cleaning">Short Rental Cleaning</option>
    <option value="Short Rental Management">Short Rental Management</option>
    <option value="Handyman">Handyman</option>
    <option value="Gardening">Gardening</option>
    <option value="Pressure Washing">Pressure Washing</option>
    <option value="Steam Cleaning">Steam Cleaning</option>
    <option value="Window Cleaning">Window Cleaning</option>
    <option value="Strata Services">Strata Services</option>
</select>
            <input type="text" placeholder="Commission Amount">
            <button type="submit" class="btn btn-success mt-3">Save Reservation</button>
        </form>
    </div>
</div>

<!-- Modal - New Quotation -->
<div id="newQuotationModal" class="custom-modal">
    <div class="custom-modal-content">
        <span class="close" id="closeQuotationModal">&times;</span>
        <h3>New Quotation</h3>
<form id="quotationForm">
    <input type="text" name="referred" placeholder="Referred">
    <input type="text" name="referred_last_name" placeholder="Referred Last Name">
    <input type="text" name="referral_code" placeholder="Referral Code">
    <input type="email" name="email" placeholder="Email">
    <input type="text" name="mobile" placeholder="Mobile">

    <input type="text" id="autocomplete-address-quotation" placeholder="Search address..." autocomplete="off" style="width: 100%;">
    
    


    <input type="hidden" name="number" placeholder="Number" readonly>
    <input type="hidden" name="postcode" placeholder="Postcode" readonly>
    <input type="hidden" name="address" placeholder="Address" readonly>
    <input type="hidden" name="suburb" placeholder="Suburb" readonly>
    <input type="hidden" name="city" placeholder="City" readonly>
    <input type="hidden" name="territory" placeholder="Territory" readonly>

    <!-- ✅ Novo campo Client Type -->
    <select name="client_type">
        <option value="">Select Client Type</option>
        <option value="Residential">Residential</option>
        <option value="Commercial">Commercial</option>
    </select>

    <select name="service_name">
        <option>Select Service</option>
        <option>Commercial Cleaning</option>
        <option>Home Cleaning</option>
        <option>Short Rental Cleaning</option>
        <option>Short Rental Management</option>
        <option>Handyman</option>
        <option>Gardening</option>
        <option>Pressure Washing</option>
        <option>Steam Cleaning</option>
        <option>Window Cleaning</option>
        <option>Strata Services</option>
    </select>

    <textarea name="more_details" placeholder="More Details"></textarea>
    <button type="submit" class="btn btn-primary mt-3">Save Quotation</button>
</form>

    </div>
</div>

<!-- JS -->
<script src="js/reservations.js"></script>
<script src="js/reservations_list.js"></script>
<script src="js/reservations_filter.js"></script>
<script src="js/address.js"></script>

<!-- Google Maps API - substitua SUA_CHAVE_AQUI -->
<script async
  src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA6dqOPMiDLe29otXTfltxkrnNyUPYCo9s&libraries=places&callback=initGooglePlaces"
  defer></script>

</body>
</html>
