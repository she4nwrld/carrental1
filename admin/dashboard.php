<?php
$base = '../';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../helpers.php';

requireAdmin();

$pdo = getConnection();

/* status tabs, plus overdue which is a confirmed booking past its return date */
$statuses = ['all', 'pending', 'confirmed', 'overdue', 'completed', 'cancelled'];
$filter   = $_GET['status'] ?? 'all';
if (!in_array($filter, $statuses, true)) {
    $filter = 'all';
}

/* every booking, all customers */
$sql = "SELECT b.*, c.name AS car_name, c.img AS car_img,
               u.full_name, u.email, u.phone
        FROM bookings b
        JOIN cars c  ON c.id = b.car_id
        JOIN users u ON u.id = b.user_id";

if ($filter === 'overdue') {
    $sql .= " WHERE b.status = 'confirmed' AND b.return_date < CURDATE()";
} elseif ($filter !== 'all') {
    $sql .= " WHERE b.status = :status";
}

/* overdue first, then newest */
$sql .= " ORDER BY (b.status = 'confirmed' AND b.return_date < CURDATE()) DESC,
                   b.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($filter !== 'all' && $filter !== 'overdue') {
    $stmt->bindValue(':status', $filter);
}
$stmt->execute();
$rows = $stmt->fetchAll();

/* tiles */
$stats = $pdo->query(
    "SELECT
       COUNT(*) AS total,
       SUM(status = 'pending')   AS pending,
       SUM(status = 'confirmed') AS confirmed,
       SUM(status = 'completed') AS completed,
       SUM(status = 'cancelled') AS cancelled,
       SUM(status = 'confirmed' AND return_date < CURDATE()) AS overdue,
       SUM(CASE WHEN status IN ('confirmed','completed') THEN total ELSE 0 END) AS revenue
     FROM bookings"
)->fetch();

$active = (int)$stats['total'] - (int)$stats['cancelled'];
$today  = date('Y-m-d');

$flash = '';
if (isset($_GET['updated'])) {
    $flash = 'Booking status updated.';
} elseif (isset($_GET['saved'])) {
    $flash = 'Booking details saved.';
} elseif (isset($_GET['deleted'])) {
    $flash = 'Booking deleted.';
} elseif (isset($_GET['failed'])) {
    $flash = 'That booking could not be updated.';
}

function niceDate(string $ymd): string {
    return date('M j, Y', strtotime($ymd));
}

