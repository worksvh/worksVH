<?php
/** @var array $cta  @var bool $large */
$large = $large ?? false;
$phone = setting('company')['phone'];

// štítek „PDF · STEP · DWG · NEBO FOTKA NÁČRTU“ → „PDF, STEP, DWG nebo fotka náčrtu“
$formats = '';
$segs = array_values(array_filter(preg_split('/\s*·\s*/u', ph($cta['label'])), static fn($s) => $s !== ''));
foreach ($segs as $i => $seg) {
    if (preg_match('/^(nebo|or)\s+(.+)$/iu', $seg, $m)) {
        $formats .= ' ' . mb_strtolower($m[1]) . ' ' . mb_strtolower($m[2]);
    } else {
        $formats .= ($i > 0 ? ', ' : '') . (mb_strlen($seg) <= 5 ? $seg : mb_strtolower($seg));
    }
}
?>
<section id="kontakt" class="wrap cta-section<?= $large ? ' cta-section--large' : '' ?>">
  <div class="cta<?= $large ? ' cta--large' : '' ?>">
    <div class="cta-text">
      <h2><?= t($cta['title']) ?></h2>
      <p class="cta-call"><?php if (ph($cta['phone_label']) !== ''): ?><?= t($cta['phone_label']) ?> <?php endif ?><a class="cta-phone" href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a></p>
    </div>
    <a class="cta-drop" href="<?= e($cta['button_url']) ?>">
      <span class="cta-drop-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22"><path d="M12 15V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14v4.5A1.5 1.5 0 0 0 6.5 20h11a1.5 1.5 0 0 0 1.5-1.5V14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
      <?php if (ph($cta['drop_title']) !== ''): ?><span class="cta-drop-title"><?= t($cta['drop_title']) ?></span><?php endif ?>
      <?php if ($formats !== ''): ?><span class="cta-drop-formats"><?= e(trim($formats)) ?></span><?php endif ?>
      <span class="btn cta-drop-btn"><?= t($cta['button']) ?> <span aria-hidden="true">→</span></span>
    </a>
  </div>
</section>
