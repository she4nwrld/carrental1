<?php
require 'auth.php';
require 'database/config.php';
require 'helpers.php';

requireLogin();

$pdo = getConnection();

$carId = filter_input(INPUT_GET, 'car_id', FILTER_VALIDATE_INT);
if (!$carId) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM cars WHERE id = :id AND available = 1");
$stmt->bindValue(':id', $carId, PDO::PARAM_INT);
$stmt->execute();
$car = $stmt->fetch();

if (!$car) {
    header('Location: index.php?missing=1');
    exit;
}

$pickups = pickupPoints();
$ages    = ageBrackets();

$errors = $_SESSION['booking_errors'] ?? [];
$old    = $_SESSION['booking_old'] ?? [];
unset($_SESSION['booking_errors'], $_SESSION['booking_old']);

/* ---------- prefill: session first, then the URL from the search form ---------- */
function old(array $old, string $key, string $fallback = ''): string {
    if (isset($old[$key]) && $old[$key] !== '') {
        return (string) $old[$key];
    }
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        return (string) $_GET[$key];
    }
    return $fallback;
}

$f = [
    'pickup_location' => old($old, 'pickup_location', $pickups[0]),
    'return_location' => old($old, 'return_location', $pickups[0]),
    'pickup_date'     => old($old, 'pickup_date'),
    'return_date'     => old($old, 'return_date'),
    'driver_age'      => old($old, 'driver_age', $ages[1]),
    'discount_code'   => strtoupper(trim(old($old, 'discount_code'))),
    'delivery'        => old($old, 'delivery') === '1',
];

/* ---------- server-side quote ---------- */
$days  = tripDays($f['pickup_date'], $f['return_date']);
$found = findPromos($pdo, $f['discount_code']);
$quote = quotePrice($found['promos'], (int)$car['price'], $days, $f['delivery'], $f['pickup_date']);

$promoErrors = $quote['errors'];
foreach ($found['unknown'] as $bad) {
    $promoErrors[] = '"' . $bad . '" is not a valid discount code.';
}

$busy = ($days > 0 && !carIsFree($pdo, $carId, $f['pickup_date'], $f['return_date']));

$pageTitle = 'Book ' . $car['name'] . ' — Shift Car Rental';
require 'header.php';
?>

