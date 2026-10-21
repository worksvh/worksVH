<?php
declare(strict_types=1);

/* ---------------------------------------------------------------- pomocné */

function admin_view(string $template, array $vars = [], int $status = 200): never
{
    http_response_code($status);
    $content = render('admin/' . $template, $vars);
    echo render('admin/layout', ['content' => $content, 'title' => $vars['title'] ?? 'Administrace', 'nav' => $vars['nav'] ?? '']);
    exit;
}

function admin_not_found(): never
{
    admin_view('message', ['title' => 'Stránka nenalezena', 'text' => 'Tahle stránka administrace neexistuje.'], 404);
}

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function wants_json(): bool
{
    return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
}

/** Bezpečný návrat jen na adresu v adminu. */
function safe_back(?string $url, string $fallback = '/admin'): string
{
    return is_string($url) && preg_match('~^/admin(/[\w\-/.]*)?(\?[\w=&%\-]*)?$~', $url) ? $url : $fallback;
}

/* ---------------------------------------------------------------- přihlášení */

function admin_login(): never
{
    if (current_user()) {
        redirect('/admin');
    }
    $error = null;
    $email = '';
    $back = safe_back($_GET['zpet'] ?? $_POST['zpet'] ?? null);
    if (is_post()) {
        $email = trim((string) ($_POST['email'] ?? ''));
        $ip = client_ip();
        if (login_blocked($ip)) {
            $error = 'Příliš mnoho neúspěšných pokusů. Přihlášení je z této adresy zablokované na ' . LOGIN_BLOCK_MINUTES . ' minut.';
        } elseif ($u = login_attempt($email, (string) ($_POST['password'] ?? ''), $ip)) {
            login_user($u);
            redirect($back);
        } else {
            $error = login_blocked($ip)
                ? 'Příliš mnoho neúspěšných pokusů. Přihlášení je z této adresy zablokované na ' . LOGIN_BLOCK_MINUTES . ' minut.'
                : 'E-mail nebo heslo nesedí.';
        }
    }
    http_response_code($error ? 422 : 200);
    echo render('admin/login', ['error' => $error, 'email' => $email, 'back' => $back]);
    exit;
}

function admin_logout(): never
{
    logout_user();
    start_session();
    flash('ok', 'Byli jste odhlášeni.');
    redirect('/admin/prihlaseni');
}

function admin_forgot(): never
{
    $sent = false;
    if (is_post()) {
        $email = trim((string) ($_POST['email'] ?? ''));
        $ip = client_ip();
        if (!login_blocked($ip)) {
            $u = db_one('SELECT * FROM users WHERE email = ?', [$email]);
            if ($u) {
                $token = password_reset_create((int) $u['id']);
                $link = config('app_url') . '/admin/obnova-hesla?token=' . $token;
                send_mail($u['email'], 'Obnova hesla – administrace webu',
                    "Dobrý den,\n\npro nastavení nového hesla otevřete tento odkaz (platí 1 hodinu a jde použít jen jednou):\n$link\n\nPokud jste o obnovu nežádali, e-mail ignorujte.\n");
                log_info('Obnova hesla vyžádána pro uživatele ' . $u['id']);
            } else {
                // stejné omezení jako u přihlášení proti zkoušení e-mailů
                db_exec('INSERT INTO login_attempts (ip, attempted_at) VALUES (?, ?)', [$ip, now()]);
            }
        }
        $sent = true;
    }
    echo render('admin/forgot', ['sent' => $sent]);
    exit;
}

function admin_reset(): never
{
    $token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
    $u = password_reset_user($token);
    $error = null;
    if ($u && is_post()) {
        $pw = (string) ($_POST['password'] ?? '');
        if ($p = password_problem($pw)) {
            $error = $p;
        } elseif ($pw !== (string) ($_POST['password2'] ?? '')) {
            $error = 'Hesla se neshodují.';
        } else {
            db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $u['id']]);
            password_reset_consume($token);
            db_exec('DELETE FROM password_resets WHERE user_id = ?', [$u['id']]);
            flash('ok', 'Heslo je změněné. Přihlaste se novým heslem.');
            redirect('/admin/prihlaseni');
        }
    }
    echo render('admin/reset', ['valid' => (bool) $u, 'token' => $token, 'error' => $error]);
    exit;
}

