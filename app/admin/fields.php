<?php
declare(strict_types=1);

/*
 * Generátor formulářů ze schématu (app/content/schema.php).
 * Cesta k poli = pole klíčů, např. ['hero', 'title'] nebo ['part', 'pins', 0, 'x'].
 *   name  = data[hero][title]
 *   id    = f-hero-title         (na tohle vedou odkazy „K doplnění“)
 *   error = hero.title
 */

function f_name(array $path): string
{
    return 'data[' . implode('][', $path) . ']';
}

function f_id(array $path): string
{
    return 'f-' . implode('-', $path);
}

function f_has_placeholder(array $f, mixed $v): bool
{
    return match ($f['type']) {
        'tags' => is_array($v) && (bool) array_filter($v, 'has_placeholder'),
        'image' => is_array($v) && empty($v['media']) && has_placeholder($v['alt'] ?? ''),
        'richtext' => has_placeholder(strip_tags((string) $v)),
        'repeater', 'toggle', 'number', 'reference', 'password' => false,
        default => has_placeholder($v),
    };
}

function f_badge(bool $on): string
{
    return '<span class="badge-todo"' . ($on ? '' : ' hidden') . ' data-todo-badge>K doplnění</span>';
}

/** Vykreslí sadu polí. */
function render_fields(array $fields, array $data, array $prefix, array $errors): string
{
    $html = '';
    foreach ($fields as $name => $f) {
        $html .= render_field($f, $data[$name] ?? field_default($f), array_merge($prefix, [$name]), $errors);
    }
    return $html;
}

