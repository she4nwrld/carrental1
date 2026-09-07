<?php
// naa ni sulod sa admin/ folder, mao nga '../' ang tanan path
$base = '../';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../helpers.php';

requireAdmin();   // customer o bisita, dili maka-sulod diri

$pdo = getConnection();

// unsa'ng status ang gi-filter, gikan sa mga tab sa ibabaw
$statuses = ['all', 'pending', 'confirmed', 'completed', 'cancelled'];
$filter   = $_GET['status'] ?? 'all';
if (!in_array($filter, $statuses, true)) {
    $filter = 'all';
}

/* tanan booking sa tanan user — mao ni ang kalainan sa bookings.php,
   walay user_id sa WHERE kay admin man */
$sql = "SELECT b.*, c.name AS car_name, c.img AS car_img,
               u.full_name, u.email, u.phone
        FROM bookings b
        JOIN cars c  ON c.id = b.car_id
        JOIN users u ON u.id = b.user_id";

if ($filter !== 'all') {
    $sql .= " WHERE b.status = :status";
}
$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($filter !== 'all') {
    $stmt->bindValue(':status', $filter);
}
$stmt->execute();
$rows = $stmt->fetchAll();

/* pang-ihap sa taas: pila ka pending, kinsa ang kita, ug uban pa */
$stats = $pdo->query(
    "SELECT
       COUNT(*) AS total,
       SUM(status = 'pending')   AS pending,
       SUM(status = 'confirmed') AS confirmed,
       SUM(CASE WHEN status IN ('confirmed','completed') THEN total ELSE 0 END) AS revenue
     FROM bookings"
)->fetch();

$flash = '';
if (isset($_GET['updated'])) {
    $flash = 'Booking status updated.';
} elseif (isset($_GET['failed'])) {
    $flash = 'That booking could not be updated.';
}

function niceDate(string $ymd): string {
    return date('M j, Y', strtotime($ymd));
}

function reference(int $id): string {
    return 'SHIFT-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

$pageTitle = 'Admin Dashboard — Shift Car Rental';
require __DIR__ . '/../header.php';
?>
