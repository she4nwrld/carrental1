<?php
// auth.php mao ang nag-set sa cookie params ug nag-start sa session
require_once __DIR__ . '/auth.php';

require 'database/config.php';
require 'validation.php';

// naka-login na? balik sa home, wala nay gamit ang signup
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$old    = ['full_name' => '', 'email' => '', 'phone' => ''];   // para dili mahurot ang gi-type

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getConnection();

    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';           // dili i-trim, basin space ang parte sa password
    $confirm  = $_POST['confirm_password'] ?? '';

    $old = ['full_name' => $fullName, 'email' => $email, 'phone' => $phone];

    // array_filter mo-tangtang sa tanan null, mabilin ra ang mga error
    $errors = array_values(array_filter([
        validateRequired($fullName, 'Full name'),
        validateMaxLength($fullName, 'Full name', 100),
        validateEmailFormat($email),
        validatePhone($phone),
        validatePassword($password),
        validatePasswordMatch($password, $confirm),
    ]));

    // ang database check lang human sa format check, para dili sagi ang query
    if (empty($errors) && emailTaken($pdo, $email)) {
        $errors[] = "That email is already registered.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    try {
        // password_hash, dili plain text — walay makakita sa tinuod nga password
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (full_name, email, phone, password)
                VALUES (:full_name, :email, :phone, :password)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':full_name', $fullName);
        $stmt->bindValue(':email',     $email);
        $stmt->bindValue(':phone',     $phone);
        $stmt->bindValue(':password',  $hash);
        $stmt->execute();

        // bag-ong session id una mo-login, panalipod sa session fixation
        session_regenerate_id(true);

        // auto-login dayon human sa signup
        $_SESSION['user_id']   = (int) $pdo->lastInsertId();
        $_SESSION['user_name'] = $fullName;
        $_SESSION['role']      = 'customer';
        $_SESSION['last_seen'] = time();

        header('Location: index.php?welcome=1');
        exit;
    } catch (PDOException $e) {
        error_log('Signup failed: ' . $e->getMessage());
        $errors[] = "Could not create your account. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create an Account — Shift Car Rental</title>
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
    <a href="login.php">Already have an account?</a>
  </div>
</header>

<main class="auth-main">
  <section class="auth-card">

    <h1>Create your account</h1>
    <p class="auth-sub">Sign up to book a car and track your reservations.</p>

    <?php if (!empty($errors)) { ?>
      <!-- tanan sayop gi-lista dinhi, dili usa-usa -->
      <div class="auth-errors" role="alert">
        <ul>
          <?php foreach ($errors as $err) { ?>
            <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
          <?php } ?>
        </ul>
      </div>
    <?php } ?>

    <form method="POST" action="signup.php" class="auth-form" novalidate>

      <div class="auth-field">
        <label for="full_name">Full Name</label>
        <!-- value gikan sa $old, para dili mag-type balik kung naay sayop -->
        <input type="text" id="full_name" name="full_name" maxlength="100"
               value="<?= htmlspecialchars($old['full_name'], ENT_QUOTES, 'UTF-8') ?>"
               placeholder="Juan Dela Cruz" required>
      </div>

      <div class="auth-field">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8') ?>"
               placeholder="juan@example.com" required>
      </div>

      <div class="auth-field">
        <label for="phone">Mobile Number</label>
        <input type="text" id="phone" name="phone"
               value="<?= htmlspecialchars($old['phone'], ENT_QUOTES, 'UTF-8') ?>"
               placeholder="09171234567" required>
      </div>

      <div class="auth-field">
        <label for="password">Password</label>
        <!-- walay value dinhi, bawal i-repopulate ang password -->
        <input type="password" id="password" name="password"
               autocomplete="new-password"
               placeholder="At least 8 characters" required>
        <small>Must be 8+ characters with a letter, a number and a special character (e.g. ! @ # $).</small>
      </div>

      <div class="auth-field">
        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password"
               autocomplete="new-password"
               placeholder="Re-type your password" required>
      </div>

      <button type="submit" class="auth-btn">Create Account</button>

    </form>

    <p class="auth-alt">Already registered? <a href="login.php">Log in instead</a></p>

  </section>
</main>

</body>
</html>
