<?php
require 'auth.php';
require 'database/config.php';
require 'helpers.php';

requireLogin();  // kung wala pa naka-login, i-redirect ni sa login.php

$pdo = getConnection();

// ang car_id gikan sa "Book Now" link; kinahanglan number gyud
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

// pareho ni sa options sa index.php
$pickups = ['Sibulan Airport', 'Rizal Boulevard', 'Valencia', 'Dauin', 'Bacong'];
$ages    = ['21-24', '25-29', '30-64', '65+'];

// mga error ug daan nga input gikan sa function.php (flash sa session)
$errors = $_SESSION['booking_errors'] ?? [];
$old    = $_SESSION['booking_old'] ?? [];
unset($_SESSION['booking_errors'], $_SESSION['booking_old']);

// para dili mawala ang gi-type sa user kung naay error
function old(array $old, string $key, string $fallback = ''): string {
    return $old[$key] ?? $fallback;
}

$pageTitle = 'Book ' . $car['name'] . ' — Shift Car Rental';
require 'header.php';
?>

<main class="book-main">
  <section class="book-wrap">

    <!-- wala sa tuo: summary sa gipili nga car -->
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

    <!-- tuo: ang form -->
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

      <form method="POST" action="function.php" class="book-form" novalidate>
        <input type="hidden" name="action" value="create_booking">
        <input type="hidden" name="car_id" value="<?= e($car['id']) ?>">

        <div class="book-row">
          <div class="auth-field">
            <label for="pickup_location">Pick-up Location</label>
            <select id="pickup_location" name="pickup_location" required>
              <?php foreach ($pickups as $place) { ?>
                <option value="<?= e($place) ?>"
                  <?= old($old, 'pickup_location') === $place ? 'selected' : '' ?>>
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
                  <?= old($old, 'return_location') === $place ? 'selected' : '' ?>>
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
                   value="<?= e(old($old, 'pickup_date')) ?>" required>
          </div>

          <div class="auth-field">
            <label for="return_date">Return Date</label>
            <input type="date" id="return_date" name="return_date"
                   value="<?= e(old($old, 'return_date')) ?>" required>
          </div>
        </div>

        <div class="book-row">
          <div class="auth-field">
            <label for="driver_age">Driver's Age</label>
            <select id="driver_age" name="driver_age" required>
              <?php foreach ($ages as $age) { ?>
                <option value="<?= e($age) ?>"
                  <?= old($old, 'driver_age') === $age ? 'selected' : '' ?>>
                  <?= e($age) ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="auth-field book-check">
            <label class="book-checkbox">
              <input type="checkbox" name="delivery" value="1"
                     <?= old($old, 'delivery') === '1' ? 'checked' : '' ?>>
              <span>Deliver the car to me (&#8369;500)</span>
            </label>
          </div>
        </div>

        <!-- dinhi mo-gawas ang kalkulado nga total, gikan sa js -->
        <div class="book-total" id="book-total"
             data-rate="<?= e($car['price']) ?>" data-delivery="500">
          <span>Estimated total</span>
          <strong id="total-amount">&#8369;0</strong>
        </div>

        <button type="submit" class="auth-btn">Confirm Booking</button>
        <p class="auth-alt"><a href="index.php#our-vehicles">Choose a different car</a></p>
      </form>
    </div>

  </section>
</main>

<?php require 'footer.php'; ?>
