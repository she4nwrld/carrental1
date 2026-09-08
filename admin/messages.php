<?php
$base = '../';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../helpers.php';

requireAdmin();

$pdo = getConnection();

/* which set to list */
$views  = ['all', 'unread', 'read'];
$filter = $_GET['view'] ?? 'unread';
if (!in_array($filter, $views, true)) {
    $filter = 'unread';
}

$sql = "SELECT m.*, u.full_name AS account_name
        FROM messages m
        LEFT JOIN users u ON u.id = m.user_id";

if ($filter === 'unread') {
    $sql .= " WHERE m.is_read = 0";
} elseif ($filter === 'read') {
    $sql .= " WHERE m.is_read = 1";
}

$sql .= " ORDER BY m.is_read, m.created_at DESC";

$rows = $pdo->query($sql)->fetchAll();

/* tiles */
$stats = $pdo->query(
    "SELECT COUNT(*) AS total,
            SUM(is_read = 0) AS unread,
            SUM(user_id IS NOT NULL) AS members,
            SUM(created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS week
     FROM messages"
)->fetch();

$flash = '';
if (isset($_GET['updated'])) {
    $flash = 'Message updated.';
} elseif (isset($_GET['removed'])) {
    $flash = 'Message deleted.';
} elseif (isset($_GET['failed'])) {
    $flash = 'That message could not be updated.';
}

$adminTitle = 'Inbox';
require __DIR__ . '/header.php';
?>

<div class="list-head">
  <h1>Contact messages</h1>
  <p class="book-sub">Everything sent through the contact form.</p>
</div>

<?php if ($flash !== '') { ?>
  <div class="flash" role="status"><?= e($flash) ?></div>
<?php } ?>

<div class="stat-grid">
  <div class="stat<?= (int)$stats['unread'] > 0 ? ' stat-warn' : '' ?>">
    <span>Unread</span>
    <strong><?= e(number_format((int)$stats['unread'])) ?></strong>
    <em>needs a reply</em>
  </div>
  <div class="stat">
    <span>All messages</span>
    <strong><?= e(number_format((int)$stats['total'])) ?></strong>
  </div>
  <div class="stat">
    <span>Last 7 days</span>
    <strong><?= e(number_format((int)$stats['week'])) ?></strong>
  </div>
  <div class="stat">
    <span>From account holders</span>
    <strong><?= e(number_format((int)$stats['members'])) ?></strong>
    <em><?= e(number_format((int)$stats['total'] - (int)$stats['members'])) ?> from guests</em>
  </div>
</div>

<div class="filters admin-filters">
  <?php foreach ($views as $v) { ?>
    <a class="filter-tab<?= $v === $filter ? ' is-active' : '' ?><?= $v === 'unread' && (int)$stats['unread'] > 0 ? ' tab-warn' : '' ?>"
       href="messages.php?view=<?= urlencode($v) ?>"><?= e(ucfirst($v)) ?></a>
  <?php } ?>
</div>

<?php if (count($rows) === 0) { ?>

  <div class="list-empty">
    <p>No <?= $filter === 'all' ? '' : e($filter) . ' ' ?>messages.</p>
  </div>

<?php } else { ?>

  <?php foreach ($rows as $m) { ?>

    <article class="ad-card msg-card<?= (int)$m['is_read'] === 0 ? ' msg-new' : '' ?>">

      <div class="ad-body">

        <div class="bk-top">
          <div>
            <h2><?= e($m['name']) ?></h2>
            <p class="bk-ref">
              <?= e(date('M j, Y g:i a', strtotime($m['created_at']))) ?>
              <?php if ($m['account_name'] !== null) { ?>
                &middot; account holder
              <?php } else { ?>
                &middot; guest
              <?php } ?>
            </p>
          </div>
          <span class="pill pill-<?= (int)$m['is_read'] === 0 ? 'pending' : 'completed' ?>">
            <?= (int)$m['is_read'] === 0 ? 'Unread' : 'Read' ?>
          </span>
        </div>

        <div class="ad-customer">
          <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>
          <?php if ($m['phone'] !== null && $m['phone'] !== '') { ?>
            <a href="tel:<?= e($m['phone']) ?>"><?= e($m['phone']) ?></a>
          <?php } else { ?>
            <span class="msg-nophone">no number given</span>
          <?php } ?>
        </div>

        <p class="msg-body"><?= nl2br(e($m['body'])) ?></p>

        <div class="bk-foot">
          <p class="bk-made">
            <a href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: your message to Shift Car Rental') ?>">
              Reply by email
            </a>
          </p>

          <div class="ad-actions">
            <form method="POST" action="function.php">
              <input type="hidden" name="action" value="set_message">
              <input type="hidden" name="message_id" value="<?= e($m['id']) ?>">
              <input type="hidden" name="view" value="<?= e($filter) ?>">
              <input type="hidden" name="is_read" value="<?= (int)$m['is_read'] === 0 ? '1' : '0' ?>">
              <button type="submit" class="ad-btn <?= (int)$m['is_read'] === 0 ? 'ad-ok' : 'ad-no' ?>">
                <?= (int)$m['is_read'] === 0 ? 'Mark handled' : 'Mark unread' ?>
              </button>
            </form>

            <form method="POST" action="function.php"
                  onsubmit="return confirm('Delete this message?');">
              <input type="hidden" name="action" value="drop_message">
              <input type="hidden" name="message_id" value="<?= e($m['id']) ?>">
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
