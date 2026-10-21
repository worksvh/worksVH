<?php
declare(strict_types=1);

/*
 * Turso (libSQL) přes HTTP API (/v2/pipeline). Bez rozšíření PHP, stačí curl.
 *
 * TursoDb napodobuje tu část PDO, kterou web používá (prepare/execute/fetch…, exec,
 * transakce, lastInsertId). Každý dotaz je jedna HTTP cesta do Turso, proto se obsah webu
 * (stránky, nastavení, sbírky, média) čte z lokální kopie storage/cache/mirror.sqlite:
 * kopie se stáhne jedním požadavkem, obnoví se po každém zápisu z tohoto webu
 * a nejpozději po TURSO_MIRROR_TTL sekundách (změny zapsané odjinud).
 */

const TURSO_MIRROR_TABLES = ['pages', 'settings', 'items', 'media', 'migrations'];

final class TursoError extends RuntimeException {}

final class TursoClient
{
    private ?CurlHandle $ch = null;
    private ?string $baton = null;
    private string $endpoint;

    public function __construct(string $url, private string $token)
    {
        $this->endpoint = preg_replace('~^libsql://~', 'https://', rtrim($url, '/')) . '/v2/pipeline';
    }

    /** Pošle požadavky, vrátí jejich výsledky. $keep = nechat spojení otevřené (transakce). */
    public function pipeline(array $requests, bool $keep = false): array
    {
        if (!$keep) {
            $requests[] = ['type' => 'close'];
        }
        $body = ['requests' => $requests];
        if ($this->baton !== null) {
            $body['baton'] = $this->baton;
        }
        if ($this->ch === null) {
            $this->ch = curl_init();
            // PHP 8.5+: spojení a TLS se drží mezi požadavky (ušetří ~1 s na handshake)
            if (function_exists('curl_share_init_persistent')) {
                static $share = null;
                $share ??= curl_share_init_persistent([CURL_LOCK_DATA_CONNECT, CURL_LOCK_DATA_SSL_SESSION, CURL_LOCK_DATA_DNS]);
                curl_setopt($this->ch, CURLOPT_SHARE, $share);
            }
        }
        curl_setopt_array($this->ch, [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->token, 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);
        $raw = curl_exec($this->ch);
        $code = (int) curl_getinfo($this->ch, CURLINFO_RESPONSE_CODE);
        if ($raw === false || $code !== 200) {
            $this->baton = null;
            throw new TursoError('Turso nedostupné (HTTP ' . $code . '): ' . ($raw === false ? curl_error($this->ch) : mb_substr((string) $raw, 0, 300)));
        }
        $res = json_decode((string) $raw, true);
        $this->baton = $keep ? ($res['baton'] ?? null) : null;
        $out = [];
        foreach ($res['results'] ?? [] as $r) {
            if (($r['type'] ?? '') === 'error') {
                $this->baton = null;
                throw new TursoError((string) ($r['error']['message'] ?? 'Chyba Turso'));
            }
            $out[] = $r['response'] ?? [];
        }
        if (!$keep) {
            array_pop($out); // odpověď na close
        }
        return $out;
    }

    public static function arg(mixed $v): array
    {
        return match (true) {
            $v === null => ['type' => 'null'],
            is_bool($v) => ['type' => 'integer', 'value' => (string) (int) $v],
            is_int($v) => ['type' => 'integer', 'value' => (string) $v],
            is_float($v) => ['type' => 'float', 'value' => $v],
            default => ['type' => 'text', 'value' => (string) $v],
        };
    }

    public static function value(array $v): mixed
    {
        return match ($v['type'] ?? 'null') {
            'null' => null,
            'integer' => (int) $v['value'],
            'float' => (float) $v['value'],
            'blob' => base64_decode((string) ($v['base64'] ?? '')),
            default => $v['value'],
        };
    }

    /** Výsledek execute → pole asociativních řádků. */
    public static function rows(array $result): array
    {
        $cols = array_map(static fn($c) => (string) $c['name'], $result['cols'] ?? []);
        $rows = [];
        foreach ($result['rows'] ?? [] as $r) {
            $row = [];
            foreach ($r as $i => $v) {
                $row[$cols[$i]] = self::value($v);
            }
            $rows[] = $row;
        }
        return $rows;
    }
}

final class TursoStatement
{
    private array $rows = [];
    private int $pos = 0;
    private int $affected = 0;

    public function __construct(private TursoDb $db, private string $sql) {}

    public function execute(array $params = []): bool
    {
        $result = $this->db->run($this->sql, array_values($params));
        $this->rows = TursoClient::rows($result);
        $this->pos = 0;
        $this->affected = (int) ($result['affected_row_count'] ?? 0);
        return true;
    }

    public function fetchAll(): array
    {
        return $this->rows;
    }

    public function fetch(): array|false
    {
        return $this->rows[$this->pos++] ?? false;
    }

