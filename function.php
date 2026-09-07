<?php
require 'auth.php';
require 'database/config.php';
require 'validation.php';

requireLogin();

/* handler ra ni nga file, wala'y HTML output */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$action = $_POST['action'] ?? '';
$pdo    = getConnection();

/* ---------- paghimo ug bag-ong booking ---------- */
if ($action === 'create_booking') {

    $carId          = filter_input(INPUT_POST, 'car_id', FILTER_VALIDATE_INT);
    $pickupLocation = trim($_POST['pickup_location'] ?? '');
    $returnLocation = trim($_POST['return_location'] ?? '');
    $pickupDate     = trim($_POST['pickup_date'] ?? '');
    $returnDate     = trim($_POST['return_date'] ?? '');
    $driverAge      = trim($_POST['driver_age'] ?? '');
    $delivery       = isset($_POST['delivery']) ? 1 : 0;

    /* kinahanglan pareho ni sa lista sa book.php */
    $pickups = ['Sibulan Airport', 'Rizal Boulevard', 'Valencia', 'Dauin', 'Bacong'];
    $ages    = ['21-24', '25-29', '30-64', '65+'];

    /* i-tipig ang gi-type para dili mawala kung mo-balik sa form */
    $old = [
        'pickup_location' => $pickupLocation,
        'return_location' => $returnLocation,
        'pickup_date'     => $pickupDate,
        'return_date'     => $returnDate,
        'driver_age'      => $driverAge,
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

    /* kung naay sayop, i-uli sa form dala ang error */
    if (!empty($errors)) {
        $_SESSION['booking_errors'] = $errors;
        $_SESSION['booking_old']    = $old;
        header('Location: book.php?car_id=' . (int)$carId);
        exit;
    }

    /* i-kuha ang presyo gikan sa database, dili gikan sa form —
       basin gi-usab sa user ang hidden field sa browser */
    $stmt = $pdo->prepare("SELECT price FROM cars WHERE id = :id AND available = 1");
    $stmt->bindValue(':id', $carId, PDO::PARAM_INT);
    $stmt->execute();
    $car = $stmt->fetch();

    if (!$car) {
        $_SESSION['booking_errors'] = ['That vehicle is no longer available.'];
        header('Location: index.php?missing=1');
        exit;
    }

    $days  = daysBetween($pickupDate, $returnDate);
    $total = ($days * (int)$car['price']) + ($delivery ? 500 : 0);

    try {
        $sql = "INSERT INTO bookings
                  (user_id, car_id, pickup_location, return_location,
                   pickup_date, return_date, driver_age, delivery, days, total)
                VALUES
                  (:user_id, :car_id, :pickup_location, :return_location,
                   :pickup_date, :return_date, :driver_age, :delivery, :days, :total)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id',         currentUserId(), PDO::PARAM_INT);
        $stmt->bindValue(':car_id',          $carId,          PDO::PARAM_INT);
        $stmt->bindValue(':pickup_location', $pickupLocation);
        $stmt->bindValue(':return_location', $returnLocation);
        $stmt->bindValue(':pickup_date',     $pickupDate);
        $stmt->bindValue(':return_date',     $returnDate);
        $stmt->bindValue(':driver_age',      $driverAge);
        $stmt->bindValue(':delivery',        $delivery, PDO::PARAM_INT);
        $stmt->bindValue(':days',            $days,     PDO::PARAM_INT);
        $stmt->bindValue(':total',           $total,    PDO::PARAM_INT);
        $stmt->execute();

        $bookingId = (int)$pdo->lastInsertId();

        header('Location: success.php?booking=' . $bookingId);
        exit;

    } catch (PDOException $e) {
        $_SESSION['booking_errors'] = ['Could not save your booking. Please try again.'];
        $_SESSION['booking_old']    = $old;
        header('Location: book.php?car_id=' . $carId);
        exit;
    }
}

/* ---------- pag-cancel sa booking sa customer ---------- */
if ($action === 'cancel_booking') {

    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);

    if (!$bookingId) {
        header('Location: bookings.php?notfound=1');
        exit;
    }

    /* ang user_id ug status naa sa WHERE — mao ni ang tinuod nga guard.
       dili igo ang pagtago sa button sa bookings.php */
    $sql = "UPDATE bookings
            SET status = 'cancelled'
            WHERE id = :id AND user_id = :user_id AND status = 'pending'";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $bookingId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', currentUserId(), PDO::PARAM_INT);
    $stmt->execute();

    /* kung walay narow nga na-update, dili iya ni o dili na pending */
    if ($stmt->rowCount() === 0) {
        header('Location: bookings.php?notfound=1');
        exit;
    }

    header('Location: bookings.php?cancelled=1');
    exit;
}

/* ---------- pag-submit o pag-update sa review sa customer ---------- */
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

    /* review lang ang pwede sa naka-complete na ug booking,
       para verified renter gyud ang tanan reviews */
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

    /* usa ra ka review kada user — kung naa na, i-update nalang */
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

/* ---------- pag-delete sa kaugalingon nga review ---------- */
if ($action === 'delete_review') {

    /* ang user_id sa WHERE mao ang guard — kaugalingon ra nga review ang ma-delete */
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', currentUserId(), PDO::PARAM_INT);
    $stmt->execute();

    header('Location: reviews.php?deleted=1#customer-reviews');
    exit;
}

/* wala mailhan nga action */
header('Location: index.php');
exit;
