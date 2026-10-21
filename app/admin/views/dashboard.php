<div class="a-head"><h1>Přehled</h1></div>
<div class="cards">
  <a class="stat" href="/admin/poptavky?stav=new">
    <span class="stat-num"><?= $newCount ?></span>
    <span class="stat-label"><?= $newCount === 1 ? 'nová poptávka' : ($newCount >= 2 && $newCount <= 4 ? 'nové poptávky' : 'nových poptávek') ?></span>
  </a>
  <a class="stat" href="#k-doplneni">
    <span class="stat-num"><?= count($todo) ?></span>
    <span class="stat-label">polí k doplnění</span>
  </a>
  <div class="stat stat-links">
    <span class="stat-label">Rychlé odkazy</span>
    <a href="/admin/stranky/home">Upravit úvodní stránku</a>
    <a href="/admin/zakazky/nova">Přidat zakázku</a>
    <a href="/admin/media">Nahrát fotky</a>
    <a href="/admin/nastaveni/company">Kontakty firmy</a>
    <a href="/" target="_blank" rel="noopener">Zobrazit web ↗</a>
  </div>
</div>

<?php if ($latest): ?>
<section class="box">
  <h2>Poslední poptávky</h2>
  <table class="tbl">
    <thead><tr><th>Datum</th><th>Firma</th><th>Jméno</th><th>Stav</th></tr></thead>
    <tbody>
    <?php foreach ($latest as $q): ?>
      <tr><td><?= e(format_date($q['created_at'])) ?></td><td><a href="/admin/poptavky/<?= (int) $q['id'] ?>"><?= e($q['company']) ?></a></td><td><?= e($q['name']) ?></td>
      <td><span class="status status-<?= e($q['status']) ?>"><?= e(INQUIRY_STATUSES[$q['status']] ?? $q['status']) ?></span></td></tr>
    <?php endforeach ?>
    </tbody>
  </table>
</section>
<?php endif ?>

<section class="box" id="k-doplneni">
  <h2>K doplnění <span class="badge-todo"><?= count($todo) ?></span></h2>
  <p class="help">Pole, ve kterých je text v hranatých závorkách [ … ]. Na webu se zatím <?= $hidden ? '<strong>nezobrazují</strong> (zapnuté skrývání v Nastavení › Logo a zobrazení)' : '<strong>zobrazují</strong>, aby šlo web ukazovat jako návrh. Skrýt je jde v Nastavení › Logo a zobrazení' ?>.</p>
  <?php if (!$todo): ?>
    <p>Vše je vyplněné.</p>
  <?php else: ?>
    <ul class="todo-list">
      <?php $last = null; foreach ($todo as $t): ?>
        <?php if ($t['where'] !== $last): $last = $t['where']; ?><li class="todo-where"><?= e($t['where']) ?></li><?php endif ?>
        <li><a href="<?= e($t['url']) ?>"><?= e($t['field']) ?></a> <span class="todo-val"><?= e(mb_strimwidth($t['value'], 0, 90, '…')) ?></span></li>
      <?php endforeach ?>
    </ul>
  <?php endif ?>
</section>
