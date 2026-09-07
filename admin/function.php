<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database/config.php';

requireAdmin();

/* handler ra ni, walay HTML */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$action = $_POST['action'] ?? '';
$pdo    = getConnection();

/* ---------- pag-usab sa status sa booking ---------- */
if ($action === 'set_status') {

    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['status'] ?? '';

    /* asa ra nga status ang pwede gikan sa asa —
       para dili maka-post ang admin ug bisan unsa nga string */
    $allowed = [
        'confirmed' => ['pending'],
        'cancelled' => ['pending', 'confirmed'],
        'completed' => ['confirmed'],
    ];

    if (!$bookingId || !isset($allowed[$newStatus])) {
        header('Location: dashboard.php?failed=1');
        exit;
    }

    /* placeholder kada sunod nga status: :from0, :from1, ... */
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

    /* kung walay na-update, sayop na ang status karon */
    if ($stmt->rowCount() === 0) {
        header('Location: dashboard.php?failed=1');
        exit;
    }

    header('Location: dashboard.php?updated=1');
    exit;
}

/* wala mailhan nga action */
header('Location: dashboard.php');
exit;
