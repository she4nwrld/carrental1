<?php
require 'auth.php';
require 'database/config.php';
require_once __DIR__ . '/helpers.php';

// usa ra ka lista karon, gikan sa helpers.php
$categories = carCategories();
$pickups    = pickupPoints();
$ages       = ageBrackets();

// asa nga filter pill ang naka-on
$active = 'All';
if (isset($_GET['category']) && in_array($_GET['category'], $categories, true)) {
  $active = $_GET['category'];
}

$pdo = getConnection();

/* Sep 22 – Sep 24, 2026 */
function niceRange(string $from, string $to): string
{
  return date('M j', strtotime($from)) . ' – ' . date('M j, Y', strtotime($to));
}

/*  kuhaa ug i-validate ang gikan sa search box */

$pickupDate = trim($_GET['pickup_date'] ?? '');
$returnDate = trim($_GET['return_date'] ?? '');

$dateOk = isYmd($pickupDate) && isYmd($returnDate) && tripDays($pickupDate, $returnDate) > 0;

$dateError = '';
if (($pickupDate !== '' || $returnDate !== '') && !$dateOk) {
  $dateError = 'Please choose a pick-up date and a later return date.';
} elseif ($dateOk && daysUntil($pickupDate) < 0) {
  $dateOk    = false;
  $dateError = 'Pick-up date cannot be in the past.';
}

$days = $dateOk ? tripDays($pickupDate, $returnDate) : 0;

// i-check batok sa lista, dili basta-basta dawaton ang gikan sa URL
$pickupLoc = (isset($_GET['pickup_location']) && in_array($_GET['pickup_location'], $pickups, true))
  ? $_GET['pickup_location'] : '';

$returnLoc = (isset($_GET['return_location']) && in_array($_GET['return_location'], $pickups, true))
  ? $_GET['return_location'] : '';

$driverAge = (isset($_GET['driver_age']) && in_array($_GET['driver_age'], $ages, true))
  ? $_GET['driver_age'] : '';

$delivery = (isset($_GET['delivery']) && $_GET['delivery'] === '1') ? '1' : '';

/*  promo code  */
$promoCode  = strtoupper(trim($_GET['discount_code'] ?? ''));
$found      = findPromos($pdo, $promoCode);
$promoNotes = [];
$promoOk    = false;

if ($promoCode !== '') {
  foreach ($found['unknown'] as $bad) {
    $promoNotes[] = ['err', 'Code "' . $bad . '" does not exist.'];
  }

  $issues = promoIssues($found['promos'], $days, $dateOk ? $pickupDate : null);

  if ($issues) {
    foreach ($issues as $msg) {
      $promoNotes[] = ['err', $msg];
    }
  } elseif ($found['promos']) {
    $titles = [];
    foreach ($found['promos'] as $p) { $titles[] = $p['code'] . ' — ' . $p['title']; }
    $promoNotes[] = ['ok', 'Applied: ' . implode(' + ', $titles)];
    $promoOk = true;
  }
}

/*  query  */

/* parehas nga alias sa index.php para magamit ra ang spec() helper */
$cols = "id, name, type, price, gear, seats, doors,
         bag_large AS bagL, bag_small AS bagS, kids, aircon, img";

if ($dateOk) {
  $cols .= ", (SELECT COUNT(*) FROM bookings bk
               WHERE bk.car_id = cars.id
                 AND bk.status IN ('pending','confirmed')
                 AND bk.pickup_date < :ret
                 AND bk.return_date > :pick) AS taken";
}

$sql    = "SELECT $cols FROM cars WHERE available = 1";
$params = [];

if ($dateOk) {
  $params[':ret']  = $returnDate;
  $params[':pick'] = $pickupDate;
}

if ($active !== 'All') {
  $sql .= " AND type = :type";
  $params[':type'] = $active;
}

$sql .= ($active === 'All') ? " ORDER BY type, price" : " ORDER BY price";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$shown = $stmt->fetchAll();

/* pila ang libre  para tinuod ang gisulti sa summary line */
$freeCount = 0;
foreach ($shown as $row) {
  if (!$dateOk || (int) ($row['taken'] ?? 0) === 0) { $freeCount++; }
}


$trip = [];
if ($dateOk)           { $trip['pickup_date'] = $pickupDate;
                         $trip['return_date'] = $returnDate; }
if ($pickupLoc !== '') { $trip['pickup_location'] = $pickupLoc; }
if ($returnLoc !== '') { $trip['return_location'] = $returnLoc; }
if ($driverAge !== '') { $trip['driver_age'] = $driverAge; }
if ($delivery !== '')  { $trip['delivery'] = $delivery; }
if ($promoCode !== '') { $trip['discount_code'] = $promoCode; }

