<?php
require 'auth.php';
require 'database/config.php';

// mga options sa dropdown ug filter pills
$categories = ['All', 'Hatchback', 'Sedan', 'SUV', 'MPV'];

// kinahanglan pareho ni sa lista sa book.php ug function.php
$pickups = ['Sibulan Airport', 'Rizal Boulevard', 'Valencia', 'Dauin', 'Bacong'];
$ages    = ['21-24', '25-29', '30-64', '65+'];

// numero nga i-call para sa mga pangutana
$phone = '+63 912 345 6789';
$phoneLink = 'tel:+639123456789';

// mga branch nga makita sa locations strip, plain text para maka-trabaho ang e()
$branches = [
  ['name' => 'Sibulan Airport', 'note' => 'Meet & greet at arrivals',  'img' => 'images/loc-sibulan.png'],
  ['name' => 'Rizal Boulevard', 'note' => 'Dumaguete City seaside hub', 'img' => 'images/loc-rizal.png'],
  ['name' => 'Valencia',        'note' => 'Highland pick-up point',     'img' => 'images/loc-valencia.png'],
  ['name' => 'Dauin',           'note' => 'Dive-resort coast branch',   'img' => 'images/loc-dauin.png'],
  ['name' => 'Bacong',          'note' => 'South coast pick-up point',  'img' => 'images/loc-bacong.png'],
];

// google reviews, unom para naa duha ka desktop page ang slider
$feedback = [
  ['name' => 'Miguel Torres', 'role' => 'Apo Island Weekender', 'when' => '2 weeks ago',
   'text' => 'The car was waiting for us right at Sibulan Airport arrivals, five minutes after landing we were already on the road to Dauin. Effortless from start to finish.'],
  ['name' => 'Anna Reyes', 'role' => 'Local Renter', 'when' => '1 month ago',
   'text' => 'The price I saw on the site was the exact price I paid. No surprise insurance add-ons, no fuel games. Best rental deal in Dumaguete, hands down.'],
  ['name' => 'James Whitmore', 'role' => 'Visitor from Australia', 'when' => '2 months ago',
   'text' => 'Flat tire on the mountain road up to Valencia. One call and the roadside team had us moving again within the hour. That kind of backup is worth everything.'],
  ['name' => 'Grace Villanueva', 'role' => 'Family Trip', 'when' => '2 months ago',
   'text' => 'Booked the Innova for a week with two kids in tow. Clean unit, cold aircon, child seat ready on pick-up. We just drove and enjoyed the island.'],
  ['name' => 'Daniel Cruz', 'role' => 'Business Traveller', 'when' => '3 months ago',
   'text' => 'Late flight into Sibulan and they still met me at arrivals. Paperwork took maybe ten minutes. This is now my default rental in Negros Oriental.'],
  ['name' => 'Sofia Lim', 'role' => 'Valencia Day Tripper', 'when' => '4 months ago',
   'text' => 'Rented the Swift for a Casaroro Falls run. Sharp handling on the climb and the tank was full. Returning it was just as painless as picking it up.'],
];

// asa nga filter pill ang naka-on
$active = 'All';
if (isset($_GET['category']) && in_array($_GET['category'], $categories, true)) {
  $active = $_GET['category'];
}

$pdo = getConnection();

/* i-alias ang bag_large/bag_small ngadto sa bagL/bagS
   para dili na usbon ang markup sa car cards sa ubos */
$cols = "id, name, type, price, gear, seats, doors,
         bag_large AS bagL, bag_small AS bagS, kids, aircon, img";

if ($active === 'All') {
  $stmt = $pdo->prepare("SELECT $cols FROM cars WHERE available = 1 ORDER BY type, price");
} else {
  $stmt = $pdo->prepare("SELECT $cols FROM cars WHERE available = 1 AND type = :type ORDER BY price");
  $stmt->bindValue(':type', $active);
}

$stmt->execute();
$shown = $stmt->fetchAll();

// ---- diri na mo-sugod ang output ----
$pageTitle = 'Shift Car Rental — Dumaguete City, Sibulan & Valencia';
require 'header.php';
?>

<main>

<!-- hero, apil ang puti nga search box -->
<section class="hero">

  <p class="reviews">
    <span class="st" aria-hidden="true">★★★★★</span> 4.9/5 · based on 1,989 Google reviews
  </p>

  <h1>Dumaguete City / Sibulan / Valencia <span>Car Rental.</span></h1>

  <p class="desc">
    Self-drive rentals across Dumaguete City, Sibulan and Valencia.
    Pick a unit, reserve it online, and we confirm your schedule
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
          <?php foreach ($pickups as $place) { ?>
            <!-- parehas ra nga lista sa pick-up, para dili ko mag-edit duha ka lugar -->
            <option><?= e($place) ?></option>
          <?php } ?>
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
      <div><a href="bookings.php">Already booked? <u>Manage your booking</u></a></div>
    </div>

  </form>
</section>

