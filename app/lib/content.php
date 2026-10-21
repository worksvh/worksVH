<?php
declare(strict_types=1);

/* ---------------------------------------------------------------- schéma a seed */

function schema(): array
{
    static $s = null;
    return $s ??= require APP_PATH . '/content/schema.php';
}

function seed_data(): array
{
    static $s = null;
    return $s ??= require APP_PATH . '/content/seed.php';
}

function page_schema(string $key): ?array
{
    return schema()['pages'][$key] ?? null;
}

function collection_schema(string $key): ?array
{
    return schema()['collections'][$key] ?? null;
}

function settings_schema(string $key): ?array
{
    return schema()['settings'][$key] ?? null;
}

/** Výchozí hodnota pole podle typu. */
function field_default(array $f): mixed
{
    if (array_key_exists('default', $f)) {
        return $f['default'];
    }
    return match ($f['type']) {
        'tags', 'repeater' => [],
        'toggle' => false,
        'image' => ['media' => null, 'alt' => ''],
        'reference', 'number' => null,
        default => '',
    };
}

/** Doplní chybějící klíče výchozími hodnotami podle polí. */
function fill_defaults(array $fields, mixed $data): array
{
    $data = is_array($data) ? $data : [];
    $out = [];
    foreach ($fields as $name => $f) {
        $v = array_key_exists($name, $data) ? $data[$name] : field_default($f);
        if ($f['type'] === 'repeater') {
            $v = array_map(static fn($row) => fill_defaults($f['fields'], $row), is_array($v) ? array_values($v) : []);
        } elseif ($f['type'] === 'image') {
            $v = is_array($v) ? ['media' => isset($v['media']) && $v['media'] !== '' ? (int) $v['media'] : null, 'alt' => (string) ($v['alt'] ?? '')] : field_default($f);
        }
        $out[$name] = $v;
    }
    return $out;
}

function fill_page_defaults(array $schema, mixed $data): array
{
    $data = is_array($data) ? $data : [];
    $out = [];
    foreach ($schema['groups'] as $g => $group) {
        $out[$g] = fill_defaults($group['fields'], $data[$g] ?? []);
    }
    return $out;
}

/* ---------------------------------------------------------------- náhled (neuložený obsah) */

/** Pokud admin otevřel náhled, vrátí neuložená data pro danou entitu. */
function preview_data(string $type, string $key): ?array
{
    if (!isset($_GET['nahled']) || session_status() !== PHP_SESSION_ACTIVE) {
        return null;
    }
    $p = $_SESSION['preview'] ?? null;
    if (!$p || !current_user() || $p['type'] !== $type || (string) $p['key'] !== $key) {
        return null;
    }
    return $p['data'];
}

/* ---------------------------------------------------------------- čtení obsahu */

function page(string $key): array
{
    static $cache = [];
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $schema = page_schema($key) ?? throw new RuntimeException("Neznámá stránka $key");
    $data = preview_data('page', $key);
    if ($data === null) {
        $row = db_one('SELECT data FROM pages WHERE key = ?', [$key]);
        $data = $row ? json_decode($row['data'], true) : (seed_data()['pages'][$key] ?? []);
    }
    return $cache[$key] = fill_page_defaults($schema, $data);
}

function setting(string $key): array
{
    static $cache = [];
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $schema = settings_schema($key) ?? throw new RuntimeException("Neznámé nastavení $key");
    $data = preview_data('settings', $key);
    if ($data === null) {
        $row = db_ready() ? db_one('SELECT data FROM settings WHERE key = ?', [$key]) : null;
        $data = $row ? json_decode($row['data'], true) : (seed_data()['settings'][$key] ?? []);
    }
    return $cache[$key] = fill_defaults($schema['fields'], $data);
}

function item_from_row(array $row): array
{
    $schema = collection_schema($row['collection']);
    return [
        'id' => (int) $row['id'],
        'slug' => (string) $row['slug'],
        'sort' => (int) $row['sort'],
        'visible' => (bool) $row['visible'],
        'data' => fill_defaults($schema['fields'], json_decode($row['data'], true)),
        'updated_at' => $row['updated_at'],
    ];
}

