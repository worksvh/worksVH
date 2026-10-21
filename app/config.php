<?php
declare(strict_types=1);

/** Načte .env (jednoduchý formát KEY=VALUE) a vrátí konfiguraci. */
function load_env(string $file): array
{
    $env = [];
    if (!is_file($file)) {
        return $env;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $v = trim($v);
        if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && $v[-1] === $v[0]) {
            $v = substr($v, 1, -1);
        }
        $env[trim($k)] = $v;
    }
    return $env;
}

$env = load_env(BASE_PATH . '/.env');
$get = static fn(string $k, string $d = ''): string => (string) ($env[$k] ?? getenv($k) ?: $d);

return [
    'app_url'    => rtrim($get('APP_URL', 'http://localhost:8000'), '/'),
    'app_env'    => $get('APP_ENV', 'development'),
    'db_path'    => BASE_PATH . '/storage/database.sqlite',
    // Turso (libSQL): když je TURSO_URL vyplněné, data jsou v Turso, jinak v lokálním souboru db_path
    'turso' => [
        'url' => $get('TURSO_URL'),
        'token' => $get('TURSO_TOKEN'),
        'mirror' => BASE_PATH . '/storage/cache/mirror.sqlite',
        'ttl' => (int) $get('TURSO_MIRROR_TTL', '300'),
    ],
    'smtp' => [
        'host' => $get('SMTP_HOST'),
        'port' => (int) $get('SMTP_PORT', '587'),
        'user' => $get('SMTP_USER'),
        'pass' => $get('SMTP_PASS'),
        'from' => $get('SMTP_FROM'),
    ],
    'inquiry_to' => $get('INQUIRY_TO'),
];
