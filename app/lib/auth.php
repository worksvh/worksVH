<?php
declare(strict_types=1);

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_BLOCK_MINUTES = 15;

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['uid'])) {
        return $user = null;
    }
    // uživatel se drží v relaci a z databáze se ověří nejvýš jednou za minutu (smazaný účet ztratí přístup do minuty)
    $cached = $_SESSION['user_cache'] ?? null;
    if (is_array($cached) && ($cached['id'] ?? 0) === (int) $_SESSION['uid'] && time() - (int) ($_SESSION['user_checked'] ?? 0) < 60) {
        return $user = $cached;
    }
    $user = db_one('SELECT id, email, name, last_login_at FROM users WHERE id = ?', [(int) $_SESSION['uid']]);
    $_SESSION['user_cache'] = $user;
    $_SESSION['user_checked'] = time();
    return $user;
}

function login_blocked(string $ip): bool
{
    $since = date('Y-m-d H:i:s', time() - LOGIN_BLOCK_MINUTES * 60);
    return (int) db_val('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > ?', [$ip, $since]) >= LOGIN_MAX_ATTEMPTS;
}

/** Ověří e-mail a heslo. Vrací uživatele, nebo null. Neúspěch se zapíše do login_attempts. */
function login_attempt(string $email, string $password, string $ip): ?array
{
    $u = db_one('SELECT * FROM users WHERE email = ?', [trim($email)]);
    // password_verify i pro neexistujícího uživatele, ať odezva neprozradí, že účet neexistuje
    $hash = $u['password_hash'] ?? '$2y$10$abcdefghijklmnopqrstuuJ9s2bS9fN8wZ7tq7U8bQk0xVt6eY0C';
    if (!password_verify($password, $hash) || !$u) {
        db_exec('INSERT INTO login_attempts (ip, attempted_at) VALUES (?, ?)', [$ip, now()]);
        db_exec('DELETE FROM login_attempts WHERE attempted_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
        return null;
    }
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    db_exec('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
    return $u;
}

function login_user(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    db_exec('UPDATE users SET last_login_at = ? WHERE id = ?', [now(), $u['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
}

function password_problem(string $pw): ?string
{
    if (mb_strlen($pw) < 10) {
        return 'Heslo musí mít aspoň 10 znaků.';
    }
    return null;
}

function user_create(string $email, string $password, string $name = ''): int
{
    db_exec('INSERT INTO users (email, password_hash, name, created_at) VALUES (?, ?, ?, ?)',
        [trim($email), password_hash($password, PASSWORD_DEFAULT), trim($name), now()]);
    return (int) db()->lastInsertId();
}

/** Vytvoří jednorázový token pro obnovu hesla (platný 1 hodinu) a vrátí ho. */
function password_reset_create(int $userId): string
{
    $token = bin2hex(random_bytes(32));
    db_exec('DELETE FROM password_resets WHERE user_id = ? OR expires_at < ?', [$userId, now()]);
    db_exec('INSERT INTO password_resets (token_hash, user_id, expires_at) VALUES (?, ?, ?)',
        [hash('sha256', $token), $userId, date('Y-m-d H:i:s', time() + 3600)]);
    return $token;
}

function password_reset_user(string $token): ?array
{
    if ($token === '') {
        return null;
    }
    return db_one('SELECT u.* FROM password_resets r JOIN users u ON u.id = r.user_id WHERE r.token_hash = ? AND r.expires_at > ?',
        [hash('sha256', $token), now()]);
}

function password_reset_consume(string $token): void
{
    db_exec('DELETE FROM password_resets WHERE token_hash = ?', [hash('sha256', $token)]);
}
