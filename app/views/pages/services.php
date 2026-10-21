<?php
/** @var array $page @var array $services */
$hero = $page['hero'];
$toc = $page['toc'];
$pos = ph($toc['pos_label']);
?>
<section class="wrap page-hero">
  <?php if (ph($hero['label']) !== ''): ?><div class="mono mono-blue eyebrow"><?= t($hero['label']) ?></div><?php endif ?>
  <h1 class="h1 h1--narrow"><?= t($hero['title']) ?></h1>
</section>

<div class="wrap services-layout">
  <nav id="seznam-sluzeb" class="services-toc" aria-label="<?= t($toc['aria']) ?>">
    <?php foreach ($services as $i => $s): ?>
      <a href="#<?= e($s['slug']) ?>"><span class="mono mono-blue"><?= $i + 1 ?></span><?= t($s['data']['title']) ?></a>
    <?php endforeach ?>
    <?php if (ph($toc['note']) !== ''): ?><p class="services-note"><?= with_phone(t($toc['note'])) ?></p><?php endif ?>
  </nav>

  <div class="services-cards">
    <?php foreach ($services as $i => $s): $d = $s['data']; ?>
      <article id="<?= e($s['slug']) ?>" class="card service-card">
        <div class="card-top mono-sm"><span><?= e(trim($pos . ' ' . ($i + 1))) ?></span><?php if (ph($d['tag']) !== ''): ?><span><?= t($d['tag']) ?></span><?php endif ?></div>
        <h2 class="h2-card"><?= t($d['title']) ?></h2>
        <div class="rich service-desc"><?= ph($d['description']) ?></div>
        <?php if ($d['pairs']): ?>
        <dl class="pairs pairs--<?= count($d['pairs']) ?>">
          <?php foreach ($d['pairs'] as $pair): ?>
            <div><dt class="mono-sm"><?= t($pair['label']) ?></dt><dd><?= t($pair['value']) ?></dd></div>
          <?php endforeach ?>
        </dl>
        <?php endif ?>
      </article>
    <?php endforeach ?>
  </div>
</div>

<?php partial('cta', ['cta' => cta_for($page['cta'])]) ?>
