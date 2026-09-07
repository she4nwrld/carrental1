<?php
require 'auth.php';
require 'database/config.php';
require 'helpers.php';

requireLogin();

$pdo = getConnection();

$bookingId = filter_input(INPUT_GET, 'booking', FILTER_VALIDATE_INT);
if (!$bookingId) {
    header('Location: index.php');
    exit;
}

/* JOIN para makuha ang pangalan ug litrato sa car;
   ang user_id sa WHERE para dili makita sa uban ang booking sa lain */
$sql = "SELECT b.*, c.name AS car_name, c.type AS car_type, c.img AS car_img, c.price
        FROM bookings b
        JOIN cars c ON c.id = b.car_id
        WHERE b.id = :id AND b.user_id = :user_id";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $bookingId, PDO::PARAM_INT);
$stmt->bindValue(':user_id', currentUserId(), PDO::PARAM_INT);
$stmt->execute();
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: bookings.php?notfound=1');
    exit;
}

/* pang-format sa petsa: 2026-11-10 -> Nov 10, 2026 */
function niceDate(string $ymd): string {
    return date('M j, Y', strtotime($ymd));
}

/* reference number nga mas tan-awon: SHIFT-0007 */
$reference = 'SHIFT-' . str_pad((string)$booking['id'], 4, '0', STR_PAD_LEFT);

$pageTitle = 'Booking Confirmed — Shift Car Rental';
require 'header.php';
?>

<main class="book-main">
  <section class="success-card">

    <div class="success-badge" aria-hidden="true">&#10003;</div>
    <h1>Booking received</h1>
    <p class="book-sub">
      Thanks, <?= e(currentUserName()) ?>. Your reference number is
      <strong><?= e($reference) ?></strong>. We'll confirm by text within 24 hours.
    </p>

    <div class="success-car">
      <div class="success-photo">
        <img src="<?= e($booking['car_img']) ?>" alt="<?= e($booking['car_name']) ?>">
      </div>
      <div>
        <h2><?= e($booking['car_name']) ?></h2>
        <p class="book-type"><?= e($booking['car_type']) ?></p>
      </div>
    </div>

    <dl class="success-list">
      <div><dt>Pick-up</dt><dd><?= e(niceDate($booking['pickup_date'])) ?> &middot; <?= e($booking['pickup_location']) ?></dd></div>
      <div><dt>Return</dt><dd><?= e(niceDate($booking['return_date'])) ?> &middot; <?= e($booking['return_location']) ?></dd></div>
      <div><dt>Duration</dt><dd><?= e($booking['days']) ?> <?= $booking['days'] == 1 ? 'day' : 'days' ?></dd></div>
      <div><dt>Driver's age</dt><dd><?= e($booking['driver_age']) ?></dd></div>
      <div><dt>Delivery</dt><dd><?= $booking['delivery'] ? 'Yes (&#8369;500)' : 'No, I will pick it up' ?></dd></div>
      <div><dt>Status</dt><dd><span class="pill pill-<?= e($booking['status']) ?>"><?= e(ucfirst($booking['status'])) ?></span></dd></div>
    </dl>

    <div class="book-total">
      <span>Total due on pick-up</span>
      <strong>&#8369;<?= e(number_format($booking['total'])) ?></strong>
    </div>

    <div class="success-actions">
      <a class="auth-btn" href="bookings.php">View my bookings</a>
      <a class="auth-alt" href="index.php">Back to home</a>
    </div>

  </section>
</main>

<?php require 'footer.php'; ?>
