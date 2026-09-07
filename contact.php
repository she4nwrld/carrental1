<?php
require 'auth.php';
require 'helpers.php';
require 'validation.php';

$errors = [];
$sent   = false;
$old    = ['name' => '', 'email' => '', 'phone' => '', 'message' => ''];

// kung naka-login, i-prefill ang ngalan para dili na mag-type
if (isLoggedIn()) {
    $old['name'] = currentUserName();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $old = ['name' => $name, 'email' => $email, 'phone' => $phone, 'message' => $message];

    // parehas nga validators sa signup.php
    $errors = array_values(array_filter([
        validateRequired($name, 'Name'),
        validateRequired($email, 'Email'),
        $email !== '' ? validateEmailFormat($email) : null,
        $phone !== '' ? validatePhone($phone) : null,   // optional ang phone
        validateRequired($message, 'Message'),
    ]));

    if (empty($errors)) {
        /* walay mail server sa sandbox, so i-log nalang sa file —
           ilisan ug mail() o PHPMailer kung naa nay SMTP */
        $line = date('Y-m-d H:i:s') . " | $name | $email | $phone | " . str_replace(["\r", "\n"], ' ', $message) . PHP_EOL;
        @file_put_contents(__DIR__ . '/database/messages.log', $line, FILE_APPEND | LOCK_EX);

        $sent = true;
        $old  = ['name' => '', 'email' => '', 'phone' => '', 'message' => ''];
    }
}

$pageTitle = 'Contact Us — Shift Car Rental';
require 'header.php';
?>

<main class="page-main">

  <section class="page-hero" id="contact-hero">
    <h1>Contact <span>Us</span></h1>
    <p>Questions about a booking, delivery or long-term rental? Message us and we reply within the day.</p>
  </section>

  <section class="contact-wrap" id="contact-body">

    <!-- wala: mga paagi sa pag-contact -->
    <aside class="contact-info">
      <h2>Get in touch</h2>
      <ul>
        <li><strong>Phone / Viber:</strong> <a href="tel:+639123456789">+63 912 345 6789</a></li>
        <li><strong>Email:</strong> <a href="mailto:hello@shiftcarrental.ph">hello@shiftcarrental.ph</a></li>
        <li><strong>Main office:</strong> Rizal Boulevard, Dumaguete City, Negros Oriental 6200</li>
        <li><strong>Hours:</strong> Mon&ndash;Sun 7:00 am &ndash; 11:00 pm</li>
      </ul>
      <p>For urgent roadside help during a rental, use the 24/7 number in your glovebox card.</p>
    </aside>

    <!-- tuo: message form -->
    <div class="contact-form-side">

      <?php if ($sent) { ?>
        <p class="contact-sent">Thanks! Your message has been received &mdash; we will get back to you within the day.</p>
      <?php } ?>

      <?php if (!empty($errors)) { ?>
        <div class="auth-errors">
          <ul>
            <?php foreach ($errors as $err) { ?>
              <li><?= e($err) ?></li>
            <?php } ?>
          </ul>
        </div>
      <?php } ?>

      <form method="post" action="contact.php" novalidate>

        <div class="auth-field">
          <label for="contact-name">Name</label>
          <input type="text" id="contact-name" name="name" value="<?= e($old['name']) ?>" required>
        </div>

        <div class="auth-field">
          <label for="contact-email">Email</label>
          <input type="email" id="contact-email" name="email" value="<?= e($old['email']) ?>" required>
        </div>

        <div class="auth-field">
          <label for="contact-phone">Mobile number (optional)</label>
          <input type="tel" id="contact-phone" name="phone" value="<?= e($old['phone']) ?>" placeholder="09171234567">
        </div>

        <div class="auth-field">
          <label for="contact-message">Message</label>
          <textarea id="contact-message" name="message" rows="6" required><?= e($old['message']) ?></textarea>
        </div>

        <button class="auth-btn" type="submit">Send Message</button>

      </form>
    </div>

  </section>

</main>

<?php require 'footer.php'; ?>
