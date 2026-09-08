<?php
$base = '../';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../helpers.php';

requireAdmin();

$pdo = getConnection();

// Which set to list
$views  = ['all', 'visible', 'hidden'];
$filter = $_GET['view'] ?? 'all';
if (!in_array($filter, $views, true)) {
    $filter = 'all';
}

$sql = "SELECT r.*, u.full_name, u.email
        FROM reviews r
        JOIN users u ON u.id = r.user_id";

if ($filter === 'visible') {
    $sql .= " WHERE r.approved = 1";
} elseif ($filter === 'hidden') {
    $sql .= " WHERE r.approved = 0";
}

$sql .= " ORDER BY r.created_at DESC";

$rows = $pdo->query($sql)->fetchAll();

// Tiles
$stats = $pdo->query(
    "SELECT COUNT(*) AS total,
            SUM(approved = 1) AS shown,
            SUM(approved = 0) AS hidden,
            ROUND(AVG(CASE WHEN approved = 1 THEN rating END), 1) AS avg_rating
     FROM reviews"
)->fetch();

$flash = '';
if (isset($_GET['updated'])) {
    $flash = 'Review updated.';
} elseif (isset($_GET['removed'])) {
    $flash = 'Review deleted.';
} elseif (isset($_GET['failed'])) {
    $flash = 'That review could not be updated.';
}

function stars(int $n): string {
    return str_repeat("\u{2605}", $n) . str_repeat("\u{2606}", 5 - $n);
}

$adminTitle = 'Reviews';
require __DIR__ . '/header.php';
?>

<div class="list-head">
  <h1>Customer reviews</h1>
  <p class="book-sub">Hide anything abusive. Everything else stays live.</p>
</div>

<?php if ($flash !== '') { ?>
  <div class="flash" role="status"><?= e($flash) ?></div>
<?php } ?>

<div class="stat-grid">
  <div class="stat">
    <span>Total reviews</span>
    <strong><?= e(number_format((int)$stats['total'])) ?></strong>
  </div>
  <div class="stat">
    <span>Live on site</span>
    <strong><?= e(number_format((int)$stats['shown'])) ?></strong>
  </div>
  <div class="stat">
    <span>Hidden</span>
    <strong><?= e(number_format((int)$stats['hidden'])) ?></strong>
  </div>
  <div class="stat">
    <span>Average rating</span>
    <strong><?= $stats['avg_rating'] === null ? '&mdash;' : e($stats['avg_rating']) ?></strong>
  </div>
</div>

<div class="filters admin-filters">
  <?php foreach ($views as $v) { ?>
    <a class="filter-tab<?= $v === $filter ? ' is-active' : '' ?>"
       href="reviews.php?view=<?= urlencode($v) ?>"><?= e(ucfirst($v)) ?></a>
  <?php } ?>
</div>

<?php if (count($rows) === 0) { ?>

  <div class="list-empty">
    <p>No <?= $filter === 'all' ? '' : e($filter) . ' ' ?>reviews to show.</p>
  </div>

<?php } else { ?>

  <?php foreach ($rows as $rv) { ?>

    <article class="ad-card rv-row">

      <div class="ad-body">

        <div class="bk-top">
          <div>
            <h2><?= e($rv['full_name']) ?></h2>
            <p class="bk-ref"><a href="mailto:<?= e($rv['email']) ?>"><?= e($rv['email']) ?></a></p>
          </div>
          <span class="pill pill-<?= $rv['approved'] ? 'confirmed' : 'cancelled' ?>">
            <?= $rv['approved'] ? 'Live' : 'Hidden' ?>
          </span>
        </div>

        <p class="rv-score">
          <span class="rv-stars" aria-hidden="true"><?= stars((int)$rv['rating']) ?></span>
          <span class="rv-outof"><?= e($rv['rating']) ?> of 5</span>
        </p>

        <p class="rv-text"><?= nl2br(e($rv['review_text'])) ?></p>

        <div class="bk-foot">
          <p class="bk-made">Posted <?= e(date('M j, Y g:i a', strtotime($rv['created_at']))) ?></p>

          <div class="ad-actions">
            <form method="POST" action="function.php">
              <input type="hidden" name="action" value="set_review">
              <input type="hidden" name="review_id" value="<?= e($rv['id']) ?>">
              <input type="hidden" name="view" value="<?= e($filter) ?>">
              <input type="hidden" name="approved" value="<?= $rv['approved'] ? '0' : '1' ?>">
              <button type="submit" class="ad-btn <?= $rv['approved'] ? 'ad-no' : 'ad-ok' ?>">
                <?= $rv['approved'] ? 'Hide' : 'Show' ?>
              </button>
            </form>

            <form method="POST" action="function.php"
                  onsubmit="return confirm('Delete this review for good?');">
              <input type="hidden" name="action" value="drop_review">
              <input type="hidden" name="review_id" value="<?= e($rv['id']) ?>">
              <input type="hidden" name="view" value="<?= e($filter) ?>">
              <button type="submit" class="ad-btn ad-no">Delete</button>
            </form>
          </div>
        </div>

      </div>

    </article>

  <?php } ?>

<?php } ?>

<?php require __DIR__ . '/footer.php'; ?>
