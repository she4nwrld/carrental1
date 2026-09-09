<?php

// tanan page nga nanginahanglan session mo-require niini
if (session_status() === PHP_SESSION_NONE) {

    /* kinahanglan i-set ang cookie params una sa session_start().
       httponly: dili maabot sa JavaScript, panalipod sa XSS
       samesite: dili ipadala sa request gikan sa laing site
       secure: false sa localhost kay http ra, true kung naa nay HTTPS */
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);

    session_start();
}

/* 30 minutos nga walay lihok, pagawson na */
const SESSION_IDLE_LIMIT = 1800;

if (isset($_SESSION['user_id'])) {
    $idle = time() - ($_SESSION['last_seen'] ?? time());

    if ($idle > SESSION_IDLE_LIMIT) {
        $_SESSION = [];
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $_SESSION['timed_out'] = true;
    } else {
        $_SESSION['last_seen'] = time();
    }
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function currentUserId(): ?int
{
    return isLoggedIn() ? (int) $_SESSION['user_id'] : null;
}

function currentUserName(): string
{
    return $_SESSION['user_name'] ?? 'Guest';
}

// ang mga file sulod sa admin/ kinahanglan mo-balik ug usa ka folder,
// kung dili mo-punta sila sa admin/login.php nga wala man
function rootPath(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    return basename(dirname($script)) === 'admin' ? '../' : '';
}

// i-butang sa taas sa book.php ug bookings.php
function requireLogin(): void
{
    if (!isLoggedIn()) {
        $target = rootPath() . 'login.php';

        // ang form handler dili pwede i-replay human sa login, so wala nay next
        if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'function.php') {
            $target .= '?next=' . urlencode($_SERVER['REQUEST_URI']);
        }

        header('Location: ' . $target);
        exit;
    }
}

// i-butang sa taas sa tanan file sulod sa admin/
function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . rootPath() . 'index.php?denied=1');
        exit;
    }
}
