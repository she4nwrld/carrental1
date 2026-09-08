<?php
$base = '../';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../helpers.php';

requireAdmin();

$pdo = getConnection();

/* which set to list */
$views  = ['all', 'active', 'quiet'];
$filter = $_GET['view'] ?? 'all';
if (!in_array($filter, $views, true)) {
    $filter = 'all';
}

/* search by name, email or phone */
$term = trim($_GET['q'] ?? '');

$sql = "SELECT u.id, u.full_name, u.email, u.phone, u.role, u.created_at,
          (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS bookings,
          (SELECT COUNT(*) FROM bookings b
            WHERE b.user_id = u.id AND b.status IN ('pending','confirmed')) AS live,
          (SELECT COUNT(*) FROM bookings b
            WHERE b.user_id = u.id AND b.status = 'completed') AS done,
          (SELECT COALESCE(SUM(b.total), 0) FROM bookings b
            WHERE b.user_id = u.id AND b.status IN ('confirmed','completed')) AS spent,
          (SELECT MAX(b.created_at) FROM bookings b WHERE b.user_id = u.id) AS last_booked,
          (SELECT COUNT(*) FROM reviews r WHERE r.user_id = u.id) AS reviews
        FROM users u
        WHERE u.role <> 'admin'";

$args = [];

if ($term !== '') {
    $sql .= " AND (u.full_name LIKE :term OR u.email LIKE :term OR u.phone LIKE :term)";
    $args[':term'] = '%' . $term . '%';
}

if ($filter === 'active') {
    $sql .= " HAVING bookings > 0";
} elseif ($filter === 'quiet') {
    $sql .= " HAVING bookings = 0";
}

$sql .= " ORDER BY spent DESC, u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

/* tiles */
$stats = $pdo->query(
    "SELECT
       COUNT(*) AS total,
       SUM(joined_recently) AS fresh,
       SUM(has_booked) AS buyers,
       COALESCE(SUM(spent), 0) AS revenue
     FROM (
       SELECT u.id,
         (u.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS joined_recently,
         (SELECT COUNT(*) > 0 FROM bookings b WHERE b.user_id = u.id) AS has_booked,
         (SELECT COALESCE(SUM(b.total), 0) FROM bookings b
           WHERE b.user_id = u.id AND b.status IN ('confirmed','completed')) AS spent
       FROM users u
       WHERE u.role <> 'admin'
     ) AS t"
)->fetch();

$adminTitle = 'Customers';
require __DIR__ . '/header.php';
?>

<div class="list-head">
  <h1>Registered customers</h1>
  <p class="book-sub">Highest spend first. Admin accounts are left out.</p>
</div>

<div class="stat-grid">
  <div class="stat">
    <span>Accounts</span>
    <strong><?= e(number_format((int)$stats['total'])) ?></strong>
    <em><?= e(number_format((int)$stats['fresh'])) ?> joined this month</em>
  </div>
  <div class="stat">
    <span>Have booked</span>
    <strong><?= e(number_format((int)$stats['buyers'])) ?></strong>
    <em><?= e(number_format((int)$stats['total'] - (int)$stats['buyers'])) ?> never have</em>
  </div>
  <div class="stat">
    <span>Total booked</span>
    <strong>&#8369;<?= e(number_format((int)$stats['revenue'])) ?></strong>
    <em>confirmed + completed</em>
  </div>
  <div class="stat">
    <span>Average per customer</span>
    <strong>&#8369;<?= e(number_format((int)$stats['buyers'] > 0 ? round($stats['revenue'] / $stats['buyers']) : 0)) ?></strong>
    <em>of those who booked</em>
  </div>
</div>

<form method="GET" action="customers.php" class="adm-search">
  <input type="hidden" name="view" value="<?= e($filter) ?>">
  <input type="search" name="q" value="<?= e($term) ?>" placeholder="Search name, email or phone">
  <button type="submit" class="ad-btn ad-ok">Search</button>
  <?php if ($term !== '') { ?>
    <a class="ad-btn ad-no" href="customers.php?view=<?= urlencode($filter) ?>">Clear</a>
  <?php } ?>
</form>

<div class="filters admin-filters">
  <?php foreach ($views as $v) { ?>
    <a class="filter-tab<?= $v === $filter ? ' is-active' : '' ?>"
       href="customers.php?view=<?= urlencode($v) ?><?= $term !== '' ? '&q=' . urlencode($term) : '' ?>">
      <?= e(ucfirst($v)) ?>
    </a>
  <?php } ?>
</div>

<?php if (count($rows) === 0) { ?>

  <div class="list-empty">
    <p><?= $term !== '' ? 'Nobody matches "' . e($term) . '".' : 'No customers in this list.' ?></p>
  </div>

<?php } else { ?>

  <div class="cust-table">

    <div class="cust-row cust-head">
      <span>Customer</span>
      <span>Contact</span>
      <span>Bookings</span>
      <span>Booked value</span>
      <span>Joined</span>
    </div>

    <?php foreach ($rows as $u) { ?>

      <div class="cust-row">

        <span class="cust-who">
          <strong><?= e($u['full_name']) ?></strong>
          <em>
            <?php if ((int)$u['bookings'] === 0) { ?>
              No bookings yet
            <?php } else { ?>
              Last booked <?= e(date('M j, Y', strtotime($u['last_booked']))) ?>
            <?php } ?>
          </em>
        </span>

        <span class="cust-contact">
          <a href="mailto:<?= e($u['email']) ?>"><?= e($u['email']) ?></a>
          <a href="tel:<?= e($u['phone']) ?>"><?= e($u['phone']) ?></a>
        </span>

        <span class="cust-count">
          <strong><?= e($u['bookings']) ?></strong>
          <em>
            <?= e($u['live']) ?> active &middot; <?= e($u['done']) ?> done
            <?= (int)$u['reviews'] > 0 ? ' &middot; ' . e($u['reviews']) . ' review' . ($u['reviews'] == 1 ? '' : 's') : '' ?>
          </em>
        </span>

        <span class="cust-spend">
          <strong>&#8369;<?= e(number_format((int)$u['spent'])) ?></strong>
        </span>

        <span class="cust-joined">
          <?= e(date('M j, Y', strtotime($u['created_at']))) ?>
        </span>

      </div>

    <?php } ?>

  </div>

<?php } ?>

<?php require __DIR__ . '/footer.php'; ?>
