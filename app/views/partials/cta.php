<?php
/** @var array $cta  @var bool $large */
$large = $large ?? false;
$phone = setting('company')['phone'];
?>
<section id="kontakt" class="wrap cta-section<?= $large ? ' cta-section--large' : '' ?>">
  <div class="cta<?= $large ? ' cta--large' : '' ?>">
    <div class="cta-text">
      <?php if (ph($cta['label']) !== ''): ?><span class="mono cta-label"><?= t($cta['label']) ?></span><?php endif ?>
      <h2><?= t($cta['title']) ?></h2>
    </div>
    <div class="cta-actions">
      <a class="btn btn-dark btn-lg" href="<?= e($cta['button_url']) ?>"><?= t($cta['button']) ?></a>
      <a class="cta-phone" href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a>
    </div>
  </div>
</section>
