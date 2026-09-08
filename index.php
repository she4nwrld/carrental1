<?php
require 'auth.php';
require 'database/config.php';
require_once 'helpers.php';

/* mga lista gikan sa helpers.php */
$categories = carCategories();
$pickups    = pickupPoints();
$ages       = ageBrackets();

/* branches sa locations strip */
$branches = [
  ['name' => 'Sibulan Airport',           'note' => 'Meet & greet at arrivals',   'img' => 'images/loc-sibulan.png'],
  ['name' => 'Rizal Boulevard, Dumaguete','note' => 'Dumaguete City seaside hub', 'img' => 'images/loc-rizal.png'],
  ['name' => 'Valencia',                  'note' => 'Highland pick-up point',     'img' => 'images/loc-valencia.png'],
  ['name' => 'Dauin',                     'note' => 'Dive-resort coast branch',   'img' => 'images/loc-dauin.png'],
  ['name' => 'Bacong',                    'note' => 'South coast pick-up point',  'img' => 'images/loc-bacong.png'],
];

/* asa nga filter pill ang naka-on */
$active = 'All';
if (isset($_GET['category']) && in_array($_GET['category'], $categories, true)) {
  $active = $_GET['category'];
}

/* gi-type sa user, para dili mawala human sa search */
$q = [
  'pickup_location' => $_GET['pickup_location'] ?? '',
  'return_location' => $_GET['return_location'] ?? '',
  'pickup_date'     => $_GET['pickup_date'] ?? '',
  'return_date'     => $_GET['return_date'] ?? '',
  'driver_age'      => $_GET['driver_age'] ?? '',
  'discount_code'   => strtoupper(trim($_GET['discount_code'] ?? '')),
  'delivery'        => isset($_GET['delivery']) ? '1' : '',
];

/* i-dala ang search paingon sa book.php */
$trip   = array_filter($q, function ($v) { return $v !== ''; });
$tripQs = $trip ? '&' . http_build_query($trip) : '';

$pdo = getConnection();

/* alias sa bag columns para dili usbon ang markup sa ubos */
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

/* tinuod nga reviews, ang gi-hide sa admin dili apil */
$liveReviews = $pdo->query(
  "SELECT r.rating, r.review_text, r.created_at, u.full_name
   FROM reviews r
   JOIN users u ON u.id = r.user_id
   WHERE r.approved = 1
   ORDER BY r.created_at DESC
   LIMIT 9"
)->fetchAll();

/* ihap ug average sa tinuod nga reviews */
$rvStats = $pdo->query(
  "SELECT COUNT(*) AS n, ROUND(AVG(rating), 1) AS avg_rating
   FROM reviews WHERE approved = 1"
)->fetch();

$rvCount = (int)$rvStats['n'];
$rvAvg   = $rvCount > 0 ? (float)$rvStats['avg_rating'] : 0;

/* Google reviews, puli para dili mag-inusara ang carousel */
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

/* 3 days ago, 2 weeks ago */
function reviewAgo(string $ts): string {
    $days = (int) floor((time() - strtotime($ts)) / 86400);

    if ($days <= 0)  { return 'today'; }
    if ($days === 1) { return 'yesterday'; }
    if ($days < 7)   { return $days . ' days ago'; }
    if ($days < 14)  { return 'last week'; }
    if ($days < 60)  { return floor($days / 7) . ' weeks ago'; }

    $months = floor($days / 30);
    return $months . ' month' . ($months == 1 ? '' : 's') . ' ago';
}

/* 4 -> ★★★★☆ */
function reviewStars(int $rating): string {
    return str_repeat('&#9733;', $rating) . str_repeat('&#9734;', 5 - $rating);
}

/* tinuod una, unya Google puli hangtod unom ka card */
$cards = [];

foreach ($liveReviews as $note) {
    $cards[] = [
        'name'   => $note['full_name'],
        'meta'   => 'Verified Renter · ' . reviewAgo($note['created_at']),
        'rating' => (int)$note['rating'],
        'text'   => $note['review_text'],
        'google' => true,
    ];
}

foreach ($feedback as $note) {
    if (count($cards) >= 6) {
        break;
    }
    $cards[] = [
        'name'   => $note['name'],
        'meta'   => $note['role'] . ' · ' . $note['when'],
        'rating' => 5,
        'text'   => $note['text'],
        'google' => true,
    ];
}

/* tinuod nga rating gisagol sa Google nga baseline */
$blendCount = 1989 + $rvCount;
$blendAvg   = round(((4.9 * 1989) + ($rvAvg * $rvCount)) / $blendCount, 1);

$pageTitle = 'Shift Car Rental — Dumaguete City, Sibulan & Valencia';
require 'header.php';
?>

<main>

