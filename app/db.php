<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . config('db_path'), null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
    }
    return $pdo;
}

function db_ready(): bool
{
    static $ready = null;
    if ($ready === null) {
        $ready = is_file(config('db_path'))
            && (bool) db()->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='pages'")->fetchColumn();
    }
    return $ready;
}

function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

function db_val(string $sql, array $params = []): mixed
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $v = $st->fetchColumn();
    return $v === false ? null : $v;
}

function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/** Spustí čekající migrace z app/migrations (*.sql), vrátí jejich názvy. */
function db_migrate(): array
{
    db()->exec('CREATE TABLE IF NOT EXISTS migrations (name TEXT PRIMARY KEY, applied_at TEXT NOT NULL)');
    $done = array_column(db_all('SELECT name FROM migrations'), 'name');
    $files = glob(APP_PATH . '/migrations/*.sql') ?: [];
    sort($files);
    $applied = [];
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $done, true)) {
            continue;
        }
        db()->beginTransaction();
        db()->exec(file_get_contents($file));
        db_exec('INSERT INTO migrations (name, applied_at) VALUES (?, ?)', [$name, now()]);
        db()->commit();
        $applied[] = $name;
    }
    return $applied;
}
