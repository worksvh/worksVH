<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', BASE_PATH . '/public');

$GLOBALS['config'] = require APP_PATH . '/config.php';

function config(string $key, mixed $default = null): mixed
{
    $v = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($v) || !array_key_exists($part, $v)) {
            return $default;
        }
        $v = $v[$part];
    }
    return $v;
}

function is_production(): bool
{
    return config('app_env') === 'production';
}

// Chyby se jen logují, návštěvník je nevidí.
ini_set('display_errors', is_production() || PHP_SAPI !== 'cli' ? '0' : '1');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');
error_reporting(E_ALL);
date_default_timezone_set('Europe/Prague');
mb_internal_encoding('UTF-8');

if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
}

require APP_PATH . '/db.php';
foreach (['helpers', 'log', 'csrf', 'auth', 'validation', 'richtext', 'content', 'image', 'upload', 'mail', 'view', 'backup', 'inquiries'] as $lib) {
    require APP_PATH . "/lib/$lib.php";
}

set_exception_handler(static function (Throwable $e): void {
    log_error('Neošetřená výjimka: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><html lang="cs"><meta charset="utf-8"><title>Chyba</title><body style="font-family:sans-serif;padding:40px">'
        . '<h1>Něco se pokazilo</h1><p>Zkuste to prosím za chvíli znovu, nebo nám zavolejte.</p></body></html>';
});
