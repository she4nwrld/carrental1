<?php
// small shortcut so I don't type htmlspecialchars everywhere
function e($text) {
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// car list and the dropdown options live here
$categories = ['All', 'Hatchback', 'Sedan', 'SUV', 'MPV'];

$cars = [
  ['name' => 'Kia Picanto',          'type' => 'Hatchback', 'price' => 1800, 'seats' => '5 seats', 'img' => 'images/kia-picanto.png'],
  ['name' => 'Suzuki Swift',         'type' => 'Hatchback', 'price' => 2000, 'seats' => '5 seats', 'img' => 'images/suzuki-swift.png'],
  ['name' => 'Toyota Corolla Altis', 'type' => 'Sedan',     'price' => 2800, 'seats' => '5 seats', 'img' => 'images/toyota-corolla-altis.png'],
];

// names only, used to check what the form posted
$carNames = [];
foreach ($cars as $car) {
  $carNames[] = $car['name'];
}

$pickups = ['Sibulan Airport', 'Rizal Boulevard', 'Valencia'];

$ages = ['18-24', '25-34', '35+'];

$steps = [
  ['Send the request', 'Choose a unit, how many days you need it, and where you want to pick it up.'],
  ['We confirm', 'A staff member replies by call or email to lock in the schedule.'],
  ['Pick up and drive', 'Bring your driver&rsquo;s license and one valid ID on pick-up day.'],
];

// which filter pill is on
$active = 'All';
if (isset($_GET['category']) && in_array($_GET['category'], $categories, true)) {
  $active = $_GET['category'];
}

$shown = [];
foreach ($cars as $car) {
  if ($active === 'All' || $car['type'] === $active) {
    $shown[] = $car;
  }
}

// booking form starts empty
$form = [
  'name' => '',
  'email' => '',
  'phone' => '',
  'car' => '',
  'pickup' => '',
  'days' => '2',
  'age' => '',
  'notes' => '',
];

$errors = [];
$license = false;
$sent = false;
$rate = 0;
$total = 0;

// booking form checks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  foreach ($form as $key => $old) {
    $form[$key] = trim($_POST[$key] ?? '');
  }

  $license = isset($_POST['license']);
  $form['notes'] = strip_tags($form['notes']);

  // name
  if ($form['name'] === '') {
    $errors['name'] = 'Please enter your full name.';
  } elseif (!preg_match('/^[A-Za-zÑñ .\'-]{2,60}$/u', $form['name'])) {
    $errors['name'] = 'Use letters, spaces, dots and dashes only for the name.';
  }

  // email
  if ($form['email'] === '') {
    $errors['email'] = 'Please enter your email address.';
  } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'That email address does not look valid.';
  }

  // phone, digits only so spaces and dashes are fine
  $digits = preg_replace('/[^0-9]/', '', $form['phone']);
  if ($form['phone'] === '') {
    $errors['phone'] = 'Please enter a mobile number we can call.';
  } elseif (strlen($digits) < 10 || strlen($digits) > 13) {
    $errors['phone'] = 'The mobile number should have 10 to 13 digits.';
  }

  // the two dropdowns, don't trust what came back
  if (!in_array($form['car'], $carNames, true)) {
    $errors['car'] = 'Please choose one of our units.';
  }

  if (!in_array($form['pickup'], $pickups, true)) {
    $errors['pickup'] = 'Please choose a pick-up point.';
  }

  // days, 1 to 30
  $days = filter_var($form['days'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 30]]);
  if ($days === false) {
    $errors['days'] = 'Rental length must be a whole number from 1 to 30 days.';
  }

  if (!in_array($form['age'], $ages, true)) {
    $errors['age'] = 'Please select your age bracket.';
  }

  if (strlen($form['notes']) > 300) {
    $errors['notes'] = 'Please keep the notes under 300 characters.';
  }

  if (!$license) {
    $errors['license'] = 'Please confirm that you hold a valid driver&rsquo;s license.';
  }

  // nothing wrong, so work out the estimate
  if (count($errors) === 0) {
    foreach ($cars as $car) {
      if ($car['name'] === $form['car']) {
        $rate = $car['price'];
      }
    }
    $total = $rate * $days;
    $sent = true;
  }

} else {

  // came from a Book Now card, so pick that unit already
  if (isset($_GET['car']) && in_array($_GET['car'], $carNames, true)) {
    $form['car'] = $_GET['car'];
  }

}

