<?php
/** @var string $class */
$c = setting('company');
$hasUrl = trim((string) $c['map_url']) !== '';
?>
<div class="map <?= e($class ?? '') ?>">
  <?php if ($hasUrl): ?><a class="map-link" href="<?= e($c['map_url']) ?>" target="_blank" rel="noopener"><?php endif ?>
    <?= img($c['map_image'], ['class' => 'map-img', 'sizes' => '(min-width: 900px) 50vw, 100vw']) ?>
    <?php if ($hasUrl && ph($c['map_link']) !== ''): ?><span class="map-cta mono"><?= t($c['map_link']) ?> ↗</span><?php endif ?>
  <?php if ($hasUrl): ?></a><?php endif ?>
  <?php if (ph($c['map_credit']) !== ''): ?><span class="map-credit"><?= t($c['map_credit']) ?></span><?php endif ?>
</div>
