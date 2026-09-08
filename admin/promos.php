<?php
$base = '../';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../helpers.php';

requireAdmin();

$pdo = getConnection();

/* which set to list */
$views  = ['all', 'live', 'hidden', 'expired'];
$filter = $_GET['view'] ?? 'all';
if (!in_array($filter, $views, true)) {
    $filter = 'all';
}

$sql = "SELECT p.*,
          (SELECT COUNT(*) FROM bookings b
            WHERE b.discount_code IS NOT NULL
              AND FIND_IN_SET(p.code, b.discount_code)) AS uses
        FROM promos p";

if ($filter === 'live') {
    $sql .= " WHERE p.active = 1 AND (p.expires_at IS NULL OR p.expires_at >= CURDATE())";
} elseif ($filter === 'hidden') {
    $sql .= " WHERE p.active = 0";
} elseif ($filter === 'expired') {
    $sql .= " WHERE p.expires_at IS NOT NULL AND p.expires_at < CURDATE()";
}

$sql .= " ORDER BY p.sort_order, p.id";

$rows = $pdo->query($sql)->fetchAll();

/* tiles */
$stats = $pdo->query(
    "SELECT COUNT(*) AS total,
            SUM(active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE())) AS live,
            SUM(active = 0) AS off,
            SUM(expires_at IS NOT NULL AND expires_at < CURDATE()) AS gone
     FROM promos"
)->fetch();

$flash = '';
if (isset($_GET['added'])) {
    $flash = 'Promo added.';
} elseif (isset($_GET['saved'])) {
    $flash = 'Promo updated.';
} elseif (isset($_GET['toggled'])) {
    $flash = 'Promo switched.';
} elseif (isset($_GET['removed'])) {
    $flash = 'Promo deleted. Past bookings keep their code.';
} elseif (isset($_GET['dupe'])) {
    $flash = 'That code already exists. Pick a different one.';
} elseif (isset($_GET['failed'])) {
    $flash = 'Check the fields and try again.';
}

/* one-line summary of what a promo actually does */
function promoRule(array $p): string {
    $bits = [];

    if ((int)$p['percent'] > 0) {
        $bits[] = $p['percent'] . '% off';
    }
    if ((int)$p['free_days'] > 0) {
        $bits[] = $p['free_days'] . ' free day' . ($p['free_days'] == 1 ? '' : 's');
    }
    if (!$bits) {
        $bits[] = 'No discount — information only';
    }

    if ((int)$p['min_days'] > 0) {
        $bits[] = 'needs ' . $p['min_days'] . '+ days';
    }
    if ((int)$p['min_advance_days'] > 0) {
        $bits[] = 'book ' . $p['min_advance_days'] . ' days ahead';
    }

    $bits[] = $p['stackable'] ? 'stackable' : 'on its own';

    return implode(' · ', $bits);
}

$adminTitle = 'Promos';
require __DIR__ . '/header.php';
?>

<div class="list-head">
  <h1>Deals and codes</h1>
  <p class="book-sub">Edits show on the Deals page and the booking form right away.</p>
</div>

<?php if ($flash !== '') { ?>
  <div class="flash" role="status"><?= e($flash) ?></div>
<?php } ?>

<div class="stat-grid">
  <div class="stat">
    <span>Total promos</span>
    <strong><?= e(number_format((int)$stats['total'])) ?></strong>
  </div>
  <div class="stat">
    <span>Live now</span>
    <strong><?= e(number_format((int)$stats['live'])) ?></strong>
  </div>
  <div class="stat">
    <span>Switched off</span>
    <strong><?= e(number_format((int)$stats['off'])) ?></strong>
  </div>
  <div class="stat">
    <span>Expired</span>
    <strong><?= e(number_format((int)$stats['gone'])) ?></strong>
  </div>
</div>

<!-- add form, collapsed until needed -->
<details class="adm-add">
  <summary>Add a promo</summary>

  <form method="POST" action="function.php" class="car-form">
    <input type="hidden" name="action" value="add_promo">

    <label>Code
      <input type="text" name="code" maxlength="20" placeholder="EARLY10">
    </label>

    <label>Tag
      <input type="text" name="tag" maxlength="30" required placeholder="Early Bird">
    </label>

    <label class="wide">Title
      <input type="text" name="title" maxlength="120" required placeholder="Book 7 days ahead, save 10%">
    </label>

    <label class="wide">Blurb
      <textarea name="blurb" rows="3" maxlength="400" required placeholder="Shown on the Deals page."></textarea>
    </label>

    <label>Percent off
      <input type="number" name="percent" min="0" max="100" value="0" required>
    </label>

    <label>Free days
      <input type="number" name="free_days" min="0" max="30" value="0" required>
    </label>

    <label>Minimum days
      <input type="number" name="min_days" min="0" max="90" value="0" required>
    </label>

    <label>Days in advance
      <input type="number" name="min_advance_days" min="0" max="365" value="0" required>
    </label>

    <label>Stackable
      <select name="stackable">
        <option value="0">No</option>
        <option value="1">Yes</option>
      </select>
    </label>

    <label>Expires
      <input type="date" name="expires_at">
    </label>

    <label>Sort order
      <input type="number" name="sort_order" min="0" max="99" value="0" required>
    </label>

    <div class="wide">
      <button type="submit" class="ad-btn ad-ok">Add promo</button>
      <span class="form-note">Leave the code blank for a standing offer with nothing to type in.</span>
    </div>
  </form>
</details>

<div class="filters admin-filters">
  <?php foreach ($views as $v) { ?>
    <a class="filter-tab<?= $v === $filter ? ' is-active' : '' ?>"
       href="promos.php?view=<?= urlencode($v) ?>"><?= e(ucfirst($v)) ?></a>
  <?php } ?>
