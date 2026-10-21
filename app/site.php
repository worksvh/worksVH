<?php
declare(strict_types=1);

/* Veřejný web: routy a controllery. */

const SITE_ROUTES = [
    '/' => 'home',
    '/sluzby' => 'services',
    '/prace' => 'work',
    '/dilna' => 'workshop',
    '/kontakt' => 'contact',
    '/poptavka-odeslana' => 'thanks',
    '/ochrana-osobnich-udaju' => 'privacy',
];

function site_dispatch(string $path): void
{
    // relace jen když už existuje (náhled z adminu) nebo je potřeba (formulář)
    if (isset($_COOKIE['vhw']) || $path === '/kontakt') {
        start_session();
    }

    if (!db_ready()) {
        http_response_code(503);
        echo 'Databáze není připravená. Spusťte: php app/cli.php migrate';
        return;
    }

    if ($path === '/sitemap.xml') {
        site_sitemap();
        return;
    }
    if ($path === '/robots.txt') {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\nDisallow: /admin\n\nSitemap: " . config('app_url') . "/sitemap.xml\n";
        return;
    }

    $key = SITE_ROUTES[$path] ?? null;
    if ($key === null) {
        site_not_found();
        return;
    }
    if ($key === 'contact' && is_post()) {
        site_contact_submit();
        return;
    }
    site_page($key, $path);
}

/** Vykreslí stránku podle klíče. $extra = proměnné navíc pro šablonu (např. chyby formuláře). */
function site_page(string $key, string $path, array $extra = [], int $status = 200): void
{
    $page = page($key);
    $vars = ['page' => $page, 'path' => $path] + $extra;

    switch ($key) {
        case 'home':
            $works = array_values(array_filter(items('works'), static fn($w) => !empty($w['data']['on_home'])));
            $vars['works'] = array_slice($works, 0, 3);
            $vars['categories'] = items_by_id('categories');
            break;
        case 'services':
            $vars['services'] = items('services');
            break;
        case 'work':
            $all = items('works');
            $vars['featured'] = array_values(array_filter($all, static fn($w) => !empty($w['data']['featured'])))[0] ?? null;
            $vars['works'] = array_values(array_filter($all, static fn($w) => empty($w['data']['featured'])));
            $vars['categories'] = items('categories', false);
            $vars['catById'] = items_by_id('categories');
            $sel = (string) ($_GET['kategorie'] ?? '');
            $vars['selected'] = in_array($sel, array_column($vars['categories'], 'slug'), true) ? $sel : '';
            break;
        case 'workshop':
            $vars['machines'] = items('machines');
            $vars['people'] = items('people');
            break;
        case 'contact':
            $vars += ['values' => [], 'errors' => [], 'formError' => null];
            if (!isset($_SESSION['inq_started'])) {
                $_SESSION['inq_started'] = time();
            }
            break;
    }

    $content = render('pages/' . $key, $vars);
    http_response_code($status);
    send_security_headers();
    if (isset($_GET['nahled'])) {
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');
    }
    echo render('layout', [
        'content' => $content,
        'pageKey' => $key,
        'path' => $path,
        'seo' => $page['seo'] ?? [],
        'styles' => $vars['styles'] ?? '',
    ]);
}

function site_not_found(): void
{
    http_response_code(404);
    send_security_headers();
    $content = render('pages/404', ['nf' => setting('notfound')]);
    echo render('layout', ['content' => $content, 'pageKey' => '404', 'path' => '', 'seo' => ['title' => setting('notfound')['title']], 'noindex' => true]);
}

function site_contact_submit(): void
{
    // příliš velký POST: PHP zahodí celé tělo, takže chybí i CSRF token
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        site_page('contact', '/kontakt', ['values' => [], 'errors' => [], 'formError' => inq_msg('files_total', ['max' => format_bytes(INQ_MAX_TOTAL_BYTES)])], 422);
        return;
    }
    if (!csrf_valid()) {
        site_page('contact', '/kontakt', ['values' => $_POST, 'errors' => [], 'formError' => 'Platnost formuláře vypršela. Odešlete ho prosím znovu.'], 422);
        return;
    }
    // honeypot: robot dostane „úspěch“, nic se neuloží
    if (trim((string) ($_POST['web'] ?? '')) !== '') {
        log_write('spam', 'honeypot ' . ip_hash(client_ip()));
        redirect('/poptavka-odeslana');
    }

    [$values, $errors] = inq_validate_fields($_POST);
    $formError = null;
    $started = (int) ($_SESSION['inq_started'] ?? 0);
    if ($started === 0 || time() - $started < INQ_MIN_SECONDS) {
        $formError = inq_msg('too_fast');
        $_SESSION['inq_started'] = time();
        log_write('spam', 'too fast ' . ip_hash(client_ip()));
    }
    $ipHash = ip_hash(client_ip());
    if (!$formError && inquiry_rate_exceeded($ipHash)) {
        $formError = inq_msg('rate');
    }
    [$files, $fileError] = inq_validate_files(inq_files_from_request());
    if ($fileError) {
        $errors['files'] = $fileError;
    }

    if ($errors || $formError) {
        site_page('contact', '/kontakt', ['values' => $values, 'errors' => $errors, 'formError' => $formError ?? (string) page('contact')['form']['error_summary']], 422);
        return;
    }

    $id = inquiry_store($values, $files, $ipHash);
    unset($_SESSION['inq_started']);
    try {
        inquiry_send_mails($id);
    } catch (Throwable $e) {
        log_error("Poptávka $id: e-maily selhaly: " . $e->getMessage());
    }
    redirect('/poptavka-odeslana');
}

function site_sitemap(): void
{
    header('Content-Type: application/xml; charset=utf-8');
    $base = config('app_url');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach (SITE_ROUTES as $path => $key) {
        if ($key === 'thanks') {
            continue;
        }
        $updated = db_val('SELECT updated_at FROM pages WHERE key = ?', [$key]);
        echo '  <url><loc>' . e($base . ($path === '/' ? '/' : $path)) . '</loc>'
            . ($updated ? '<lastmod>' . e(date('Y-m-d', strtotime($updated))) . '</lastmod>' : '') . "</url>\n";
    }
    echo "</urlset>\n";
}
