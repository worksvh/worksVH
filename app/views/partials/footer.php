<?php
$c = setting('company');
$menu = setting('menu');
$left = implode(' · ', array_filter([t($c['name']), trim(t($c['street']) . ', ' . t(trim($c['zip'] . ' ' . $c['city'])), ', ')]));
$ico = ph($c['ico']);
$right = array_filter([t($c['email']), $ico !== '' ? t($c['ico_label']) . ' ' . e($ico) : '']);
?>
<footer class="site-footer wrap">
  <span><?= $left ?></span>
  <span class="footer-right">
    <?php if ($right): ?><span class="mono"><?= implode(' · ', $right) ?></span><?php endif ?>
    <a href="/ochrana-osobnich-udaju"><?= t($menu['privacy_link']) ?></a>
  </span>
</footer>