/** Položky sbírky seřazené podle pořadí. */
function items(string $collection, bool $onlyVisible = true): array
{
    $rows = db_all('SELECT * FROM items WHERE collection = ? ' . ($onlyVisible ? 'AND visible = 1 ' : '') . 'ORDER BY sort, id', [$collection]);
    $items = array_map('item_from_row', $rows);
    // náhled jedné položky s neuloženými změnami
    if (isset($_GET['nahled']) && session_status() === PHP_SESSION_ACTIVE && ($p = $_SESSION['preview'] ?? null) && $p['type'] === 'item' && current_user()) {
        [$pc, $pid] = explode(':', (string) $p['key'], 2) + [1 => ''];
        if ($pc === $collection) {
            $found = false;
            foreach ($items as &$it) {
                if ((string) $it['id'] === $pid) {
                    $it['data'] = fill_defaults(collection_schema($collection)['fields'], $p['data']);
                    $it['slug'] = $p['slug'] ?? $it['slug'];
                    $found = true;
                }
            }
            unset($it);
            if (!$found && $pid === 'new') {
                $items[] = ['id' => 0, 'slug' => $p['slug'] ?? 'nahled', 'sort' => PHP_INT_MAX, 'visible' => true,
                    'data' => fill_defaults(collection_schema($collection)['fields'], $p['data']), 'updated_at' => now()];
            }
        }
    }
    return $items;
}

function item(int $id): ?array
{
    $row = db_one('SELECT * FROM items WHERE id = ?', [$id]);
    return $row ? item_from_row($row) : null;
}

/** Mapa id → položka (pro reference). */
function items_by_id(string $collection): array
{
    static $cache = [];
    if (!isset($cache[$collection])) {
        $cache[$collection] = [];
        foreach (items($collection, false) as $it) {
            $cache[$collection][$it['id']] = $it;
        }
    }
    return $cache[$collection];
}

/** CTA texty stránky s doplněním výchozích z Nastavení. */
function cta_for(array $pageCta): array
{
    $def = setting('cta');
    $out = $def;
    foreach (['label', 'title', 'button'] as $k) {
        if (trim((string) ($pageCta[$k] ?? '')) !== '') {
            $out[$k] = $pageCta[$k];
        }
    }
    return $out;
}

/* ---------------------------------------------------------------- normalizace a validace */

/**
 * Převede data z formuláře na hodnoty podle schématu a ověří je.
 * Vrací [data, errors] – errors je mapa cesta-k-poli → hláška.
 */
function normalize_fields(array $fields, mixed $input, string $path, array &$errors, array $old = []): array
{
    $input = is_array($input) ? $input : [];
    $out = [];
    foreach ($fields as $name => $f) {
        $p = $path === '' ? $name : "$path.$name";
        $raw = $input[$name] ?? null;
        $v = normalize_value($f, $raw, $p, $errors, $old[$name] ?? null);
        $out[$name] = $v;
    }
    return $out;
}

