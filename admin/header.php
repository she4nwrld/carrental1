<?php
$adminTitle = $adminTitle ?? 'Dashboard';
$here       = basename($_SERVER['PHP_SELF']);

// Sidebar groups
$nav = [
  'Operations' => [
    'dashboard.php' => 'Bookings',
    'cars.php'      => 'Fleet',
    'promos.php'    => 'Promos',
  ],
  'People' => [
    'customers.php' => 'Customers',
    'reviews.php'   => 'Reviews',
    'messages.php'  => 'Inbox',
  ],
];

// Unread badge, quiet if the table is not there yet
$unread = 0;
try {
    $link   = $pdo ?? getConnection();
    $unread = (int) $link->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
} catch (Throwable $e) {
    $unread = 0;
}

// Who is signed in
$adminName = function_exists('currentUserName')
    ? currentUserName()
    : ($_SESSION['full_name'] ?? $_SESSION['name'] ?? 'Admin');

$adminInitial = strtoupper(substr(trim($adminName), 0, 1));

// Same mark the public header uses
$logoFile = '../images/shift-logo.png';
$hasLogo  = file_exists(__DIR__ . '/' . $logoFile);

$adminCss = '../css/admin.css';
$cssVer   = file_exists(__DIR__ . '/' . $adminCss) ? filemtime(__DIR__ . '/' . $adminCss) : 1;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($adminTitle) ?> &middot; Shift Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($adminCss) ?>?v=<?= e($cssVer) ?>">
</head>
<body class="adm">

<aside class="adm-side">

  <a class="adm-brand" href="dashboard.php">
    <?php if ($hasLogo) { ?>
      <img src="<?= e($logoFile) ?>" alt="Shift Car Rental">
    <?php } else { ?>
      <span class="adm-brand-text">SHIFT</span>
    <?php } ?>
  </a>

  <nav class="adm-nav">
    <?php foreach ($nav as $group => $items) { ?>
      <p class="adm-group"><?= e($group) ?></p>
      <?php foreach ($items as $file => $label) { ?>
        <a class="adm-link<?= $here === $file ? ' is-on' : '' ?>" href="<?= e($file) ?>">
          <span><?= e($label) ?></span>
          <?php if ($file === 'messages.php' && $unread > 0) { ?>
            <span class="adm-badge"><?= e($unread) ?></span>
          <?php } ?>
        </a>
      <?php } ?>
    <?php } ?>
  </nav>

  <div class="adm-side-foot">
    <a class="adm-link adm-quiet" href="../index.php" target="_blank" rel="noopener">View site</a>
    <a class="adm-link adm-quiet" href="../logout.php">Log out</a>
  </div>

</aside>

<div class="adm-main">

  <header class="adm-top">
    <h1><?= e($adminTitle) ?></h1>
    <div class="adm-user">
      <span class="adm-avatar" aria-hidden="true"><?= e($adminInitial) ?></span>
      <span class="adm-who"><?= e($adminName) ?></span>
    </div>
  </header>

  <div class="adm-body">
