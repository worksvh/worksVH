<?php
declare(strict_types=1);

/*
 * Příkazy:
 *   php app/cli.php migrate              – vytvoří/aktualizuje databázi a naplní výchozí obsah
 *   php app/cli.php create-admin e-mail  – vytvoří správce (heslo se zadá interaktivně)
 *   php app/cli.php backup               – záloha do storage/backups (posledních 14)
 *   php app/cli.php anonymize            – anonymizace starých poptávek (pro cron)
 *   php app/cli.php import-sqlite [soubor] – přenese data z lokální SQLite do Turso (přepíše je)
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__ . '/bootstrap.php';

$cmd = $argv[1] ?? '';

function out(string $s): void { fwrite(STDOUT, $s . "\n"); }
function fail(string $s): never { fwrite(STDERR, $s . "\n"); exit(1); }

function prompt_hidden(string $label): string
{
    fwrite(STDOUT, $label);
    $tty = DIRECTORY_SEPARATOR === '/' && stream_isatty(STDIN);
    if ($tty) {
        shell_exec('stty -echo');
    }
    $v = rtrim((string) fgets(STDIN), "\r\n");
    if ($tty) {
        shell_exec('stty echo');
        fwrite(STDOUT, "\n");
    }
    return $v;
}

switch ($cmd) {
    case 'migrate':
        foreach (['/storage/logs', '/storage/inquiries', '/storage/backups', '/public/media'] as $d) {
            if (!is_dir(BASE_PATH . $d)) {
                mkdir(BASE_PATH . $d, 0775, true);
            }
        }
        $applied = db_migrate();
        out($applied ? 'Migrace: ' . implode(', ', $applied) : 'Migrace: nic nového.');
        $seeded = seed_database();
        out($seeded ? 'Výchozí obsah: ' . implode(', ', $seeded) : 'Výchozí obsah: vše už existuje.');
        app_key();
        break;

    case 'import-sqlite':
        if (!db_is_turso()) {
            fail('Nejdřív vyplňte TURSO_URL a TURSO_TOKEN v .env.');
        }
        $file = $argv[2] ?? config('db_path');
        if (!is_file($file)) {
            fail("Soubor $file neexistuje.");
        }
        $applied = db_migrate();
        out($applied ? 'Migrace v Turso: ' . implode(', ', $applied) : 'Migrace v Turso: nic nového.');
        $src = new PDO('sqlite:' . $file, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $tables = $src->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND name <> 'migrations'")->fetchAll(PDO::FETCH_COLUMN);
        // pořadí kvůli cizím klíčům: nejdřív rodiče
        usort($tables, static fn($a, $b) => (int) in_array($a, ['sessions', 'inquiry_files', 'password_resets'], true) <=> (int) in_array($b, ['sessions', 'inquiry_files', 'password_resets'], true));
        $reqs = [['type' => 'execute', 'stmt' => ['sql' => 'PRAGMA foreign_keys = OFF']]];
        foreach (array_reverse($tables) as $t) {
            $reqs[] = ['type' => 'execute', 'stmt' => ['sql' => "DELETE FROM \"$t\""]];
        }
        $count = 0;
        foreach ($tables as $t) {
            foreach ($src->query("SELECT * FROM \"$t\"") as $row) {
                $cols = array_keys($row);
                $reqs[] = ['type' => 'execute', 'stmt' => [
                    'sql' => "INSERT INTO \"$t\" (\"" . implode('","', $cols) . '") VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')',
                    'args' => array_map([TursoClient::class, 'arg'], array_values($row)),
                ]];
                $count++;
            }
        }
        // vše v jedné transakci: buď se přenese celé, nebo nic
        $client = new TursoClient(config('turso')['url'], config('turso')['token']);
        $client->pipeline(array_merge([$reqs[0], ['type' => 'execute', 'stmt' => ['sql' => 'BEGIN']]], array_slice($reqs, 1), [['type' => 'execute', 'stmt' => ['sql' => 'COMMIT']]]));
        db()->refreshMirror();
        @unlink(config('turso')['mirror'] . '.stale');
        out("Přeneseno $count řádků z " . count($tables) . " tabulek do Turso.");
        break;

    case 'create-admin':
        $email = trim($argv[2] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            fail('Použití: php app/cli.php create-admin email@firma.cz [--password=…]');
        }
        if (!db_ready()) {
            fail('Nejdřív spusťte: php app/cli.php migrate');
        }
        if (db_val('SELECT 1 FROM users WHERE email = ?', [$email])) {
            fail('Uživatel s tímto e-mailem už existuje.');
        }
        $pw = '';
        foreach ($argv as $a) {
            if (str_starts_with($a, '--password=')) {
                $pw = substr($a, 11);
            }
        }
        if ($pw === '') {
            $pw = prompt_hidden('Heslo (min. 10 znaků): ');
            if ($pw !== prompt_hidden('Heslo znovu: ')) {
                fail('Hesla se neshodují.');
            }
        }
        if ($p = password_problem($pw)) {
            fail($p);
        }
        user_create($email, $pw, strstr($email, '@', true) ?: '');
        out("Správce $email vytvořen. Přihlášení: " . config('app_url') . '/admin');
        break;

    case 'backup':
        $file = backup_create(STORAGE_PATH . '/backups');
        backup_prune(STORAGE_PATH . '/backups', 14);
        out('Záloha: ' . $file);
        break;

    case 'anonymize':
        out('Anonymizováno: ' . inquiries_anonymize_old());
        break;

    default:
        out("Příkazy: migrate | create-admin <email> | backup | anonymize");
        exit($cmd === '' ? 0 : 1);
}
