<?php
/** @var string $c @var array $cs @var array $items @var array $categories */
$thumbField = $cs['thumb'] ?? null;
?>
<div class="a-head">
  <div>
    <h1><?= e($cs['label']) ?></h1>
    <p class="help">Pořadí na webu změníte přetažením řádku nebo šipkami. <a href="<?= e($cs['web']) ?>" target="_blank" rel="noopener">Zobrazit na webu ↗</a></p>
  </div>
  <a class="btn-primary" href="/admin/<?= e($cs['path']) ?>/nova">+ Přidat <?= e($cs['singular']) ?></a>
</div>

<div class="list-tools">
  <label for="list-search" class="sr">Hledat</label>
  <input type="search" id="list-search" placeholder="Hledat…" data-list-search>
  <span class="help" data-sort-state role="status"></span>
</div>

<?php if (!$items): ?>
  <p class="box">Zatím tu nic není. <a href="/admin/<?= e($cs['path']) ?>/nova">Přidat první <?= e($cs['singular']) ?></a>.</p>
<?php else: ?>
<table class="tbl tbl-list" data-sortable data-sort-url="/admin/<?= e($cs['path']) ?>/poradi">
  <thead><tr>
    <th class="col-handle"><span class="sr">Pořadí</span></th>
    <th class="col-num">#</th>
    <?php if ($thumbField): ?><th class="col-thumb">Náhled</th><?php endif ?>
    <th>Název</th>
    <?php if ($c === 'works'): ?><th>Kategorie</th><th>Označení</th><?php endif ?>
    <?php if (!empty($cs['visibility'])): ?><th>Na webu</th><?php endif ?>
    <th class="col-actions"><span class="sr">Akce</span></th>
  </tr></thead>
  <tbody>
  <?php foreach ($items as $i => $it):
    $d = $it['data'];
    $title = (string) $d[$cs['title']];
    $img = $thumbField ? $d[$thumbField] : null;
    $m = $img && !empty($img['media']) ? media_get((int) $img['media']) : null;
    $todo = false;
    foreach ($cs['fields'] as $fn => $fd) { if (f_has_placeholder($fd, $d[$fn] ?? null)) { $todo = true; break; } }
  ?>
    <tr data-id="<?= $it['id'] ?>" data-search="<?= e(mb_strtolower($title . ' ' . $it['slug'])) ?>">
      <td class="col-handle"><span class="drag" data-row-handle aria-hidden="true" title="Přetáhnout">⠿</span></td>
      <td class="col-num" data-row-num><?= $i + 1 ?></td>
      <?php if ($thumbField): ?>
        <td class="col-thumb"><?= $m ? '<img src="' . e(media_url($m['variants'] ? $m['variants'][0][2] : $m['filename'])) . '" alt="" width="64" height="48">' : '<span class="thumb-empty">bez fotky</span>' ?></td>
      <?php endif ?>
      <td><a href="/admin/<?= e($cs['path']) ?>/<?= $it['id'] ?>" class="strong"><?= e($title !== '' ? $title : '(bez názvu)') ?></a><?= $todo ? ' <span class="badge-todo">K doplnění</span>' : '' ?></td>
      <?php if ($c === 'works'): ?>
        <td><?= e((string) ($categories[$d['category']]['data']['name'] ?? '–')) ?></td>
        <td><?= $d['featured'] ? '<span class="pill pill-soft">Vybraná</span> ' : '' ?><?= $d['on_home'] ? '<span class="pill pill-soft">Úvod</span>' : '' ?></td>
      <?php endif ?>
      <?php if (!empty($cs['visibility'])): ?><td><?= $it['visible'] ? 'Ano' : '<span class="muted">Skryto</span>' ?></td><?php endif ?>
      <td class="col-actions">
        <button type="button" class="icon-btn" data-row-move="-1" aria-label="Posunout „<?= e($title) ?>“ nahoru">↑</button>
        <button type="button" class="icon-btn" data-row-move="1" aria-label="Posunout „<?= e($title) ?>“ dolů">↓</button>
        <a class="btn-small btn-ghost" href="/admin/<?= e($cs['path']) ?>/<?= $it['id'] ?>">Upravit</a>
        <form method="post" action="/admin/<?= e($cs['path']) ?>/<?= $it['id'] ?>/smazat" data-confirm="Opravdu smazat „<?= e($title) ?>“? Smazání nejde vrátit.">
          <?= csrf_field() ?><button type="submit" class="btn-small btn-danger">Smazat</button>
        </form>
      </td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
<?php endif ?>
