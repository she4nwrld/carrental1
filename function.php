<?php
require 'auth.php';
require 'database/config.php';
require 'validation.php';
require_once 'helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$action = $_POST['action'] ?? '';
$pdo    = getConnection();

/* ---------- create booking ---------- */
if ($action === 'create_booking') {

    $carId          = filter_input(INPUT_POST, 'car_id', FILTER_VALIDATE_INT);
    $pickupLocation = trim($_POST['pickup_location'] ?? '');
    $returnLocation = trim($_POST['return_location'] ?? '');
    $pickupDate     = trim($_POST['pickup_date'] ?? '');
    $returnDate     = trim($_POST['return_date'] ?? '');
    $driverAge      = trim($_POST['driver_age'] ?? '');
    $discountCode   = strtoupper(trim($_POST['discount_code'] ?? ''));
    $delivery       = isset($_POST['delivery']) ? 1 : 0;

    $pickups = pickupPoints();
    $ages    = ageBrackets();

    $old = [
        'pickup_location' => $pickupLocation,
        'return_location' => $returnLocation,
        'pickup_date'     => $pickupDate,
        'return_date'     => $returnDate,
        'driver_age'      => $driverAge,
        'discount_code'   => $discountCode,
        'delivery'        => (string)$delivery,
    ];

    $errors = array_values(array_filter([
        validateInList($pickupLocation, 'pick-up location', $pickups),
        validateInList($returnLocation, 'return location', $pickups),
        validateDate($pickupDate, 'Pick-up date'),
        validateDate($returnDate, 'Return date'),
        validateDateOrder($pickupDate, $returnDate),
        validateInList($driverAge, "driver's age", $ages),
    ]));

    if (!$carId) {
        $errors[] = 'Please choose a vehicle first.';
    }

    if (isYmd($pickupDate) && daysUntil($pickupDate) < 0) {
        $errors[] = 'Pick-up date cannot be in the past.';
    }

    if (!empty($errors)) {
        $_SESSION['booking_errors'] = $errors;
        $_SESSION['booking_old']    = $old;
        header('Location: book.php?car_id=' . (int)$carId);
        exit;
    }

    $stmt = $pdo->prepare("SELECT price FROM cars WHERE id = :id AND available = 1");
    $stmt->bindValue(':id', $carId, PDO::PARAM_INT);
    $stmt->execute();
    $car = $stmt->fetch();

    if (!$car) {
        $_SESSION['booking_errors'] = ['That vehicle is no longer available.'];
        header('Location: index.php?missing=1');
        exit;
    }

    $days  = tripDays($pickupDate, $returnDate);
    $found = findPromos($pdo, $discountCode);
    $quote = quotePrice($found['promos'], (int)$car['price'], $days, (bool)$delivery, $pickupDate);

    foreach ($found['unknown'] as $bad) {
        $errors[] = '"' . $bad . '" is not a valid discount code.';
    }
    $errors = array_merge($errors, $quote['errors']);

    /* unang tan-aw, para makauban ang error sa promo errors */
    if (!carIsFree($pdo, $carId, $pickupDate, $returnDate)) {
        $errors[] = 'Sorry, that unit was just booked for those dates. Please pick other dates.';
    }

    if (!empty($errors)) {
        $_SESSION['booking_errors'] = $errors;
        $_SESSION['booking_old']    = $old;
        header('Location: book.php?car_id=' . (int)$carId);
        exit;
    }

    $appliedCode = $quote['applied'] ? implode(',', $quote['applied']) : null;

    /* store the days we actually billed, promos can change the count */
    $billedDays = $quote['billable_days'] ?: $days;

    try {
        /* ang check ug ang insert kinahanglan usa ra ka transaction,
           kung dili duha ka tawo makasulod sa parehas nga segundo */
        $pdo->beginTransaction();

        if (!carIsFree($pdo, $carId, $pickupDate, $returnDate, null, true)) {
            $pdo->rollBack();
            $_SESSION['booking_errors'] = ['Sorry, that unit was just booked for those dates. Please pick other dates.'];
            $_SESSION['booking_old']    = $old;
            header('Location: book.php?car_id=' . (int)$carId);
            exit;
        }

        $sql = "INSERT INTO bookings
                  (user_id, car_id, pickup_location, return_location,
                   pickup_date, return_date, driver_age, delivery, days,
                   subtotal, discount_code, discount, total)
                VALUES
                  (:user_id, :car_id, :pickup_location, :return_location,
                   :pickup_date, :return_date, :driver_age, :delivery, :days,
                   :subtotal, :discount_code, :discount, :total)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id',         currentUserId(), PDO::PARAM_INT);
        $stmt->bindValue(':car_id',          $carId,          PDO::PARAM_INT);
        $stmt->bindValue(':pickup_location', $pickupLocation);
        $stmt->bindValue(':return_location', $returnLocation);
        $stmt->bindValue(':pickup_date',     $pickupDate);
        $stmt->bindValue(':return_date',     $returnDate);
        $stmt->bindValue(':driver_age',      $driverAge);
        $stmt->bindValue(':delivery',        $delivery,   PDO::PARAM_INT);
        $stmt->bindValue(':days',            $billedDays, PDO::PARAM_INT);
        $stmt->bindValue(':subtotal',        $quote['subtotal'], PDO::PARAM_INT);
        $stmt->bindValue(':discount_code',   $appliedCode, $appliedCode === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':discount',        $quote['discount'], PDO::PARAM_INT);
        $stmt->bindValue(':total',           $quote['total'],    PDO::PARAM_INT);
        $stmt->execute();

        $bookingId = (int)$pdo->lastInsertId();

        $pdo->commit();

        header('Location: success.php?booking=' . $bookingId);
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Booking insert failed: ' . $e->getMessage());
        $_SESSION['booking_errors'] = ['Could not save your booking. Please try again.'];
        $_SESSION['booking_old']    = $old;
        header('Location: book.php?car_id=' . $carId);
        exit;
    }
}