function normalize_value(array $f, mixed $raw, string $p, array &$errors, mixed $old): mixed
{
    $req = !empty($f['required']);
    switch ($f['type']) {
        case 'text':
        case 'tel':
        case 'select':
            $v = trim(str_replace(["\r", "\n"], ' ', (string) (is_scalar($raw) ? $raw : '')));
            if ($f['type'] === 'select' && !array_key_exists($v, $f['options'] ?? [])) {
                $v = (string) array_key_first($f['options'] ?? ['' => '']);
            }
            break;
        case 'textarea':
            $v = trim(str_replace("\r\n", "\n", (string) (is_scalar($raw) ? $raw : '')));
            break;
        case 'password':
            $v = (string) (is_scalar($raw) ? $raw : '');
            if ($v === '') {
                $v = (string) ($old ?? ''); // prázdné = beze změny
            }
            return $v;
        case 'richtext':
            $v = richtext_sanitize((string) (is_scalar($raw) ? $raw : ''));
            if ($req && trim(strip_tags($v)) === '') {
                $errors[$p] = 'Vyplňte prosím toto pole.';
            }
            return $v;
        case 'url':
            $v = trim((string) (is_scalar($raw) ? $raw : ''));
            if ($v !== '' && !has_placeholder($v) && !preg_match('~^(https?://[^\s]+|/[^\s]*|#[\w-]*|mailto:[^\s]+|tel:[+\d\s]+)$~i', $v)) {
                $errors[$p] = 'Zadejte odkaz začínající https://, nebo cestu na webu začínající lomítkem (např. /kontakt).';
            }
            break;
        case 'email':
            $v = trim((string) (is_scalar($raw) ? $raw : ''));
            if ($v !== '' && !has_placeholder($v) && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                $errors[$p] = 'Zadejte platný e-mail.';
            }
            break;
        case 'number':
            $s = trim(str_replace(',', '.', (string) (is_scalar($raw) ? $raw : '')));
            if ($s === '') {
                $v = null;
            } elseif (!is_numeric($s)) {
                $errors[$p] = 'Zadejte číslo.';
                $v = null;
            } else {
                $v = $s + 0;
                if (isset($f['min']) && $v < $f['min'] || isset($f['max']) && $v > $f['max']) {
                    $errors[$p] = 'Číslo musí být mezi ' . $f['min'] . ' a ' . $f['max'] . '.';
                }
            }
            if ($req && $v === null && !isset($errors[$p])) {
                $errors[$p] = 'Vyplňte prosím toto pole.';
            }
            return $v;
        case 'toggle':
            return !empty($raw) && $raw !== '0';
        case 'tags':
            $list = is_array($raw) ? $raw : preg_split('/\R/', (string) $raw);
            $v = array_values(array_filter(array_map(static fn($t) => trim((string) $t), $list), static fn($t) => $t !== ''));
            if ($req && !$v) {
                $errors[$p] = 'Přidejte aspoň jednu položku.';
            }
            return $v;
        case 'image':
            $raw = is_array($raw) ? $raw : [];
            $mid = isset($raw['media']) && ctype_digit((string) $raw['media']) ? (int) $raw['media'] : null;
            $alt = trim((string) ($raw['alt'] ?? ''));
            if ($mid !== null) {
                $m = media_get($mid);
                if (!$m) {
                    $mid = null;
                } elseif ($m['mime'] === 'image/svg+xml' && empty($f['svg'])) {
                    $errors[$p] = 'SVG jde použít jen pro logo. Vyberte JPG, PNG nebo WebP.';
                } elseif ($alt === '') {
                    $errors[$p] = 'Doplňte popis obrázku (alt). Popisuje obrázek nevidomým a vyhledávačům.';
                }
            }
            if ($req && $mid === null) {
                $errors[$p] = 'Vyberte obrázek.';
            }
            return ['media' => $mid, 'alt' => $alt];
        case 'reference':
            $id = ctype_digit((string) $raw) ? (int) $raw : null;
            if ($id !== null && !db_val('SELECT 1 FROM items WHERE id = ? AND collection = ?', [$id, $f['collection']])) {
                $id = null;
            }
            if ($req && $id === null) {
                $errors[$p] = 'Vyberte položku.';
            }
            return $id;
        case 'repeater':
            $rows = is_array($raw) ? array_values($raw) : [];
            $oldRows = is_array($old) ? array_values($old) : [];
            $v = [];
            foreach ($rows as $i => $row) {
                $v[] = normalize_fields($f['fields'], $row, "$p.$i", $errors, $oldRows[$i] ?? []);
            }
            $n = count($v);
            if (isset($f['min'], $f['max']) && $f['min'] === $f['max'] && $n !== $f['min']) {
                $errors[$p] = "Musí tu být přesně {$f['min']} položky.";
            } elseif (isset($f['min']) && $n < $f['min']) {
                $errors[$p] = "Přidejte aspoň {$f['min']} položky.";
            } elseif (isset($f['max']) && $n > $f['max']) {
                $errors[$p] = "Položek může být nejvýše {$f['max']}.";
            }
            return $v;
        default:
            $v = is_scalar($raw) ? (string) $raw : '';
    }
    if (isset($f['maxLength']) && mb_strlen($v) > $f['maxLength']) {
        $errors[$p] = "Text je moc dlouhý (max. {$f['maxLength']} znaků, teď " . mb_strlen($v) . ').';
    }
    if ($req && $v === '' && !isset($errors[$p])) {
        $errors[$p] = 'Vyplňte prosím toto pole.';
    }
    return $v;
}

function normalize_page(array $schema, array $input, array &$errors, array $old = []): array
{
    $out = [];
    foreach ($schema['groups'] as $g => $group) {
        $out[$g] = normalize_fields($group['fields'], $input[$g] ?? [], $g, $errors, $old[$g] ?? []);
    }
    return $out;
}

/* ---------------------------------------------------------------- ukládání a historie */

const REVISIONS_KEEP = 10;

function revision_add(string $type, string $key, array $data): void
{
    db_exec('INSERT INTO revisions (entity_type, entity_key, data, created_at, user_id) VALUES (?, ?, ?, ?, ?)',
        [$type, $key, json_encode($data, JSON_UNESCAPED_UNICODE), now(), current_user()['id'] ?? null]);
    db_exec('DELETE FROM revisions WHERE entity_type = ? AND entity_key = ? AND id NOT IN (
        SELECT id FROM revisions WHERE entity_type = ? AND entity_key = ? ORDER BY id DESC LIMIT ' . REVISIONS_KEEP . ')',
        [$type, $key, $type, $key]);
}

