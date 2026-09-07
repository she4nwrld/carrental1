<?php
require 'auth.php';
require 'database/config.php';
require 'validation.php';

requireLogin();

/* ang mga file ni handler ra, wala'y HTML output */
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
}
