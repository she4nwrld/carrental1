<?php
// tanan page mo-require niini, mao ni ang nag-start sa session ug naghatag sa isLoggedIn()
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

// ang page mo-set niini una sa require, kung dili default ra ang gamiton
$pageTitle = $pageTitle ?? 'Shift Car Rental — Dumaguete City, Sibulan & Valencia';

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
<link rel="stylesheet" href="<?= e($cssFile) ?>?v=<?= e($cssVersion) ?>">
</head>

<body>
