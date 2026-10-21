<?php
$c = setting('company');
$menu = setting('menu');
$left = implode(' · ', array_filter([t($c['name']), trim(t($c['street']) . ', ' . t(trim($c['zip'] . ' ' . $c['city'])), ', ')]));
$email = ph($c['email']);

// řádek s provozovatelem: jméno, sídlo, IČO, DIČ, zápis v rejstříku
$operator = trim(implode(', ', array_filter([ph($c['operator']), ph($c['operator_address'])])));
$legal = array_filter([
    $operator !== '' ? trim(t($c['operator_label']) . ($c['operator_label'] !== '' ? ': ' : '') . e($operator)) : '',
    ph($c['ico']) !== '' ? t($c['ico_label']) . ' ' . t($c['ico']) : '',
    ph($c['dic']) !== '' ? t($c['dic_label']) . ' ' . t($c['dic']) : '',
]);
$note = ph($c['register_note']);
$credit = ph($menu['credit_name']);
?>
<footer class="site-footer wrap">
  <div class="footer-row">
    <span><?= $left ?></span>
    <span class="footer-right">
      <?php if ($email !== ''): ?><?= has_placeholder($c['email']) ? '<span class="mono">' . t($c['email']) . '</span>' : '<a href="mailto:' . e($email) . '">' . e($email) . '</a>' ?><?php endif ?>
      <a href="/ochrana-osobnich-udaju"><?= t($menu['privacy_link']) ?></a>
    </span>
  </div>
  <?php if ($legal || $note !== '' || $credit !== ''): ?>
  <div class="footer-row footer-legal">
    <span><?= implode(' · ', $legal) ?><?php if ($note !== ''): ?><?= $legal ? '. ' : '' ?><?= t($note) ?><?php endif ?></span>
    <?php if ($credit !== ''): ?>
      <span><?= t($menu['credit_label']) ?> <?php if (trim((string) $menu['credit_url']) !== ''): ?><a href="<?= e($menu['credit_url']) ?>" target="_blank" rel="noopener"><?= e($credit) ?></a><?php else: ?><?= e($credit) ?><?php endif ?></span>
    <?php endif ?>
  </div>
  <?php endif ?>
</footer>
