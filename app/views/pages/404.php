<section class="wrap simple-page">
  <?php if (ph($nf['label']) !== ''): ?><div class="mono mono-blue eyebrow"><?= t($nf['label']) ?></div><?php endif ?>
  <h1 class="h1 h1--contact"><?= t($nf['title']) ?></h1>
  <?php if (ph($nf['text']) !== ''): ?><p class="text-18"><?= tbr($nf['text']) ?></p><?php endif ?>
  <p class="simple-actions">
    <a class="btn btn-dark" href="/"><?= t($nf['home']) ?></a>
    <a class="btn btn-outline" href="/kontakt"><?= t($nf['contact']) ?></a>
  </p>
</section>