function render_field(array $f, mixed $v, array $path, array $errors): string
{
    $id = f_id($path);
    $name = f_name($path);
    $errKey = implode('.', $path);
    $err = $errors[$errKey] ?? null;
    $req = !empty($f['required']);
    $describedBy = [];
    if (!empty($f['help'])) {
        $describedBy[] = "$id-help";
    }
    if ($err) {
        $describedBy[] = "$id-err";
    }
    $aria = ($describedBy ? ' aria-describedby="' . implode(' ', $describedBy) . '"' : '') . ($err ? ' aria-invalid="true"' : '') . ($req ? ' aria-required="true"' : '');
    $todo = f_has_placeholder($f, $v);
    $label = '<span class="f-label-text">' . e($f['label']) . ($req ? ' <span class="req" aria-hidden="true">*</span>' : '') . '</span>' . f_badge($todo);
    $help = !empty($f['help']) ? '<p class="help" id="' . $id . '-help">' . e($f['help']) . '</p>' : '';
    $errHtml = $err ? '<p class="err" id="' . $id . '-err">' . e($err) . '</p>' : '';
    $max = isset($f['maxLength']) ? ' maxlength="' . (int) $f['maxLength'] . '" data-count' : '';
    $wrapOpen = '<div class="f-field f-' . e($f['type']) . ($err ? ' has-error' : '') . '" data-field>';

    switch ($f['type']) {
        case 'text':
        case 'tel':
        case 'email':
        case 'url':
            $type = $f['type'] === 'tel' ? 'tel' : 'text';
            $mode = match ($f['type']) { 'email' => ' inputmode="email" autocomplete="off"', 'url' => ' inputmode="url"', default => '' };
            return $wrapOpen . '<label for="' . $id . '">' . $label . '</label>'
                . '<input type="' . $type . '" id="' . $id . '" name="' . e($name) . '" value="' . e((string) $v) . '"' . $max . $mode . $aria . ' data-ph-check>'
                . $help . $errHtml . '</div>';

        case 'password':
            return $wrapOpen . '<label for="' . $id . '">' . $label . '</label>'
                . '<input type="password" id="' . $id . '" name="' . e($name) . '" value="" autocomplete="new-password" placeholder="' . ((string) $v !== '' ? '•••••••• (uloženo)' : '') . '"' . $aria . '>'
                . $help . $errHtml . '</div>';

        case 'textarea':
            $rows = max(3, min(12, substr_count((string) $v, "\n") + 2, (int) ceil(mb_strlen((string) $v) / 80) + 1));
            return $wrapOpen . '<label for="' . $id . '">' . $label . '</label>'
                . '<textarea id="' . $id . '" name="' . e($name) . '" rows="' . $rows . '"' . $max . $aria . ' data-ph-check>' . e((string) $v) . '</textarea>'
                . $help . $errHtml . '</div>';

        case 'number':
            $mm = (isset($f['min']) ? ' min="' . $f['min'] . '"' : '') . (isset($f['max']) ? ' max="' . $f['max'] . '"' : '');
            return $wrapOpen . '<label for="' . $id . '">' . $label . '</label>'
                . '<input type="number" step="any" id="' . $id . '" name="' . e($name) . '" value="' . e((string) $v) . '"' . $mm . $aria . ' class="input-short">'
                . $help . $errHtml . '</div>';

        case 'select':
            $opts = '';
            foreach ($f['options'] as $ov => $ol) {
                $opts .= '<option value="' . e((string) $ov) . '"' . ((string) $ov === (string) $v ? ' selected' : '') . '>' . e($ol) . '</option>';
            }
            return $wrapOpen . '<label for="' . $id . '">' . $label . '</label><select id="' . $id . '" name="' . e($name) . '"' . $aria . '>' . $opts . '</select>' . $help . $errHtml . '</div>';

        case 'reference':
            $cs = collection_schema($f['collection']);
            $opts = '<option value="">– vyberte –</option>';
            foreach (items($f['collection'], false) as $it) {
                $opts .= '<option value="' . $it['id'] . '"' . ((int) $v === $it['id'] ? ' selected' : '') . '>' . e((string) $it['data'][$cs['title']]) . '</option>';
            }
            return $wrapOpen . '<label for="' . $id . '">' . $label . '</label><select id="' . $id . '" name="' . e($name) . '"' . $aria . '>' . $opts . '</select>'
                . $help . '<p class="help"><a href="/admin/' . e($cs['path']) . '">Upravit ' . e(mb_strtolower($cs['label'])) . ' →</a></p>' . $errHtml . '</div>';

        case 'toggle':
            return $wrapOpen . '<input type="hidden" name="' . e($name) . '" value="0">'
                . '<label class="switch" for="' . $id . '"><input type="checkbox" id="' . $id . '" name="' . e($name) . '" value="1"' . ($v ? ' checked' : '') . $aria . '>'
                . '<span class="switch-ui" aria-hidden="true"></span>' . $label . '</label>' . $help . $errHtml . '</div>';

        case 'richtext':
            return $wrapOpen . '<span class="f-label" id="' . $id . '-label">' . $label . '</span>'
                . '<div class="rte" data-rte>'
                . '<div class="rte-bar" role="toolbar" aria-label="Formátování textu">'
                . '<button type="button" data-cmd="bold" title="Tučně (Ctrl+B)" aria-label="Tučně"><b>B</b></button>'
                . '<button type="button" data-cmd="link" title="Odkaz" aria-label="Vložit odkaz">Odkaz</button>'
                . '<button type="button" data-cmd="unlink" title="Zrušit odkaz" aria-label="Zrušit odkaz">Zrušit odkaz</button>'
                . '<button type="button" data-cmd="insertUnorderedList" title="Seznam s odrážkami" aria-label="Seznam s odrážkami">• Seznam</button>'
                . '<button type="button" data-cmd="insertOrderedList" title="Číslovaný seznam" aria-label="Číslovaný seznam">1. Seznam</button>'
                . '<button type="button" data-cmd="formatBlock" title="Obyčejný odstavec" aria-label="Obyčejný odstavec">Odstavec</button>'
                . '</div>'
                . '<div class="rte-area" id="' . $id . '" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="' . $id . '-label"' . $aria . ' data-ph-check>' . (string) $v . '</div>'
                . '<textarea name="' . e($name) . '" hidden data-rte-value>' . e((string) $v) . '</textarea>'
                . '</div>' . $help . $errHtml . '</div>';

        case 'tags':
            $rows = '';
            foreach ((array) $v as $i => $tag) {
                $rows .= tag_row($name, (string) $tag, $id . '-' . $i);
            }
            return $wrapOpen . '<span class="f-label" id="' . $id . '-label">' . $label . '</span>'
                . '<div class="tags-edit" role="group" aria-labelledby="' . $id . '-label" data-tags id="' . $id . '">' . $rows . '</div>'
                . '<template data-tag-template>' . tag_row($name, '', '') . '</template>'
                . '<button type="button" class="btn-small" data-tag-add>+ Přidat položku</button>'
                . $help . $errHtml . '</div>';

        case 'image':
            return render_image_field($f, is_array($v) ? $v : field_default($f), $path, $id, $name, $label, $help, $errHtml, $aria, $wrapOpen);

        case 'repeater':
            return render_repeater($f, is_array($v) ? array_values($v) : [], $path, $errors, $id, $label, $help, $errHtml, $wrapOpen);
    }
    return '';
}

function tag_row(string $name, string $val, string $id): string
{
    return '<div class="tag-row" data-tag-row>'
        . '<input type="text" name="' . e($name) . '[]" value="' . e($val) . '" aria-label="Položka" data-ph-check>'
        . '<button type="button" class="icon-btn" data-move="-1" aria-label="Posunout nahoru">↑</button>'
        . '<button type="button" class="icon-btn" data-move="1" aria-label="Posunout dolů">↓</button>'
        . '<button type="button" class="icon-btn icon-danger" data-tag-remove aria-label="Odebrat položku">×</button>'
        . '</div>';
}

