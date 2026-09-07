<?php
session_start();

// hawaan ang array, dayon gub-on ang session sa server
$_SESSION = [];

// tangtangon pod ang session cookie sa browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,          // expiry sa nangagi, mao ni ang mo-delete
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: index.php?logged_out=1');
exit;
