<?php
require 'auth.php';
require 'database/config.php';
require_once 'helpers.php';

$pdo = getConnection();

// gikan na sa promos table, dili na hardcoded — usa ra ka source of truth
$stmt = $pdo->prepare(
  "SELECT code, tag, title, blurb, percent, free_days,
          min_days, min_advance_days, stackable, expires_at
   FROM promos
   WHERE active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE())
   ORDER BY sort_order, id"
);
$stmt->execute();
$deals = $stmt->fetchAll();

/* ---------- ang mga kondisyon, gikan sa parehong columns nga
             gi-check sa promoIssues() — dili manual nga sulat ---------- */
function dealTerms(array $d): array
{
  $terms = [];

  if ((int) $d['percent'] > 0) {
    $terms[] = (int) $d['percent'] . '% off the daily rate';
  }
  if ((int) $d['free_days'] > 0) {
    $n = (int) $d['free_days'];
    $terms[] = $n . ' free day' . ($n > 1 ? 's' : '');
  }
  if ((int) $d['min_days'] > 0) {
    $terms[] = 'Minimum ' . (int) $d['min_days'] . ' days';
  }
  if ((int) $d['min_advance_days'] > 0) {
    $terms[] = 'Book ' . (int) $d['min_advance_days'] . '+ days ahead';
  }
  if ($d['code'] !== null && $d['code'] !== '') {
    $terms[] = (int) $d['stackable']
      ? 'Can be combined with other stackable codes'
      : 'Cannot be combined with other codes';
  }
  if (!empty($d['expires_at'])) {
    $terms[] = 'Until ' . date('M j, Y', strtotime($d['expires_at']));
  }

  return $terms;
}

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
      <?php $terms = dealTerms($deal); ?>

      <article class="deal-card">
        <span class="car-tag"><?= e($deal['tag']) ?></span>
        <h2><?= e($deal['title']) ?></h2>
        <p><?= e($deal['blurb']) ?></p>

        <?php if ($terms) { ?>
          <ul class="deal-terms">
            <?php foreach ($terms as $t) { ?>
              <li><?= e($t) ?></li>
            <?php } ?>
          </ul>
        <?php } ?>

        <?php if ($deal['code'] !== null && $deal['code'] !== '') { ?>
          <p class="deal-code">Code: <strong><?= e($deal['code']) ?></strong></p>
          <!-- paingon sa vehicles.php para makita dayon kung applicable ba ang code -->
          <a class="promo-btn"
             href="vehicles.php?discount_code=<?= urlencode($deal['code']) ?>#all-vehicles">
            Use this deal <span aria-hidden="true">&#8599;</span>
          </a>
        <?php } else { ?>
          <p class="deal-code muted">No code needed — automatic on every booking</p>
          <a class="promo-btn" href="vehicles.php#all-vehicles">
            Browse vehicles <span aria-hidden="true">&#8599;</span>
          </a>
        <?php } ?>
      </article>

    <?php } ?>

    <?php if (count($deals) === 0) { ?>
      <p class="no-cars">No active promos right now. Check back soon.</p>
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

        <a class="promo-btn" href="vehicles.php#all-vehicles">Book Now <span aria-hidden="true">&#8599;</span></a>
      </div>

      <div class="promo-photo">
        <img src="images/promo-bg.png" alt="Coastline near Dumaguete City" loading="lazy">
      </div>

    </div>
  </section>

</main>

<?php require 'footer.php'; ?>
