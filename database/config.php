<?php

date_default_timezone_set('Asia/Manila');

function getConnection(): PDO
{
    $host = 'localhost';
    $db   = 'carrental';
    $user = 'root';
    $pass = '';          // default sa XAMPP walay password; ilisan ug 'root' kung naa

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$db;charset=utf8mb4",
            $user,
            $pass
        );

        // ang sayop mo-throw ug exception, dili tago nga false
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // associative array ang default, walay duplicate nga numbered keys
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // tinuod nga prepared statement, dili emulated sa PHP
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return $pdo;
    } catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Sorry, the site is temporarily unavailable. Please try again shortly.');
}

}
