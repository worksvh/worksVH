<?php
/** @var string $content @var string $pageKey @var string $path @var array $seo */
$company = setting('company');
$seoDef = setting('seo');
$display = setting('display');
$title = trim((string) ($seo['title'] ?? '')) ?: $company['name'];
$fullTitle = $title . ($pageKey === 'home' && str_contains($title, $company['name']) ? '' : (string) $seoDef['title_suffix']);
$desc = trim((string) ($seo['description'] ?? '')) ?: (string) $seoDef['description'];
$ogImage = image_abs_url($seo['image'] ?? []) ?? image_abs_url($seoDef['image']);
$canonical = config('app_url') . ($path === '/' ? '/' : $path);
$noindex = !empty($noindex) || in_array($pageKey, ['thanks'], true) || isset($_GET['nahled']);

$ld = [
    '@context' => 'https://schema.org',
    '@type' => 'LocalBusiness',
    'name' => $company['name'],
    'url' => config('app_url') . '/',
    'telephone' => preg_replace('/\s+/', '', (string) $company['phone']),
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $company['street'],
        'postalCode' => $company['zip'],
        'addressLocality' => $company['city'],
        'addressCountry' => 'CZ',
    ],
];
if ($company['email'] !== '' && !has_placeholder($company['email'])) {
    $ld['email'] = $company['email'];
}
if ($company['hours_schema'] !== '' && !has_placeholder($company['hours_schema'])) {
    $ld['openingHours'] = $company['hours_schema'];
}
if ($company['ico'] !== '' && !has_placeholder($company['ico'])) {
    $ld['taxID'] = $company['ico'];
}
if ($company['dic'] !== '' && !has_placeholder($company['dic'])) {
    $ld['vatID'] = $company['dic'];
}
if ($company['operator'] !== '' && !has_placeholder($company['operator'])) {
    $ld['legalName'] = $company['operator'];
}
?><!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= t($fullTitle) ?></title>
<?php if ($desc !== ''): ?><meta name="description" content="<?= t($desc) ?>">
<?php endif ?>
<?php if ($noindex): ?><meta name="robots" content="noindex">
<?php else: ?><link rel="canonical" href="<?= e($canonical) ?>">
<?php endif ?>
<meta property="og:type" content="website">
<meta property="og:locale" content="cs_CZ">
<meta property="og:site_name" content="<?= e($company['name']) ?>">
<meta property="og:title" content="<?= t($title) ?>">
<?php if ($desc !== ''): ?><meta property="og:description" content="<?= t($desc) ?>">
<?php endif ?>
<meta property="og:url" content="<?= e($canonical) ?>">
<?php if ($ogImage): ?><meta property="og:image" content="<?= e($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php endif ?>
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link rel="preload" href="/assets/fonts/exo2-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/exo2-latin-ext.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/montserrat-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/montserrat-latin-ext.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/css/site.css?v=<?= e((string) @filemtime(PUBLIC_PATH . '/assets/css/site.css')) ?>">
<noscript><link rel="stylesheet" href="/assets/css/nojs.css"></noscript>
<?php if (($css = page_style()) !== ''): ?><style nonce="<?= e(csp_nonce()) ?>"><?= $css ?></style>
<?php endif ?>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="/assets/js/site.js?v=<?= e((string) @filemtime(PUBLIC_PATH . '/assets/js/site.js')) ?>" defer></script>
</head>
<body class="page-<?= e($pageKey) ?>">
<a class="skip" href="#obsah"><?= t($display['skip_link'] ?: 'Přeskočit na obsah') ?></a>
<?php if (isset($_GET['nahled']) && current_user()): ?><div class="preview-bar" role="status">Náhled neuloženého obsahu. Změny se uloží až v administraci tlačítkem Uložit.</div><?php endif ?>
<?php partial('header', ['path' => $path, 'pageKey' => $pageKey]) ?>
<main id="obsah" tabindex="-1">
<?= $content ?>
</main>
<?php partial('footer') ?>
<?php if (!empty($display['cookie_bar']) && ph($display['cookie_text']) !== ''): ?>
<div class="cookie-bar" role="region" aria-label="Cookies" data-cookie-bar hidden>
  <p><?= t($display['cookie_text']) ?><?php if (ph($display['cookie_link']) !== ''): ?> <a href="/ochrana-osobnich-udaju"><?= t($display['cookie_link']) ?></a><?php endif ?></p>
  <button type="button" class="btn btn-dark" data-cookie-ok><?= t($display['cookie_button'] ?: 'OK') ?></button>
</div>
<?php endif ?>
</body>
</html>
