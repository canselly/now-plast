<?php
define('ROOT', dirname(__DIR__));

$GLOBALS['config'] = require ROOT . '/config.php';

if (!empty($GLOBALS['config']['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

date_default_timezone_set('Europe/Istanbul');
mb_internal_encoding('UTF-8');

if (session_status() === PHP_SESSION_NONE) {
    session_name('np_sess');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'path'     => '/',
    ]);
    session_start();
}

require ROOT . '/app/db.php';
require ROOT . '/app/helpers.php';
