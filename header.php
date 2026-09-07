<?php
// tanan page mo-require niini, mao ni ang nag-start sa session ug naghatag sa isLoggedIn()
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

// ang page mo-set niini una sa require, kung dili default ra ang gamiton
$pageTitle = $pageTitle ?? 'Shift Car Rental — Dumaguete City, Sibulan & Valencia';

// kung ang page naa sa sulod sa admin/ folder, mo-set siya ug $base = '../'
// para dili mabuak ang path sa css, images ug mga link
$base = $base ?? '';

$logo    = 'images/shift-logo.png';
$hasLogo = file_exists(__DIR__ . '/' . $logo);

// filemtime para dili mo-cache ang browser sa daan nga css
$cssFile    = 'css/style.css';
$cssVersion = file_exists(__DIR__ . '/' . $cssFile) ? filemtime(__DIR__ . '/' . $cssFile) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($base . $cssFile) ?>?v=<?= e($cssVersion) ?>">
</head>

<body>

<header class="topbar">
  <div class="brand">
    <a href="<?= e($base) ?>index.php">
      <?php if ($hasLogo) { ?>
        <img src="<?= e($base . $logo) ?>" alt="Shift Car Rental">
      <?php } else { ?>
        <span class="wordmark">SHI<span>F</span>T</span>
      <?php } ?>
    </a>
  </div>

  <nav class="menu" aria-label="Main navigation">
    <a href="<?= e($base) ?>index.php">Home</a>
    <a href="<?= e($base) ?>vehicles.php">Vehicles</a>
    <a href="<?= e($base) ?>locations.php">Locations</a>
    <a href="<?= e($base) ?>deals.php">Deals</a>
    <a href="<?= e($base) ?>reviews.php">Reviews</a>
    <a href="<?= e($base) ?>faqs.php">FAQs</a>
    <a href="<?= e($base) ?>contact.php">Contact Us</a>
  </nav>

  <div class="right">
    <?php if (isLoggedIn()) { ?>

      <!-- naka-login: ngalan, mga booking, ug logout -->
      <?php if (isAdmin()) { ?>
        <a href="<?= e($base) ?>admin/dashboard.php">Dashboard</a>
      <?php } ?>
      <a href="<?= e($base) ?>bookings.php">My Bookings</a>
      <span class="greet">Hi, <?= e(currentUserName()) ?></span>
      <a class="bookbtn" href="<?= e($base) ?>logout.php">Log Out</a>

    <?php } else { ?>

      <!-- bisita: My Booking ug Book Now -->
      <a href="<?= e($base) ?>bookings.php">My Booking</a>
      <a class="bookbtn" href="#" id="booknow-btn">Book Now</a>

    <?php } ?>
  </div>
</header>

<?php if (!isLoggedIn()) { ?>
<!-- modal: mo-gawas kung guest mo-click sa Book Now — kinahanglan mag-login o mag-create account una -->
<div class="auth-modal" id="auth-modal" hidden>
  <div class="auth-modal-box" role="dialog" aria-labelledby="auth-modal-title">
    <button type="button" class="auth-modal-close" id="auth-modal-close" aria-label="Close">&times;</button>
    <h3 id="auth-modal-title">Ready to book?</h3>
    <p>You need an account to book a vehicle. Log in or create one — it only takes a minute.</p>
    <div class="auth-modal-actions">
      <a class="am-btn" href="<?= e($base) ?>login.php?next=<?= urlencode($base ? '../vehicles.php' : 'vehicles.php') ?>">Log In</a>
      <a class="am-btn am-ghost" href="<?= e($base) ?>signup.php">Create Account</a>
    </div>
  </div>
</div>
<script>
(function () {
  var btn   = document.getElementById('booknow-btn');
  var modal = document.getElementById('auth-modal');
  if (!btn || !modal) return;
  btn.addEventListener('click', function (e) { e.preventDefault(); modal.hidden = false; });
  document.getElementById('auth-modal-close').addEventListener('click', function () { modal.hidden = true; });
  modal.addEventListener('click', function (e) { if (e.target === modal) modal.hidden = true; });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') modal.hidden = true; });
})();
</script>
<?php } ?>
