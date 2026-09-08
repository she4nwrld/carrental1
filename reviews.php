<?php
require 'auth.php';
require 'database/config.php';
require 'helpers.php';

$pdo = getConnection();

// Approved reviews, newest first
$sql = "SELECT r.rating, r.review_text, r.created_at, u.full_name
        FROM reviews r
        JOIN users u ON u.id = r.user_id
        WHERE r.approved = 1
        ORDER BY r.created_at DESC";
$customerReviews = $pdo->query($sql)->fetchAll();

// Logged in user: their own review and whether a rental is finished
$myReview  = null;
$canReview = false;

if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT rating, review_text, approved FROM reviews WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', currentUserId(), PDO::PARAM_INT);
    $stmt->execute();
    $myReview = $stmt->fetch() ?: null;

    $stmt = $pdo->prepare("SELECT COUNT(*) AS done FROM bookings
                           WHERE user_id = :user_id AND status = 'completed'");
    $stmt->bindValue(':user_id', currentUserId(), PDO::PARAM_INT);
    $stmt->execute();
    $canReview = (int)$stmt->fetch()['done'] > 0;
}

// Errors and old input from function.php
$errors = $_SESSION['review_errors'] ?? [];
$old    = $_SESSION['review_old'] ?? [];
unset($_SESSION['review_errors'], $_SESSION['review_old']);

// Prefill order: flash old, saved review, blank
$formRating = $old['rating'] ?? ($myReview['rating'] ?? '5');
$formText   = $old['review_text'] ?? ($myReview['review_text'] ?? '');

// Flash messages
$flash = '';
if (isset($_GET['reviewed'])) {
    $flash = 'Thank you! Your review has been posted.';
} elseif (isset($_GET['deleted'])) {
    $flash = 'Your review has been removed.';
}

// Real count and average
$rvCount = count($customerReviews);
$rvAvg   = $rvCount > 0
    ? round(array_sum(array_column($customerReviews, 'rating')) / $rvCount, 1)
    : 0;

// Same blend as the homepage so the numbers match
$blendCount = 1989 + $rvCount;
$blendAvg   = round(((4.9 * 1989) + ($rvAvg * $rvCount)) / $blendCount, 1);

// Google reviews already on file
$feedback = [
  ['name' => 'Miguel Torres', 'role' => 'Apo Island Weekender', 'when' => '2 weeks ago',
   'text' => 'The car was waiting for us right at Sibulan Airport arrivals, five minutes after landing we were already on the road to Dauin. Effortless from start to finish.'],
  ['name' => 'Anna Reyes', 'role' => 'Local Renter', 'when' => '1 month ago',
   'text' => 'The price I saw on the site was the exact price I paid. No surprise insurance add-ons, no fuel games. Best rental deal in Dumaguete, hands down.'],
  ['name' => 'James Whitmore', 'role' => 'Visitor from Australia', 'when' => '2 months ago',
   'text' => 'Flat tire on the mountain road up to Valencia. One call and the roadside team had us moving again within the hour. That kind of backup is worth everything.'],
  ['name' => 'Grace Villanueva', 'role' => 'Family Trip', 'when' => '2 months ago',
   'text' => 'Booked the Innova for a week with two kids in tow. Clean unit, cold aircon, child seat ready on pick-up. We just drove and enjoyed the island.'],
  ['name' => 'Daniel Cruz', 'role' => 'Business Traveller', 'when' => '3 months ago',
   'text' => 'Late flight into Sibulan and they still met me at arrivals. Paperwork took maybe ten minutes. This is now my default rental in Negros Oriental.'],
  ['name' => 'Sofia Lim', 'role' => 'Valencia Day Tripper', 'when' => '4 months ago',
   'text' => 'Rented the Swift for a Casaroro Falls run. Sharp handling on the climb and the tank was full. Returning it was just as painless as picking it up.'],
];

// Date and star helpers
function reviewDate(string $ts): string {
    return date('M j, Y', strtotime($ts));
}

function stars(int $rating): string {
    return str_repeat('&#9733;', $rating) . str_repeat('&#9734;', 5 - $rating);
}

$pageTitle = 'Reviews — Shift Car Rental';
require 'header.php';
?>