<!-- our vehicles -->
<section class="vehicles" id="our-vehicles">

  <h2 class="vehicles-title">Our <span>Vehicles</span></h2>

  <!-- filter pills, link ra ni balik sa parehas nga page -->
  <div class="filters">
    <?php foreach ($categories as $cat) { ?>
      <a class="filter-tab<?php if ($cat === $active) echo ' is-active'; ?>"
         href="index.php?category=<?= urlencode($cat) ?>#our-vehicles"
         <?php if ($cat === $active) echo 'aria-current="true"'; ?>><?= e($cat) ?></a>
    <?php } ?>
  </div>

  <!-- arrow, tulo ka cards, arrow -->
  <div class="carousel" id="carCarousel" aria-roledescription="carousel" aria-label="Available vehicles">

    <button class="arrow" type="button" data-dir="prev" aria-label="Previous vehicles">&#8592;</button>

    <div class="slide-view">
      <div class="slide-row" id="carRow">
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
              <?= spec('passenger', $car['seats'] . ' Passenger') ?>
              <?= spec('doors',     $car['doors'] . ' Doors') ?>
              <?= spec('bagL',      $car['bagL'] . ' Large Bags') ?>
              <?= spec('bagS',      $car['bagS'] . ' Small Bags') ?>
              <?= spec('kids',      $car['kids'] . ' Children') ?>
              <?= spec('gear',      $car['gear']) ?>
              <?php if ($car['aircon']) { ?>
                <?= spec('aircon', 'Airconditioning') ?>
              <?php } ?>
            </div>

            <!-- dala na ang car_id paingon sa booking form -->
            <a class="book" href="book.php?car_id=<?= e($car['id']) ?>">
              <span class="label">Book Now <span class="book-arrow" aria-hidden="true">&#8599;</span></span>
            </a>

          </article>

        <?php } ?>

        <?php if (count($shown) === 0) { ?>
          <p class="no-cars">No <?= e($active) ?> units available yet.</p>
        <?php } ?>
      </div>
    </div>

    <button class="arrow" type="button" data-dir="next" aria-label="Next vehicles">&#8594;</button>

  </div>

  <!-- dots ra dinhi, ang arrows naa sa kilid sa cards -->
  <div class="controls" id="carControls">
    <div class="dots" id="carDots" aria-hidden="true"></div>
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

      <a class="promo-btn" href="index.php#our-vehicles">Book Now <span aria-hidden="true">&#8599;</span></a>
    </div>

    <div class="promo-photo">
      <img src="images/promo-bg.png" alt="Coastline near Dumaguete City" loading="lazy">
    </div>

  </div>
</section>

<!-- our locations -->
<section class="locations" id="locations">

  <div class="sec-head">
    <h2>Our <span>Locations</span></h2>
    <p>Pick-up and delivery across Negros Oriental</p>
  </div>

  <div class="loc-grid">
    <?php foreach ($branches as $branch) { ?>

      <!-- ang photo mo-fill sa tibuok card, ang ngalan naa sa ibabaw niini -->
      <article class="loc">
        <img src="<?= e($branch['img']) ?>" alt="<?= e($branch['name']) ?> pick-up point" loading="lazy">

        <div class="loc-body">
          <span class="loc-pin" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.4"/></svg>
          </span>
          <h3><?= e($branch['name']) ?></h3>
          <p><?= e($branch['note']) ?></p>
        </div>
      </article>

    <?php } ?>
  </div>

</section>

<!-- recent reviews, parehas ra nga slider parts sa vehicles sa taas -->
<section class="reviews-sec" id="reviews">

  <div class="sec-head">
    <h2>Recent <span>Reviews</span></h2>

    <div class="rv-rating">
      <span class="rv-big">4.9</span>
      <span class="rv-stars" aria-label="4.9 out of 5 stars"><span aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span></span>
      <span class="rv-count">from 1,989 Google reviews</span>
    </div>
  </div>

  <div class="carousel" id="rvCarousel" aria-roledescription="carousel" aria-label="Recent reviews">

    <button class="arrow" type="button" data-dir="prev" aria-label="Previous reviews">&#8592;</button>

    <div class="slide-view">
      <div class="slide-row" id="rvRow">
        <?php foreach ($feedback as $note) { ?>

          <article class="rv">

            <div class="rv-who">
              <!-- unang letra sa ngalan, puli sa profile photo -->
              <span class="rv-initial" aria-hidden="true"><?= e(strtoupper(substr($note['name'], 0, 1))) ?></span>

              <div class="rv-name">
                <h3><?= e($note['name']) ?><span class="rv-check" title="Verified renter">&#10003;</span></h3>
                <p><?= e($note['role']) ?> &middot; <?= e($note['when']) ?></p>
              </div>

              <span class="rv-g" role="img" aria-label="Google review"><?= googleMark() ?></span>
            </div>

            <p class="rv-stars" aria-label="5 out of 5 stars">
              <span aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
            </p>

            <p class="rv-text">&ldquo;<?= e($note['text']) ?>&rdquo;</p>

          </article>

        <?php } ?>
      </div>
    </div>

    <button class="arrow" type="button" data-dir="next" aria-label="Next reviews">&#8594;</button>

  </div>

  <div class="controls" id="rvControls">
    <div class="dots" id="rvDots" aria-hidden="true"></div>
  </div>

  <!-- plain text ra, walay link paingon maps -->
  <p class="rv-more">&amp; 1,900+ more <span>Google reviews</span></p>

</section>

</main>

<?php require 'footer.php'; ?>