/* ---------------------------------------------------------------- přehled */

function admin_dashboard(): never
{
    admin_view('dashboard', [
        'title' => 'Přehled',
        'nav' => 'dashboard',
        'newCount' => (int) db_val("SELECT COUNT(*) FROM inquiries WHERE status = 'new'"),
        'latest' => db_all("SELECT * FROM inquiries ORDER BY id DESC LIMIT 5"),
        'todo' => placeholder_report(),
        'hidden' => (bool) setting('display')['hide_placeholders'],
    ]);
}

/* ---------------------------------------------------------------- editor entit (stránky, nastavení, položky) */

/** Popis editované entity: odkud se berou pole, data a kam se ukládá. */
function admin_entity(string $type, string $key): array
{
    if ($type === 'page') {
        $ps = page_schema($key);
        $row = db_one('SELECT data, updated_at FROM pages WHERE key = ?', [$key]);
        return ['type' => 'page', 'key' => $key, 'title' => 'Stránka: ' . $ps['label'], 'label' => $ps['label'], 'groups' => $ps['groups'], 'flat' => false,
            'data' => fill_page_defaults($ps, $row ? json_decode($row['data'], true) : []), 'updated_at' => $row['updated_at'] ?? null,
            'url' => "/admin/stranky/$key", 'web' => $ps['path'], 'nav' => "page:$key", 'rev' => ['page', $key]];
    }
    if ($type === 'settings') {
        $ss = settings_schema($key);
        $row = db_one('SELECT data, updated_at FROM settings WHERE key = ?', [$key]);
        $web = match ($key) { 'notfound' => '/neexistuje', 'form_msgs' => '/kontakt', default => '/' };
        return ['type' => 'settings', 'key' => $key, 'title' => 'Nastavení: ' . $ss['label'], 'label' => $ss['label'],
            'groups' => ['_' => ['label' => $ss['label'], 'help' => $ss['help'] ?? null, 'fields' => $ss['fields']]], 'flat' => true,
            'data' => fill_defaults($ss['fields'], $row ? json_decode($row['data'], true) : []), 'updated_at' => $row['updated_at'] ?? null,
            'url' => "/admin/nastaveni/$key", 'web' => $web, 'nav' => 'settings', 'rev' => ['settings', $key], 'settingsTab' => $key];
    }
    [$c, $id] = explode(':', $key, 2);
    $cs = collection_schema($c);
    $item = $id === 'new' ? null : item((int) $id);
    if ($id !== 'new' && (!$item || db_val('SELECT collection FROM items WHERE id = ?', [(int) $id]) !== $c)) {
        admin_not_found();
    }
    $data = $item ? $item['data'] : fill_defaults($cs['fields'], []);
    $title = $item ? (string) $item['data'][$cs['title']] : '';
    $web = $cs['web'] . ($c === 'services' && $item ? '#' . $item['slug'] : '');
    return ['type' => 'item', 'key' => $key, 'collection' => $c, 'cs' => $cs, 'id' => $item['id'] ?? null,
        'title' => $item ? $cs['label'] . ': ' . ($title !== '' ? $title : '#' . $item['id']) : 'Nová ' . $cs['singular'],
        'label' => $cs['label'], 'groups' => ['_' => ['label' => 'Obsah', 'fields' => $cs['fields']]], 'flat' => true,
        'data' => $data, 'slug' => $item['slug'] ?? '', 'visible' => $item['visible'] ?? true, 'updated_at' => $item['updated_at'] ?? null,
        'url' => $item ? "/admin/{$cs['path']}/{$item['id']}" : "/admin/{$cs['path']}/nova", 'web' => $web,
        'nav' => "col:$c", 'rev' => $item ? ['item', (string) $item['id']] : null];
}

