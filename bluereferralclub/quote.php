<?php
// 1) Carrega o array do .env.php
$env = require_once __DIR__ . '/../src/.env.php';

// 2) Pega a chave que você precisa
$googleKey = $env['GOOGLE_PLACES_KEY'] ?? '';

// 3) Protege contra XSS (opcional, mas recomendável)
$googleKey = htmlspecialchars($googleKey, ENT_QUOTES, 'UTF-8');
?>
<!-- index.html -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Blue Facility Services</title>
  <link rel="stylesheet" href="quote/landing.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

  <!-- Sticky Header -->
  <header>
    <div class="container header-inner">
      <a href="/" class="logo"><img src="https://bluefacilityservices.com.au/wp-content/uploads/2024/11/logo_blue.png" alt="Blue Logo" style="height:40px;"></a>
      <nav>
        <ul class="nav-links">
         <li><a href="index.php#services">Services</a></li>
          <li><a href="index.php#work">Recent Work</a></li>
          <li><a href="index.php#about">About Us</a></li>
          <li><a href="/../contact_us.php">Contact</a></li>
        </ul>
        <button class="hamburger" aria-label="Open menu">&#9776;</button>
      </nav>
      <div class="header-ctas">
        <a href="tel:+180057711" class="phone">1800 577 11</a>
        <a href="#quoteModal" class="btn btn-book">BOOK NOW</a>
      </div>
    </div>
  </header>

  <!-- Hero Slider -->
  <section class="hero-slider">
    <div class="slides">
      <div class="slide" style="background-image:url('https://bluefacilityservices.com.au/wp-content/uploads/2025/01/home_cleaning_banner.webp');">
        <div class="overlay"></div>
        <div class="slide-content">
          <h1>Home Cleaning</h1>
          <p>Transform Your Home with Our Cleaning Services</p>
          <a href="#services" class="btn btn-outline">Learn More</a>
        </div>
      </div>
      <div class="slide" style="background-image:url('https://bluefacilityservices.com.au/wp-content/uploads/2025/01/office2.webp');">
        <div class="overlay"></div>
        <div class="slide-content">
          <h1>Professional Cleaning</h1>
          <p>Elevate Your Workspace with Tailored Cleaning</p>
          <a href="#services" class="btn btn-outline">Learn More</a>
        </div>
      </div>
      <div class="slide" style="background-image:url('https://bluefacilityservices.com.au/wp-content/uploads/2024/12/short_rental-bg.webp');">
        <div class="overlay"></div>
        <div class="slide-content">
          <h1>Short Rental Services</h1>
          <p>Comprehensive Short-Rental Management Solutions</p>
          <a href="#services" class="btn btn-outline">Learn More</a>
        </div>
      </div>
      <div class="slide" style="background-image:url('https://bluefacilityservices.com.au/wp-content/uploads/2024/11/pressure_wash.webp');">
        <div class="overlay"></div>
        <div class="slide-content">
          <h1>Support Services</h1>
          <p>Dedicated Care for Every Corner of Your Property</p>
          <a href="#services" class="btn btn-outline">Learn More</a>
        </div>
      </div>
      <div class="slide" style="https://bluefacilityservices.com.au/wp-content/uploads/2024/10/strata.webp');">
        <div class="overlay"></div>
        <div class="slide-content">
          <h1>Strata Services</h1>
          <p>Comprehensive Strata Management Services</p>
          <a href="#services" class="btn btn-outline">Learn More</a>
        </div>
      </div>      
    </div>
    <button class="prev" aria-label="Previous slide">&#10094;</button>
    <button class="next" aria-label="Next slide">&#10095;</button>
    <div class="indicators"></div>
  </section>

  <!-- Services Section -->
  <section id="services" class="services">
    <div class="container">
      <div id="cards-container">

        <!-- Home Cleaning -->
        <div class="card" data-service="Home Cleaning">
          <div class="card-image">
            <img src="https://bluefacilityservices.com.au/wp-content/uploads/2025/01/home_cleaning_banner.webp" alt="Home Cleaning">
          </div>
          <div class="card-body">
            <div class="title-rating">
              <h3>Home Cleaning</h3>
            </div>
            <p class="category">Transform Your Home with Our Cleaning Services.</p>
            <p class="mini-desc">Experience unmatched cleanliness with Basic, Deep, and End of Lease Cleaning tailored to your needs.</p>

            <div class="price-book">

              <button class="btn btn-orange booking-btn">Quote Now</button>
            </div>
          </div>
        </div>

        <!-- Commercial Cleaning -->
        <div class="card" data-service="Commercial Cleaning">
          <div class="card-image">
            <img src="https://bluefacilityservices.com.au/wp-content/uploads/2025/01/office2.webp" alt="Commercial Cleaning">
          </div>
          <div class="card-body">
            <div class="title-rating">
              <h3>Commercial Cleaning</h3>
            </div>
            <p class="category">Elevate Your Workspace with Tailored Cleaning.</p>
            <p class="mini-desc">We serve gyms, churches, medical centers, and offices with expert commercial cleaning solutions.</p>

            <div class="price-book">

              <button class="btn btn-orange booking-btn">Quote Now</button>
            </div>
          </div>
        </div>

        <!-- Short Rental Services -->
        <div class="card" data-service="Short Rental Cleaning">
          <div class="card-image">
            <img src="https://bluefacilityservices.com.au/wp-content/uploads/2024/12/short_rental-bg.webp" alt="Short Rental Services">
          </div>
          <div class="card-body">
            <div class="title-rating">
              <h3>Short Rental Cleaning</h3>
            </div>
            <p class="category">Reliable and Thorough Cleaning for a Hassle-Free Move-Out.</p>
            <p class="mini-desc">From deep cleans to quick turnarounds between guests.</p>

            <div class="price-book">

              <button class="btn btn-orange booking-btn">Quote Now</button>
            </div>
          </div>
        </div>
        
                <!-- Short Rental Services -->
        <div class="card" data-service="Short Rental Management">
          <div class="card-image">
            <img src="https://bluefacilityservices.com.au/wp-content/uploads/2024/12/short_rental-bg.webp" alt="Short Rental Services">
          </div>
          <div class="card-body">
            <div class="title-rating">
              <h3>Short Rental Management</h3>
            </div>
            <p class="category">Comprehensive Short-Rental Management Solutions.</p>
            <p class="mini-desc">Enhance guest satisfaction, and keep your rental in top condition.</p>

            <div class="price-book">

              <button class="btn btn-orange booking-btn">Quote Now</button>
            </div>
          </div>
        </div>

        <!-- Strata Services -->
        <div class="card" data-service="Strata Services">
          <div class="card-image">
            <img src="https://bluefacilityservices.com.au/wp-content/uploads/2024/10/strata.webp" alt="Strata Services">
          </div>
          <div class="card-body">
            <div class="title-rating">
              <h3>Strata Services</h3>
            </div>
            <p class="category">Comprehensive Strata Management Services.</p>
            <p class="mini-desc">Maintain harmony and cleanliness in shared spaces with expert care.</p>

            <div class="price-book">

              <button class="btn btn-orange booking-btn">Quote Now</button>
            </div>
          </div>
        </div>

        <!-- Support Services -->
        <div class="card" data-service="Handyman">
          <div class="card-image">
            <img src="https://bluefacilityservices.com.au/wp-content/uploads/2024/10/handyman.webp" alt="Support Services">
          </div>
          <div class="card-body">
            <div class="title-rating">
              <h3>Handyman</h3>
            </div>
            <p class="category">Reliable Handyman Services for Every Need.</p>
            <p class="mini-desc">From minor repairs to complex projects, we handle it all with expertise and care.</p>
 
            <div class="price-book">

              <button class="btn btn-orange booking-btn">Quote Now</button>
            </div>
          </div>
        </div>
        
        <!-- Support Services -->
        <div class="card" data-service="Gardening">
          <div class="card-image">
            <img src="https://bluefacilityservices.com.au/wp-content/uploads/2024/11/gardining.webp" alt="Support Services">
          </div>
          <div class="card-body">
            <div class="title-rating">
              <h3>Gardening</h3>
            </div>
            <p class="category">Professional Gardening Services for Every Season.</p>
            <p class="mini-desc">Transform your outdoor space into a lush, vibrant haven with expert care.</p>

            <div class="price-book">

              <button class="btn btn-orange booking-btn">Quote Now</button>
            </div>
          </div>
        </div>

        <!-- Support Services -->
        <div class="card" data-service="Pressure Washing">
          <div class="card-image">
            <img src="https://bluefacilityservices.com.au/wp-content/uploads/2024/11/pressure_wash-1.webp" alt="Support Services">
          </div>
          <div class="card-body">
            <div class="title-rating">
              <h3>Pressure Washing</h3>
            </div>
            <p class="category">Restore with Professional Pressure Cleaning.</p>
            <p class="mini-desc">Bring life back to your property with our advanced pressure cleaning services.</p>

            <div class="price-book">

              <button class="btn btn-orange booking-btn">Quote Now</button>
            </div>
          </div>
        </div>      
        
        <!-- Support Services -->
        <div class="card" data-service="Steam Cleaning">
          <div class="card-image">
            <img src="https://bluefacilityservices.com.au/wp-content/uploads/2024/11/steam.webp" alt="Support Services">
          </div>
          <div class="card-body">
            <div class="title-rating">
              <h3>Steam Cleaning</h3>
            </div>
            <p class="category">Deep Clean Your Surfaces with Steam Cleaning.</p>
            <p class="mini-desc">Revitalize your space with our eco-friendly steam cleaning solutions.</p>

            <div class="price-book">

              <button class="btn btn-orange booking-btn">Quote Now</button>
            </div>
          </div>
        </div>

        <!-- Support Services -->
        <div class="card" data-service="Window Cleaning">
          <div class="card-image">
            <img src="https://bluefacilityservices.com.au/wp-content/uploads/2024/10/windonws.webp" alt="Support Services">
          </div>
          <div class="card-body">
            <div class="title-rating">
              <h3>Window Cleaning</h3>
            </div>
            <p class="category">Crystal Clear Windows, Every Time,</p>
            <p class="mini-desc">Enhance your property’s appearance with our professional window cleaning services.</p>

            <div class="price-book">

              <button class="btn btn-orange booking-btn">Quote Now</button>
            </div>
          </div>
        </div>           

      </div>
    </div>
  </section>

  <!-- Quote Modal -->
  <div id="quoteModal" class="modal">
    <div class="modal-content">
      <button class="close-modal" aria-label="Close modal">&times;</button>
      <div class="modal-grid">
        <div class="modal-left" id="modalLeft" style="background-image:url('assets/images/default-bg.jpg');">
          <div class="modal-left-overlay"></div>
          <div class="modal-left-text">
            <h2 id="modalServiceName">Service Name</h2>
            <h4 id="modalServiceSubtitle">Service Subtitle</h4>
            <p id="modalServiceDesc">Mini description goes here.</p>
          </div>
        </div>
        <div class="modal-right">
          <h3>Enter Your Details</h3>
          <form id="quoteForm">
  <input type="hidden" name="user_id" value="1"> <!-- ✅ Aqui está o user_id -->

  <div class="input-group form-group">
  <div class="input-group-prepend">
    <span class="input-group-text">Discount Code</span>
  </div>
  <input
    type="text"
    name="referral_code"
    id="referral_code"
    placeholder="Referral Code (if any)"
    aria-label="Referral Code"
    class="form-control"
    required
  >