function reference(int $id): string {
    return 'SHIFT-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

/* how many days past the return date */
function daysLate(string $returnDate): int {
    $end = new DateTime($returnDate);
    $now = new DateTime(date('Y-m-d'));
    return (int)$end->diff($now)->days;
}

$adminTitle = 'Bookings';
require __DIR__ . '/header.php';
?>

<div class="list-head">
  <h1>Every reservation</h1>
  <p class="book-sub">All customers, overdue first.</p>
</div>

<?php if ($flash !== '') { ?>
  <div class="flash" role="status"><?= e($flash) ?></div>
<?php } ?>

<?php if ((int)$stats['overdue'] > 0 && $filter !== 'overdue') { ?>
  <div class="flash flash-warn" role="status">
    <?= e($stats['overdue']) ?> booking<?= $stats['overdue'] == 1 ? ' is' : 's are' ?>
    past the return date and still holding a car.
    <a href="dashboard.php?status=overdue">Show them</a>
  </div>
<?php } ?>

<div class="stat-grid">
  <div class="stat">
    <span>Active bookings</span>
    <strong><?= e(number_format($active)) ?></strong>
    <em><?= e(number_format((int)$stats['cancelled'])) ?> cancelled</em>
  </div>
  <div class="stat">
    <span>Awaiting action</span>
    <strong><?= e(number_format((int)$stats['pending'])) ?></strong>
  </div>
  <div class="stat<?= (int)$stats['overdue'] > 0 ? ' stat-warn' : '' ?>">
    <span>Overdue</span>
    <strong><?= e(number_format((int)$stats['overdue'])) ?></strong>
    <em>past return date</em>
  </div>
  <div class="stat">
    <span>Confirmed</span>
    <strong><?= e(number_format((int)$stats['confirmed'])) ?></strong>
    <em><?= e(number_format((int)$stats['completed'])) ?> completed</em>
  </div>
  <div class="stat">
    <span>Booked revenue</span>
    <strong>&#8369;<?= e(number_format((int)$stats['revenue'])) ?></strong>
    <em>confirmed + completed</em>
  </div>
</div>

<div class="filters admin-filters">
  <?php foreach ($statuses as $st) { ?>
    <a class="filter-tab<?= $st === $filter ? ' is-active' : '' ?><?= $st === 'overdue' && (int)$stats['overdue'] > 0 ? ' tab-warn' : '' ?>"
       href="dashboard.php?status=<?= urlencode($st) ?>"><?= e(ucfirst($st)) ?></a>
  <?php } ?>
</div>

<?php if (count($rows) === 0) { ?>

  <div class="list-empty">
    <p>No <?= $filter === 'all' ? '' : e($filter) . ' ' ?>bookings to show.</p>
  </div>

<?php } else { ?>

  <?php foreach ($rows as $bk) {

    $isLate = $bk['status'] === 'confirmed' && $bk['return_date'] < $today;
  ?>

    <article class="ad-card<?= $isLate ? ' bk-late' : '' ?>">

      <div class="ad-photo">
        <img src="<?= e($base . $bk['car_img']) ?>" alt="<?= e($bk['car_name']) ?>" loading="lazy">
      </div>

      <div class="ad-body">

        <div class="bk-top">
          <div>
            <h2><?= e($bk['car_name']) ?></h2>
            <p class="bk-ref"><?= e(reference((int)$bk['id'])) ?></p>
          </div>
          <?php if ($isLate) { ?>
            <span class="pill pill-overdue">Overdue</span>
          <?php } else { ?>
            <span class="pill pill-<?= e($bk['status']) ?>"><?= e(ucfirst($bk['status'])) ?></span>
          <?php } ?>
        </div>

        <!-- who booked it -->
        <div class="ad-customer">
          <strong><?= e($bk['full_name']) ?></strong>
          <a href="mailto:<?= e($bk['email']) ?>"><?= e($bk['email']) ?></a>
          <a href="tel:<?= e($bk['phone']) ?>"><?= e($bk['phone']) ?></a>
        </div>

        <?php if ($isLate) { ?>
          <p class="bk-late-note">
            Due back <?= e(niceDate($bk['return_date'])) ?> &mdash;
            <?= e(daysLate($bk['return_date'])) ?> day<?= daysLate($bk['return_date']) == 1 ? '' : 's' ?> ago.
            The car stays booked until this is closed off.
          </p>
        <?php } ?>

        <div class="bk-grid">
          <div><span>Pick-up</span><strong><?= e(niceDate($bk['pickup_date'])) ?></strong><em><?= e($bk['pickup_location']) ?></em></div>
          <div><span>Return</span><strong><?= e(niceDate($bk['return_date'])) ?></strong><em><?= e($bk['return_location']) ?></em></div>
          <div><span>Driver / days</span><strong><?= e($bk['driver_age']) ?></strong><em><?= e($bk['days']) ?> <?= $bk['days'] == 1 ? 'day' : 'days' ?><?= $bk['delivery'] ? ' · delivery' : '' ?></em></div>
          <div><span>Total</span><strong>&#8369;<?= e(number_format($bk['total'])) ?></strong><em><?php
            if ($bk['status'] === 'completed') {
                echo 'Paid';
            } elseif ($bk['status'] === 'cancelled') {
                echo 'Not charged';
            } else {
                echo 'Due on pick-up';
            }
          ?></em></div>
        </div>

        <?php if ((int)$bk['discount'] > 0) { ?>
          <p class="ad-disc">
            Subtotal <?= e(peso((int)$bk['subtotal'])) ?>
            &middot; <?= e($bk['discount_code']) ?> &minus;<?= e(peso((int)$bk['discount'])) ?>
          </p>
        <?php } ?>

        <details class="adm-edit">
          <summary>Edit booking</summary>

          <form method="POST" action="function.php" class="car-form wide">
            <input type="hidden" name="action" value="edit_booking">
            <input type="hidden" name="booking_id" value="<?= e($bk['id']) ?>">

            <label>Pick-up date
              <input type="date" name="pickup_date" value="<?= e($bk['pickup_date']) ?>" required>
            </label>

            <label>Return date
              <input type="date" name="return_date" value="<?= e($bk['return_date']) ?>" required>
            </label>

            <label>Pick-up location
              <select name="pickup_location" required>
                <?php foreach (pickupPoints() as $place) { ?>
                  <option value="<?= e($place) ?>"<?= $bk['pickup_location'] === $place ? ' selected' : '' ?>>
                    <?= e($place) ?>
                  </option>
                <?php } ?>
              </select>
            </label>

            <label>Return location
              <select name="return_location" required>
                <?php foreach (pickupPoints() as $place) { ?>
                  <option value="<?= e($place) ?>"<?= $bk['return_location'] === $place ? ' selected' : '' ?>>
                    <?= e($place) ?>
                  </option>
                <?php } ?>
              </select>
            </label>

            <label>Driver's age
              <select name="driver_age" required>
                <?php foreach (ageBrackets() as $age) { ?>
                  <option value="<?= e($age) ?>"<?= $bk['driver_age'] === $age ? ' selected' : '' ?>>
                    <?= e($age) ?>
                  </option>
                <?php } ?>
              </select>
            </label>

            <label>Total
              <input type="text" value="<?= e(peso((int)$bk['total'])) ?>" disabled>
              <small class="muted">Worked out again from the dates on save.</small>
            </label>

            <label>Status
              <select name="status">
                <?php foreach (['pending','confirmed','completed','cancelled'] as $st) { ?>
                  <option value="<?= e($st) ?>"<?= $bk['status'] === $st ? ' selected' : '' ?>>
                    <?= e(ucfirst($st)) ?>
                  </option>
                <?php } ?>
              </select>
            </label>

            <div class="wide">
              <button type="submit" class="ad-btn ad-ok">Save changes</button>
            </div>
          </form>
        </details>

        <div class="bk-foot">
          <p class="bk-made">Booked <?= e(date('M j, Y g:i a', strtotime($bk['created_at']))) ?></p>

          <div class="ad-actions">
            <?php if ($bk['status'] === 'pending') { ?>
              <form method="POST" action="function.php">
                <input type="hidden" name="action" value="set_status">
                <input type="hidden" name="booking_id" value="<?= e($bk['id']) ?>">
                <input type="hidden" name="status" value="confirmed">
                <button type="submit" class="ad-btn ad-ok">Confirm</button>
              </form>
              <form method="POST" action="function.php"
                    onsubmit="return confirm('Cancel this booking?');">
                <input type="hidden" name="action" value="set_status">
                <input type="hidden" name="booking_id" value="<?= e($bk['id']) ?>">
                <input type="hidden" name="status" value="cancelled">
                <button type="submit" class="ad-btn ad-no">Cancel</button>
              </form>
            <?php } elseif ($bk['status'] === 'confirmed') { ?>
              <form method="POST" action="function.php">
                <input type="hidden" name="action" value="set_status">
                <input type="hidden" name="booking_id" value="<?= e($bk['id']) ?>">
                <input type="hidden" name="status" value="completed">
                <button type="submit" class="ad-btn ad-ok">
                  <?= $isLate ? 'Car returned' : 'Mark completed' ?>
                </button>
              </form>
              <?php if ($isLate) { ?>
                <form method="POST" action="function.php"
                      onsubmit="return confirm('Cancel this booking? Use this if the trip never happened.');">
                  <input type="hidden" name="action" value="set_status">
                  <input type="hidden" name="booking_id" value="<?= e($bk['id']) ?>">
                  <input type="hidden" name="status" value="cancelled">
                  <button type="submit" class="ad-btn ad-no">Never collected</button>
                </form>
              <?php } ?>
            <?php } ?>

            <form method="POST" action="function.php"
                  onsubmit="return confirm('Delete this booking permanently? This cannot be undone.');">
              <input type="hidden" name="action" value="delete_booking">
              <input type="hidden" name="booking_id" value="<?= e($bk['id']) ?>">
              <button type="submit" class="ad-btn ad-no">Delete</button>
            </form>
          </div>
        </div>

      </div>

    </article>

  <?php } ?>

<?php } ?>

<?php require __DIR__ . '/footer.php'; ?>
