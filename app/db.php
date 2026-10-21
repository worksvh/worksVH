<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/turso.php';

function db_is_turso(): bool
{
    return (string) config('turso')['url'] !== '';
}

/** PDO pro lokální SQLite, nebo TursoDb se stejným rozhraním. */
function db(): PDO|TursoDb
{
    static $pdo = null;
    if ($pdo === null && db_is_turso()) {
        $t = config('turso');
        $pdo = new TursoDb($t['url'], $t['token'], $t['mirror'], max(5, $t['ttl']));
    }
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
    if ($ready === null && db_is_turso()) {
        try {
            $ready = (bool) db()->query("SELECT 1 FROM pages LIMIT 1")->fetchColumn();
        } catch (Throwable $e) {
            log_error('Databáze Turso: ' . $e->getMessage());
            $ready = false;
        }
    }
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

/** Několik zápisů najednou: v Turso jedním požadavkem, v SQLite v transakci. $stmts = [[sql, params], …] */
function db_batch(array $stmts): void
{
    if ($stmts === []) {
        return;
    }
    $db = db();
    if ($db instanceof TursoDb) {
        $db->batch($stmts);
        return;
    }
    $db->beginTransaction();
    try {
        foreach ($stmts as [$sql, $params]) {
            db_exec($sql, $params);
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
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