// filemtime on the css so the browser stops caching the old one
$cssFile = 'css/style.css';
$cssVersion = file_exists(__DIR__ . '/' . $cssFile) ? filemtime(__DIR__ . '/' . $cssFile) : 1;

$logo = 'images/shift-logo.png';
$hasLogo = file_exists(__DIR__ . '/' . $logo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Shift Car Rental — Dumaguete City, Sibulan &amp; Valencia</title>
<meta name="description" content="Self-drive car rental in Dumaguete City, Sibulan and Valencia. Send a booking request online and we confirm the schedule with you.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($cssFile) ?>?v=<?= e($cssVersion) ?>">
</head>

<body>

<!-- header -->
<header class="topbar">
  <div class="brand">
    <?php if ($hasLogo) { ?>
      <img src="<?= e($logo) ?>" alt="Shift Car Rental">
    <?php } else { ?>
      <span class="wordmark">SHI<span>F</span>T</span>
    <?php } ?>
  </div>

  <nav class="menu" aria-label="Main navigation">
    <a href="index.php" aria-current="page">Home</a>
    <a href="#our-vehicles">Vehicles</a>
    <a href="#booking">Reserve</a>
    <a href="#">Locations</a>
    <a href="#">Deals</a>
    <a href="#">FAQs</a>
    <a href="#">Contact Us</a>
  </nav>

  <div class="right">
    <a href="#">My booking</a>
    <a class="bookbtn" href="#booking">Book a Car</a>
  </div>
</header>

<main>

<!-- hero, plus the white search box -->
<section class="hero">

  <p class="reviews">
    <span class="st" aria-hidden="true">★★★★★</span> 4.9/5 · based on 1,989 Google reviews
  </p>

  <h1>Dumaguete City / Sibulan / Valencia <span>Car Rental.</span></h1>

  <p class="desc">
    Self-drive rentals across Dumaguete City, Sibulan and Valencia.
    Pick a unit, send a request, and we confirm the schedule with you
    before you pay anything.
  </p>

  <form class="box" action="index.php" method="get">

    <div class="line">
      <div class="col">
        <label for="pickup-location">Pick-up Location</label>
        <select id="pickup-location" name="pickup_location">
          <option>Select location</option>
          <?php foreach ($pickups as $place) { ?>
            <option><?= e($place) ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="col">
        <label for="return-location">Return Location</label>
        <select id="return-location" name="return_location">
          <option>Same as pick-up</option>
          <option>Sibulan Airport</option>
        </select>
      </div>
      <div class="col wide">
        <label for="pickup-date">Pick-up Date &amp; Time</label>
        <div class="pair">
          <input type="date" id="pickup-date" name="pickup_date">
          <input type="time" name="pickup_time" value="10:00" aria-label="Pick-up time">
        </div>
      </div>
      <div class="col wide">
        <label for="return-date">Return Date &amp; Time</label>
        <div class="pair">
          <input type="date" id="return-date" name="return_date">
          <input type="time" name="return_time" value="10:00" aria-label="Return time">
        </div>
      </div>
    </div>

    <div class="line">
      <div class="col">
        <label for="category">Category</label>
        <select id="category" name="category">
          <?php foreach ($categories as $cat) { ?>
            <option value="<?= e($cat) ?>"<?php if ($cat === $active) echo ' selected'; ?>><?= e($cat) ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="col">
        <label for="driver-age">Driver Age</label>
        <select id="driver-age" name="driver_age">
          <?php foreach ($ages as $bracket) { ?>
            <option><?= e($bracket) ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="col wide">
        <label for="discount-code">Discount Code</label>
        <input type="text" id="discount-code" name="discount_code" placeholder="Optional">
      </div>
      <div class="col wide findcol">
        <button class="findbtn" type="submit">Find Your Car</button>
      </div>
    </div>

    <div class="bottom">
      <div>
        <input type="checkbox" id="delivery" name="delivery">
        <label class="checkbox-label" for="delivery">Deliver the car to Sibulan Airport arrivals</label>
      </div>
      <div><a href="#">Already booked? <u>Manage your booking</u></a></div>
    </div>

  </form>
</section>

<!-- our vehicles -->
<section class="vehicles" id="our-vehicles">

  <h2 class="vehicles-title">Our <span>Vehicles</span></h2>

  <!-- filter pills, each one is just a link back to this page -->
  <div class="filters">
    <?php foreach ($categories as $cat) { ?>
      <a class="filter-tab<?php if ($cat === $active) echo ' is-active'; ?>"
         href="index.php?category=<?= urlencode($cat) ?>#our-vehicles"
         <?php if ($cat === $active) echo 'aria-current="true"'; ?>><?= e($cat) ?></a>
    <?php } ?>
  </div>

  <div class="carousel">

    <button class="arrow arrow-side arrow-left" type="button" disabled aria-label="Previous vehicles">&#8592;</button>

    <div class="cars">
      <?php foreach ($shown as $car) { ?>

        <article class="car">

          <div class="car-photo">
            <span class="car-tag"><?= e($car['type']) ?></span>
            <img src="<?= e($car['img']) ?>" alt="<?= e($car['name']) ?>" loading="lazy">
          </div>

          <div class="car-top">
            <h3><?= e($car['name']) ?></h3>
            <p class="car-price">
              <span class="amount">&#8369;<?= number_format($car['price']) ?></span>
              <span class="per">per day</span>
            </p>
          </div>

          <div class="car-specs">
            <span>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 12l3.5-3"/><path d="M12 3.5v2"/></svg>
              Auto
            </span>
            <span>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="8" r="3.4"/><path d="M5.5 20a6.5 6.5 0 0 1 13 0"/></svg>
              <?= e($car['seats']) ?>
            </span>
          </div>

          <a class="book" href="index.php?car=<?= urlencode($car['name']) ?>#booking">Book Now <span class="book-arrow" aria-hidden="true">&#8599;</span></a>

        </article>

      <?php } ?>

      <?php if (count($shown) === 0) { ?>
        <p class="no-cars">No <?= e($active) ?> units available yet.</p>
      <?php } ?>
    </div>

    <button class="arrow arrow-side arrow-right" type="button" disabled aria-label="Next vehicles">&#8594;</button>

  </div>

  <!-- these arrows only show up under 820px, the side ones hide -->
  <div class="controls">
    <button class="arrow arrow-inline" type="button" disabled aria-label="Previous vehicles">&#8592;</button>

    <div class="dots" aria-hidden="true">
      <span class="dot on"></span>
    </div>

    <button class="arrow arrow-inline" type="button" disabled aria-label="Next vehicles">&#8594;</button>
  </div>

</section>

<!-- promo banner -->
<section class="promo" id="promo">
  <div class="promo-inner">

    <div class="promo-text">
      <p class="promo-eyebrow">Dumaguete City Car Rentals</p>

      <h2 class="promo-title">
        Book now to get a
        <span>special discount!</span>
      </h2>

      <p class="promo-desc">
        Island roads, waterfalls and dive spots are waiting. Reserve early
        and save on your Dumaguete trip. Free cancellation up to 24 hours
        before pick-up.
      </p>

      <a class="promo-btn" href="#booking">Book Now <span aria-hidden="true">&#8599;</span></a>
    </div>

    <div class="promo-photo">
      <img src="images/promo-bg.png" alt="Coastline near Dumaguete City" loading="lazy">
    </div>

  </div>
</section>

<!-- booking request -->
<section class="booking" id="booking">

  <div class="booking-head">
    <h2>Reserve a <span>Unit</span></h2>
    <p>Fill in the form and we will get back to you to confirm the schedule. No payment is taken on this form.</p>
  </div>

  <div class="bk-inner">

    <?php if ($sent) { ?>

      <!-- shows instead of the form once everything passed -->
      <div class="ok">

        <div class="ok-head">
          <span class="tick" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 13l4 4 10-10"/></svg>
          </span>
          <h3>Thanks, <?= e($form['name']) ?>.</h3>
        </div>

        <p>We received your request. Expect a reply at <?= e($form['email']) ?> or a call on <?= e($form['phone']) ?>.</p>

        <div class="sum">
          <div class="sum-row"><span>Vehicle</span><span><?= e($form['car']) ?></span></div>
          <div class="sum-row"><span>Pick-up point</span><span><?= e($form['pickup']) ?></span></div>
          <div class="sum-row"><span>Rental length</span><span><?= e($form['days']) ?> day(s)</span></div>
          <div class="sum-row"><span>Driver age</span><span><?= e($form['age']) ?></span></div>
          <?php if ($form['notes'] !== '') { ?>
            <div class="sum-row"><span>Notes</span><span><?= e($form['notes']) ?></span></div>
          <?php } ?>
          <div class="sum-row total"><span>Estimated total</span><span>&#8369;<?= number_format($total) ?></span></div>
        </div>

        <p class="fine">Estimate only, based on &#8369;<?= number_format($rate) ?> per day. Final rate is confirmed by our staff.</p>

        <a class="ok-back" href="index.php#booking">Send another request</a>

      </div>

    <?php } else { ?>

      <!-- the form itself, values stay put if something failed -->
      <form class="bk-form" action="index.php#booking" method="post">

        <?php if (count($errors) > 0) { ?>
          <div class="errbox" role="alert">
            <p>Please fix the following before sending:</p>
            <ul>
              <?php foreach ($errors as $message) { ?>
                <li><?= $message ?></li>
              <?php } ?>
            </ul>
          </div>
        <?php } ?>

        <div class="fields">

          <div class="field">
            <label for="bk-name">Full Name</label>
            <input type="text" id="bk-name" name="name" value="<?= e($form['name']) ?>" maxlength="60"<?php if (isset($errors['name'])) echo ' class="bad" aria-invalid="true"'; ?>>
            <?php if (isset($errors['name'])) { ?>
              <p class="err"><?= $errors['name'] ?></p>
            <?php } ?>
          </div>

          <div class="field">
            <label for="bk-email">Email Address</label>
            <input type="email" id="bk-email" name="email" value="<?= e($form['email']) ?>"<?php if (isset($errors['email'])) echo ' class="bad" aria-invalid="true"'; ?>>
            <?php if (isset($errors['email'])) { ?>
              <p class="err"><?= $errors['email'] ?></p>
            <?php } ?>
          </div>

          <div class="field">
            <label for="bk-phone">Mobile Number</label>
            <input type="tel" id="bk-phone" name="phone" value="<?= e($form['phone']) ?>" placeholder="09XX XXX XXXX"<?php if (isset($errors['phone'])) echo ' class="bad" aria-invalid="true"'; ?>>
            <?php if (isset($errors['phone'])) { ?>
              <p class="err"><?= $errors['phone'] ?></p>
            <?php } ?>
          </div>

          <div class="field">
            <label for="bk-car">Vehicle</label>
            <select id="bk-car" name="car"<?php if (isset($errors['car'])) echo ' class="bad" aria-invalid="true"'; ?>>
              <option value="">Choose a unit</option>
              <?php foreach ($cars as $car) { ?>
                <option value="<?= e($car['name']) ?>"<?php if ($car['name'] === $form['car']) echo ' selected'; ?>><?= e($car['name']) ?> &mdash; &#8369;<?= number_format($car['price']) ?>/day</option>
              <?php } ?>
            </select>
            <?php if (isset($errors['car'])) { ?>
              <p class="err"><?= $errors['car'] ?></p>
            <?php } ?>
          </div>

          <div class="field">
            <label for="bk-pickup">Pick-up Point</label>
            <select id="bk-pickup" name="pickup"<?php if (isset($errors['pickup'])) echo ' class="bad" aria-invalid="true"'; ?>>
              <option value="">Choose a pick-up point</option>
              <?php foreach ($pickups as $place) { ?>
                <option value="<?= e($place) ?>"<?php if ($place === $form['pickup']) echo ' selected'; ?>><?= e($place) ?></option>
              <?php } ?>
            </select>
            <?php if (isset($errors['pickup'])) { ?>
              <p class="err"><?= $errors['pickup'] ?></p>
            <?php } ?>
          </div>

          <div class="field">
            <label for="bk-days">Number of Days</label>
            <input type="number" id="bk-days" name="days" min="1" max="30" value="<?= e($form['days']) ?>"<?php if (isset($errors['days'])) echo ' class="bad" aria-invalid="true"'; ?>>
            <?php if (isset($errors['days'])) { ?>
              <p class="err"><?= $errors['days'] ?></p>
            <?php } ?>
          </div>

          <div class="field">
            <label for="bk-age">Driver Age</label>
            <select id="bk-age" name="age"<?php if (isset($errors['age'])) echo ' class="bad" aria-invalid="true"'; ?>>
              <option value="">Select bracket</option>
              <?php foreach ($ages as $bracket) { ?>
                <option value="<?= e($bracket) ?>"<?php if ($bracket === $form['age']) echo ' selected'; ?>><?= e($bracket) ?></option>
              <?php } ?>
            </select>
            <?php if (isset($errors['age'])) { ?>
              <p class="err"><?= $errors['age'] ?></p>
            <?php } ?>
          </div>

          <div class="field full">
            <label for="bk-notes">Notes for our staff</label>
            <textarea id="bk-notes" name="notes" maxlength="300" placeholder="Flight number, drop-off request, child seat, and so on."<?php if (isset($errors['notes'])) echo ' class="bad" aria-invalid="true"'; ?>><?= e($form['notes']) ?></textarea>
            <?php if (isset($errors['notes'])) { ?>
              <p class="err"><?= $errors['notes'] ?></p>
            <?php } ?>
          </div>

        </div>

        <div class="agree">
          <input type="checkbox" id="bk-license" name="license" value="1"<?php if ($license) echo ' checked'; ?>>
          <label for="bk-license">I hold a valid driver&rsquo;s license and will present it with one valid ID on pick-up.</label>
        </div>
        <?php if (isset($errors['license'])) { ?>
          <p class="err"><?= $errors['license'] ?></p>
        <?php } ?>

        <button class="sendbtn" type="submit">Send Request</button>

        <p class="form-fine">We only use your details to contact you about this booking.</p>

      </form>

    <?php } ?>

    <!-- navy panel on the right -->
    <aside class="bk-note">
      <h3>How it works</h3>

      <ul class="steps">
        <?php $n = 1; ?>
        <?php foreach ($steps as $step) { ?>
          <li>
            <span class="num" aria-hidden="true"><?= $n ?></span>
            <div>
              <h4><?= $step[0] ?></h4>
              <p><?= $step[1] ?></p>
            </div>
          </li>
          <?php $n++; ?>
        <?php } ?>
      </ul>

      <p class="note-call">Prefer to talk to someone? Call <a href="tel:+639123456789">+63 912 345 6789</a>.</p>
    </aside>

  </div>

</section>

</main>

<!-- footer -->
<footer class="footer">

  <div class="footer-top">

    <div class="footer-brand">
      <img src="images/shift-logo-white-transparent.png" alt="Shift Car Rental">
      <p class="footer-tag">Car Rental</p>

      <!-- letters for now, no icon files yet -->
      <div class="footer-social">
        <a href="#" aria-label="Facebook">f</a>
        <a href="#" aria-label="Instagram">ig</a>
        <a href="#" aria-label="LinkedIn">in</a>
      </div>
    </div>

    <nav class="footer-col" aria-label="Quick links">
      <h2>Quick Links</h2>
      <ul>
        <li><a href="#booking">Reserve a Unit</a></li>
        <li><a href="#">Terms and Conditions</a></li>
        <li><a href="#">Fees and Charges Guide</a></li>
        <li><a href="#our-vehicles">Vehicles</a></li>
        <li><a href="#">FAQ</a></li>
      </ul>
    </nav>

    <div class="footer-col">
      <h2>Contact</h2>
      <ul>
        <li><a href="tel:+63123456789">+63 12 345 6789</a></li>
        <li><a href="tel:+639123456789">+63 912 345 6789</a></li>
        <li><a href="mailto:hello@shiftcarrental.ph">hello@shiftcarrental.ph</a></li>
      </ul>
    </div>

    <div class="footer-col footer-locations">
      <h2>Locations</h2>
      <p>Rizal Boulevard, Dumaguete City, Negros Oriental 6200 &middot; Mon&ndash;Sun 7:00 am &ndash; 11:00 pm</p>
      <p>Sibulan, Negros Oriental &middot; Mon&ndash;Sun 7:00 am &ndash; 6:00 pm</p>
      <p>Valencia, Negros Oriental &middot; Mon&ndash;Sun 7:00 am &ndash; 6:00 pm</p>
    </div>

  </div>

  <div class="footer-bottom">
    <p>&copy; 2026 SHIFT Car Rental &mdash; All Rights Reserved</p>
    <img class="footer-mark" src="images/standalone-footer-logo.png" alt="">
  </div>

</footer>

</body>
</html>
