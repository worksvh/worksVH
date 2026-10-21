<?php
declare(strict_types=1);

/*
 * Příkazy:
 *   php app/cli.php migrate              – vytvoří/aktualizuje databázi a naplní výchozí obsah
 *   php app/cli.php create-admin e-mail  – vytvoří správce (heslo se zadá interaktivně)
 *   php app/cli.php backup               – záloha do storage/backups (posledních 14)
 *   php app/cli.php anonymize            – anonymizace starých poptávek (pro cron)
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
