<?php
/** @var array $cta  @var bool $large */
$large = $large ?? false;
$phone = setting('company')['phone'];
?>
<section id="kontakt" class="wrap cta-section<?= $large ? ' cta-section--large' : '' ?>">
  <div class="cta<?= $large ? ' cta--large' : '' ?>">
    <div class="cta-text">
      <?php if (ph($cta['label']) !== ''): ?>
        <?php /* štítek „PDF · STEP · DWG · NEBO FOTKA NÁČRTU“ → čipy; část začínající „nebo“ je zvýrazněná alternativa */ ?>
        <p class="cta-formats">
          <?php foreach (preg_split('/\s*·\s*/u', ph($cta['label'])) as $seg): if ($seg === '') continue; ?>
            <?php if (preg_match('/^(nebo|or)\s+(.+)$/iu', $seg, $m)): $alt = mb_strtoupper(mb_substr($m[2], 0, 1)) . mb_strtolower(mb_substr($m[2], 1)); ?>
              <span class="cta-alt"><span class="cta-or"><?= e(mb_strtolower($m[1])) ?></span>
              <span class="cta-chip cta-chip--alt"><svg aria-hidden="true" viewBox="0 0 16 16" width="14" height="14"><path d="M2.5 5.5h2l1-1.5h5l1 1.5h2v7h-11z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><circle cx="8" cy="8.8" r="2.2" fill="none" stroke="currentColor" stroke-width="1.4"/></svg><?= e($alt) ?></span></span>
            <?php elseif (mb_strlen($seg) <= 5): ?>
              <span class="cta-chip"><?= e($seg) ?></span>
            <?php else: ?>
              <span class="cta-chip cta-chip--text"><?= e($seg) ?></span>
            <?php endif ?>
          <?php endforeach ?>
        </p>
      <?php endif ?>
      <h2><?= t($cta['title']) ?></h2>
    </div>
    <div class="cta-actions">
      <a class="btn btn-dark btn-lg" href="<?= e($cta['button_url']) ?>"><?= t($cta['button']) ?></a>
      <a class="cta-phone" href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a>
    </div>
  </div>
</section>
