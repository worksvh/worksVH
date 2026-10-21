<?php
/** @var string $content @var string $title @var string $nav */
$u = current_user();
$newCount = (int) db_val("SELECT COUNT(*) FROM inquiries WHERE status = 'new'");
$link = static function (string $href, string $label, bool $on, string $extra = ''): string {
    return '<a href="' . e($href) . '"' . ($on ? ' aria-current="page" class="is-active"' : '') . '>' . e($label) . $extra . '</a>';
};
?><!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> – Administrace VH Works</title>
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/admin.css?v=<?= e((string) @filemtime(PUBLIC_PATH . '/assets/css/admin.css')) ?>">
<script src="/assets/js/admin.js?v=<?= e((string) @filemtime(PUBLIC_PATH . '/assets/js/admin.js')) ?>" defer></script>
</head>
<body class="admin" data-csrf="<?= e(csrf_token()) ?>">
<a class="skip" href="#a-main">Přeskočit na obsah</a>
<div class="a-shell">
  <aside class="a-side">
    <div class="a-brand"><a href="/admin"><img src="/assets/img/vh-works-logo.svg" alt="VH Works" width="169" height="28"></a><span class="a-brand-sub">Administrace</span></div>
    <nav class="a-nav" aria-label="Administrace">
      <?= $link('/admin', 'Přehled', $nav === 'dashboard') ?>
      <details class="a-nav-group" <?= str_starts_with($nav, 'page:') ? 'open' : '' ?>>
        <summary>Stránky</summary>
        <?php foreach (schema()['pages'] as $pk => $ps): ?>
          <?= $link('/admin/stranky/' . $pk, $ps['label'], $nav === "page:$pk") ?>
        <?php endforeach ?>
      </details>
      <?php foreach (['services', 'works', 'categories', 'machines', 'people'] as $ck): $cs = collection_schema($ck); ?>
        <?= $link('/admin/' . $cs['path'], $cs['label'], $nav === "col:$ck") ?>
      <?php endforeach ?>
      <?= $link('/admin/poptavky', 'Poptávky', $nav === 'inquiries', $newCount ? ' <span class="pill">' . $newCount . '<span class="sr"> nových</span></span>' : '') ?>
      <?= $link('/admin/media', 'Média', $nav === 'media') ?>
      <?= $link('/admin/nastaveni', 'Nastavení', $nav === 'settings') ?>
    </nav>
    <div class="a-side-foot">
      <a href="/" target="_blank" rel="noopener">Otevřít web ↗</a>
      <span class="a-user"><?= e($u['email'] ?? '') ?></span>
      <form method="post" action="/admin/odhlasit"><?= csrf_field() ?><button type="submit" class="linkish">Odhlásit</button></form>
    </div>
  </aside>
  <main class="a-main" id="a-main" tabindex="-1">
    <?php foreach (take_flash() as $f): ?>
      <div class="toast toast-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>" data-toast><?= e($f['msg']) ?></div>
    <?php endforeach ?>
    <?= $content ?>
  </main>
</div>
<dialog class="picker" id="media-picker" aria-labelledby="picker-title">
  <div class="picker-head">
    <h2 id="picker-title">Vyberte obrázek</h2>
    <button type="button" class="icon-btn" data-picker-close aria-label="Zavřít">×</button>
  </div>
  <div class="picker-upload">
    <label for="picker-file" class="btn-small">Nahrát nový obrázek<input type="file" id="picker-file" accept=".jpg,.jpeg,.png,.webp,.svg" class="sr" data-picker-file></label>
    <span class="help">JPG, PNG nebo WebP do 10 MB<span data-picker-svg-note hidden>, u loga i SVG</span>.</span>
    <input type="search" placeholder="Hledat…" aria-label="Hledat v obrázcích" data-picker-search>
  </div>
  <p class="err" data-picker-error hidden></p>
  <div class="picker-grid" data-picker-grid role="list"></div>
</dialog>
</body>
</html>