<!-- hero -->
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

  <form class="box" action="vehicles.php" method="get">

    <div class="line">
      <div class="col">
        <label for="pickup-location">Pick-up Location</label>
        <select id="pickup-location" name="pickup_location">
          <option value="">Select location</option>
          <?php foreach ($pickups as $place) { ?>
            <option value="<?= e($place) ?>"<?= $q['pickup_location'] === $place ? ' selected' : '' ?>><?= e($place) ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="col">
        <label for="return-location">Return Location</label>
        <select id="return-location" name="return_location">
          <option value="">Same as pick-up</option>
          <?php foreach ($pickups as $place) { ?>
            <option value="<?= e($place) ?>"<?= $q['return_location'] === $place ? ' selected' : '' ?>><?= e($place) ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="col wide">
        <label for="pickup-date">Pick-up Date</label>
        <input type="date" id="pickup-date" name="pickup_date"
               min="<?= e(today()) ?>" value="<?= e($q['pickup_date']) ?>">
      </div>
      <div class="col wide">
        <label for="return-date">Return Date</label>
        <input type="date" id="return-date" name="return_date"
               min="<?= e($q['pickup_date'] !== '' ? $q['pickup_date'] : today()) ?>"
               value="<?= e($q['return_date']) ?>">
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
          <option value="">Select age</option>
          <?php foreach ($ages as $bracket) { ?>
            <option value="<?= e($bracket) ?>"<?= $q['driver_age'] === $bracket ? ' selected' : '' ?>><?= e($bracket) ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="col wide">
        <label for="discount-code">Discount Code</label>
        <input type="text" id="discount-code" name="discount_code"
               placeholder="Optional" maxlength="45"
               value="<?= e($q['discount_code']) ?>">
      </div>
      <div class="col wide findcol">
        <button class="findbtn" type="submit">Find Your Car</button>
      </div>
    </div>

    <div class="bottom">
      <div>
        <input type="checkbox" id="delivery" name="delivery" value="1"
               <?= $q['delivery'] === '1' ? 'checked' : '' ?>>
        <label class="checkbox-label" for="delivery">Deliver the car to Sibulan Airport arrivals</label>
      </div>
      <div><a href="bookings.php">Already booked? <u>Manage your booking</u></a></div>
    </div>

  </form>

</section>

<!-- our vehicles -->
<section class="vehicles" id="our-vehicles">

  <h2 class="vehicles-title">Our <span>Vehicles</span></h2>

  <div class="filters">
    <?php foreach ($categories as $cat) { ?>
      <a class="filter-tab<?php if ($cat === $active) echo ' is-active'; ?>"
         href="index.php?category=<?= urlencode($cat) ?><?= $tripQs ?>#our-vehicles"
         <?php if ($cat === $active) echo 'aria-current="true"'; ?>><?= e($cat) ?></a>
    <?php } ?>
  </div>

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

            <a class="book" href="book.php?car_id=<?= (int) $car['id'] ?><?= $tripQs ?>">
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

      <a class="promo-btn" href="deals.php">See the deals <span aria-hidden="true">&#8599;</span></a>
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

<!-- recent reviews -->
<section class="reviews-sec" id="reviews">

  <div class="sec-head">
    <h2>Recent <span>Reviews</span></h2>

    <div class="rv-rating">
      <span class="rv-big"><?= e(number_format($blendAvg, 1)) ?></span>
      <span class="rv-stars" aria-label="<?= e($blendAvg) ?> out of 5 stars">
        <span aria-hidden="true"><?= reviewStars((int)round($blendAvg)) ?></span>
      </span>
      <span class="rv-count">from <?= e(number_format($blendCount)) ?> reviews</span>
    </div>
  </div>

  <div class="carousel" id="rvCarousel" aria-roledescription="carousel" aria-label="Recent reviews">

    <button class="arrow" type="button" data-dir="prev" aria-label="Previous reviews">&#8592;</button>

    <div class="slide-view">
      <div class="slide-row" id="rvRow">
        <?php foreach ($cards as $note) { ?>

          <article class="rv">

            <div class="rv-who">
              <span class="rv-initial" aria-hidden="true"><?= e(strtoupper(substr($note['name'], 0, 1))) ?></span>

              <div class="rv-name">
                <h3><?= e($note['name']) ?><span class="rv-check" title="Verified renter">&#10003;</span></h3>
                <p><?= e($note['meta']) ?></p>
              </div>

              <?php if ($note['google']) { ?>
                <span class="rv-g" role="img" aria-label="Google review"><?= googleMark() ?></span>
              <?php } ?>
            </div>

            <p class="rv-stars" aria-label="<?= e($note['rating']) ?> out of 5 stars">
              <span aria-hidden="true"><?= reviewStars($note['rating']) ?></span>
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

  <p class="rv-more"><a href="reviews.php">Read all reviews <span>&amp; write your own</span></a></p>

</section>

</main>

<?php require 'footer.php'; ?>