    public function fetchColumn(): mixed
    {
        $row = $this->fetch();
        return $row === false ? false : (array_values($row)[0] ?? null);
    }

    public function rowCount(): int
    {
        return $this->affected;
    }
}

final class TursoDb
{
    private TursoClient $client;
    private ?PDO $mirror = null;
    private bool $inTx = false;
    private bool $dirty = false;
    private ?int $lastId = null;

    public function __construct(string $url, string $token, private string $mirrorPath, private int $ttl)
    {
        $this->client = new TursoClient($url, $token);
    }

    /** SELECT jen nad tabulkami obsahu jde do lokální kopie, ostatní do Turso. */
    public function prepare(string $sql): TursoStatement|PDOStatement
    {
        if (!$this->inTx && !$this->dirty && self::mirrorable($sql) && ($m = $this->mirror()) !== null) {
            return $m->prepare($sql);
        }
        return new TursoStatement($this, $sql);
    }

    public function query(string $sql): TursoStatement|PDOStatement
    {
        $st = $this->prepare($sql);
        $st->execute();
        return $st;
    }

    /** Spustí jeden dotaz v Turso a vrátí surový výsledek. */
    public function run(string $sql, array $params = []): array
    {
        $stmt = ['sql' => $sql, 'args' => array_map([TursoClient::class, 'arg'], $params)];
        $reqs = [['type' => 'execute', 'stmt' => $stmt]];
        if (!$this->inTx) {
            array_unshift($reqs, ['type' => 'execute', 'stmt' => ['sql' => 'PRAGMA foreign_keys = ON']]);
        }
        $t0 = microtime(true);
        $res = $this->client->pipeline($reqs, $this->inTx);
        if (getenv('TURSO_DEBUG')) { error_log(sprintf("[turso %4dms] %s", (microtime(true) - $t0) * 1000, preg_replace('/\s+/', ' ', mb_substr($sql, 0, 110)))); }
        $result = end($res)['result'] ?? [];
        if (isset($result['last_insert_rowid']) && $result['last_insert_rowid'] !== null) {
            $this->lastId = (int) $result['last_insert_rowid'];
        }
        if (!self::isRead($sql)) {
            $this->touched($sql);
        }
        return $result;
    }

    /** Několik příkazů s parametry v jedné transakci a jednom požadavku. */
    public function batch(array $stmts): void
    {
        $reqs = [['type' => 'execute', 'stmt' => ['sql' => 'PRAGMA foreign_keys = ON']], ['type' => 'execute', 'stmt' => ['sql' => 'BEGIN']]];
        foreach ($stmts as [$sql, $params]) {
            $reqs[] = ['type' => 'execute', 'stmt' => ['sql' => $sql, 'args' => array_map([TursoClient::class, 'arg'], array_values($params))]];
        }
        $reqs[] = ['type' => 'execute', 'stmt' => ['sql' => 'COMMIT']];
        // při chybě Turso zbytek neprovede a nedokončenou transakci se zavřením spojení zahodí
        $this->client->pipeline($reqs);
        foreach ($stmts as [$sql]) {
            $this->touched($sql);
        }
    }

    /** Víc příkazů najednou (migrace). */
    public function exec(string $sql): int
    {
        $this->client->pipeline([['type' => 'sequence', 'sql' => $sql]], $this->inTx);
        $this->touched($sql);
        return 0;
    }

    public function beginTransaction(): bool
    {
        $this->client->pipeline([
            ['type' => 'execute', 'stmt' => ['sql' => 'PRAGMA foreign_keys = ON']],
            ['type' => 'execute', 'stmt' => ['sql' => 'BEGIN']],
        ], true);
        $this->inTx = true;
        return true;
    }

    public function commit(): bool
    {
        $this->client->pipeline([['type' => 'execute', 'stmt' => ['sql' => 'COMMIT']]]);
        $this->inTx = false;
        $this->afterWrite();
        return true;
    }

    public function rollBack(): bool
    {
        try {
            $this->client->pipeline([['type' => 'execute', 'stmt' => ['sql' => 'ROLLBACK']]]);
        } catch (TursoError) {
            // spojení už je zavřené, Turso transakci zahodilo samo
        }
        $this->inTx = false;
        $this->dirty = false;
        return true;
    }

    public function inTransaction(): bool
    {
        return $this->inTx;
    }

    public function lastInsertId(): string
    {
        return (string) ($this->lastId ?? 0);
    }

    public function quote(string $s): string
    {
        return "'" . str_replace("'", "''", $s) . "'";
    }

    /* ---------------------------------------------------------- lokální kopie obsahu */

    private static function isRead(string $sql): bool
    {
        return (bool) preg_match('~^\s*(SELECT|WITH)\b~i', $sql);
    }

