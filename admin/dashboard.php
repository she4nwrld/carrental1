<?php
// naa ni sulod sa admin/ folder, mao nga '../' ang tanan path
$base = '../';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../helpers.php';

requireAdmin();   // customer o bisita, dili maka-sulod diri

$pdo = getConnection();

// unsa'ng status ang gi-filter, gikan sa mga tab sa ibabaw
$statuses = ['all', 'pending', 'confirmed', 'completed', 'cancelled'];
$filter   = $_GET['status'] ?? 'all';
if (!in_array($filter, $statuses, true)) {
    $filter = 'all';
}

/* tanan booking sa tanan user — mao ni ang kalainan sa bookings.php,
   walay user_id sa WHERE kay admin man */
$sql = "SELECT b.*, c.name AS car_name, c.img AS car_img,
               u.full_name, u.email, u.phone
        FROM bookings b
        JOIN cars c  ON c.id = b.car_id
        JOIN users u ON u.id = b.user_id";

if ($filter !== 'all') {
    $sql .= " WHERE b.status = :status";
}
$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($filter !== 'all') {
    $stmt->bindValue(':status', $filter);
}
$stmt->execute();
$rows = $stmt->fetchAll();

/* pang-ihap sa taas: pila ka pending, kinsa ang kita, ug uban pa */
$stats = $pdo->query(
    "SELECT
       COUNT(*) AS total,
       SUM(status = 'pending')   AS pending,
       SUM(status = 'confirmed') AS confirmed,
       SUM(CASE WHEN status IN ('confirmed','completed') THEN total ELSE 0 END) AS revenue
     FROM bookings"
)->fetch();

$flash = '';
if (isset($_GET['updated'])) {
    $flash = 'Booking status updated.';
} elseif (isset($_GET['failed'])) {
    $flash = 'That booking could not be updated.';
}

function niceDate(string $ymd): string {
    return date('M j, Y', strtotime($ymd));
}

function reference(int $id): string {
    return 'SHIFT-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

$pageTitle = 'Admin Dashboard — Shift Car Rental';
require __DIR__ . '/../header.php';
?>

<main class="book-main">
  <section class="admin-wrap">

    <div class="list-head">
      <h1>Admin dashboard</h1>
      <p class="book-sub">Every reservation across all customers.</p>
    </div>

    <?php if ($flash !== '') { ?>
      <div class="flash" role="status"><?= e($flash) ?></div>
    <?php } ?>

    <!-- upat ka tile sa ibabaw -->
    <div class="stat-grid">
      <div class="stat">
        <span>Total bookings</span>
        <strong><?= e(number_format((int)$stats['total'])) ?></strong>
      </div>
      <div class="stat">
        <span>Awaiting action</span>
        <strong><?= e(number_format((int)$stats['pending'])) ?></strong>
      </div>
      <div class="stat">
        <span>Confirmed</span>
        <strong><?= e(number_format((int)$stats['confirmed'])) ?></strong>
      </div>
      <div class="stat">
        <span>Booked revenue</span>
        <strong>&#8369;<?= e(number_format((int)$stats['revenue'])) ?></strong>
      </div>
    </div>

    <!-- tabs, link ra balik sa parehas nga page -->
    <div class="filters admin-filters">
      <?php foreach ($statuses as $st) { ?>
        <a class="filter-tab<?= $st === $filter ? ' is-active' : '' ?>"
           href="dashboard.php?status=<?= urlencode($st) ?>"><?= e(ucfirst($st)) ?></a>
      <?php } ?>
    </div>

    <?php if (count($rows) === 0) { ?>

      <div class="list-empty">
        <p>No <?= $filter === 'all' ? '' : e($filter) . ' ' ?>bookings to show.</p>
      </div>

    <?php } else { ?>

      <?php foreach ($rows as $bk) { ?>

        <article class="ad-card">

          <div class="ad-photo">
            <img src="<?= e($base . $bk['car_img']) ?>" alt="<?= e($bk['car_name']) ?>" loading="lazy">
          </div>

          <div class="ad-body">

            <div class="bk-top">
              <div>
                <h2><?= e($bk['car_name']) ?></h2>
                <p class="bk-ref"><?= e(reference((int)$bk['id'])) ?></p>
              </div>
              <span class="pill pill-<?= e($bk['status']) ?>"><?= e(ucfirst($bk['status'])) ?></span>
            </div>

            <!-- kinsa ang nag-book -->
            <div class="ad-customer">
              <strong><?= e($bk['full_name']) ?></strong>
              <a href="mailto:<?= e($bk['email']) ?>"><?= e($bk['email']) ?></a>
              <a href="tel:<?= e($bk['phone']) ?>"><?= e($bk['phone']) ?></a>
            </div>

            <div class="bk-grid">
              <div><span>Pick-up</span><strong><?= e(niceDate($bk['pickup_date'])) ?></strong><em><?= e($bk['pickup_location']) ?></em></div>
              <div><span>Return</span><strong><?= e(niceDate($bk['return_date'])) ?></strong><em><?= e($bk['return_location']) ?></em></div>
              <div><span>Driver / days</span><strong><?= e($bk['driver_age']) ?></strong><em><?= e($bk['days']) ?> <?= $bk['days'] == 1 ? 'day' : 'days' ?><?= $bk['delivery'] ? ' · delivery' : '' ?></em></div>
              <div><span>Total</span><strong>&#8369;<?= e(number_format($bk['total'])) ?></strong><em>Due on pick-up</em></div>
            </div>

            <div class="bk-foot">
              <p class="bk-made">Booked <?= e(date('M j, Y g:i a', strtotime($bk['created_at']))) ?></p>

              <div class="ad-actions">
                <?php if ($bk['status'] === 'pending') { ?>
                  <!-- usa ka form kada buton, ang bag-ong status naa sa hidden field -->
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
                    <button type="submit" class="ad-btn ad-ok">Mark completed</button>
                  </form>
                <?php } else { ?>
                  <p class="ad-done">No action needed</p>
                <?php } ?>
              </div>
            </div>

          </div>

        </article>

      <?php } ?>

    <?php } ?>

  </section>
</main>

<?php require __DIR__ . '/../footer.php'; ?>