$tripQs = $trip ? '&' . http_build_query($trip) : '';

$pageTitle = 'Our Vehicles — Shift Car Rental';
require 'header.php';
?>

<main class="page-main">

  <section class="page-hero" id="vehicles-hero">
    <h1>Our <span>Vehicles</span></h1>
    <p>Every unit in our Dumaguete fleet — pick a category, compare specs, and book online.</p>
  </section>

  <section class="vehicles page-vehicles" id="all-vehicles">

    <!-- summary sa gipangita, makita lang kung naay gi-search -->
    <?php if ($dateOk || $dateError !== '' || $promoNotes || $pickupLoc !== '') { ?>
      <div class="search-summary">
        <?php if ($dateError !== '') { ?>
          <p class="err"><?= e($dateError) ?></p>
        <?php } ?>

        <?php if ($dateOk) { ?>
          <p><strong><?= (int) $freeCount ?></strong> of <?= count($shown) ?>
             units free from <strong><?= e($pickupDate) ?></strong>
             to <strong><?= e($returnDate) ?></strong>
             (<?= (int) $days ?> <?= $days === 1 ? 'day' : 'days' ?>)<?php
             if ($pickupLoc !== '') { ?>, pick-up at <strong><?= e($pickupLoc) ?></strong><?php } ?>.</p>
        <?php } elseif ($pickupLoc !== '') { ?>
          <p>Pick-up at <strong><?= e($pickupLoc) ?></strong>.</p>
        <?php } ?>

        <?php foreach ($promoNotes as $note) { ?>
          <p class="<?= e($note[0]) ?>"><?= e($note[1]) ?></p>
        <?php } ?>
      </div>
    <?php } ?>

    <!-- filter pills, gidala na ang petsa ug promo -->
    <div class="filters">
      <?php foreach ($categories as $cat) { ?>
        <a class="filter-tab<?php if ($cat === $active) echo ' is-active'; ?>"
           href="vehicles.php?category=<?= urlencode($cat) ?><?= e($tripQs) ?>#all-vehicles"
           <?php if ($cat === $active) echo 'aria-current="true"'; ?>><?= e($cat) ?></a>
      <?php } ?>
    </div>

    <!-- grid, dili carousel — tanan cars makita dayon -->
    <div class="cars-grid">
      <?php foreach ($shown as $car) { ?>
        <?php
          /* naka-book na ba ni nga unit sa gipili nga petsa? */
          $taken = $dateOk && (int) ($car['taken'] ?? 0) > 0;

          /* kung kompleto ang petsa ug libre pa, ipakita ang tinuod nga total */
          $quote = ($dateOk && !$taken)
            ? quotePrice($promoOk ? $found['promos'] : [], (int) $car['price'],
                         $days, $delivery === '1', $pickupDate)
            : null;
        ?>

        <article class="car<?= $taken ? ' car-taken' : '' ?>">

          <div class="car-photo">
            <span class="car-tag"><?= e($car['type']) ?></span>
            <?php if ($taken) { ?>
              <span class="car-booked">Booked</span>
            <?php } ?>
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

          <?php if ($taken) { ?>
            <p class="car-quote car-quote-off">
              Already reserved for <?= e(niceRange($pickupDate, $returnDate)) ?>.
              <em>Try different dates to book this unit.</em>
            </p>
          <?php } elseif ($quote) { ?>
            <p class="car-quote">
              <?= (int) $days ?> <?= $days === 1 ? 'day' : 'days' ?>:
              <strong><?= e(peso($quote['total'])) ?></strong>
              <?php if ($quote['discount'] > 0 || $quote['notes']) { ?>
                <em><?= e(implode(', ', $quote['notes'])) ?></em>
              <?php } ?>
            </p>
          <?php } ?>

          <?php if ($taken) { ?>
            <span class="label label-off">Not available for these dates</span>
          <?php } else { ?>
            <!-- dala na ang car_id ug ang tibuok search paingon sa booking form -->
            <a class="book" href="book.php?car_id=<?= (int) $car['id'] ?><?= e($tripQs) ?>">
              <span class="label">Book Now <span class="book-arrow" aria-hidden="true">&#8599;</span></span>
            </a>
          <?php } ?>

        </article>

      <?php } ?>

      <?php if (count($shown) === 0) { ?>
        <p class="no-cars">No <?= e($active) ?> units in the fleet yet.</p>
      <?php } ?>
    </div>

  </section>

</main>

<?php require 'footer.php'; ?>