    private static function tables(string $sql): array
    {
        preg_match_all('~\b(?:FROM|JOIN|INTO|UPDATE|TABLE(?:\s+IF\s+(?:NOT\s+)?EXISTS)?)\s+["`]?([A-Za-z_][A-Za-z0-9_]*)~i', $sql, $m);
        return array_unique(array_map('strtolower', $m[1]));
    }

    private static function mirrorable(string $sql): bool
    {
        if (!self::isRead($sql)) {
            return false;
        }
        $t = self::tables($sql);
        return $t !== [] && array_diff($t, TURSO_MIRROR_TABLES) === [];
    }

    private function touched(string $sql): void
    {
        if (array_intersect(self::tables($sql), TURSO_MIRROR_TABLES) === []) {
            return;
        }
        $this->dirty = true;
        if (!$this->inTx) {
            $this->afterWrite();
        }
    }

    private function afterWrite(): void
    {
        if ($this->dirty) {
            $this->dirty = false;
            $this->mirror = null;
            @touch($this->mirrorPath . '.stale');
        }
    }

    private function mirror(): ?PDO
    {
        if ($this->mirror !== null) {
            return $this->mirror;
        }
        $stale = is_file($this->mirrorPath . '.stale');
        $fresh = is_file($this->mirrorPath) && !$stale && time() - (int) filemtime($this->mirrorPath) < $this->ttl;
        if (!$fresh) {
            try {
                $this->refreshMirror();
                @unlink($this->mirrorPath . '.stale');
            } catch (Throwable $e) {
                log_error('Kopii obsahu z Turso se nepodařilo obnovit: ' . $e->getMessage());
                if (!is_file($this->mirrorPath)) {
                    return null; // bez kopie čte vše přímo z Turso
                }
            }
        }
        $pdo = new PDO('sqlite:' . $this->mirrorPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $this->mirror = $pdo;
    }

    /** Stáhne tabulky obsahu jedním požadavkem a uloží je do nového souboru (výměna je atomická). */
    public function refreshMirror(): void
    {
        $list = implode(',', array_map(fn($t) => $this->quote($t), TURSO_MIRROR_TABLES));
        $reqs = [['type' => 'execute', 'stmt' => ['sql' => "SELECT name, sql FROM sqlite_master WHERE type IN ('table','index') AND tbl_name IN ($list) AND sql IS NOT NULL"]]];
        foreach (TURSO_MIRROR_TABLES as $t) {
            $reqs[] = ['type' => 'execute', 'stmt' => ['sql' => "SELECT * FROM $t"]];
        }
        $res = $this->client->pipeline($reqs);
        $schema = TursoClient::rows($res[0]['result'] ?? []);
        if (!array_filter($schema, static fn($r) => $r['name'] === 'pages')) {
            throw new TursoError('V databázi Turso chybí tabulky. Spusťte: php app/cli.php migrate');
        }
        $dir = dirname($this->mirrorPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }
        $tmp = $this->mirrorPath . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $pdo = new PDO('sqlite:' . $tmp, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA journal_mode = OFF');
        $pdo->beginTransaction();
        foreach ($schema as $s) {
            $pdo->exec((string) $s['sql']);
        }
        foreach (TURSO_MIRROR_TABLES as $i => $t) {
            $rows = TursoClient::rows($res[$i + 1]['result'] ?? []);
            if (!$rows) {
                continue;
            }
            $cols = array_keys($rows[0]);
            $st = $pdo->prepare("INSERT INTO $t (" . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')');
            foreach ($rows as $r) {
                $st->execute(array_values($r));
            }
        }
        $pdo->commit();
        $pdo = null;
        rename($tmp, $this->mirrorPath);
    }

    /** Celá databáze do lokálního souboru SQLite (záloha). */
    public function exportTo(string $path): void
    {
        $schema = TursoClient::rows($this->run("SELECT type, name, sql FROM sqlite_master WHERE sql IS NOT NULL AND name NOT LIKE 'sqlite_%' AND name NOT LIKE '_litestream%' AND name NOT LIKE 'libsql_%' ORDER BY type = 'table' DESC"));
        $tables = array_column(array_filter($schema, static fn($s) => $s['type'] === 'table'), 'name');
        $res = $tables ? $this->client->pipeline(array_map(static fn($t) => ['type' => 'execute', 'stmt' => ['sql' => "SELECT * FROM \"$t\""]], $tables)) : [];
        $pdo = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->beginTransaction();
        foreach ($schema as $s) {
            $pdo->exec((string) $s['sql']);
        }
        foreach ($tables as $i => $t) {
            foreach (TursoClient::rows($res[$i]['result'] ?? []) as $r) {
                $cols = array_keys($r);
                $pdo->prepare("INSERT INTO \"$t\" (\"" . implode('","', $cols) . '") VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')')
                    ->execute(array_values($r));
            }
        }
        $pdo->commit();
    }
}