<main class="page-main">

  <section class="page-hero" id="reviews-hero">
    <h1>Customer <span>Reviews</span></h1>
    <p>What renters say about driving with Shift across Negros Oriental.</p>

    <div class="rv-rating">
      <span class="rv-big"><?= e(number_format($blendAvg, 1)) ?></span>
      <span class="rv-stars" aria-label="<?= e($blendAvg) ?> out of 5 stars">
        <span aria-hidden="true"><?= stars((int)round($blendAvg)) ?></span>
      </span>
      <span class="rv-count">from <?= e(number_format($blendCount)) ?> reviews</span>
    </div>
  </section>

  <section class="review-form-sec" id="write-review">

    <?php if ($flash !== '') { ?>
      <p class="contact-sent"><?= e($flash) ?></p>
    <?php } ?>

    <?php if (!isLoggedIn()) { ?>

      <div class="review-gate">
        <h2>Rented with us? <span>Share your experience.</span></h2>
        <p><a href="login.php?next=<?= e(urlencode('reviews.php#write-review')) ?>">Log in</a>
           or <a href="signup.php">create an account</a> to leave a review.</p>
      </div>

    <?php } elseif (!$canReview) { ?>

      <div class="review-gate">
        <h2>Reviews are for <span>verified renters</span></h2>
        <p>Complete a rental with us first, then come back and tell everyone how it went.
           <a href="vehicles.php">Browse vehicles</a></p>
      </div>

    <?php } else { ?>

      <div class="review-form-card">
        <h2><?= $myReview ? 'Edit your' : 'Write a' ?> <span>review</span></h2>

        <?php if ($myReview && (int)$myReview['approved'] === 0) { ?>
          <p class="review-pending">
            Your review is being checked by our team, so it is not on the site yet.
            You can still edit it below.
          </p>
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

        <form method="post" action="function.php">
          <input type="hidden" name="action" value="save_review">

          <fieldset class="star-pick">
            <legend>Your rating</legend>
            <?php for ($i = 5; $i >= 1; $i--) { ?>
              <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>"
                     <?php if ((string)$i === (string)$formRating) echo 'checked'; ?>>
              <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">&#9733;</label>
            <?php } ?>
          </fieldset>

          <div class="auth-field">
            <label for="review-text">Your review</label>
            <textarea id="review-text" name="review_text" rows="4" maxlength="600"
                      placeholder="How was the car, the pick-up, the service?" required><?= e($formText) ?></textarea>
            <small>Max 600 characters. Posting as <?= e(currentUserName()) ?>.</small>
          </div>

          <button class="auth-btn" type="submit"><?= $myReview ? 'Update Review' : 'Post Review' ?></button>
        </form>

        <?php if ($myReview) { ?>
          <form method="post" action="function.php" class="review-delete">
            <input type="hidden" name="action" value="delete_review">
            <button type="submit">Delete my review</button>
          </form>
        <?php } ?>
      </div>

    <?php } ?>

  </section>

  <section class="customer-reviews" id="customer-reviews">
    <div class="sec-head">
      <h2>From our <span>Renters</span></h2>
      <p><?= $rvCount === 0 ? 'Be the first to leave a review!' : 'Real reviews from verified Shift renters.' ?></p>
    </div>

    <?php if ($rvCount > 0) { ?>
      <div class="reviews-grid">
        <?php foreach ($customerReviews as $note) { ?>

          <article class="rv">

            <div class="rv-who">
              <span class="rv-initial" aria-hidden="true"><?= e(strtoupper(substr($note['full_name'], 0, 1))) ?></span>

              <div class="rv-name">
                <h3><?= e($note['full_name']) ?><span class="rv-check" title="Verified renter">&#10003;</span></h3>
                <p>Verified Renter &middot; <?= e(reviewDate($note['created_at'])) ?></p>
              </div>

              <span class="rv-g" role="img" aria-label="Google review"><?= googleMark() ?></span>
            </div>

            <p class="rv-stars" aria-label="<?= e($note['rating']) ?> out of 5 stars">
              <span aria-hidden="true"><?= stars((int)$note['rating']) ?></span>
            </p>

            <p class="rv-text">&ldquo;<?= e($note['review_text']) ?>&rdquo;</p>

          </article>

        <?php } ?>
      </div>
    <?php } ?>
  </section>

  <section class="customer-reviews" id="google-reviews">
    <div class="sec-head">
      <h2>Google <span>Reviews</span></h2>
      <p>Verified reviews from Google</p>
    </div>

    <div class="reviews-grid">
      <?php foreach ($feedback as $note) { ?>

        <article class="rv">

          <div class="rv-who">
            <span class="rv-initial" aria-hidden="true"><?= e(strtoupper(substr($note['name'], 0, 1))) ?></span>

            <div class="rv-name">
              <h3><?= e($note['name']) ?><span class="rv-check" title="Verified renter">&#10003;</span></h3>
              <p><?= e($note['role']) ?> &middot; <?= e($note['when']) ?></p>
            </div>

            <span class="rv-g" role="img" aria-label="Google review"><?= googleMark() ?></span>
          </div>

          <p class="rv-stars" aria-label="5 out of 5 stars">
            <span aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
          </p>

          <p class="rv-text">&ldquo;<?= e($note['text']) ?>&rdquo;</p>

        </article>

      <?php } ?>
    </div>

    <p class="rv-more">&amp; many more on <span>Google</span></p>
  </section>

</main>

<?php require 'footer.php'; ?>
