<?php

// tanan page nga nanginahanglan session mo-require niini
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

// i-butang sa taas sa book.php ug bookings.php
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// i-butang sa taas sa tanan file sulod sa admin/
function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php?denied=1');
        exit;
    }
}
