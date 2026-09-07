<?php
require 'auth.php';
require 'helpers.php';

// mga promo nga makita sa page, plain array para sayon usbon
$deals = [
  [
    'tag'   => 'Early Bird',
    'title' => 'Book 7 days ahead, save 10%',
    'desc'  => 'Reserve any unit at least one week before your pick-up date and we knock 10% off the daily rate. Applies to all categories.',
    'code'  => 'EARLY10',
  ],
  [
    'tag'   => 'Long Trip',
    'title' => '7+ days: 1 day free',
    'desc'  => 'Rent for seven days or more and the seventh day is on us. Perfect for island loops down to Apo Island jump-offs and up to Twin Lakes.',
    'code'  => 'WEEKFREE',
  ],
  [
    'tag'   => 'Airport',
    'title' => 'Free Sibulan Airport meet & greet',
    'desc'  => 'Airport pick-up service is free on all bookings — our agent meets you at arrivals so you skip the taxi line entirely.',
    'code'  => 'No code needed',
  ],
  [
    'tag'   => 'Local',
    'title' => 'Negros Oriental resident discount',
    'desc'  => 'Show a valid ID with a Negros Oriental address at pick-up and get 5% off. Can be combined with the early bird promo.',
    'code'  => 'LOCAL5',
  ],
];

$pageTitle = 'Deals & Promos — Shift Car Rental';
require 'header.php';
?>

<main class="page-main">

  <section class="page-hero" id="deals-hero">
    <h1>Deals &amp; <span>Promos</span></h1>
    <p>Ways to pay less for the same clean, cold-aircon unit. Enter the code in the Discount Code box when booking.</p>
  </section>

  <section class="deals-grid" id="deal-list">
    <?php foreach ($deals as $deal) { ?>

      <article class="deal-card">
        <span class="car-tag"><?= e($deal['tag']) ?></span>
        <h2><?= e($deal['title']) ?></h2>
        <p><?= e($deal['desc']) ?></p>
        <p class="deal-code">Code: <strong><?= e($deal['code']) ?></strong></p>
        <a class="promo-btn" href="index.php#our-vehicles">Use this deal <span aria-hidden="true">&#8599;</span></a>
      </article>

    <?php } ?>
  </section>

  <!-- parehas nga promo banner sa home, para consistent -->
  <section class="promo" id="promo">
    <div class="promo-inner">

      <div class="promo-text">
        <p class="promo-eyebrow">Dumaguete City Car Rentals</p>

        <h2 class="promo-title">
          Book now to get a
          <span>special discount!</span>
        </h2>

        <p class="promo-desc">
          Island roads, waterfalls and dive spots are waiting. Reserve early
          and save on your Dumaguete trip. Free cancellation up to 24 hours
          before pick-up.
        </p>

        <a class="promo-btn" href="index.php#our-vehicles">Book Now <span aria-hidden="true">&#8599;</span></a>
      </div>

      <div class="promo-photo">
        <img src="images/promo-bg.png" alt="Coastline near Dumaguete City" loading="lazy">
      </div>

    </div>
  </section>

</main>

<?php require 'footer.php'; ?>
