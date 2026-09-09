<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../helpers.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$action = $_POST['action'] ?? '';
$pdo    = getConnection();

/* the four car types, minus the All pill */
function carTypes(): array {
    return array_values(array_filter(carCategories(), function ($t) {
        return strtolower($t) !== 'all';
    }));
}

/* shared field reader for adding and editing a car */
function carFields(): array {
    $types = carTypes();

    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? '';
    $gear = $_POST['gear'] ?? '';
    $img  = trim($_POST['img'] ?? '');

    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_INT);
    $seats = filter_input(INPUT_POST, 'seats', FILTER_VALIDATE_INT);
    $doors = filter_input(INPUT_POST, 'doors', FILTER_VALIDATE_INT);
    $bagL  = filter_input(INPUT_POST, 'bag_large', FILTER_VALIDATE_INT);
    $bagS  = filter_input(INPUT_POST, 'bag_small', FILTER_VALIDATE_INT);
    $kids  = filter_input(INPUT_POST, 'kids', FILTER_VALIDATE_INT);

    $aircon = ($_POST['aircon'] ?? '1') === '1' ? 1 : 0;

    $ok = $name !== ''
       && $img !== ''
       && in_array($type, $types, true)
       && in_array($gear, ['Manual', 'Auto'], true)
       && $price !== false && $price > 0
       && $seats !== false && $seats > 0
       && $doors !== false && $doors > 0
       && $bagL !== false && $bagL >= 0
       && $bagS !== false && $bagS >= 0
       && $kids !== false && $kids >= 0;

    return [
        'ok'   => $ok,
        'data' => [
            ':name'   => $name,
            ':type'   => $type,
            ':price'  => $price,
            ':gear'   => $gear,
            ':seats'  => $seats,
            ':doors'  => $doors,
            ':bagL'   => $bagL,
            ':bagS'   => $bagS,
            ':kids'   => $kids,
            ':aircon' => $aircon,
            ':img'    => $img,
        ],
    ];
}

/* shared field reader for adding and editing a promo */
function promoFields(): array {
    $code  = strtoupper(trim($_POST['code'] ?? ''));
    $tag   = trim($_POST['tag'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $blurb = trim($_POST['blurb'] ?? '');
    $stamp = trim($_POST['expires_at'] ?? '');

    $percent = filter_input(INPUT_POST, 'percent', FILTER_VALIDATE_INT);
    $freeD   = filter_input(INPUT_POST, 'free_days', FILTER_VALIDATE_INT);
    $minD    = filter_input(INPUT_POST, 'min_days', FILTER_VALIDATE_INT);
    $minAdv  = filter_input(INPUT_POST, 'min_advance_days', FILTER_VALIDATE_INT);
    $order   = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);

    $stackable = ($_POST['stackable'] ?? '0') === '1' ? 1 : 0;

    /* blank code means a standing offer with nothing to type */
    $codeVal = $code === '' ? null : $code;

    /* blank date means it never runs out */
    $dateOk  = $stamp === '' || isYmd($stamp);
    $dateVal = $stamp === '' ? null : $stamp;

    $ok = $tag !== ''
       && $title !== ''
       && $blurb !== ''
       && $dateOk
       && ($codeVal === null || preg_match('/^[A-Z0-9]{3,20}$/', $codeVal))
       && $percent !== false && $percent >= 0 && $percent <= 100
       && $freeD !== false && $freeD >= 0
       && $minD !== false && $minD >= 0
       && $minAdv !== false && $minAdv >= 0
       && $order !== false && $order >= 0;

    return [
        'ok'   => $ok,
        'data' => [
            ':code'    => $codeVal,
            ':tag'     => $tag,
            ':title'   => $title,
            ':blurb'   => $blurb,
            ':percent' => $percent,
            ':freeD'   => $freeD,
            ':minD'    => $minD,
            ':minAdv'  => $minAdv,
            ':stack'   => $stackable,
            ':expires' => $dateVal,
            ':order'   => $order,
        ],
    ];
}

/* booking status */
if ($action === 'set_status') {

    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['status'] ?? '';

    $allowed = [
        'confirmed' => ['pending'],
        'cancelled' => ['pending', 'confirmed'],
        'completed' => ['confirmed'],
    ];

    if (!$bookingId || !isset($allowed[$newStatus])) {
        header('Location: dashboard.php?failed=1');
        exit;
    }

    $from  = $allowed[$newStatus];
    $marks = [];
    foreach ($from as $i => $status) {
        $marks[] = ':from' . $i;
    }

    $sql = "UPDATE bookings
            SET status = :new
            WHERE id = :id AND status IN (" . implode(', ', $marks) . ")";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':new', $newStatus);
    $stmt->bindValue(':id', $bookingId, PDO::PARAM_INT);
    foreach ($from as $i => $status) {
        $stmt->bindValue(':from' . $i, $status);
    }
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        header('Location: dashboard.php?failed=1');
        exit;
    }

    header('Location: dashboard.php?updated=1');
    exit;
}

