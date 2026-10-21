<?php $m = $page['main']; ?>
<article class="wrap simple-page">
  <?php if (ph($m['label']) !== ''): ?><div class="mono mono-blue eyebrow"><?= t($m['label']) ?></div><?php endif ?>
  <h1 class="h1 h1--contact"><?= t($m['title']) ?></h1>
  <div class="rich text-18 prose"><?= ph($m['body']) ?></div>
</article>
