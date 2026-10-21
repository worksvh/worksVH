<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$path = request_path();

if ($path === '/admin' || str_starts_with($path, '/admin/')) {
    require APP_PATH . '/admin/router.php';
    exit;
}

require APP_PATH . '/site.php';
site_dispatch($path);