function revisions_for(string $type, string $key): array
{
    return db_all('SELECT r.id, r.created_at, u.name, u.email FROM revisions r LEFT JOIN users u ON u.id = r.user_id
        WHERE r.entity_type = ? AND r.entity_key = ? ORDER BY r.id DESC', [$type, $key]);
}

function page_save(string $key, array $data): void
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    db_exec('INSERT INTO pages (key, data, updated_at, updated_by) VALUES (?, ?, ?, ?)
        ON CONFLICT(key) DO UPDATE SET data = excluded.data, updated_at = excluded.updated_at, updated_by = excluded.updated_by',
        [$key, $json, now(), current_user()['id'] ?? null]);
    revision_add('page', $key, $data);
}

function setting_save(string $key, array $data): void
{
    db_exec('INSERT INTO settings (key, data, updated_at) VALUES (?, ?, ?)
        ON CONFLICT(key) DO UPDATE SET data = excluded.data, updated_at = excluded.updated_at',
        [$key, json_encode($data, JSON_UNESCAPED_UNICODE), now()]);
    revision_add('settings', $key, $data);
}

/** Jedinečný slug v rámci sbírky. */
function unique_slug(string $collection, string $slug, ?int $exceptId): string
{
    $base = $slug !== '' ? $slug : 'polozka';
    $slug = $base;
    $i = 2;
    while (db_val('SELECT 1 FROM items WHERE collection = ? AND slug = ? AND id <> ?', [$collection, $slug, $exceptId ?? 0])) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

function item_save(string $collection, ?int $id, array $data, ?string $slug, bool $visible): int
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($id === null) {
        $sort = (int) db_val('SELECT COALESCE(MAX(sort), 0) + 1 FROM items WHERE collection = ?', [$collection]);
        db_exec('INSERT INTO items (collection, slug, data, sort, visible, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$collection, $slug, $json, $sort, (int) $visible, now(), now()]);
        $id = (int) db()->lastInsertId();
    } else {
        db_exec('UPDATE items SET slug = ?, data = ?, visible = ?, updated_at = ? WHERE id = ? AND collection = ?',
            [$slug, $json, (int) $visible, now(), $id, $collection]);
    }
    revision_add('item', (string) $id, ['data' => $data, 'slug' => $slug, 'visible' => $visible]);
    return $id;
}

/* ---------------------------------------------------------------- seed do DB */

/** Naplní prázdné části DB výchozím obsahem. Existující obsah nepřepisuje. */
function seed_database(): array
{
    $seed = seed_data();
    $done = [];
    foreach ($seed['pages'] as $key => $data) {
        if (!db_val('SELECT 1 FROM pages WHERE key = ?', [$key])) {
            db_exec('INSERT INTO pages (key, data, updated_at) VALUES (?, ?, ?)', [$key, json_encode($data, JSON_UNESCAPED_UNICODE), now()]);
            $done[] = "stránka $key";
        }
    }
    foreach ($seed['settings'] as $key => $data) {
        if (!db_val('SELECT 1 FROM settings WHERE key = ?', [$key])) {
            if ($key === 'mail' && ($data['inquiry_to'] ?? '') === '') {
                $data['inquiry_to'] = (string) config('inquiry_to');
            }
            db_exec('INSERT INTO settings (key, data, updated_at) VALUES (?, ?, ?)', [$key, json_encode($data, JSON_UNESCAPED_UNICODE), now()]);
            $done[] = "nastavení $key";
        }
    }
    // kategorie musí být první kvůli referencím
    $order = ['categories', 'services', 'works', 'machines', 'people'];
    foreach ($order as $c) {
        if (db_val('SELECT 1 FROM items WHERE collection = ?', [$c])) {
            continue;
        }
        foreach ($seed['collections'][$c] ?? [] as $i => $row) {
            $data = $row['data'];
            foreach ($data as $k => $v) {
                if (is_string($v) && str_starts_with($v, 'cat:')) {
                    $data[$k] = (int) db_val("SELECT id FROM items WHERE collection = 'categories' AND slug = ?", [substr($v, 4)]) ?: null;
                }
            }
            db_exec('INSERT INTO items (collection, slug, data, sort, visible, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?)',
                [$c, $row['slug'] ?? null, json_encode($data, JSON_UNESCAPED_UNICODE), $i + 1, now(), now()]);
        }
        $done[] = "sbírka $c";
    }
    return $done;
}

/* ---------------------------------------------------------------- „K doplnění“ */

/**
 * Projde všechen obsah a vrátí pole s textem v [hranatých závorkách].
 * Každá položka: label (kde), field (popisek pole), value, url (odkaz do adminu na pole).
 */
function placeholder_report(): array
{
    $out = [];
    $walk = static function (array $fields, array $data, string $idPrefix, string $where, string $url, array $labels) use (&$walk, &$out): void {
        foreach ($fields as $name => $f) {
            $v = $data[$name] ?? null;
            $id = $idPrefix . '-' . $name;
            $label = array_merge($labels, [$f['label']]);
            if ($f['type'] === 'repeater' && is_array($v)) {
                foreach ($v as $i => $row) {
                    $walk($f['fields'], is_array($row) ? $row : [], "$id-$i", $where, $url, array_merge($label, [(string) ($i + 1)]));
                }
                continue;
            }
            $vals = match ($f['type']) {
                'tags' => is_array($v) ? $v : [],
                'image' => is_array($v) && empty($v['media']) ? [$v['alt'] ?? ''] : [],
                'richtext' => [strip_tags((string) $v)],
                default => is_scalar($v) ? [(string) $v] : [],
            };
            foreach ($vals as $s) {
                if (has_placeholder($s)) {
                    $out[] = ['where' => $where, 'field' => implode(' › ', $label), 'value' => $s, 'url' => $url . '#f' . $id];
                    break;
                }
            }
        }
    };
    foreach (schema()['pages'] as $key => $ps) {
        $data = page($key);
        foreach ($ps['groups'] as $g => $group) {
            $walk($group['fields'], $data[$g], '-' . $g, 'Stránka ' . $ps['label'], "/admin/stranky/$key", [$group['label']]);
        }
    }
    foreach (schema()['collections'] as $c => $cs) {
        foreach (items($c, false) as $it) {
            $title = (string) ($it['data'][$cs['title']] ?? '');
            $walk($cs['fields'], $it['data'], '', $cs['label'] . ' › ' . ($title !== '' ? $title : '#' . $it['id']), "/admin/{$cs['path']}/{$it['id']}", []);
        }
    }
    foreach (schema()['settings'] as $key => $ss) {
        $walk($ss['fields'], setting($key), '', 'Nastavení › ' . $ss['label'], "/admin/nastaveni/$key", []);
    }
    return $out;
}

/* ---------------------------------------------------------------- použití médií */

/** Kde je obrázek použitý: seznam [label, url]. */
function media_usage(int $mediaId): array
{
    $uses = [];
    $find = static function (mixed $data, array $fields) use (&$find, $mediaId): bool {
        foreach ($fields as $name => $f) {
            $v = $data[$name] ?? null;
            if ($f['type'] === 'image' && is_array($v) && (int) ($v['media'] ?? 0) === $mediaId) {
                return true;
            }
            if ($f['type'] === 'repeater' && is_array($v)) {
                foreach ($v as $row) {
                    if ($find($row, $f['fields'])) {
                        return true;
                    }
                }
            }
        }
        return false;
    };
    foreach (schema()['pages'] as $key => $ps) {
        $row = db_one('SELECT data FROM pages WHERE key = ?', [$key]);
        $data = $row ? json_decode($row['data'], true) : [];
        foreach ($ps['groups'] as $g => $group) {
            if ($find($data[$g] ?? [], $group['fields'])) {
                $uses[] = ['Stránka ' . $ps['label'] . ' › ' . $group['label'], "/admin/stranky/$key"];
            }
        }
    }
    foreach (schema()['collections'] as $c => $cs) {
        foreach (db_all('SELECT id, data FROM items WHERE collection = ?', [$c]) as $row) {
            $data = json_decode($row['data'], true) ?: [];
            if ($find($data, $cs['fields'])) {
                $uses[] = [$cs['label'] . ' › ' . ($data[$cs['title']] ?? '#' . $row['id']), "/admin/{$cs['path']}/{$row['id']}"];
            }
        }
    }
    foreach (schema()['settings'] as $key => $ss) {
        $row = db_one('SELECT data FROM settings WHERE key = ?', [$key]);
        if ($row && $find(json_decode($row['data'], true) ?: [], $ss['fields'])) {
            $uses[] = ['Nastavení › ' . $ss['label'], "/admin/nastaveni/$key"];
        }
    }
    return $uses;
}