</div>

<?php if (count($rows) === 0) { ?>

  <div class="list-empty">
    <p>No promos in this list.</p>
  </div>

<?php } else { ?>

  <?php foreach ($rows as $p) {

    $expired = $p['expires_at'] !== null && $p['expires_at'] < date('Y-m-d');
    $liveNow = (int)$p['active'] === 1 && !$expired;

    if ($expired) {
        $pillClass = 'cancelled';
        $pillText  = 'Expired';
    } elseif ($liveNow) {
        $pillClass = 'completed';
        $pillText  = 'Live';
    } else {
        $pillClass = 'pending';
        $pillText  = 'Off';
    }
  ?>

    <article class="ad-card promo-card<?= $liveNow ? '' : ' car-off' ?>">

      <div class="ad-body">

        <div class="bk-top">
          <div>
            <h2><?= e($p['title']) ?></h2>
            <p class="bk-ref">
              <?= e($p['tag']) ?> &middot;
              <?= $p['code'] === null || $p['code'] === '' ? 'no code needed' : e($p['code']) ?>
            </p>
          </div>
          <span class="pill pill-<?= e($pillClass) ?>"><?= e($pillText) ?></span>
        </div>

        <p class="promo-rule"><?= e(promoRule($p)) ?></p>
        <p class="promo-blurb"><?= e($p['blurb']) ?></p>

        <div class="bk-grid">
          <div><span>Discount</span><strong><?= (int)$p['percent'] > 0 ? e($p['percent']) . '%' : '&mdash;' ?></strong><em><?= (int)$p['free_days'] > 0 ? e($p['free_days']) . ' free day' . ($p['free_days'] == 1 ? '' : 's') : 'no free days' ?></em></div>
          <div><span>Conditions</span><strong><?= (int)$p['min_days'] > 0 ? e($p['min_days']) . ' days min' : 'Any length' ?></strong><em><?= (int)$p['min_advance_days'] > 0 ? e($p['min_advance_days']) . ' days ahead' : 'any time' ?></em></div>
          <div><span>Expiry</span><strong><?= $p['expires_at'] === null ? 'None' : e(date('M j, Y', strtotime($p['expires_at']))) ?></strong><em>sort <?= e($p['sort_order']) ?></em></div>
          <div><span>Used by</span><strong><?= e($p['uses']) ?></strong><em>booking<?= $p['uses'] == 1 ? '' : 's' ?></em></div>
        </div>

        <!-- edit form, collapsed -->
        <details class="adm-edit">
          <summary>Edit promo</summary>

          <form method="POST" action="function.php" class="car-form">
            <input type="hidden" name="action" value="edit_promo">
            <input type="hidden" name="promo_id" value="<?= e($p['id']) ?>">
            <input type="hidden" name="view" value="<?= e($filter) ?>">

            <label>Code
              <input type="text" name="code" maxlength="20" value="<?= e($p['code']) ?>">
            </label>

            <label>Tag
              <input type="text" name="tag" maxlength="30" value="<?= e($p['tag']) ?>" required>
            </label>

            <label class="wide">Title
              <input type="text" name="title" maxlength="120" value="<?= e($p['title']) ?>" required>
            </label>

            <label class="wide">Blurb
              <textarea name="blurb" rows="3" maxlength="400" required><?= e($p['blurb']) ?></textarea>
            </label>

            <label>Percent off
              <input type="number" name="percent" min="0" max="100" value="<?= e($p['percent']) ?>" required>
            </label>

            <label>Free days
              <input type="number" name="free_days" min="0" max="30" value="<?= e($p['free_days']) ?>" required>
            </label>

            <label>Minimum days
              <input type="number" name="min_days" min="0" max="90" value="<?= e($p['min_days']) ?>" required>
            </label>

            <label>Days in advance
              <input type="number" name="min_advance_days" min="0" max="365" value="<?= e($p['min_advance_days']) ?>" required>
            </label>

            <label>Stackable
              <select name="stackable">
                <option value="0"<?= $p['stackable'] ? '' : ' selected' ?>>No</option>
                <option value="1"<?= $p['stackable'] ? ' selected' : '' ?>>Yes</option>
              </select>
            </label>

            <label>Expires
              <input type="date" name="expires_at" value="<?= e($p['expires_at']) ?>">
            </label>

            <label>Sort order
              <input type="number" name="sort_order" min="0" max="99" value="<?= e($p['sort_order']) ?>" required>
            </label>

            <div class="wide">
              <button type="submit" class="ad-btn ad-ok">Save changes</button>
            </div>
          </form>
        </details>

        <div class="bk-foot">
          <p class="bk-made">
            <?php if ($expired) { ?>
              Ran out on <?= e(date('M j, Y', strtotime($p['expires_at']))) ?>
            <?php } elseif ($liveNow) { ?>
              Showing on the Deals page
            <?php } else { ?>
              Hidden from customers
            <?php } ?>
          </p>

          <div class="ad-actions">
            <form method="POST" action="function.php">
              <input type="hidden" name="action" value="toggle_promo">
              <input type="hidden" name="promo_id" value="<?= e($p['id']) ?>">
              <input type="hidden" name="view" value="<?= e($filter) ?>">
              <input type="hidden" name="active" value="<?= $p['active'] ? '0' : '1' ?>">
              <button type="submit" class="ad-btn <?= $p['active'] ? 'ad-no' : 'ad-ok' ?>">
                <?= $p['active'] ? 'Switch off' : 'Switch on' ?>
              </button>
            </form>

            <form method="POST" action="function.php"
                  onsubmit="return confirm('Delete this promo? Past bookings keep their code.');">
              <input type="hidden" name="action" value="drop_promo">
              <input type="hidden" name="promo_id" value="<?= e($p['id']) ?>">
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