<main class="book-main">
  <section class="book-wrap">

    <aside class="book-summary">
      <div class="book-photo">
        <img src="<?= e($car['img']) ?>" alt="<?= e($car['name']) ?>">
      </div>
      <h2><?= e($car['name']) ?></h2>
      <p class="book-type"><?= e($car['type']) ?> · <?= e($car['gear']) ?></p>
      <div class="book-specs">
        <?= spec('passenger', $car['seats'] . ' Passenger') ?>
        <?= spec('doors',     $car['doors'] . ' Doors') ?>
        <?= spec('bagL',      $car['bag_large'] . ' Large Bags') ?>
        <?= spec('bagS',      $car['bag_small'] . ' Small Bags') ?>
        <?php if ($car['aircon']) { ?>
          <?= spec('aircon', 'Airconditioning') ?>
        <?php } ?>
      </div>
      <p class="book-rate">
        <strong>&#8369;<?= e(number_format($car['price'])) ?></strong> / day
      </p>
    </aside>

    <div class="book-form-side">
      <h1>Reserve this vehicle</h1>
      <p class="book-sub">Fill in your trip details. We'll confirm within 24 hours.</p>

      <?php if (!empty($errors)) { ?>
        <div class="auth-errors" role="alert">
          <ul>
            <?php foreach ($errors as $err) { ?>
              <li><?= e($err) ?></li>
            <?php } ?>
          </ul>
        </div>
      <?php } ?>

      <?php if ($busy) { ?>
        <div class="auth-errors" role="alert">
          <ul>
            <li>This unit is already reserved for those dates. Please pick other
                dates or <a href="index.php#our-vehicles">choose another car</a>.</li>
          </ul>
        </div>
      <?php } ?>

      <form method="POST" action="function.php" class="book-form" id="bookForm"
            data-rate="<?= e($car['price']) ?>"
            data-delivery="<?= e(deliveryFee()) ?>"
            data-promos='<?= promoJson($pdo) ?>'>
        <input type="hidden" name="action" value="create_booking">
        <input type="hidden" name="car_id" value="<?= e($car['id']) ?>">

        <div class="book-row">
          <div class="auth-field">
            <label for="pickup_location">Pick-up Location</label>
            <select id="pickup_location" name="pickup_location" required>
              <?php foreach ($pickups as $place) { ?>
                <option value="<?= e($place) ?>"
                  <?= $f['pickup_location'] === $place ? 'selected' : '' ?>>
                  <?= e($place) ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="auth-field">
            <label for="return_location">Return Location</label>
            <select id="return_location" name="return_location" required>
              <?php foreach ($pickups as $place) { ?>
                <option value="<?= e($place) ?>"
                  <?= $f['return_location'] === $place ? 'selected' : '' ?>>
                  <?= e($place) ?>
                </option>
              <?php } ?>
            </select>
          </div>
        </div>

        <div class="book-row">
          <div class="auth-field">
            <label for="pickup_date">Pick-up Date</label>
            <input type="date" id="pickup_date" name="pickup_date"
                   min="<?= e(today()) ?>"
                   value="<?= e($f['pickup_date']) ?>" required>
          </div>

          <div class="auth-field">
            <label for="return_date">Return Date</label>
            <input type="date" id="return_date" name="return_date"
                   min="<?= e($f['pickup_date'] !== '' ? $f['pickup_date'] : today()) ?>"
                   value="<?= e($f['return_date']) ?>" required>
          </div>
        </div>

        <div class="book-row">
          <div class="auth-field">
            <label for="driver_age">Driver's Age</label>
            <select id="driver_age" name="driver_age" required>
              <?php foreach ($ages as $age) { ?>
                <option value="<?= e($age) ?>"
                  <?= $f['driver_age'] === $age ? 'selected' : '' ?>>
                  <?= e($age) ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="auth-field book-check">
            <label class="book-checkbox">
              <input type="checkbox" name="delivery" value="1"
                     <?= $f['delivery'] ? 'checked' : '' ?>>
              <span>Deliver the car to me (&#8369;<?= e(number_format(deliveryFee())) ?>)</span>
            </label>
          </div>
        </div>

        <!-- ---------- discount code ---------- -->
        <div class="auth-field promo-field">
          <label for="discount_code">Discount code <span class="muted">(optional)</span></label>
          <div class="promo-row">
            <input type="text" id="discount_code" name="discount_code"
                   value="<?= e($f['discount_code']) ?>"
                   placeholder="e.g. EARLY10" maxlength="45" autocomplete="off">
            <button type="submit" class="btn-ghost"
                    formaction="book.php" formmethod="GET" formnovalidate>Apply</button>
          </div>

          <?php foreach ($promoErrors as $pe) { ?>
            <p class="field-error"><?= e($pe) ?></p>
          <?php } ?>

          <?php if (!empty($quote['applied'])) { ?>
            <p class="field-ok">
              <?= e(implode(' + ', $quote['applied'])) ?> applied —
              <?= e(implode(', ', $quote['notes'])) ?>
            </p>
          <?php } ?>
        </div>

        <!-- ---------- running total ---------- -->
        <div class="book-total" id="book-total">
          <ul class="quote">
            <li><span>Daily rate</span><span><?= e(peso($car['price'])) ?></span></li>
            <li>
              <span id="q-days"><?= e(($quote['billable_days'] ?: $days)) ?> day(s)</span>
              <span id="q-sub"><?= e(peso($quote['subtotal'])) ?></span>
            </li>
            <li id="q-disc-row"<?= $quote['discount'] ? '' : ' hidden' ?>>
              <span>Discount</span>
              <span id="q-disc">&minus;<?= e(peso($quote['discount'])) ?></span>
            </li>
            <li id="q-del-row"<?= $quote['delivery'] ? '' : ' hidden' ?>>
              <span>Delivery</span>
              <span id="q-del"><?= e(peso($quote['delivery'])) ?></span>
            </li>
            <li class="quote-total">
              <span>Estimated total</span>
              <strong id="total-amount"><?= e(peso($quote['total'])) ?></strong>
            </li>
          </ul>
        </div>

        <button type="submit" class="auth-btn" <?= $busy ? 'disabled' : '' ?>>
          Confirm Booking
        </button>
        <p class="auth-alt"><a href="index.php#our-vehicles">Choose a different car</a></p>
      </form>
    </div>

  </section>
</main>

<?php require 'footer.php'; ?>
