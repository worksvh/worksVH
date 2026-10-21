<?php
declare(strict_types=1);

/** Vykreslí šablonu z app/views (nebo app/admin/views) s proměnnými a vrátí HTML. */
function render(string $template, array $vars = []): string
{
    $file = str_starts_with($template, 'admin/')
        ? APP_PATH . '/admin/views/' . substr($template, 6) . '.php'
        : APP_PATH . '/views/' . $template . '.php';
    extract($vars, EXTR_SKIP);
    ob_start();
    try {
        require $file;
    } catch (Throwable $e) {
        ob_end_clean();
        throw $e;
    }
    return (string) ob_get_clean();
}

function partial(string $name, array $vars = []): void
{
    echo render('partials/' . $name, $vars);
}

/** Jednorázový nonce pro <style> v hlavičce (CSP). */
function csp_nonce(): string
{
    static $n = null;
    return $n ??= base64_encode(random_bytes(16));
}

function send_security_headers(bool $admin = false): void
{
    $nonce = csp_nonce();
    $csp = "default-src 'self'; script-src 'self'; style-src 'self' 'nonce-$nonce'; "
        . "font-src 'self'; img-src 'self' data: blob:; connect-src 'self'; form-action 'self'; "
        . "frame-ancestors 'none'; base-uri 'self'; object-src 'none'";
    header('Content-Security-Policy: ' . $csp);
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('X-Frame-Options: DENY');
    // stránka nese jednorázový nonce a obsah z CMS → prohlížeč ji musí pokaždé ověřit u serveru
    header('Cache-Control: no-cache');
    if (is_production() && is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    if ($admin) {
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
    }
}

/** Je odkaz aktivní pro aktuální cestu? */
function nav_active(string $url, string $path): bool
{
    $u = '/' . trim((string) parse_url($url, PHP_URL_PATH), '/');
    return $u === $path || ($u !== '/' && str_starts_with($path, $u . '/'));
}

/** Přidá CSS do <style nonce> v hlavičce stránky (např. pozice pinů z CMS). */
function page_style(?string $css = null): string
{
    static $styles = '';
    if ($css !== null) {
        $styles .= $css;
    }
    return $styles;
}
