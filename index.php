<?php

/**
 * XAMPP / Apache subdirectory bootstrap.
 * Normalizes paths so Laravel routes work at http://localhost/INHS SMART-DTR/
 */

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

// When Apache rewrites into /public, strip that segment for routing
if (str_ends_with($basePath, '/public')) {
    $basePath = substr($basePath, 0, -7);
}

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$queryString = $_SERVER['QUERY_STRING'] ?? '';

if ($basePath !== '' && $basePath !== '/' && str_starts_with($requestPath, $basePath)) {
    $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
}

$_SERVER['SCRIPT_NAME'] = $basePath.'/index.php';
$_SERVER['REQUEST_URI'] = $requestPath.($queryString !== '' ? '?'.$queryString : '');

require __DIR__.'/public/index.php';
