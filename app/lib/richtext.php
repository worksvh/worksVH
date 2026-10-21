<?php
declare(strict_types=1);

/**
 * Sanitizace omezeného HTML z editoru: odstavce, tučně, odkaz, seznam.
 * Vše ostatní se buď rozbalí (zůstane text), nebo zahodí (script, style…).
 */
function richtext_sanitize(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }
    if (!str_contains($html, '<')) {
        // čistý text: odstavce podle prázdných řádků
        $paras = preg_split('/\R{2,}/', $html) ?: [];
        return implode('', array_map(static fn($p) => '<p>' . nl2br(e(trim($p)), false) . '</p>', array_filter($paras, static fn($p) => trim($p) !== '')));
    }
    $doc = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="rt-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors();
    $root = $doc->getElementById('rt-root');
    if (!$root) {
        return '';
    }
    $out = richtext_clean_children($root);
    // inline obsah na nejvyšší úrovni zabalit do odstavců
    $out = preg_replace('~(<br>\s*){3,}~', '<br><br>', $out) ?? $out;
    if (!preg_match('~^\s*<(p|ul|ol)>~', $out)) {
        $out = '<p>' . $out . '</p>';
    }
    $out = preg_replace('~<p>(\s|<br>|&nbsp;)*</p>~u', '', $out) ?? $out;
    return trim($out);
}

function richtext_clean_children(DOMNode $node): string
{
    $out = '';
    foreach ($node->childNodes as $child) {
        $out .= richtext_clean_node($child);
    }
    return $out;
}

function richtext_clean_node(DOMNode $n): string
{
    if ($n instanceof DOMText) {
        return e($n->nodeValue);
    }
    if (!$n instanceof DOMElement) {
        return '';
    }
    $tag = strtolower($n->tagName);
    $inner = static fn() => richtext_clean_children($n);
    switch ($tag) {
        case 'script': case 'style': case 'iframe': case 'object': case 'embed': case 'svg': case 'math': case 'template': case 'noscript': case 'head': case 'title': case 'meta': case 'link':
            return '';
        case 'p': case 'div': case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6': case 'blockquote':
            $c = trim($inner());
            return $c === '' ? '' : "<p>$c</p>";
        case 'br':
            return '<br>';
        case 'strong': case 'b':
            $c = $inner();
            return $c === '' ? '' : "<strong>$c</strong>";
        case 'ul': case 'ol':
            $items = '';
            foreach ($n->childNodes as $li) {
                if ($li instanceof DOMElement && strtolower($li->tagName) === 'li') {
                    $items .= '<li>' . trim(richtext_clean_children($li)) . '</li>';
                } elseif (trim($li->textContent) !== '') {
                    $items .= '<li>' . trim(richtext_clean_node($li)) . '</li>';
                }
            }
            return $items === '' ? '' : "<$tag>$items</$tag>";
        case 'li':
            return '<li>' . $inner() . '</li>';
        case 'a':
            $href = trim($n->getAttribute('href'));
            $c = $inner();
            if ($href === '' || !preg_match('~^(https?://|mailto:|tel:|/|#)~i', $href)) {
                return $c;
            }
            $ext = preg_match('~^https?://~i', $href) === 1;
            return '<a href="' . e($href) . '"' . ($ext ? ' rel="noopener"' : '') . '>' . $c . '</a>';
        default:
            return $inner(); // span, em, font… → jen obsah
    }
}
