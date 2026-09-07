<?php
require 'auth.php';
require 'database/config.php';

// pareho nga categories sa index.php
$categories = ['All', 'Hatchback', 'Sedan', 'SUV', 'MPV'];

// asa nga filter pill ang naka-on
$active = 'All';
if (isset($_GET['category']) && in_array($_GET['category'], $categories, true)) {
  $active = $_GET['category'];
}

$pdo = getConnection();

/* parehas nga alias sa index.php para magamit ra ang spec() helper */
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

$pageTitle = 'Our Vehicles — Shift Car Rental';
require 'header.php';
?>

<main class="page-main">

  <section class="page-hero" id="vehicles-hero">
    <h1>Our <span>Vehicles</span></h1>
    <p>Every unit in our Dumaguete fleet pick a category, compare specs, and book online.</p>
  </section>

  <section class="vehicles page-vehicles" id="all-vehicles">

    <!-- filter pills, link ra balik sa parehas nga page -->
    <div class="filters">
      <?php foreach ($categories as $cat) { ?>
        <a class="filter-tab<?php if ($cat === $active) echo ' is-active'; ?>"
           href="vehicles.php?category=<?= urlencode($cat) ?>#all-vehicles"
           <?php if ($cat === $active) echo 'aria-current="true"'; ?>><?= e($cat) ?></a>
      <?php } ?>
    </div>

    <!-- grid, dili carousel — tanan cars makita dayon -->
    <div class="cars-grid">
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

          <a class="book" href="book.php?car_id=<?= e($car['id']) ?>">
            <span class="label">Book Now <span class="book-arrow" aria-hidden="true">&#8599;</span></span>
          </a>

        </article>

      <?php } ?>

      <?php if (count($shown) === 0) { ?>
        <p class="no-cars">No <?= e($active) ?> units available yet.</p>
      <?php } ?>
    </div>

  </section>

</main>

<?php require 'footer.php'; ?>
