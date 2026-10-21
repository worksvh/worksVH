<?php
/** @var array $page @var ?array $featured @var array $works @var array $categories @var array $catById @var string $selected */
$hero = $page['hero'];
$fl = $page['featured'];
$grid = $page['grid'];
$slugById = array_column(array_map(static fn($c) => ['id' => $c['id'], 'slug' => $c['slug']], $categories), 'slug', 'id');
?>
<section class="wrap page-hero page-hero--split">
  <div>
    <?php if (ph($hero['label']) !== ''): ?><div class="mono mono-blue eyebrow"><?= t($hero['label']) ?></div><?php endif ?>
    <h1 class="h1"><?= t($hero['title']) ?></h1>
  </div>
  <?php if (ph($hero['lead']) !== ''): ?><p class="hero-side"><?= tbr($hero['lead']) ?></p><?php endif ?>
</section>

<?php if ($featured): $d = $featured['data']; ?>
<section id="vybrana-zakazka" class="wrap" aria-label="<?= t($fl['label']) ?>">
  <article class="panel featured">
    <div class="photo photo--featured"><?= img($d['photo'], ['sizes' => '(min-width: 900px) 58vw, 100vw', 'lazy' => false]) ?></div>
    <div class="featured-body">
      <div class="mono-sm mono-blue"><?= e(implode(' · ', array_filter([ph($fl['label']), ph($d['year'])]))) ?></div>
      <h2 class="h2-detail"><?= t($d['title']) ?></h2>
      <dl class="featured-dl">
        <?php foreach (['brief', 'solution', 'result'] as $k): if (ph($d[$k]) === '') continue; ?>
          <div><dt class="mono-sm"><?= t($fl[$k]) ?></dt><dd><?= tbr($d[$k]) ?></dd></div>
        <?php endforeach ?>
      </dl>
    </div>
  </article>
</section>
<?php endif ?>

<section id="zakazky" class="wrap work-list" data-filter-root>
  <div class="filters" role="group" aria-label="<?= t($grid['aria']) ?>">
    <a class="chip chip--panel" href="/prace#zakazky" data-filter=""<?= $selected === '' ? ' aria-current="true"' : '' ?>><?= t($grid['all']) ?></a>
    <?php foreach ($categories as $c): ?>
      <a class="chip chip--panel" href="/prace?kategorie=<?= e(rawurlencode($c['slug'])) ?>#zakazky" data-filter="<?= e($c['slug']) ?>"<?= $selected === $c['slug'] ? ' aria-current="true"' : '' ?>><?= t($c['data']['name']) ?></a>
    <?php endforeach ?>
  </div>
  <div class="work-grid">
    <?php $visible = 0; foreach ($works as $w): $d = $w['data'];
      $slug = $slugById[$d['category']] ?? '';
      $show = $selected === '' || $selected === $slug;
      $visible += (int) $show;
      $cat = $catById[$d['category']] ?? null; ?>
      <figure class="work-card" data-cat="<?= e($slug) ?>"<?= $show ? '' : ' hidden' ?>>
        <div class="photo photo--4x3"><?= img($d['photo'], ['sizes' => '(min-width: 1200px) 30vw, (min-width: 800px) 45vw, 100vw']) ?></div>
        <figcaption>
          <span class="work-card-top"><span class="work-card-title"><?= t($d['title']) ?></span><?php if ($cat): ?><span class="mono-sm mono-blue"><?= t($cat['data']['label']) ?></span><?php endif ?></span>
          <?php if (ph($d['spec']) !== ''): ?><span class="mono"><?= t($d['spec']) ?></span><?php endif ?>
        </figcaption>
      </figure>
    <?php endforeach ?>
  </div>
  <p class="work-empty" data-empty<?= $visible ? ' hidden' : '' ?>><?= t($grid['empty']) ?></p>
</section>

<?php partial('cta', ['cta' => cta_for($page['cta'])]) ?>
