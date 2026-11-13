<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// The site runs from this folder (shared hosting, or http://localhost/darsgah locally).
// CSS, JS and images stay in public/, so asset links point there unless .env sets ASSET_URL.
if (! isset($_SERVER['ASSET_URL']) && ! empty($_SERVER['HTTP_HOST'])) {
    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    // The folder is part of the address only when the visitor typed it (localhost/darsgah),
    // not when a school's domain is sent here by the main site's .htaccess.
    $dir = rtrim(strtr(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), [chr(92) => '/']), '/');
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $base = $dir !== '' && str_starts_with($path.'/', $dir.'/') ? $dir : '';
    $_SERVER['ASSET_URL'] = $_ENV['ASSET_URL'] = ($https ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$base.'/public';
}

if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
