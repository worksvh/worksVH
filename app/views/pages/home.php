<?php
/** @var array $page @var array $works @var array $categories */
$hero = $page['hero'];
$part = $page['part'];
$pins = $part['pins'];
$n = count($pins);
$css = '';
foreach ($pins as $i => $p) {
    $x = max(0, min(100, (float) $p['x']));
    $y = max(0, min(100, (float) $p['y']));
    $css .= '#pin-' . ($i + 1) . '{left:' . $x . '%;top:' . $y . '%}';
}
page_style($css);
$tags = array_values(array_filter(array_map('ph', $hero['tags']), static fn($t) => $t !== ''));
$posLabel = ph($part['pos_label']);
?>
<section class="wrap home-hero">
  <?php if ($tags): ?>
  <div class="tags mono">
    <?php foreach ($tags as $i => $tag): ?><?= $i ? '<span aria-hidden="true">·</span>' : '' ?><span><?= e($tag) ?></span><?php endforeach ?>
  </div>
  <?php endif ?>
  <h1 class="h1-home"><?= t($hero['title']) ?></h1>
  <?php if (ph($hero['lead']) !== ''): ?><p class="lead"><?= tbr($hero['lead']) ?></p><?php endif ?>
</section>

<section id="sluzby" class="wrap part-section" aria-label="<?= t($part['aria']) ?>">
  <div class="panel">
    <div class="panel-head mono-sm"><span><?= t($part['head_left']) ?></span><span class="js-only"><?= t($part['head_right']) ?></span></div>
    <div class="part-body" data-part>
      <div class="part-figure">
        <?php require __DIR__ . '/../partials/part-svg.php' ?>
        <?php foreach ($pins as $i => $p): ?>
          <button type="button" class="pin" id="pin-<?= $i + 1 ?>" data-index="<?= $i ?>" aria-label="<?= e(($posLabel !== '' ? $posLabel . ' ' : '') . ($i + 1) . ': ' . ph($p['title'])) ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="part-detail"><?= $i + 1 ?></button>
        <?php endforeach ?>
      </div>

      <?php $a = $pins[0] ?? null; if ($a): ?>
      <div class="part-detail" id="part-detail">
        <div aria-live="polite" class="part-detail-live">
          <div class="mono mono-blue" data-f="pos"><?= e(trim($posLabel . ' 1 / ' . $n)) ?></div>
          <h2 class="h2-detail" data-f="title"><?= t($a['title']) ?></h2>
          <p class="part-text" data-f="text"><?= tbr($a['text']) ?></p>
          <div class="part-spec mono" data-f="spec"><?= t($a['spec']) ?></div>
        </div>
        <div class="chips">
          <?php foreach ($pins as $i => $p): ?>
            <button type="button" class="chip" data-index="<?= $i ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="part-detail"><?= t($p['title']) ?></button>
          <?php endforeach ?>
        </div>
      </div>
      <?php endif ?>

      <ol class="part-list">
        <?php foreach ($pins as $i => $p): ?>
          <li>
            <div class="mono mono-blue" data-f="pos"><?= e(trim($posLabel . ' ' . ($i + 1) . ' / ' . $n)) ?></div>
            <h2 class="h2-detail" data-f="title"><?= t($p['title']) ?></h2>
            <p class="part-text" data-f="text"><?= tbr($p['text']) ?></p>
            <?php if (ph($p['spec']) !== ''): ?><div class="part-spec mono" data-f="spec"><?= t($p['spec']) ?></div><?php endif ?>
          </li>
        <?php endforeach ?>
      </ol>
    </div>
  </div>
</section>

<?php partial('ruler') ?>

<section id="prace" class="wrap home-work">
  <div class="section-head">
    <h2 class="h2"><?= t($page['work']['title']) ?></h2>
    <?php if (ph($page['work']['link']) !== ''): ?><a class="link" href="/prace"><?= t($page['work']['link']) ?></a><?php endif ?>
  </div>
  <?php if ($works): ?>
  <div class="home-work-grid">
    <?php foreach ($works as $i => $w):
      $cat = $categories[$w['data']['category']] ?? null; ?>
      <?php if ($i === 1): ?><div class="home-work-side"><?php endif ?>
      <figure class="work-fig<?= $i === 0 ? ' work-fig--big' : '' ?>">
        <div class="photo <?= $i === 0 ? 'photo--home-big' : 'photo--home-small' ?>"><?= img($w['data']['photo'], ['sizes' => $i === 0 ? '(min-width: 900px) 58vw, 100vw' : '(min-width: 900px) 38vw, 100vw']) ?></div>
        <figcaption class="fig-cap">
          <span class="strong"><?= t($w['data']['title']) ?></span>
          <?php if ($cat): ?><span class="mono"><?= t($cat['data']['label']) ?></span><?php endif ?>
        </figcaption>
      </figure>
      <?php if ($i > 0 && $i === count($works) - 1): ?></div><?php endif ?>
    <?php endforeach ?>
  </div>
  <?php endif ?>
</section>

<?php partial('cta', ['cta' => cta_for($page['cta']), 'large' => true]) ?>
