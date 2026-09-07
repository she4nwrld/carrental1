<?php
require 'auth.php';
require 'database/config.php';
require 'helpers.php';

requireLogin();

$pdo = getConnection();

/* tanan booking sa naka-login nga user, bag-o ang una */
$sql = "SELECT b.*, c.name AS car_name, c.type AS car_type, c.img AS car_img
        FROM bookings b
        JOIN cars c ON c.id = b.car_id
        WHERE b.user_id = :user_id
        ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', currentUserId(), PDO::PARAM_INT);
$stmt->execute();
$bookings = $stmt->fetchAll();

/* mensahe gikan sa function.php pagkahuman sa cancel */
$flash = '';
if (isset($_GET['cancelled'])) {
    $flash = 'Your booking has been cancelled.';
} elseif (isset($_GET['notfound'])) {
    $flash = 'We could not find that booking.';
}

function niceDate(string $ymd): string {
    return date('M j, Y', strtotime($ymd));
}

/* SHIFT-0007 */
function reference(int $id): string {
    return 'SHIFT-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

$pageTitle = 'My Bookings — Shift Car Rental';
require 'header.php';
?>