/** Přečte a ověří odeslaný formulář entity. Vrací [data, errors, extra]. */
function admin_entity_parse(array $ent, bool $forPreview = false): array
{
    $input = $_POST['data'] ?? [];
    $errors = [];
    if ($ent['flat']) {
        $data = normalize_fields($ent['groups']['_']['fields'], $input, '', $errors, $ent['data']);
    } else {
        $data = normalize_page(['groups' => $ent['groups']], is_array($input) ? $input : [], $errors, $ent['data']);
    }
    $extra = [];
    if ($ent['type'] === 'item') {
        $cs = $ent['cs'];
        $extra['visible'] = empty($cs['visibility']) || !empty($_POST['visible']);
        $extra['slug'] = null;
        if (!empty($cs['slug'])) {
            $raw = slugify((string) ($_POST['slug'] ?? ''));
            if ($raw === '') {
                $raw = slugify((string) ($data[$cs['slug']] ?? ''));
            }
            $extra['slug'] = $forPreview ? $raw : unique_slug($ent['collection'], $raw, $ent['id']);
        }
        if ($ent['collection'] === 'works' && !empty($data['on_home']) && !$forPreview) {
            $others = 0;
            foreach (items('works', false) as $w) {
                if ($w['id'] !== $ent['id'] && !empty($w['data']['on_home'])) {
                    $others++;
                }
            }
            if ($others >= 3) {
                $errors['on_home'] = 'Na úvodní stránce už jsou 3 zakázky. Nejdřív u jiné zakázky vypněte „Na úvodní stránce“.';
            }
        }
    }
    return [$data, $errors, $extra];
}

function admin_entity_save(array $ent, array $data, array $extra): string
{
    switch ($ent['type']) {
        case 'page':
            page_save($ent['key'], $data);
            return $ent['url'];
        case 'settings':
            setting_save($ent['key'], $data);
            return $ent['url'];
        default:
            db()->beginTransaction();
            $id = item_save($ent['collection'], $ent['id'], $data, $extra['slug'], $extra['visible']);
            if ($ent['collection'] === 'works' && !empty($data['featured'])) {
                // vybraná zakázka může být jen jedna
                db_exec("UPDATE items SET data = json_set(data, '$.featured', json('false')) WHERE collection = 'works' AND id <> ?", [$id]);
            }
            db()->commit();
            return "/admin/{$ent['cs']['path']}/$id";
    }
}

function admin_entity_edit(array $ent): never
{
    $errors = [];
    $ent['data_orig'] = $ent['data'];
    $ent['slug_orig'] = $ent['slug'] ?? null;
    $ent['visible_orig'] = $ent['visible'] ?? true;
    if (is_post()) {
        [$data, $errors, $extra] = admin_entity_parse($ent);
        if ($extra['slug'] ?? null) {
            $ent['slug'] = $extra['slug'];
        }
        if (array_key_exists('visible', $extra)) {
            $ent['visible'] = $extra['visible'];
        }
        if (!$errors) {
            // první úprava: původní (výchozí) obsah uložit do historie, ať jde vrátit
            if ($ent['rev'] && !revisions_for(...$ent['rev'])) {
                revision_add($ent['rev'][0], $ent['rev'][1], $ent['type'] === 'item'
                    ? ['data' => $ent['data_orig'], 'slug' => $ent['slug_orig'], 'visible' => $ent['visible_orig']]
                    : $ent['data_orig']);
            }
            $url = admin_entity_save($ent, $data, $extra);
            flash('ok', 'Uloženo.');
            redirect($url);
        }
        $ent['data'] = $data;
    }
    admin_view('edit', [
        'title' => $ent['title'],
        'nav' => $ent['nav'],
        'ent' => $ent,
        'errors' => $errors,
        'revisions' => $ent['rev'] ? revisions_for(...$ent['rev']) : [],
    ], $errors ? 422 : 200);
}

function admin_preview(): never
{
    $type = (string) ($_POST['_type'] ?? '');
    $key = (string) ($_POST['_key'] ?? '');
    if (!in_array($type, ['page', 'settings', 'item'], true)) {
        admin_not_found();
    }
    $ent = admin_entity($type, $key);
    [$data, , $extra] = admin_entity_parse($ent, true);
    $_SESSION['preview'] = ['type' => $type, 'key' => $type === 'item' ? $ent['collection'] . ':' . ($ent['id'] ?? 'new') : $key, 'data' => $data, 'slug' => $extra['slug'] ?? null];
    $web = $ent['web'];
    $hash = '';
    if (str_contains($web, '#')) {
        [$web, $hash] = explode('#', $web, 2);
        $hash = '#' . $hash;
    }
    if ($type === 'item' && $ent['collection'] === 'services' && !empty($extra['slug'])) {
        $hash = '#' . $extra['slug'];
    }
    redirect($web . '?nahled=1' . $hash);
}