function render_image_field(array $f, array $v, array $path, string $id, string $name, string $label, string $help, string $errHtml, string $aria, string $wrapOpen): string
{
    $m = !empty($v['media']) ? media_get((int) $v['media']) : null;
    $thumb = $m ? media_url($m['variants'] ? $m['variants'][0][2] : $m['filename']) : '';
    $svg = !empty($f['svg']) ? '1' : '0';
    return $wrapOpen . '<span class="f-label" id="' . $id . '-label">' . $label . '</span>'
        . '<div class="img-field" id="' . $id . '" data-image-field data-svg="' . $svg . '">'
        . '<div class="img-preview" data-img-preview>' . ($m ? '<img src="' . e($thumb) . '" alt="">' : '<span>Bez obrázku</span>') . '</div>'
        . '<div class="img-controls">'
        . '<input type="hidden" name="' . e($name) . '[media]" value="' . e((string) ($v['media'] ?? '')) . '" data-img-id>'
        . '<div class="img-buttons">'
        . '<button type="button" class="btn-small" data-img-pick aria-describedby="' . $id . '-label">Vybrat nebo nahrát obrázek</button>'
        . '<button type="button" class="btn-small btn-ghost" data-img-clear' . ($m ? '' : ' hidden') . '>Odebrat obrázek</button>'
        . '</div>'
        . ($m ? '<p class="help" data-img-name>' . e($m['original_name']) . ($m['width'] ? ' · ' . (int) $m['width'] . ' × ' . (int) $m['height'] . ' px' : '') . '</p>' : '<p class="help" data-img-name hidden></p>')
        . '<label for="' . $id . '-alt" class="sub-label">Popis obrázku (alt)' . ($m ? ' <span class="req" aria-hidden="true">*</span>' : '') . '</label>'
        . '<input type="text" id="' . $id . '-alt" name="' . e($name) . '[alt]" value="' . e((string) ($v['alt'] ?? '')) . '"' . $aria . ' data-ph-check data-img-alt>'
        . '<p class="help">Co je na obrázku – čte ho nevidomým čtečka a vidí ho Google. Dokud obrázek chybí, zobrazí se tento text v šedém místě na webu.</p>'
        . '</div></div>' . $help . $errHtml . '</div>';
}

function render_repeater(array $f, array $rows, array $path, array $errors, string $id, string $label, string $help, string $errHtml, string $wrapOpen): string
{
    $min = $f['min'] ?? 0;
    $max = $f['max'] ?? 999;
    $items = '';
    foreach ($rows as $i => $row) {
        $items .= repeater_item($f, is_array($row) ? $row : [], array_merge($path, [$i]), $errors, $i + 1);
    }
    $tpl = repeater_item($f, fill_defaults($f['fields'], []), array_merge($path, ['__i__']), [], 0);
    $fixed = $min === $max;
    return $wrapOpen . '<span class="f-label" id="' . $id . '-label">' . $label
        . ' <span class="rep-count">(' . ($fixed ? 'přesně ' . $min : ($min ? "{$min}–{$max}" : "max. $max")) . ')</span></span>'
        . $help . $errHtml
        . '<div class="repeater" id="' . $id . '" data-repeater data-min="' . $min . '" data-max="' . $max . '" role="list" aria-labelledby="' . $id . '-label">' . $items . '</div>'
        . '<template data-rep-template>' . $tpl . '</template>'
        . ($fixed ? '' : '<button type="button" class="btn-small" data-rep-add>+ Přidat položku</button>')
        . '<p class="help" data-rep-limit hidden></p>'
        . '</div>';
}

function repeater_item(array $f, array $row, array $path, array $errors, int $n): string
{
    $title = isset($f['item_label']) ? (string) ($row[$f['item_label']] ?? '') : '';
    $fixed = ($f['min'] ?? -1) === ($f['max'] ?? -2);
    return '<fieldset class="rep-item" role="listitem" data-rep-item draggable="false">'
        . '<legend class="rep-head">'
        . '<span class="rep-drag" data-drag-handle title="Přetáhněte pro změnu pořadí" aria-hidden="true">⠿</span>'
        . '<span class="rep-num" data-rep-num>' . ($n ?: '') . '</span>'
        . '<span class="rep-title" data-rep-title>' . e($title) . '</span>'
        . '</legend>'
        . '<div class="rep-actions">'
        . '<button type="button" class="icon-btn" data-move="-1" aria-label="Posunout nahoru">↑</button>'
        . '<button type="button" class="icon-btn" data-move="1" aria-label="Posunout dolů">↓</button>'
        . ($fixed ? '' : '<button type="button" class="icon-btn icon-danger" data-rep-remove aria-label="Smazat položku">×</button>')
        . '</div>'
        . '<div class="rep-body">' . render_fields($f['fields'], $row, $path, $errors) . '</div>'
        . '</fieldset>';
}
