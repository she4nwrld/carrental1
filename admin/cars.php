<?php
$base = '../';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../helpers.php';

requireAdmin();

$pdo = getConnection();

/* types come from the same helper the public filters use */
$types = array_values(array_filter(carCategories(), function ($t) {
    return strtolower($t) !== 'all';
}));

/* which set to list */
$views  = ['all', 'available', 'hidden'];
$filter = $_GET['view'] ?? 'all';
if (!in_array($filter, $views, true)) {
    $filter = 'all';
}

$sql = "SELECT c.*,
          (SELECT COUNT(*) FROM bookings b WHERE b.car_id = c.id) AS bookings,
          (SELECT COUNT(*) FROM bookings b
            WHERE b.car_id = c.id AND b.status IN ('pending','confirmed')) AS live
        FROM cars c";

if ($filter === 'available') {
    $sql .= " WHERE c.available = 1";
} elseif ($filter === 'hidden') {
    $sql .= " WHERE c.available = 0";
}

$sql .= " ORDER BY c.type, c.name";

$rows = $pdo->query($sql)->fetchAll();

/* tiles */
$stats = $pdo->query(
    "SELECT COUNT(*) AS total,
            SUM(available = 1) AS live,
            SUM(available = 0) AS off,
            ROUND(AVG(price)) AS avg_price
     FROM cars"
)->fetch();

$flash = '';
if (isset($_GET['added'])) {
    $flash = 'Vehicle added to the fleet.';
} elseif (isset($_GET['saved'])) {
    $flash = 'Vehicle updated.';
} elseif (isset($_GET['toggled'])) {
    $flash = 'Availability changed.';
} elseif (isset($_GET['removed'])) {
    $flash = 'Vehicle deleted.';
} elseif (isset($_GET['inuse'])) {
    $flash = 'That vehicle has bookings, so it cannot be deleted. Hide it instead.';
} elseif (isset($_GET['failed'])) {
    $flash = 'Check the fields and try again.';
}

$adminTitle = 'Fleet';
require __DIR__ . '/header.php';
?>

<div class="list-head">
  <h1>Vehicles</h1>
  <p class="book-sub">Hiding a unit removes it from the site straight away.</p>
</div>

<?php if ($flash !== '') { ?>
  <div class="flash" role="status"><?= e($flash) ?></div>
<?php } ?>

<div class="stat-grid">
  <div class="stat">
    <span>Fleet size</span>
    <strong><?= e(number_format((int)$stats['total'])) ?></strong>
  </div>
  <div class="stat">
    <span>On the site</span>
    <strong><?= e(number_format((int)$stats['live'])) ?></strong>
  </div>
  <div class="stat">
    <span>Hidden</span>
    <strong><?= e(number_format((int)$stats['off'])) ?></strong>
  </div>
  <div class="stat">
    <span>Average rate</span>
    <strong>&#8369;<?= e(number_format((int)$stats['avg_price'])) ?></strong>
    <em>per day</em>
  </div>
</div>

<!-- add form, collapsed until needed -->
<details class="adm-add">
  <summary>Add a vehicle</summary>

  <form method="POST" action="function.php" class="car-form">
    <input type="hidden" name="action" value="add_car">

    <label>Name
      <input type="text" name="name" maxlength="60" required placeholder="Toyota Vios">
    </label>

    <label>Type
      <select name="type" required>
        <?php foreach ($types as $t) { ?>
          <option value="<?= e($t) ?>"><?= e($t) ?></option>
        <?php } ?>
      </select>
    </label>

    <label>Rate per day
      <input type="number" name="price" min="1" max="99999" required placeholder="2400">
    </label>

    <label>Gear
      <select name="gear" required>
        <option value="Manual">Manual</option>
        <option value="Auto">Auto</option>
      </select>
    </label>

    <label>Seats
      <input type="number" name="seats" min="1" max="30" value="5" required>
    </label>

    <label>Doors
      <input type="number" name="doors" min="1" max="10" value="5" required>
    </label>

    <label>Large bags
      <input type="number" name="bag_large" min="0" max="20" value="2" required>
    </label>

    <label>Small bags
      <input type="number" name="bag_small" min="0" max="20" value="2" required>
    </label>

    <label>Child seats
      <input type="number" name="kids" min="0" max="10" value="1" required>
    </label>

    <label>Aircon
      <select name="aircon">
        <option value="1">Yes</option>
        <option value="0">No</option>
      </select>
    </label>

    <label class="wide">Image path
      <input type="text" name="img" maxlength="120" required placeholder="images/toyota-vios.png">
    </label>

    <div class="wide">
      <button type="submit" class="ad-btn ad-ok">Add vehicle</button>
    </div>
  </form>
</details>

<div class="filters admin-filters">
  <?php foreach ($views as $v) { ?>
    <a class="filter-tab<?= $v === $filter ? ' is-active' : '' ?>"
       href="cars.php?view=<?= urlencode($v) ?>"><?= e(ucfirst($v)) ?></a>
  <?php } ?>
</div>