/* edit booking details, the money is worked out again from the new dates */
if ($action === 'edit_booking') {

    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);

    $pickupDate     = trim($_POST['pickup_date'] ?? '');
    $returnDate     = trim($_POST['return_date'] ?? '');
    $pickupLocation = trim($_POST['pickup_location'] ?? '');
    $returnLocation = trim($_POST['return_location'] ?? '');
    $driverAge      = trim($_POST['driver_age'] ?? '');
    $status         = $_POST['status'] ?? '';

    $pickups  = pickupPoints();
    $ages     = ageBrackets();
    $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];

    $ok = $bookingId
       && isYmd($pickupDate)
       && isYmd($returnDate)
       && strtotime($returnDate) > strtotime($pickupDate)
       && in_array($pickupLocation, $pickups, true)
       && in_array($returnLocation, $pickups, true)
       && in_array($driverAge, $ages, true)
       && in_array($status, $statuses, true);

    if (!$ok) {
        header('Location: dashboard.php?failed=1');
        exit;
    }

    /* the rate and the promo come from the row itself, not the form */
    $stmt = $pdo->prepare(
        "SELECT b.delivery, b.discount_code, c.price
         FROM bookings b
         JOIN cars c ON c.id = b.car_id
         WHERE b.id = :id"
    );
    $stmt->bindValue(':id', $bookingId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if (!$row) {
        header('Location: dashboard.php?failed=1');
        exit;
    }

    $days  = tripDays($pickupDate, $returnDate);
    $found = findPromos($pdo, $row['discount_code']);
    $quote = quotePrice($found['promos'], (int)$row['price'], $days, (bool)$row['delivery'], $pickupDate);

    $appliedCode = $quote['applied'] ? implode(',', $quote['applied']) : null;
    $billedDays  = $quote['billable_days'] ?: $days;

    $stmt = $pdo->prepare(
        "UPDATE bookings SET
           pickup_date = :pickup, return_date = :ret,
           pickup_location = :pickLoc, return_location = :retLoc,
           driver_age = :age, days = :days,
           subtotal = :subtotal, discount_code = :code, discount = :discount,
           total = :total, status = :status
         WHERE id = :id"
    );
    $stmt->bindValue(':pickup',   $pickupDate);
    $stmt->bindValue(':ret',      $returnDate);
    $stmt->bindValue(':pickLoc',  $pickupLocation);
    $stmt->bindValue(':retLoc',   $returnLocation);
    $stmt->bindValue(':age',      $driverAge);
    $stmt->bindValue(':days',     $billedDays, PDO::PARAM_INT);
    $stmt->bindValue(':subtotal', $quote['subtotal'], PDO::PARAM_INT);
    $stmt->bindValue(':code',     $appliedCode, $appliedCode === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':discount', $quote['discount'], PDO::PARAM_INT);
    $stmt->bindValue(':total',    $quote['total'], PDO::PARAM_INT);
    $stmt->bindValue(':status',   $status);
    $stmt->bindValue(':id',       $bookingId, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: dashboard.php?saved=1');
    exit;
}

/* delete a booking outright */
if ($action === 'delete_booking') {

    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);

    if (!$bookingId) {
        header('Location: dashboard.php?failed=1');
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :id");
    $stmt->bindValue(':id', $bookingId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        header('Location: dashboard.php?failed=1');
        exit;
    }

    header('Location: dashboard.php?deleted=1');
    exit;
}

/* show or hide a review */
if ($action === 'set_review') {

    $reviewId = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
    $show     = isset($_POST['approved']) && $_POST['approved'] === '1' ? 1 : 0;
    $view     = $_POST['view'] ?? 'all';
    $back     = 'reviews.php?view=' . urlencode($view);

    if (!$reviewId) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE reviews SET approved = :ok WHERE id = :id");
    $stmt->bindValue(':ok', $show, PDO::PARAM_INT);
    $stmt->bindValue(':id', $reviewId, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: ' . $back . '&updated=1');
    exit;
}

/* delete a review outright */
if ($action === 'drop_review') {

    $reviewId = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
    $view     = $_POST['view'] ?? 'all';
    $back     = 'reviews.php?view=' . urlencode($view);

    if (!$reviewId) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
    $stmt->bindValue(':id', $reviewId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    header('Location: ' . $back . '&removed=1');
    exit;
}

/* add a vehicle */
if ($action === 'add_car') {

    $car = carFields();

    if (!$car['ok']) {
        header('Location: cars.php?failed=1');
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO cars
           (name, type, price, gear, seats, doors, bag_large, bag_small, kids, aircon, img, available)
         VALUES
           (:name, :type, :price, :gear, :seats, :doors, :bagL, :bagS, :kids, :aircon, :img, 1)"
    );
    $stmt->execute($car['data']);

    header('Location: cars.php?added=1');
    exit;
}

/* edit a vehicle */
if ($action === 'edit_car') {

    $carId = filter_input(INPUT_POST, 'car_id', FILTER_VALIDATE_INT);
    $view  = $_POST['view'] ?? 'all';
    $back  = 'cars.php?view=' . urlencode($view);

    $car = carFields();

    if (!$carId || !$car['ok']) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE cars SET
           name = :name, type = :type, price = :price, gear = :gear,
           seats = :seats, doors = :doors, bag_large = :bagL, bag_small = :bagS,
           kids = :kids, aircon = :aircon, img = :img
         WHERE id = :id"
    );
    $stmt->execute($car['data'] + [':id' => $carId]);

    header('Location: ' . $back . '&saved=1');
    exit;
}

/* show or hide a vehicle */
if ($action === 'toggle_car') {

    $carId = filter_input(INPUT_POST, 'car_id', FILTER_VALIDATE_INT);
    $show  = ($_POST['available'] ?? '') === '1' ? 1 : 0;
    $view  = $_POST['view'] ?? 'all';
    $back  = 'cars.php?view=' . urlencode($view);

    if (!$carId) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE cars SET available = :ok WHERE id = :id");
    $stmt->bindValue(':ok', $show, PDO::PARAM_INT);
    $stmt->bindValue(':id', $carId, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: ' . $back . '&toggled=1');
    exit;
}

/* delete a vehicle, only if it was never booked */
if ($action === 'drop_car') {

    $carId = filter_input(INPUT_POST, 'car_id', FILTER_VALIDATE_INT);
    $view  = $_POST['view'] ?? 'all';
    $back  = 'cars.php?view=' . urlencode($view);

    if (!$carId) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    $check = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE car_id = :id");
    $check->bindValue(':id', $carId, PDO::PARAM_INT);
    $check->execute();

    if ((int)$check->fetchColumn() > 0) {
        header('Location: ' . $back . '&inuse=1');
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM cars WHERE id = :id");
    $stmt->bindValue(':id', $carId, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: ' . $back . '&removed=1');
    exit;
}

/* add a promo */
if ($action === 'add_promo') {

    $promo = promoFields();

    if (!$promo['ok']) {
        header('Location: promos.php?failed=1');
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO promos
           (code, tag, title, blurb, percent, free_days, min_days,
            min_advance_days, stackable, active, expires_at, sort_order)
         VALUES
           (:code, :tag, :title, :blurb, :percent, :freeD, :minD,
            :minAdv, :stack, 1, :expires, :order)"
    );

    try {
        $stmt->execute($promo['data']);
    } catch (PDOException $err) {
        header('Location: promos.php?dupe=1');
        exit;
    }

    header('Location: promos.php?added=1');
    exit;
}

/* edit a promo */
if ($action === 'edit_promo') {

    $promoId = filter_input(INPUT_POST, 'promo_id', FILTER_VALIDATE_INT);
    $view    = $_POST['view'] ?? 'all';
    $back    = 'promos.php?view=' . urlencode($view);

    $promo = promoFields();

    if (!$promoId || !$promo['ok']) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE promos SET
           code = :code, tag = :tag, title = :title, blurb = :blurb,
           percent = :percent, free_days = :freeD, min_days = :minD,
           min_advance_days = :minAdv, stackable = :stack,
           expires_at = :expires, sort_order = :order
         WHERE id = :id"
    );

    try {
        $stmt->execute($promo['data'] + [':id' => $promoId]);
    } catch (PDOException $err) {
        header('Location: ' . $back . '&dupe=1');
        exit;
    }

    header('Location: ' . $back . '&saved=1');
    exit;
}

/* switch a promo on or off */
if ($action === 'toggle_promo') {

    $promoId = filter_input(INPUT_POST, 'promo_id', FILTER_VALIDATE_INT);
    $on      = ($_POST['active'] ?? '') === '1' ? 1 : 0;
    $view    = $_POST['view'] ?? 'all';
    $back    = 'promos.php?view=' . urlencode($view);

    if (!$promoId) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE promos SET active = :on WHERE id = :id");
    $stmt->bindValue(':on', $on, PDO::PARAM_INT);
    $stmt->bindValue(':id', $promoId, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: ' . $back . '&toggled=1');
    exit;
}

/* delete a promo, past bookings keep the code as plain text */
if ($action === 'drop_promo') {

    $promoId = filter_input(INPUT_POST, 'promo_id', FILTER_VALIDATE_INT);
    $view    = $_POST['view'] ?? 'all';
    $back    = 'promos.php?view=' . urlencode($view);

    if (!$promoId) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM promos WHERE id = :id");
    $stmt->bindValue(':id', $promoId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    header('Location: ' . $back . '&removed=1');
    exit;
}

/* mark a message read or unread */
if ($action === 'set_message') {

    $msgId = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
    $read  = ($_POST['is_read'] ?? '') === '1' ? 1 : 0;
    $view  = $_POST['view'] ?? 'unread';
    $back  = 'messages.php?view=' . urlencode($view);

    if (!$msgId) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE messages SET is_read = :r WHERE id = :id");
    $stmt->bindValue(':r', $read, PDO::PARAM_INT);
    $stmt->bindValue(':id', $msgId, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: ' . $back . '&updated=1');
    exit;
}

/* delete a message */
if ($action === 'drop_message') {

    $msgId = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
    $view  = $_POST['view'] ?? 'unread';
    $back  = 'messages.php?view=' . urlencode($view);

    if (!$msgId) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = :id");
    $stmt->bindValue(':id', $msgId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    header('Location: ' . $back . '&removed=1');
    exit;
}

/* enable or disable a customer account */
if ($action === 'toggle_customer') {

    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $on     = ($_POST['is_active'] ?? '') === '1' ? 1 : 0;
    $view   = $_POST['view'] ?? 'all';
    $back   = 'customers.php?view=' . urlencode($view);

    /* never touch your own row, even if the id is typed by hand */
    if (!$userId || $userId === currentUserId()) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE users SET is_active = :on WHERE id = :id AND role <> 'admin'"
    );
    $stmt->bindValue(':on', $on, PDO::PARAM_INT);
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    header('Location: ' . $back . ($on ? '&enabled=1' : '&disabled=1'));
    exit;
}

/* delete a customer, only if they never booked anything */
if ($action === 'drop_customer') {

    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $view   = $_POST['view'] ?? 'all';
    $back   = 'customers.php?view=' . urlencode($view);

    if (!$userId || $userId === currentUserId()) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    /* gitago ang button sa page, pero i-check gihapon diri */
    $check = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = :id");
    $check->bindValue(':id', $userId, PDO::PARAM_INT);
    $check->execute();

    if ((int)$check->fetchColumn() > 0) {
        header('Location: ' . $back . '&inuse=1');
        exit;
    }

    /* walay booking history, pero basin naay review */
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE user_id = :id");
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role <> 'admin'");
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    header('Location: ' . $back . '&removed=1');
    exit;
}

/* issue a temporary password for a customer who is locked out */
if ($action === 'reset_customer') {

    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $view   = $_POST['view'] ?? 'all';
    $back   = 'customers.php?view=' . urlencode($view);

    if (!$userId) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    /* mo-pasar sa validatePassword(): letra, numero ug special character */
    $temp = 'Shift' . random_int(1000, 9999) . '!';

    $stmt = $pdo->prepare(
        "UPDATE users SET password = :hash WHERE id = :id AND role <> 'admin'"
    );
    $stmt->bindValue(':hash', password_hash($temp, PASSWORD_DEFAULT));
    $stmt->bindValue(':id',   $userId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        header('Location: ' . $back . '&failed=1');
        exit;
    }

    /* gipakita kausa ra sa admin, wala gi-save nga plain text sa database */
    $_SESSION['temp_password'] = $temp;
    header('Location: ' . $back . '&reset=1');
    exit;
}

header('Location: dashboard.php');
exit;
