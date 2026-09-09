<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database/config.php';
require_once __DIR__ . '/helpers.php';

requireLogin();

$pdo = getConnection();

$stmt = $pdo->prepare(
    "SELECT full_name, email, phone, is_active, created_at
     FROM users WHERE id = :id"
);
$stmt->bindValue(':id', currentUserId(), PDO::PARAM_INT);
$stmt->execute();
$me = $stmt->fetch();

// gi-delete o gi-disable samtang naka-login pa, pagawson dayon
if (!$me || (int)$me['is_active'] === 0) {
    header('Location: logout.php');
    exit;
}

$infoErrors = $_SESSION['profile_errors']  ?? [];
$old        = $_SESSION['profile_old']     ?? [];
$passErrors = $_SESSION['password_errors'] ?? [];
unset($_SESSION['profile_errors'], $_SESSION['profile_old'], $_SESSION['password_errors']);

$pageTitle = 'My Profile — Shift Car Rental';
require __DIR__ . '/header.php';
?>

<main class="pf-wrap">

  <div class="pf-head">
    <h1>My Profile</h1>
    <p class="pf-sub">Member since <?= e(date('F j, Y', strtotime($me['created_at']))) ?></p>
  </div>

  <?php if (isset($_GET['saved'])) { ?>
    <div class="pf-flash">Your details have been updated.</div>
  <?php } ?>

  <?php if (isset($_GET['password'])) { ?>
    <div class="pf-flash">Your password has been changed.</div>
  <?php } ?>

  <section class="pf-card">
    <h2>Account details</h2>

    <?php if (!empty($infoErrors)) { ?>
      <div class="pf-errors" role="alert">
        <ul>
          <?php foreach ($infoErrors as $err) { ?>
            <li><?= e($err) ?></li>
          <?php } ?>
        </ul>
      </div>
    <?php } ?>

    <form method="POST" action="function.php" class="pf-form" novalidate>
      <input type="hidden" name="action" value="update_profile">

      <div class="pf-field">
        <label for="full_name">Full Name</label>
        <input type="text" id="full_name" name="full_name" maxlength="100" required
               value="<?= e($old['full_name'] ?? $me['full_name']) ?>">
      </div>

      <div class="pf-field">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required
               value="<?= e($old['email'] ?? $me['email']) ?>">
      </div>

      <div class="pf-field">
        <label for="phone">Mobile Number</label>
        <input type="tel" id="phone" name="phone" required
               placeholder="09171234567"
               value="<?= e($old['phone'] ?? (string)$me['phone']) ?>">
      </div>

      <button type="submit" class="pf-btn">Save Changes</button>
    </form>
  </section>

  <section class="pf-card">
    <h2>Change password</h2>

    <?php if (!empty($passErrors)) { ?>
      <div class="pf-errors" role="alert">
        <ul>
          <?php foreach ($passErrors as $err) { ?>
            <li><?= e($err) ?></li>
          <?php } ?>
        </ul>
      </div>
    <?php } ?>

    <form method="POST" action="function.php" class="pf-form" novalidate>
      <input type="hidden" name="action" value="change_password">

      <div class="pf-field">
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password"
               autocomplete="current-password" required>
      </div>

      <div class="pf-field">
        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password"
               autocomplete="new-password" required>
        <small>At least 8 characters, with a letter, a number and a special character.</small>
      </div>

      <div class="pf-field">
        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password"
               autocomplete="new-password" required>
      </div>

      <button type="submit" class="pf-btn">Update Password</button>
    </form>
  </section>

  <p class="pf-back"><a href="bookings.php">&larr; Back to my bookings</a></p>

</main>

<?php require __DIR__ . '/footer.php'; ?>