/* ---------- cancel booking ---------- */
if ($action === 'cancel_booking') {

    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);

    if (!$bookingId) {
        header('Location: bookings.php?notfound=1');
        exit;
    }

    $sql = "UPDATE bookings
            SET status = 'cancelled'
            WHERE id = :id AND user_id = :user_id AND status IN ('pending','confirmed')";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $bookingId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', currentUserId(), PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        header('Location: bookings.php?notfound=1');
        exit;
    }

    header('Location: bookings.php?cancelled=1');
    exit;
}

/* ---------- save review ---------- */
if ($action === 'save_review') {

    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $text   = trim($_POST['review_text'] ?? '');

    $errors = [];

    if (!$rating || $rating < 1 || $rating > 5) {
        $errors[] = 'Please choose a star rating from 1 to 5.';
    }
    if ($text === '') {
        $errors[] = 'Please write a few words about your experience.';
    } elseif (mb_strlen($text) > 600) {
        $errors[] = 'Review is too long — keep it under 600 characters.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS done FROM bookings
                               WHERE user_id = :user_id AND status = 'completed'");
        $stmt->bindValue(':user_id', currentUserId(), PDO::PARAM_INT);
        $stmt->execute();
        if ((int)$stmt->fetch()['done'] === 0) {
            $errors[] = 'You can leave a review after completing a rental with us.';
        }
    }

    if (!empty($errors)) {
        $_SESSION['review_errors'] = $errors;
        $_SESSION['review_old']    = ['rating' => (string)$rating, 'review_text' => $text];
        header('Location: reviews.php#write-review');
        exit;
    }

    $sql = "INSERT INTO reviews (user_id, rating, review_text)
            VALUES (:user_id, :rating, :review_text)
            ON DUPLICATE KEY UPDATE
              rating = VALUES(rating),
              review_text = VALUES(review_text),
              created_at = CURRENT_TIMESTAMP";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', currentUserId(), PDO::PARAM_INT);
    $stmt->bindValue(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindValue(':review_text', $text);
    $stmt->execute();

    header('Location: reviews.php?reviewed=1#customer-reviews');
    exit;
}

/* ---------- delete review ---------- */
if ($action === 'delete_review') {

    $stmt = $pdo->prepare("DELETE FROM reviews WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', currentUserId(), PDO::PARAM_INT);
    $stmt->execute();

    header('Location: reviews.php?deleted=1#customer-reviews');
    exit;
}

header('Location: index.php');
exit;
