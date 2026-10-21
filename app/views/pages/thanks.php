<?php
$m = $page['main'];
$c = setting('company');
?>
<section class="wrap simple-page">
  <?php if (ph($m['label']) !== ''): ?><div class="mono mono-blue eyebrow"><?= t($m['label']) ?></div><?php endif ?>
  <h1 class="h1 h1--contact"><?= t($m['title']) ?></h1>
  <div class="rich text-18"><?= ph($m['text']) ?></div>
  <?php if (ph($m['phone_label']) !== ''): ?><p class="mono simple-label"><?= t($m['phone_label']) ?></p><?php endif ?>
  <a class="big-phone" href="<?= e(tel_href($c['phone'])) ?>"><?= e($c['phone']) ?></a>
  <?php if (ph($m['back']) !== ''): ?><p><a class="link" href="/"><?= t($m['back']) ?></a></p><?php endif ?>
</section>
