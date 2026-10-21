<?php
declare(strict_types=1);

const MEDIA_MAX_BYTES = 10 * 1024 * 1024;
const MEDIA_TYPES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'svg' => 'image/svg+xml'];

function detect_mime(string $path): string
{
    $f = new finfo(FILEINFO_MIME_TYPE);
    return (string) $f->file($path);
}

function upload_error_text(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Soubor je větší, než server povoluje.',
        UPLOAD_ERR_PARTIAL => 'Soubor se nahrál jen částečně.',
        UPLOAD_ERR_NO_FILE => 'Nevybrali jste žádný soubor.',
        default => 'Soubor se nepodařilo nahrát.',
    };
}

/**
 * Nahraje obrázek do knihovny médií. Vrací [id|null, chyba|null].
 */
function media_upload(array $file, string $alt = ''): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return [null, upload_error_text((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE))];
    }
    if ($file['size'] > MEDIA_MAX_BYTES) {
        return [null, 'Obrázek je větší než 10 MB.'];
    }
    $original = basename((string) $file['name']);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!isset(MEDIA_TYPES[$ext])) {
        return [null, 'Povolené jsou jen obrázky JPG, PNG, WebP a SVG.'];
    }
    $mime = detect_mime($file['tmp_name']);
    $expected = MEDIA_TYPES[$ext];
    $svgOk = $expected === 'image/svg+xml' && in_array($mime, ['image/svg+xml', 'text/xml', 'application/xml', 'text/plain', 'text/html'], true);
    if ($mime !== $expected && !$svgOk) {
        return [null, 'Obsah souboru neodpovídá příponě .' . $ext . '.'];
    }
    $base = date('Y') . '-' . bin2hex(random_bytes(8));
    $dir = PUBLIC_PATH . '/media';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $filename = "$base.$ext";
    $dest = "$dir/$filename";
    if ($expected === 'image/svg+xml') {
        $clean = svg_sanitize((string) file_get_contents($file['tmp_name']));
        if ($clean === null) {
            return [null, 'SVG se nepodařilo bezpečně zpracovat.'];
        }
        file_put_contents($dest, $clean);
    } elseif (!move_uploaded_file($file['tmp_name'], $dest)) {
        return [null, 'Soubor se nepodařilo uložit.'];
    }
    @chmod($dest, 0644);
    [$w, $h] = image_dimensions($dest, $expected);
    $variants = $expected === 'image/svg+xml' ? [] : image_make_variants($dest, $expected, $base);
    db_exec('INSERT INTO media (filename, original_name, mime, width, height, alt, variants, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$filename, $original, $expected, $w, $h, trim($alt), json_encode($variants), now()]);
    return [(int) db()->lastInsertId(), null];
}

function media_delete(int $id): void
{
    $m = media_get($id);
    if (!$m) {
        return;
    }
    @unlink(PUBLIC_PATH . '/media/' . $m['filename']);
    foreach ($m['variants'] as $v) {
        @unlink(PUBLIC_PATH . '/media/' . $v[2]);
    }
    db_exec('DELETE FROM media WHERE id = ?', [$id]);
}

/** Bezpečné SVG: bez skriptů, událostí, vnějších odkazů a entit. */
function svg_sanitize(string $svg): ?string
{
    if (preg_match('/<!DOCTYPE|<!ENTITY/i', $svg)) {
        return null;
    }
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $ok = $doc->loadXML($svg, LIBXML_NONET);
    libxml_clear_errors();
    if (!$ok || !$doc->documentElement || strtolower($doc->documentElement->localName) !== 'svg') {
        return null;
    }
    $allowed = ['svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'text', 'tspan', 'defs', 'lineargradient', 'radialgradient', 'stop', 'clippath', 'mask', 'pattern', 'symbol', 'use', 'title', 'desc'];
    $xp = new DOMXPath($doc);
    foreach (iterator_to_array($xp->query('//*')) as $el) {
        if (!in_array(strtolower($el->localName), $allowed, true)) {
            $el->parentNode?->removeChild($el);
            continue;
        }
        foreach (iterator_to_array($el->attributes) as $attr) {
            $n = strtolower($attr->nodeName);
            $v = trim($attr->nodeValue);
            if (str_starts_with($n, 'on')
                || (str_ends_with($n, 'href') && !str_starts_with($v, '#'))
                || preg_match('/javascript:|url\s*\(\s*[\'"]?\s*(?!#)/i', $v)) {
                $el->removeAttributeNode($attr);
            }
        }
    }
    return $doc->saveXML($doc->documentElement);
}
