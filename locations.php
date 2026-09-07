<?php
require 'auth.php';
require 'helpers.php';

// detalyado nga info kada branch, gamiton sa cards sa ubos
$branches = [
  [
    'name'  => 'Sibulan Airport',
    'note'  => 'Meet & greet at arrivals',
    'img'   => 'images/loc-sibulan.png',
    'addr'  => 'Sibulan Airport (Dumaguete Airport), Sibulan, Negros Oriental',
    'hours' => 'Mon–Sun 7:00 am – 6:00 pm',
    'desc'  => 'Our agent waits at the arrivals exit holding a Shift Car Rental sign. Paperwork takes about ten minutes and you drive straight out of the airport parking.',
  ],
  [
    'name'  => 'Rizal Boulevard',
    'note'  => 'Dumaguete City seaside hub',
    'img'   => 'images/loc-rizal.png',
    'addr'  => 'Rizal Boulevard, Dumaguete City, Negros Oriental 6200',
    'hours' => 'Mon–Sun 7:00 am – 11:00 pm',
    'desc'  => 'Our main office along the boulevard. Walk-ins welcome, and this is the branch with the longest opening hours — perfect for late returns.',
  ],
  [
    'name'  => 'Valencia',
    'note'  => 'Highland pick-up point',
    'img'   => 'images/loc-valencia.png',
    'addr'  => 'Poblacion, Valencia, Negros Oriental',
    'hours' => 'Mon–Sun 7:00 am – 6:00 pm',
    'desc'  => 'Starting point for Casaroro Falls, Pulangbato and the mountain roads. Pick up here if you are staying in the highlands.',
  ],
];

// delivery areas nga wala pa'y branch
$deliveryAreas = ['Dauin', 'Bacong', 'Bais City', 'Tanjay', 'San Jose', 'Amlan'];

$pageTitle = 'Our Locations — Shift Car Rental';
require 'header.php';
?>

<main class="page-main">

  <section class="page-hero" id="locations-hero">
    <h1>Our <span>Locations</span></h1>
    <p>Pick-up, return and delivery across Negros Oriental.</p>
  </section>

  <section class="loc-detail-list" id="branches">
    <?php foreach ($branches as $branch) { ?>

      <article class="loc-detail">
        <div class="loc-detail-photo">
          <img src="<?= e($branch['img']) ?>" alt="<?= e($branch['name']) ?> pick-up point" loading="lazy">
        </div>

        <div class="loc-detail-body">
          <span class="loc-pin" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.4"/></svg>
          </span>
          <h2><?= e($branch['name']) ?></h2>
          <p class="loc-detail-note"><?= e($branch['note']) ?></p>
          <p><?= e($branch['desc']) ?></p>
          <ul class="loc-detail-meta">
            <li><strong>Address:</strong> <?= e($branch['addr']) ?></li>
            <li><strong>Hours:</strong> <?= e($branch['hours']) ?></li>
            <li><strong>Phone:</strong> <a href="tel:+639123456789">+63 912 345 6789</a></li>
          </ul>
          <a class="promo-btn" href="index.php#our-vehicles">Book from here <span aria-hidden="true">&#8599;</span></a>
        </div>
      </article>

    <?php } ?>
  </section>

  <section class="loc-delivery" id="delivery-areas">
    <div class="sec-head">
      <h2>Car <span>Delivery</span></h2>
      <p>No branch near you? We deliver the unit to your hotel or resort for a flat &#8369;500 fee.</p>
    </div>
    <div class="loc-delivery-pills">
      <?php foreach ($deliveryAreas as $area) { ?>
        <span class="filter-tab"><?= e($area) ?></span>
      <?php } ?>
    </div>
  </section>

</main>

<?php require 'footer.php'; ?>
