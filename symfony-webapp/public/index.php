<?php

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

set_time_limit(0);

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    // Trust ngrok / reverse proxy forwarded headers for HTTPS and host detection
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $trusted = ['127.0.0.1', '::1'];
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $trusted[] = $_SERVER['REMOTE_ADDR'];
        }
        Request::setTrustedProxies(
            array_values(array_unique($trusted)),
            Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO
        );
    }

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