</div>
  <div class="form-row">
    <input type="text" name="referred" placeholder="First Name" required aria-label="First Name">
    <input type="text" name="referred_last_name" placeholder="Last Name" required aria-label="Last Name">
  </div>
  <input type="email" name="email" placeholder="Email" required aria-label="Email">
  <input type="tel" name="mobile" placeholder="Mobile" required aria-label="Mobile">
<input
  type="text"
  id="autocomplete-address-quote"
  placeholder="Search Address..."
  aria-label="Search Address"
>
<input type="hidden" name="address" id="address">
  <input type="hidden" name="postcode" id="postcode">
  <input type="hidden" name="number" id="number">
  <input type="hidden" name="suburb" id="suburb">
  <input type="hidden" name="city" id="city">
  <input type="hidden" name="territory" id="territory">
  <select name="client_type" required>
  <option value="">Select Client Type</option>
  <option value="Home">Residential</option>
  <option value="Company">Commercial</option>
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
  <textarea name="more_details" placeholder="Additional Comments" rows="3" aria-label="Additional Details"></textarea>
  <button type="submit" class="btn btn-submit">Submit</button>
  <p class="help-link">Need Help? <a href="/../contact_us.php">Contact Us</a></p>
</form>
        </div>
      </div>
    </div>
  </div>
  
  <script>
  // Guarda o alert original, se algum dia quiser restaurar
  const _originalAlert = window.alert;
  // Sobrescreve o alert para não fazer nada
  window.alert = function(msg) {
    // opcional:  
    // if (!msg.includes('Referral not found')) {
    //   _originalAlert(msg);
    // }
  };
</script>
   <script src="quote/landing.js"></script>
   <script src="emails_action/landing_send.js"></script>
   
   
   <!-- Awesomplete CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.css" />

<!-- Autocomplete script -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js"></script>
<script src="js/address.js"></script> <!-- já pronto e usado por você -->



<script type="module">
  // aponte para a build ES module
  import { Loader } from "https://cdn.jsdelivr.net/npm/@googlemaps/js-api-loader/dist/index.esm.js";

  const loader = new Loader({
    apiKey: "<?= $googleKey ?>",
    libraries: ["places"]
  });

  loader.load()
    .then(() => initGooglePlaces())
    .catch(err => console.error("Erro ao carregar Google Maps:", err));
</script>
   
</body>
</html>