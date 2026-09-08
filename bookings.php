<?php
require 'auth.php';
require 'database/config.php';
require_once 'helpers.php';

requireLogin();

$pdo = getConnection();

/* tanan booking sa naka-login nga user, bag-o ang una */
$sql = "SELECT b.*, c.name AS car_name, c.type AS car_type, c.img AS car_img, c.price
        FROM bookings b
        JOIN cars c ON c.id = b.car_id
        WHERE b.user_id = :user_id
        ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', currentUserId(), PDO::PARAM_INT);
$stmt->execute();
$bookings = $stmt->fetchAll();

/* mensahe gikan sa function.php pagkahuman sa cancel */
$flash = '';
if (isset($_GET['cancelled'])) {
    $flash = 'Your booking has been cancelled.';
} elseif (isset($_GET['notfound'])) {
    $flash = 'We could not find that booking, or it can no longer be cancelled.';
}

function niceDate(string $ymd): string {
    return date('M j, Y', strtotime($ymd));
}

/* SHIFT-0007 */
function reference(int $id): string {
    return 'SHIFT-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

$pageTitle = 'My Bookings — Shift Car Rental';
require 'header.php';
?>

<main class="book-main">
  <section class="list-wrap">

    <div class="list-head">
      <h1>My bookings</h1>
      <p class="book-sub">All your reservations, newest first.</p>
    </div>

    <?php if ($flash !== '') { ?>
      <div class="flash" role="status"><?= e($flash) ?></div>
    <?php } ?>

    <?php if (count($bookings) === 0) { ?>

      <div class="list-empty">
        <p>You have no bookings yet.</p>
        <a class="auth-btn" href="vehicles.php#all-vehicles">Browse our vehicles</a>
      </div>

    <?php } else { ?>

      <?php foreach ($bookings as $bk) { ?>
        <?php
          /* pending ug confirmed ra ang ma-cancel — parehas sa
             WHERE clause sa cancel_booking sa function.php */
          $canCancel = in_array($bk['status'], ['pending', 'confirmed'], true);
          $discount  = (int) $bk['discount'];
        ?>

        <article class="bk-card">

          <div class="bk-photo">
            <img src="<?= e($bk['car_img']) ?>" alt="<?= e($bk['car_name']) ?>" loading="lazy">
          </div>

          <div class="bk-body">

            <div class="bk-top">
              <div>
                <h2><?= e($bk['car_name']) ?></h2>
                <p class="bk-ref"><?= e(reference((int)$bk['id'])) ?> &middot; <?= e($bk['car_type']) ?></p>
              </div>
              <span class="pill pill-<?= e($bk['status']) ?>"><?= e(ucfirst($bk['status'])) ?></span>
            </div>

            <div class="bk-grid">
              <div><span>Pick-up</span><strong><?= e(niceDate($bk['pickup_date'])) ?></strong><em><?= e($bk['pickup_location']) ?></em></div>
              <div><span>Return</span><strong><?= e(niceDate($bk['return_date'])) ?></strong><em><?= e($bk['return_location']) ?></em></div>
              <div><span>Duration</span><strong><?= e($bk['days']) ?> <?= $bk['days'] == 1 ? 'day' : 'days' ?></strong><em><?= $bk['delivery'] ? 'With delivery' : 'Self pick-up' ?></em></div>
              <div><span>Total</span><strong><?= e(peso($bk['total'])) ?></strong><em>Due on pick-up</em></div>
            </div>

            <?php if ($discount > 0 || !empty($bk['discount_code'])) { ?>
              <p class="saved">
                <?php if (!empty($bk['discount_code'])) { ?>
                  <?= e(str_replace(',', ' + ', $bk['discount_code'])) ?> applied
                <?php } ?>
                <?php if ($discount > 0) { ?>
                  — you saved <?= e(peso($discount)) ?>
                <?php } ?>
              </p>
            <?php } ?>

            <div class="bk-foot">
              <p class="bk-made">Booked <?= e(date('M j, Y', strtotime($bk['created_at']))) ?></p>

              <?php if ($canCancel) { ?>
                <form method="POST" action="function.php" class="bk-cancel"
                      onsubmit="return confirm('Cancel this booking?');">
                  <input type="hidden" name="action" value="cancel_booking">
                  <input type="hidden" name="booking_id" value="<?= e($bk['id']) ?>">
                  <button type="submit" class="bk-cancel-btn">Cancel booking</button>
                </form>
              <?php } ?>
            </div>

          </div>

        </article>

      <?php } ?>

    <?php } ?>

  </section>
</main>

<?php require 'footer.php'; ?>
