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

// lahi ni sa wordmark — square nga "S" ra, para klaro sa browser tab
$favicon = 'images/shift-mark.png';

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
<link rel="icon" type="image/png" href="<?= e($base . $favicon) ?>?v=2">
<link rel="apple-touch-icon" href="<?= e($base . $favicon) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($base . $cssFile) ?>?v=<?= e($cssVersion) ?>">
</head>

<body>

<!-- para sa keyboard users, mo-laktaw sa tibuok nav -->
<a class="skip-link" href="#main-content">Skip to main content</a>

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

  <nav class="menu" id="main-menu" aria-label="Main navigation">
    <a href="<?= e($base) ?>index.php">Home</a>
    <a href="<?= e($base) ?>vehicles.php">Vehicles</a>
    <a href="<?= e($base) ?>locations.php">Locations</a>
    <a href="<?= e($base) ?>deals.php">Deals</a>
    <a href="<?= e($base) ?>reviews.php">Reviews</a>
    <a href="<?= e($base) ?>faqs.php">FAQs</a>
    <a href="<?= e($base) ?>contact.php">Contact Us</a>

    <!-- makita ra ni sa gamay nga screen, kay gitago ang .right links didto -->
    <span class="menu-auth">
      <?php if (isLoggedIn()) { ?>
        <?php if (isAdmin()) { ?>
          <a href="<?= e($base) ?>admin/dashboard.php">Dashboard</a>
        <?php } ?>
        <a href="<?= e($base) ?>profile.php">My Profile</a>
        <a href="<?= e($base) ?>bookings.php">My Bookings</a>
        <a href="<?= e($base) ?>logout.php">Log Out</a>
      <?php } else { ?>
        <a href="<?= e($base) ?>login.php">Log In</a>
        <a href="<?= e($base) ?>signup.php">Create Account</a>
      <?php } ?>
    </span>
  </nav>

  <div class="right">
    <?php if (isLoggedIn()) { ?>

      <!-- naka-login: ngalan, profile, mga booking, ug logout -->
      <?php if (isAdmin()) { ?>
        <a href="<?= e($base) ?>admin/dashboard.php">Dashboard</a>
      <?php } ?>
      <a href="<?= e($base) ?>profile.php">My Profile</a>
      <a href="<?= e($base) ?>bookings.php">My Bookings</a>
      <span class="greet">Hi, <?= e(currentUserName()) ?></span>
      <a class="bookbtn" href="<?= e($base) ?>logout.php">Log Out</a>

    <?php } else { ?>

      <!-- bisita: My Booking ug Book Now — ang data-auth mao ang nag-trigger sa modal -->
      <a href="<?= e($base) ?>bookings.php" data-auth="1">My Booking</a>
      <a class="bookbtn" href="#" id="booknow-btn">Book Now</a>

    <?php } ?>
  </div>

  <button type="button" class="nav-toggle" aria-expanded="false"
          aria-controls="main-menu" aria-label="Toggle navigation menu">
    <span></span><span></span><span></span>
  </button>
</header>

<!-- target sa skip link, walay gilapdon so dili makaguba sa layout -->
<span id="main-content" tabindex="-1"></span>

<script>
(function () {
  var btn  = document.querySelector('.nav-toggle');
  var menu = document.getElementById('main-menu');
  if (!btn || !menu) return;

  function close() {
    menu.classList.remove('is-open');
    btn.setAttribute('aria-expanded', 'false');
  }

  btn.addEventListener('click', function () {
    var open = menu.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  /* mo-click sa gawas sa panel: sira */
  document.addEventListener('click', function (e) {
    if (!menu.classList.contains('is-open')) return;
    if (menu.contains(e.target) || btn.contains(e.target)) return;
    close();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && menu.classList.contains('is-open')) {
      close();
      btn.focus();
    }
  });
})();
</script>

<?php if (!isLoggedIn()) { ?>
<!-- modal: mo-gawas kung guest mo-click sa Book Now kinahanglan mag-login o mag-create account una -->
<div class="auth-modal" id="auth-modal" hidden>
  <div class="auth-modal-box" role="dialog" aria-labelledby="auth-modal-title">
    <button type="button" class="auth-modal-close" id="auth-modal-close" aria-label="Close">&times;</button>
    <h3 id="auth-modal-title">Ready to book?</h3>
    <p>You need an account to book a vehicle. Log in or create one it only takes a minute.</p>
    <div class="auth-modal-actions">
      <a class="am-btn" href="<?= e($base) ?>login.php?next=<?= urlencode($base ? '../vehicles.php' : 'vehicles.php') ?>">Log In</a>
      <a class="am-btn am-ghost" href="<?= e($base) ?>signup.php">Create Account</a>
    </div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('auth-modal');
  if (!modal) return;
  var loginLink = modal.querySelector('.auth-modal-actions a.am-btn');

  function openModal(e, nextUrl) {
    e.preventDefault();
    if (nextUrl && loginLink) {
      var base = loginLink.getAttribute('href').split('?')[0];
      loginLink.setAttribute('href', base + '?next=' + encodeURIComponent(nextUrl));
    }
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
  }

  var btn = document.getElementById('booknow-btn');
  if (btn) btn.addEventListener('click', function (e) { openModal(e); });

  /* guest presses Book Now on a car card, o My Booking sa header: modal ang mogawas */
  document.addEventListener('click', function (e) {
    var link = e.target.closest ? e.target.closest('a.book, a[data-auth]') : null;
    if (link) openModal(e, link.getAttribute('href'));
  });

  document.getElementById('auth-modal-close').addEventListener('click', closeModal);
  modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>
<?php } ?>