function admin_restore(int $revId): never
{
    $rev = db_one('SELECT * FROM revisions WHERE id = ?', [$revId]);
    if (!$rev) {
        admin_not_found();
    }
    $data = json_decode($rev['data'], true) ?: [];
    switch ($rev['entity_type']) {
        case 'page':
            page_save($rev['entity_key'], $data);
            $url = '/admin/stranky/' . $rev['entity_key'];
            break;
        case 'settings':
            setting_save($rev['entity_key'], $data);
            $url = '/admin/nastaveni/' . $rev['entity_key'];
            break;
        default:
            $row = db_one('SELECT collection FROM items WHERE id = ?', [(int) $rev['entity_key']]);
            if (!$row) {
                flash('error', 'Položka už neexistuje, verzi nejde obnovit.');
                redirect('/admin');
            }
            $slug = $data['slug'] ?? null;
            if ($slug) {
                $slug = unique_slug($row['collection'], $slug, (int) $rev['entity_key']);
            }
            item_save($row['collection'], (int) $rev['entity_key'], $data['data'] ?? [], $slug, (bool) ($data['visible'] ?? true));
            $url = '/admin/' . collection_schema($row['collection'])['path'] . '/' . $rev['entity_key'];
    }
    flash('ok', 'Obnovena verze z ' . format_date($rev['created_at']) . '.');
    redirect($url);
}

/* ---------------------------------------------------------------- sbírky */

function admin_collection_list(string $c): never
{
    $cs = collection_schema($c);
    admin_view('list', ['title' => $cs['label'], 'nav' => "col:$c", 'c' => $c, 'cs' => $cs, 'items' => items($c, false),
        'categories' => $c === 'works' ? items_by_id('categories') : []]);
}

function admin_collection_reorder(string $c): never
{
    $ids = json_decode((string) file_get_contents('php://input'), true)['ids'] ?? $_POST['ids'] ?? [];
    if (!is_array($ids)) {
        json_out(['ok' => false], 400);
    }
    db()->beginTransaction();
    foreach (array_values($ids) as $i => $id) {
        db_exec('UPDATE items SET sort = ? WHERE id = ? AND collection = ?', [$i + 1, (int) $id, $c]);
    }
    db()->commit();
    json_out(['ok' => true]);
}

function admin_collection_delete(string $c, int $id): never
{
    $cs = collection_schema($c);
    if ($c === 'categories' && db_val("SELECT 1 FROM items WHERE collection = 'works' AND json_extract(data, '$.category') = ?", [$id])) {
        flash('error', 'Kategorii nejde smazat, protože ji používají zakázky. Nejdřív jim nastavte jinou kategorii.');
        redirect("/admin/{$cs['path']}");
    }
    $row = db_one('SELECT data FROM items WHERE id = ? AND collection = ?', [$id, $c]);
    if ($row) {
        revision_add('item', (string) $id, ['data' => json_decode($row['data'], true), 'deleted' => true]);
        db_exec('DELETE FROM items WHERE id = ? AND collection = ?', [$id, $c]);
        flash('ok', 'Smazáno.');
    }
    redirect("/admin/{$cs['path']}");
}

/* ---------------------------------------------------------------- média */

function admin_media(): never
{
    $media = db_all('SELECT * FROM media ORDER BY id DESC');
    foreach ($media as &$m) {
        $m['variants'] = json_decode($m['variants'], true) ?: [];
        $m['usage'] = media_usage((int) $m['id']);
    }
    unset($m);
    admin_view('media', ['title' => 'Média', 'nav' => 'media', 'media' => $media, 'gd' => function_exists('imagewebp')]);
}

