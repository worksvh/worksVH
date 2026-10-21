<?php
/** @var array $page @var array $machines @var array $people */
$hero = $page['hero'];
$m = $page['machines'];
$pp = $page['people'];
$v = $page['visit'];
$c = setting('company');
?>
<section class="wrap page-hero page-hero--workshop">
  <div class="workshop-title">
    <?php if (ph($hero['label']) !== ''): ?><div class="mono mono-blue eyebrow"><?= t($hero['label']) ?></div><?php endif ?>
    <h1 class="h1 h1--short"><?= t($hero['title']) ?></h1>
  </div>
  <?php if (ph($hero['story']) !== ''): ?><p class="workshop-story"><?= tbr($hero['story']) ?></p><?php endif ?>
</section>

<section id="fotka" class="wrap">
  <div class="photo photo--wide"><?= img($page['photo']['image'], ['sizes' => '(min-width: 1360px) 1280px, 100vw', 'lazy' => false]) ?></div>
</section>

<section id="stroje" class="wrap section-pad">
  <div class="section-head section-head--tight">
    <h2 class="h2"><?= t($m['title']) ?></h2>
    <?php if (ph($m['status']) !== ''): ?><span class="mono"><?= t($m['status']) ?></span><?php endif ?>
  </div>
  <div class="table-frame" tabindex="0" role="region" aria-label="<?= t($m['title']) ?>">
    <table class="machines">
      <thead><tr>
        <th scope="col" class="mono-sm"><?= t($m['col_name']) ?></th>
        <th scope="col" class="mono-sm"><?= t($m['col_type']) ?></th>
        <th scope="col" class="mono-sm"><?= t($m['col_range']) ?></th>
        <th scope="col" class="mono-sm"><?= t($m['col_use']) ?></th>
      </tr></thead>
      <tbody>
        <?php foreach ($machines as $row): $d = $row['data']; ?>
        <tr>
          <th scope="row"><?= t($d['name']) ?></th>
          <td><?= t($d['type']) ?></td>
          <td class="mono-td"><?= t($d['range']) ?></td>
          <td class="muted"><?= t($d['use']) ?></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</section>

<section id="lide" class="wrap section-pad people">
  <div class="people-intro">
    <h2 class="h2"><?= t($pp['title']) ?></h2>
    <?php if (ph($pp['text']) !== ''): ?><p class="text-17"><?= tbr($pp['text']) ?></p><?php endif ?>
  </div>
  <div class="people-cards">
    <?php foreach ($people as $p): $d = $p['data']; ?>
      <figure class="person">
        <div class="photo photo--4x5"><?= img($d['portrait'], ['sizes' => '(min-width: 900px) 25vw, 100vw']) ?></div>
        <figcaption>
          <span class="person-name"><?= t($d['name']) ?></span>
          <?php if (ph($d['role']) !== ''): ?><span class="mono"><?= t($d['role']) ?></span><?php endif ?>
        </figcaption>
      </figure>
    <?php endforeach ?>
  </div>
</section>

<section id="navsteva" class="wrap section-pad section-pad--bottom">
  <div class="panel visit">
    <div class="visit-body">
      <h2 class="h2-detail"><?= t($v['title']) ?></h2>
      <p class="text-17">
        <?= t($c['street']) ?>, <?= t(trim($c['zip'] . ' ' . $c['city'])) ?>
        <?php if (ph($c['hours']) !== ''): ?><br><?= tbr($c['hours']) ?><?php endif ?>
        <?php if (ph($v['parking']) !== ''): ?><br><?= tbr($v['parking']) ?><?php endif ?>
      </p>
      <?php if (ph($v['button']) !== ''): ?><a class="btn btn-blue" href="/kontakt"><?= t($v['button']) ?></a><?php endif ?>
    </div>
    <?php partial('map', ['class' => 'map--visit']) ?>
  </div>
</section>
