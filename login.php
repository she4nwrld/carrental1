<?php
session_start();

require 'database/config.php';
require 'validation.php';

// naka-login na? wala nay gamit ang login page
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$old    = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $old['email'] = $email;

    $errors = array_values(array_filter([
        validateRequired($email, 'Email'),
        validateRequired($password, 'Password'),
    ]));

    if (empty($errors)) {
        $pdo = getConnection();

        $sql  = "SELECT id, full_name, password, role FROM users WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        // password_verify mo-compare sa gi-type ug sa hash sa database
        if ($user && password_verify($password, $user['password'])) {

            // bag-ong session id, panalipod sa session fixation
            session_regenerate_id(true);

            $_SESSION['user_id']   = (int) $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            // admin paingon sa dashboard, customer paingon sa home
            $target = $user['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php';
            header('Location: ' . $target);
            exit;
        }

        // usa ra ka message bisan asa ang sayop, para dili mabutyag kinsa naay account
        $errors[] = "Incorrect email or password.";
    }
}
?>
