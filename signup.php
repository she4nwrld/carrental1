<?php
session_start();

require 'database/config.php';
require 'validation.php';

// naka-login na? balik sa home, wala nay gamit ang signup
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$old    = ['full_name' => '', 'email' => '', 'phone' => ''];   // para dili mahurot ang gi-type

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getConnection();

    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';           // dili i-trim, basin space ang parte sa password
    $confirm  = $_POST['confirm_password'] ?? '';

    $old = ['full_name' => $fullName, 'email' => $email, 'phone' => $phone];

    // array_filter mo-tangtang sa tanan null, mabilin ra ang mga error
    $errors = array_values(array_filter([
        validateRequired($fullName, 'Full name'),
        validateEmailFormat($email),
        validatePhone($phone),
        validatePassword($password),
        validatePasswordMatch($password, $confirm),
    ]));

    // ang database check lang human sa format check, para dili sagi ang query
    if (empty($errors) && emailTaken($pdo, $email)) {
        $errors[] = "That email is already registered.";
    }
}
?>