function media_public(array $m): array
{
    $v = is_array($m['variants']) ? $m['variants'] : (json_decode($m['variants'], true) ?: []);
    return ['id' => (int) $m['id'], 'url' => media_url($m['filename']), 'thumb' => media_url($v ? $v[0][2] : $m['filename']),
        'alt' => $m['alt'], 'name' => $m['original_name'], 'svg' => $m['mime'] === 'image/svg+xml',
        'size' => $m['width'] ? $m['width'] . ' × ' . $m['height'] . ' px' : ''];
}

function admin_media_json(): never
{
    json_out(['items' => array_map('media_public', db_all('SELECT * FROM media ORDER BY id DESC'))]);
}

function admin_media_upload(): never
{
    $files = $_FILES['file'] ?? null;
    $alt = trim((string) ($_POST['alt'] ?? ''));
    $list = [];
    if ($files && is_array($files['name'])) {
        foreach ($files['name'] as $i => $n) {
            $list[] = ['name' => $n, 'tmp_name' => $files['tmp_name'][$i], 'size' => $files['size'][$i], 'error' => $files['error'][$i]];
        }
    } elseif ($files) {
        $list[] = $files;
    }
    $ok = [];
    $errs = [];
    foreach ($list as $f) {
        [$id, $err] = media_upload($f, $alt);
        if ($id) {
            $ok[] = media_public(db_one('SELECT * FROM media WHERE id = ?', [$id]));
        } else {
            $errs[] = basename((string) $f['name']) . ': ' . $err;
        }
    }
    if (!$list) {
        $errs[] = empty($_POST) ? 'Soubor je větší, než server povoluje.' : 'Nevybrali jste žádný soubor.';
    }
    if (wants_json()) {
        json_out(['items' => $ok, 'errors' => $errs], $ok ? 200 : 422);
    }
    if ($ok) {
        flash('ok', 'Nahráno: ' . count($ok) . '. Doplňte prosím popis (alt) u každého obrázku.');
    }
    foreach ($errs as $e) {
        flash('error', $e);
    }
    redirect('/admin/media');
}

function admin_media_update(int $id, bool $delete): never
{
    $m = media_get($id);
    if (!$m) {
        admin_not_found();
    }
    if ($delete) {
        if (media_usage($id)) {
            flash('error', 'Obrázek je použitý na webu, nejde smazat. Nejdřív ho odeberte z míst, kde je použitý.');
        } else {
            media_delete($id);
            flash('ok', 'Obrázek smazán.');
        }
    } else {
        db_exec('UPDATE media SET alt = ? WHERE id = ?', [mb_substr(trim((string) ($_POST['alt'] ?? '')), 0, 300), $id]);
        flash('ok', 'Popis uložen.');
    }
    redirect('/admin/media#m-' . $id);
}

/* ---------------------------------------------------------------- poptávky */

function admin_inquiries(): never
{
    $status = (string) ($_GET['stav'] ?? '');
    $q = trim((string) ($_GET['q'] ?? ''));
    $sql = 'SELECT i.*, (SELECT COUNT(*) FROM inquiry_files f WHERE f.inquiry_id = i.id) AS files FROM inquiries i WHERE 1';
    $params = [];
    if (isset(INQUIRY_STATUSES[$status])) {
        $sql .= ' AND status = ?';
        $params[] = $status;
    }
    if ($q !== '') {
        $sql .= ' AND (company LIKE ? OR name LIKE ? OR email LIKE ? OR phone LIKE ?)';
        array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
    }
    $sql .= ' ORDER BY id DESC LIMIT 500';
    $counts = array_column(db_all('SELECT status, COUNT(*) n FROM inquiries GROUP BY status'), 'n', 'status');
    admin_view('inquiries', ['title' => 'Poptávky', 'nav' => 'inquiries', 'rows' => db_all($sql, $params), 'status' => $status, 'q' => $q, 'counts' => $counts]);
}

function admin_inquiry(int $id): never
{
    $q = db_one('SELECT * FROM inquiries WHERE id = ?', [$id]);
    if (!$q) {
        admin_not_found();
    }
    if (is_post()) {
        $st = (string) ($_POST['status'] ?? $q['status']);
        db_exec('UPDATE inquiries SET status = ?, note = ? WHERE id = ?',
            [isset(INQUIRY_STATUSES[$st]) ? $st : $q['status'], mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 10000), $id]);
        flash('ok', 'Uloženo.');
        redirect("/admin/poptavky/$id");
    }
    admin_view('inquiry', ['title' => 'Poptávka č. ' . $id, 'nav' => 'inquiries', 'q' => $q, 'files' => inquiry_files($id),
        'months' => (int) setting('mail')['retention_months']]);
}

