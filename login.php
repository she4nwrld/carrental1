<?php
// auth.php mao ang nag-set sa cookie params ug nag-start sa session
require_once __DIR__ . '/auth.php';

require 'database/config.php';
require 'validation.php';

// naka-login na? wala nay gamit ang login page
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$old    = ['email' => ''];

// gikan sa inactivity timeout sa auth.php
$timedOut = !empty($_SESSION['timed_out']);
unset($_SESSION['timed_out']);

// gikan sa requireLogin() o sa Book Now modal — asa mo-balik human sa login
// gidawat ra ang relative path para walay open-redirect
$next = $_GET['next'] ?? $_POST['next'] ?? '';
if ($next !== '' && (str_contains($next, '//') || $next[0] === '/' && ($next[1] ?? '') === '/' || preg_match('/^[a-z]+:/i', $next))) {
    $next = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $old['email'] = $email;

    $errors = array_values(array_filter([
        validateRequired($email, 'Email'),
        validateRequired($password, 'Password'),
    ]));

    if (empty($errors)) {
        $pdo = getConnection();

        $sql  = "SELECT id, full_name, password, role, is_active FROM users WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        // password_verify mo-compare sa gi-type ug sa hash sa database
        if ($user && password_verify($password, $user['password'])) {

            // gi-check human sa password, para dili mabutyag kinsa naay account
            if ((int) $user['is_active'] === 0) {
                $errors[] = "This account has been disabled. Please contact us for help.";
            } else {

                // bag-ong session id, panalipod sa session fixation
                session_regenerate_id(true);

                $_SESSION['user_id']   = (int) $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['last_seen'] = time();

                // admin paingon sa dashboard, customer paingon sa next (kung naa) o sa home
                if ($user['role'] === 'admin') {
                    $target = 'admin/dashboard.php';
                } else {
                    $target = $next !== '' ? $next : 'index.php';
                }
                header('Location: ' . $target);
                exit;
            }
        }

        // usa ra ka message bisan asa ang sayop, para dili mabutyag kinsa naay account
        if (empty($errors)) {
            $errors[] = "Incorrect email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Log In — Shift Car Rental</title>
<link rel="icon" type="image/png" href="images/shift-mark.png?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>

<body class="auth-page">

<header class="topbar">
  <div class="brand">
    <a href="index.php">
      <?php if (file_exists(__DIR__ . '/images/shift-logo.png')) { ?>
        <img src="images/shift-logo.png" alt="Shift Car Rental">
      <?php } else { ?>
        <span class="wordmark">SHI<span>F</span>T</span>
      <?php } ?>
    </a>
  </div>
  <div class="right">
    <a href="signup.php">Need an account?</a>
  </div>
</header>

<main class="auth-main">
  <section class="auth-card">

    <h1>Welcome back</h1>
    <p class="auth-sub">Log in to manage your bookings.</p>

    <?php if ($timedOut) { ?>
      <div class="auth-errors" role="status">
        <ul><li>You were logged out after 30 minutes of inactivity. Please log in again.</li></ul>
      </div>
    <?php } ?>

    <?php if (!empty($errors)) { ?>
      <div class="auth-errors" role="alert">
        <ul>
          <?php foreach ($errors as $err) { ?>
            <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
          <?php } ?>
        </ul>
      </div>
    <?php } ?>

    <form method="POST" action="login.php" class="auth-form" novalidate>

      <?php if ($next !== '') { ?>
        <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">
      <?php } ?>

      <div class="auth-field">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8') ?>"
               placeholder="juan@example.com" required>
      </div>

      <div class="auth-field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               autocomplete="current-password"
               placeholder="Your password" required>
      </div>

      <button type="submit" class="auth-btn">Log In</button>

    </form>

        <p class="auth-alt">No account yet? <a href="signup.php">Create one</a></p>

  </section>
</main>

</body>
</html>
