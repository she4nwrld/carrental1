<?php
session_start();

require 'database/config.php';
require 'validation.php';

// naka-login na? wala nay gamit ang login page
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$old    = ['email' => ''];

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

        $sql  = "SELECT id, full_name, password, role FROM users WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        // password_verify mo-compare sa gi-type ug sa hash sa database
        if ($user && password_verify($password, $user['password'])) {

            // bag-ong session id, panalipod sa session fixation
            session_regenerate_id(true);

            $_SESSION['user_id']   = (int) $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            // admin paingon sa dashboard, customer paingon sa next (kung naa) o sa home
            if ($user['role'] === 'admin') {
                $target = 'admin/dashboard.php';
            } else {
                $target = $next !== '' ? $next : 'index.php';
            }
            header('Location: ' . $target);
            exit;
        }

        // usa ra ka message bisan asa ang sayop, para dili mabutyag kinsa naay account
        $errors[] = "Incorrect email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Log In — Shift Car Rental</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/fixes.css">
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
               placeholder="Your password" required>
      </div>

      <button type="submit" class="auth-btn">Log In</button>

    </form>

    <p class="auth-alt">No account yet? <a href="signup.php">Create one</a></p>

  </section>
</main>

</body>
</html>
