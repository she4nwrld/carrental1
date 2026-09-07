<?php
// shortcut ni para dili ko magsige ug type ug htmlspecialchars
function e($text) {
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// google mark, gisulat kausa ra instead nga unom ka beses sa markup
function googleMark() {
  return '<svg viewBox="0 0 48 48" aria-hidden="true">'
    . '<path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3l5.7-5.7C34 6.1 29.3 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.6-.4-3.9z"/>'
    . '<path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>'
    . '<path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.3 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/>'
    . '<path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4.1 5.6l6.2 5.2C37.8 39.2 44 34.5 44 24c0-1.3-.1-2.6-.4-3.9z"/>'
    . '</svg>';
}
// diri nagpuyo ang car list ug ang mga dropdown options
$categories = ['All', 'Hatchback', 'Sedan', 'SUV', 'MPV'];

// unom ka units, para even ang desktop page nga tulo
$cars = [
  ['name' => 'Kia Picanto',          'type' => 'Hatchback', 'price' => 1800,
   'gear' => 'Auto',   'seats' => 5, 'doors' => 5, 'bagL' => 1, 'bagS' => 2, 'kids' => 1, 'aircon' => true,
   'img' => 'images/kia-picanto.png'],

  ['name' => 'Suzuki Swift',         'type' => 'Hatchback', 'price' => 2000,
   'gear' => 'Auto',   'seats' => 5, 'doors' => 5, 'bagL' => 1, 'bagS' => 2, 'kids' => 1, 'aircon' => true,
   'img' => 'images/suzuki-swift.png'],

  ['name' => 'Toyota Corolla Altis', 'type' => 'Sedan',     'price' => 2800,
   'gear' => 'Auto',   'seats' => 5, 'doors' => 4, 'bagL' => 2, 'bagS' => 2, 'kids' => 2, 'aircon' => true,
   'img' => 'images/toyota-corolla-altis.png'],

  ['name' => 'Honda City',           'type' => 'Sedan',     'price' => 2500,
   'gear' => 'Auto',   'seats' => 5, 'doors' => 4, 'bagL' => 2, 'bagS' => 1, 'kids' => 2, 'aircon' => true,
   'img' => 'images/honda-city.png'],

  ['name' => 'Toyota Fortuner',      'type' => 'SUV',       'price' => 4500,
   'gear' => 'Auto',   'seats' => 7, 'doors' => 5, 'bagL' => 3, 'bagS' => 2, 'kids' => 2, 'aircon' => true,
   'img' => 'images/toyota-fortuner.png'],

  ['name' => 'Toyota Innova',        'type' => 'MPV',       'price' => 3500,
   'gear' => 'Manual', 'seats' => 8, 'doors' => 5, 'bagL' => 3, 'bagS' => 3, 'kids' => 3, 'aircon' => true,
   'img' => 'images/toyota-innova.png'],
];
// icon shapes para sa spec rows, gibutang diri para mubo ra ang card markup
$specIcons = [
  'passenger' => '<circle cx="12" cy="7.4" r="3.2"/><path d="M5.6 20a6.4 6.4 0 0 1 12.8 0"/>',
  'kids'      => '<circle cx="12" cy="5.6" r="2.3"/><path d="M12 7.9v5.4"/><path d="M8.6 10.4h6.8"/><path d="M9.9 20l2.1-6.7 2.1 6.7"/>',
  'doors'     => '<path d="M6.5 4h8.2a3 3 0 0 1 3 3v13H6.5z"/><path d="M9.4 12.4h2.6"/>',
  'aircon'    => '<path d="M4 7.6h8.4a2.8 2.8 0 1 0-2.8-2.8"/><path d="M4 12h13.4"/><path d="M4 16.4h8.4a2.8 2.8 0 1 1-2.8 2.8"/>',
  'bagL'      => '<rect x="4.6" y="7.8" width="14.8" height="12.2" rx="2.2"/><path d="M9.2 7.8V5.4A1.4 1.4 0 0 1 10.6 4h2.8a1.4 1.4 0 0 1 1.4 1.4v2.4"/><path d="M12 11.4v5"/>',
  'bagS'      => '<rect x="6.6" y="9.4" width="10.8" height="10.6" rx="2"/><path d="M10.1 9.4V7.3a1.4 1.4 0 0 1 1.4-1.4h1a1.4 1.4 0 0 1 1.4 1.4v2.1"/>',
  'gear'      => '<circle cx="12" cy="12" r="8.4"/><path d="M12 12l3.4-3"/><path d="M12 3.6v2"/>',
];

// gi-wrap ang usa ka shape sa svg para kausa ra ni nako gisulat
function spec($icons, $key, $text) {
  if (!isset($icons[$key])) return '';
  return '<span><span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
       . 'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . $icons[$key] . '</svg></span>'
       . '<span class="txt">' . e($text) . '</span></span>';
}
$pickups = ['Sibulan Airport', 'Rizal Boulevard', 'Valencia'];

$ages = ['18-24', '25-34', '35+'];

// numero nga gitawagan sa mga tawo para mag-book, walay online form sa site
$phone = '+63 912 345 6789';
$phoneLink = 'tel:+639123456789';

// mga branch nga makita sa locations strip, plain text na para maka-trabaho si e()
$branches = [
  ['name' => 'Sibulan Airport', 'note' => 'Meet & greet at arrivals',  'img' => 'images/loc-sibulan.png'],
  ['name' => 'Rizal Boulevard', 'note' => 'Dumaguete City seaside hub', 'img' => 'images/loc-rizal.png'],
  ['name' => 'Valencia',        'note' => 'Highland pick-up point',     'img' => 'images/loc-valencia.png'],
];

// google reviews, unom ka buok para duha ka desktop pages ang slider
$feedback = [
  ['name' => 'Miguel Torres', 'role' => 'Apo Island Weekender', 'when' => '2 weeks ago',
   'text' => 'The car was waiting for us right at Sibulan Airport arrivals, five minutes after landing we were already on the road to Dauin. Effortless from start to finish.'],
  ['name' => 'Anna Reyes', 'role' => 'Local Renter', 'when' => '1 month ago',
   'text' => 'The price I saw on the site was the exact price I paid. No surprise insurance add-ons, no fuel games. Best rental deal in Dumaguete, hands down.'],
  ['name' => 'James Whitmore', 'role' => 'Visitor from Australia', 'when' => '2 months ago',
   'text' => 'Flat tire on the mountain road up to Valencia. One call and the roadside team had us moving again within the hour. That kind of backup is worth everything.'],
  ['name' => 'Grace Villanueva', 'role' => 'Family Trip', 'when' => '2 months ago',
   'text' => 'Booked the Innova for a week with two kids in tow. Clean unit, cold aircon, child seat ready on pick-up. We just drove and enjoyed the island.'],
  ['name' => 'Daniel Cruz', 'role' => 'Business Traveller', 'when' => '3 months ago',
   'text' => 'Late flight into Sibulan and they still met me at arrivals. Paperwork took maybe ten minutes. This is now my default rental in Negros Oriental.'],
  ['name' => 'Sofia Lim', 'role' => 'Valencia Day Tripper', 'when' => '4 months ago',
   'text' => 'Rented the Swift for a Casaroro Falls run. Sharp handling on the climb and the tank was full. Returning it was just as painless as picking it up.'],
];

// asa nga filter pill ang naka-on
$active = 'All';
if (isset($_GET['category']) && in_array($_GET['category'], $categories, true)) {
  $active = $_GET['category'];
}

$shown = [];
foreach ($cars as $car) {
  if ($active === 'All' || $car['type'] === $active) {
    $shown[] = $car;
  }
}

// filemtime sa css para muundang ang browser sa pag-cache sa daan
$cssFile = 'css/style.css';
$cssVersion = file_exists(__DIR__ . '/' . $cssFile) ? filemtime(__DIR__ . '/' . $cssFile) : 1;

$logo = 'images/shift-logo.png';
$hasLogo = file_exists(__DIR__ . '/' . $logo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Shift Car Rental — Dumaguete City, Sibulan &amp; Valencia</title>
<meta name="description" content="Self-drive car rental in Dumaguete City, Sibulan and Valencia. Browse the fleet and call us to lock in your dates.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($cssFile) ?>?v=<?= e($cssVersion) ?>">
</head>

<body>
