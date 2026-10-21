<?php
$menu = setting('menu');
$company = setting('company');
$logo = setting('display')['logo'];
$logoMedia = !empty($logo['media']) ? media_get((int) $logo['media']) : null;
$logoAlt = trim((string) $logo['alt']) ?: 'VH Works – Tooling, Engineering, Precision';
$isContact = $pageKey === 'contact';
?>
<header class="site-header wrap">
  <a class="logo" href="/" <?= $path === '/' ? 'aria-current="page"' : '' ?>>
    <?php if ($logoMedia): ?>
      <img src="<?= e(media_url($logoMedia['filename'])) ?>" alt="<?= e($logoAlt) ?>" height="46" <?= $logoMedia['width'] && $logoMedia['height'] ? 'width="' . (int) round(46 * $logoMedia['width'] / $logoMedia['height']) . '"' : '' ?>>
    <?php else: ?>
      <img src="/assets/img/vh-works-logo.svg" alt="<?= e($logoAlt) ?>" width="278" height="46">
    <?php endif ?>
  </a>
  <nav class="main-nav" aria-label="<?= t($menu['aria'] ?: 'Hlavní menu') ?>">
    <?php foreach ($menu['items'] as $mi): $on = nav_active($mi['url'], $path); ?>
      <a href="<?= e($mi['url']) ?>"<?= $on ? ' aria-current="page" class="is-active"' : '' ?>><?= t($mi['label']) ?></a>
    <?php endforeach ?>
    <a class="nav-phone" href="<?= e(tel_href($company['phone'])) ?>"><?= e(phone_short($company['phone'])) ?></a>
    <a class="btn btn-dark btn-sm<?= $isContact ? ' is-active' : '' ?>" href="<?= e($menu['button_url']) ?>"<?= $isContact ? ' aria-current="page"' : '' ?>><?= t($menu['button']) ?></a>
  </nav>
</header>
