<?php
declare(strict_types=1);

/* Administrace: /admin/… */

require __DIR__ . '/fields.php';
require __DIR__ . '/controllers.php';

start_session();
send_security_headers(true);

if (!db_ready()) {
    http_response_code(503);
    echo 'Databáze není připravená. Spusťte: php app/cli.php migrate';
    exit;
}

$sub = trim(substr($path, strlen('/admin')), '/');
$seg = $sub === '' ? [] : explode('/', $sub);
$post = is_post();

if ($post) {
    csrf_check();
}

// veřejné části adminu
switch ($sub) {
    case 'prihlaseni':
        admin_login();
        exit;
    case 'zapomenute-heslo':
        admin_forgot();
        exit;
    case 'obnova-hesla':
        admin_reset();
        exit;
}

if (!current_user()) {
    redirect('/admin/prihlaseni' . ($sub !== '' && !$post ? '?zpet=' . rawurlencode('/admin/' . $sub) : ''));
}

// anonymizace starých poptávek nejvýše jednou za hodinu na relaci
if (time() - (int) ($_SESSION['anon_at'] ?? 0) > 3600) {
    inquiries_anonymize_old();
    $_SESSION['anon_at'] = time();
}

$collectionByPath = [];
foreach (schema()['collections'] as $ck => $cs) {
    $collectionByPath[$cs['path']] = $ck;
}

$s0 = $seg[0] ?? '';
$s1 = $seg[1] ?? null;
$s2 = $seg[2] ?? null;
$s3 = $seg[3] ?? null;

match (true) {
    $s0 === '' => admin_dashboard(),
    $s0 === 'odhlasit' && $post => admin_logout(),
    $s0 === 'stranky' && $s1 === null => redirect('/admin/stranky/home'),
    $s0 === 'stranky' && page_schema((string) $s1) !== null => admin_entity_edit(admin_entity('page', $s1)),
    $s0 === 'nastaveni' && $s1 === null => redirect('/admin/nastaveni/company'),
    $s0 === 'nastaveni' && $s1 === 'uzivatele' => admin_users(),
    $s0 === 'nastaveni' && $s1 === 'zaloha' => admin_backup(),
    $s0 === 'nastaveni' && settings_schema((string) $s1) !== null => admin_entity_edit(admin_entity('settings', $s1)),
    $s0 === 'nahled' && $post => admin_preview(),
    $s0 === 'historie' && ctype_digit((string) $s1) && $s2 === 'obnovit' && $post => admin_restore((int) $s1),
    $s0 === 'media' && $s1 === null => admin_media(),
    $s0 === 'media' && $s1 === 'seznam.json' => admin_media_json(),
    $s0 === 'media' && $s1 === 'nahrat' && $post => admin_media_upload(),
    $s0 === 'media' && ctype_digit((string) $s1) && $post => admin_media_update((int) $s1, $s2 === 'smazat'),
    $s0 === 'poptavky' && $s1 === null => admin_inquiries(),
    $s0 === 'poptavky' && ctype_digit((string) $s1) && $s2 === null => admin_inquiry((int) $s1),
    $s0 === 'poptavky' && ctype_digit((string) $s1) && $s2 === 'soubor' && ctype_digit((string) $s3) => admin_inquiry_file((int) $s1, (int) $s3),
    $s0 === 'poptavky' && ctype_digit((string) $s1) && $s2 === 'smazat' && $post => admin_inquiry_delete((int) $s1),
    isset($collectionByPath[$s0]) && $s1 === null => admin_collection_list($collectionByPath[$s0]),
    isset($collectionByPath[$s0]) && $s1 === 'nova' => admin_entity_edit(admin_entity('item', $collectionByPath[$s0] . ':new')),
    isset($collectionByPath[$s0]) && $s1 === 'poradi' && $post => admin_collection_reorder($collectionByPath[$s0]),
    isset($collectionByPath[$s0]) && ctype_digit((string) $s1) && $s2 === null => admin_entity_edit(admin_entity('item', $collectionByPath[$s0] . ':' . $s1)),
    isset($collectionByPath[$s0]) && ctype_digit((string) $s1) && $s2 === 'smazat' && $post => admin_collection_delete($collectionByPath[$s0], (int) $s1),
    default => admin_not_found(),
};