<?php if (count($rows) === 0) { ?>

  <div class="list-empty">
    <p>No vehicles in this list.</p>
  </div>

<?php } else { ?>

  <?php foreach ($rows as $car) { ?>

    <article class="ad-card<?= $car['available'] ? '' : ' car-off' ?>">

      <div class="ad-photo">
        <img src="<?= e($base . $car['img']) ?>" alt="<?= e($car['name']) ?>" loading="lazy">
      </div>

      <div class="ad-body">

        <div class="bk-top">
          <div>
            <h2><?= e($car['name']) ?></h2>
            <p class="bk-ref"><?= e($car['type']) ?> &middot; unit #<?= e($car['id']) ?></p>
          </div>
          <span class="pill pill-<?= $car['available'] ? 'completed' : 'cancelled' ?>">
            <?= $car['available'] ? 'On site' : 'Hidden' ?>
          </span>
        </div>

        <div class="bk-grid">
          <div><span>Rate</span><strong>&#8369;<?= e(number_format($car['price'])) ?></strong><em>per day</em></div>
          <div><span>Seats / doors</span><strong><?= e($car['seats']) ?> / <?= e($car['doors']) ?></strong><em><?= e($car['gear']) ?><?= $car['aircon'] ? ' · aircon' : '' ?></em></div>
          <div><span>Luggage</span><strong><?= e($car['bag_large']) ?> large, <?= e($car['bag_small']) ?> small</strong><em><?= e($car['kids']) ?> child seat<?= $car['kids'] == 1 ? '' : 's' ?></em></div>
          <div><span>Bookings</span><strong><?= e($car['bookings']) ?></strong><em><?= e($car['live']) ?> active</em></div>
        </div>

        <!-- edit form, collapsed -->
        <details class="adm-edit">
          <summary>Edit details</summary>

          <form method="POST" action="function.php" class="car-form">
            <input type="hidden" name="action" value="edit_car">
            <input type="hidden" name="car_id" value="<?= e($car['id']) ?>">
            <input type="hidden" name="view" value="<?= e($filter) ?>">

            <label>Name
              <input type="text" name="name" maxlength="60" value="<?= e($car['name']) ?>" required>
            </label>

            <label>Type
              <select name="type" required>
                <?php foreach ($types as $t) { ?>
                  <option value="<?= e($t) ?>"<?= $t === $car['type'] ? ' selected' : '' ?>><?= e($t) ?></option>
                <?php } ?>
              </select>
            </label>

            <label>Rate per day
              <input type="number" name="price" min="1" max="99999" value="<?= e($car['price']) ?>" required>
            </label>

            <label>Gear
              <select name="gear" required>
                <option value="Manual"<?= $car['gear'] === 'Manual' ? ' selected' : '' ?>>Manual</option>
                <option value="Auto"<?= $car['gear'] === 'Auto' ? ' selected' : '' ?>>Auto</option>
              </select>
            </label>

            <label>Seats
              <input type="number" name="seats" min="1" max="30" value="<?= e($car['seats']) ?>" required>
            </label>

            <label>Doors
              <input type="number" name="doors" min="1" max="10" value="<?= e($car['doors']) ?>" required>
            </label>

            <label>Large bags
              <input type="number" name="bag_large" min="0" max="20" value="<?= e($car['bag_large']) ?>" required>
            </label>

            <label>Small bags
              <input type="number" name="bag_small" min="0" max="20" value="<?= e($car['bag_small']) ?>" required>
            </label>

            <label>Child seats
              <input type="number" name="kids" min="0" max="10" value="<?= e($car['kids']) ?>" required>
            </label>

            <label>Aircon
              <select name="aircon">
                <option value="1"<?= $car['aircon'] ? ' selected' : '' ?>>Yes</option>
                <option value="0"<?= $car['aircon'] ? '' : ' selected' ?>>No</option>
              </select>
            </label>

            <label class="wide">Image path
              <input type="text" name="img" maxlength="120" value="<?= e($car['img']) ?>" required>
            </label>

            <div class="wide">
              <button type="submit" class="ad-btn ad-ok">Save changes</button>
            </div>
          </form>
        </details>

        <div class="bk-foot">
          <p class="bk-made">
            <?php if ((int)$car['live'] > 0) { ?>
              Held by <?= e($car['live']) ?> active booking<?= $car['live'] == 1 ? '' : 's' ?>
            <?php } else { ?>
              No active bookings
            <?php } ?>
          </p>

          <div class="ad-actions">
            <form method="POST" action="function.php">
              <input type="hidden" name="action" value="toggle_car">
              <input type="hidden" name="car_id" value="<?= e($car['id']) ?>">
              <input type="hidden" name="view" value="<?= e($filter) ?>">
              <input type="hidden" name="available" value="<?= $car['available'] ? '0' : '1' ?>">
              <button type="submit" class="ad-btn <?= $car['available'] ? 'ad-no' : 'ad-ok' ?>">
                <?= $car['available'] ? 'Hide from site' : 'Put on site' ?>
              </button>
            </form>

            <?php if ((int)$car['bookings'] === 0) { ?>
              <form method="POST" action="function.php"
                    onsubmit="return confirm('Delete this vehicle for good?');">
                <input type="hidden" name="action" value="drop_car">
                <input type="hidden" name="car_id" value="<?= e($car['id']) ?>">
                <input type="hidden" name="view" value="<?= e($filter) ?>">
                <button type="submit" class="ad-btn ad-no">Delete</button>
              </form>
            <?php } ?>
          </div>
        </div>

      </div>

    </article>

  <?php } ?>

<?php } ?>

<?php require __DIR__ . '/footer.php'; ?>