function admin_inquiry_file(int $id, int $fid): never
{
    $f = db_one('SELECT * FROM inquiry_files WHERE id = ? AND inquiry_id = ?', [$fid, $id]);
    $path = $f ? inquiry_dir($id) . '/' . basename($f['stored_name']) : '';
    if (!$f || !is_file($path)) {
        admin_not_found();
    }
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($path));
    header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($f['original_name']));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

function admin_inquiry_delete(int $id): never
{
    inquiry_delete_files($id);
    db_exec('DELETE FROM inquiries WHERE id = ?', [$id]);
    flash('ok', "Poptávka č. $id je smazaná i s přílohami.");
    redirect('/admin/poptavky');
}

/* ---------------------------------------------------------------- uživatelé a záloha */

function admin_users(): never
{
    $me = current_user();
    $errors = [];
    if (is_post()) {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'add') {
            $email = trim((string) ($_POST['email'] ?? ''));
            $pw = (string) ($_POST['password'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['add'] = 'Zadejte platný e-mail.';
            } elseif (db_val('SELECT 1 FROM users WHERE email = ?', [$email])) {
                $errors['add'] = 'Uživatel s tímto e-mailem už existuje.';
            } elseif ($p = password_problem($pw)) {
                $errors['add'] = $p;
            } else {
                user_create($email, $pw, (string) ($_POST['name'] ?? ''));
                flash('ok', "Správce $email přidán. Heslo mu předejte bezpečnou cestou.");
                redirect('/admin/nastaveni/uzivatele');
            }
        } elseif ($action === 'password') {
            $u = db_one('SELECT * FROM users WHERE id = ?', [$me['id']]);
            $pw = (string) ($_POST['new'] ?? '');
            if (!password_verify((string) ($_POST['current'] ?? ''), $u['password_hash'])) {
                $errors['password'] = 'Současné heslo nesedí.';
            } elseif ($p = password_problem($pw)) {
                $errors['password'] = $p;
            } elseif ($pw !== (string) ($_POST['new2'] ?? '')) {
                $errors['password'] = 'Nová hesla se neshodují.';
            } else {
                db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $me['id']]);
                flash('ok', 'Heslo změněno.');
                redirect('/admin/nastaveni/uzivatele');
            }
        } elseif ($action === 'delete') {
            $uid = (int) ($_POST['id'] ?? 0);
            if ($uid === (int) $me['id']) {
                flash('error', 'Sami sebe smazat nemůžete.');
            } else {
                db_exec('DELETE FROM users WHERE id = ?', [$uid]);
                flash('ok', 'Uživatel smazán.');
            }
            redirect('/admin/nastaveni/uzivatele');
        }
    }
    admin_view('users', ['title' => 'Nastavení: Uživatelé', 'nav' => 'settings', 'settingsTab' => 'uzivatele',
        'users' => db_all('SELECT id, email, name, created_at, last_login_at FROM users ORDER BY id'), 'me' => $me, 'errors' => $errors], $errors ? 422 : 200);
}

function admin_backup(): never
{
    if (is_post()) {
        $tmp = STORAGE_PATH . '/backups/tmp';
        try {
            $file = backup_create($tmp);
        } catch (Throwable $e) {
            log_error('Záloha selhala: ' . $e->getMessage());
            flash('error', 'Zálohu se nepodařilo vytvořit: ' . $e->getMessage());
            redirect('/admin/nastaveni/zaloha');
        }
        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        readfile($file);
        @unlink($file);
        exit;
    }
    $files = glob(STORAGE_PATH . '/backups/vhworks-zaloha-*.zip') ?: [];
    rsort($files);
    admin_view('backup', ['title' => 'Nastavení: Záloha', 'nav' => 'settings', 'settingsTab' => 'zaloha', 'files' => $files, 'zip' => class_exists(ZipArchive::class)]);
}
