<?php
declare(strict_types=1);

function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

const PLACEHOLDER_RE = '/\[[^\[\]]+\]/u';

/** Obsahuje hodnota text „k doplnění“ v hranatých závorkách? */
function has_placeholder(mixed $v): bool
{
    return is_string($v) && preg_match(PLACEHOLDER_RE, $v) === 1;
}

/** Hodnota pro veřejný web: když je zapnuté skrývání, zmizí texty v hranatých závorkách. */
function ph(mixed $v): string
{
    $v = (string) $v;
    if ($v !== '' && hide_placeholders()) {
        $v = trim((string) preg_replace(PLACEHOLDER_RE, '', $v));
        // zbylé oddělovače typu „ · “ na krajích
        $v = (string) preg_replace('/^[\s·–,-]+|[\s·–,-]+$/u', '', $v);
    }
    return $v;
}

/** Escapovaný text pro web. */
function t(mixed $v): string
{
    return e(ph($v));
}

/** Escapovaný text, konce řádků jako <br>. */
function tbr(mixed $v): string
{
    return nl2br(t($v), false);
}

function hide_placeholders(): bool
{
    static $hide = null;
    if ($hide === null) {
        $hide = (bool) (setting('display')['hide_placeholders'] ?? false);
    }
    return $hide;
}

function slugify(string $s): string
{
    $s = mb_strtolower(trim($s));
    $map = ['á'=>'a','č'=>'c','ď'=>'d','é'=>'e','ě'=>'e','í'=>'i','ň'=>'n','ó'=>'o','ř'=>'r','š'=>'s','ť'=>'t','ú'=>'u','ů'=>'u','ý'=>'y','ž'=>'z','ä'=>'a','ö'=>'o','ü'=>'u','ľ'=>'l','ĺ'=>'l','ô'=>'o','ŕ'=>'r'];
    $s = strtr($s, $map);
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        if ($t !== false) {
            $s = $t;
        }
    }
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    return trim($s, '-');
}

/** Telefon pro odkaz tel: */
function tel_href(string $phone): string
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', $phone);
}

/** Telefon bez předvolby +420 (hlavička). */
function phone_short(string $phone): string
{
    return trim((string) preg_replace('/^\+420\s*/', '', $phone));
}

function redirect(string $url, int $code = 303): never
{
    header('Location: ' . $url, true, $code);
    exit;
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function take_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function request_path(): string
{
    $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $p = '/' . trim(rawurldecode($p), '/');
    return $p;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/** Tajný klíč aplikace (generuje se při prvním použití do storage/app.key). */
function app_key(): string
{
    $file = STORAGE_PATH . '/app.key';
    if (!is_file($file)) {
        file_put_contents($file, bin2hex(random_bytes(32)));
        @chmod($file, 0600);
    }
    return trim((string) file_get_contents($file));
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('vhw');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        // Secure jen na HTTPS, jinak by na http://localhost nešlo přihlášení
        'secure' => is_https() || is_production(),
        'samesite' => 'Lax',
    ]);
    session_start();
}

function format_bytes(int $b): string
{
    if ($b >= 1048576) {
        return number_format($b / 1048576, 1, ',', ' ') . ' MB';
    }
    return number_format(max(1, (int) round($b / 1024)), 0, ',', ' ') . ' kB';
}

function format_date(?string $d, bool $time = true): string
{
    if (!$d) {
        return '';
    }
    $ts = strtotime($d);
    return $ts ? date($time ? 'j. n. Y H:i' : 'j. n. Y', $ts) : $d;
}

/** Nahradí {telefon} odkazem na telefon (hodnota je už escapovaná). */
function with_phone(string $escapedHtml): string
{
    $phone = (string) (setting('company')['phone'] ?? '');
    $link = '<a href="' . e(tel_href($phone)) . '" class="strong">' . e(phone_short($phone)) . '</a>';
    return str_replace('{telefon}', $link, $escapedHtml);
}
