<?php
require 'auth.php';
require 'database/config.php';
require 'helpers.php';

requireLogin();  // kung wala pa naka-login, i-redirect ni sa login.php

$pdo = getConnection();

// ang car_id gikan sa "Book Now" link; kinahanglan number gyud
$carId = filter_input(INPUT_GET, 'car_id', FILTER_VALIDATE_INT);
if (!$carId) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM cars WHERE id = :id AND available = 1");
$stmt->bindValue(':id', $carId, PDO::PARAM_INT);
$stmt->execute();
$car = $stmt->fetch();

if (!$car) {
    header('Location: index.php?missing=1');
    exit;
}

// pareho ni sa options sa index.php
$pickups = ['Sibulan Airport', 'Rizal Boulevard', 'Valencia', 'Dauin', 'Bacong'];
$ages    = ['21-24', '25-29', '30-64', '65+'];

// mga error ug daan nga input gikan sa function.php (flash sa session)
$errors = $_SESSION['booking_errors'] ?? [];
$old    = $_SESSION['booking_old'] ?? [];
unset($_SESSION['booking_errors'], $_SESSION['booking_old']);

// para dili mawala ang gi-type sa user kung naay error
function old(array $old, string $key, string $fallback = ''): string {
    return $old[$key] ?? $fallback;
}
