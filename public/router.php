<?php
// Router pro vestavěný server: php -S localhost:8000 -t public public/router.php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . rawurldecode($path);
if ($path !== '/' && is_file($file) && !preg_match('/\.(php|phtml|phar|htaccess)$/i', $file)) {
    if (str_starts_with($path, '/media/') && str_ends_with(strtolower($path), '.svg')) {
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
    }
    return false; // statický soubor obslouží PHP server
}
require __DIR__ . '/index.php';
