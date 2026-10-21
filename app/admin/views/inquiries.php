<?php /** @var array $rows @var string $status @var string $q @var array $counts */ ?>
<div class="a-head"><h1>Poptávky</h1></div>
<nav class="tabs" aria-label="Filtr podle stavu">
  <a href="/admin/poptavky"<?= $status === '' ? ' aria-current="page"' : '' ?>>Vše (<?= array_sum($counts) ?>)</a>
  <?php foreach (INQUIRY_STATUSES as $k => $label): ?>
    <a href="/admin/poptavky?stav=<?= e($k) ?>"<?= $status === $k ? ' aria-current="page"' : '' ?>><?= e($label) ?> (<?= (int) ($counts[$k] ?? 0) ?>)</a>
  <?php endforeach ?>
</nav>
<form method="get" action="/admin/poptavky" class="list-tools">
  <?php if ($status !== ''): ?><input type="hidden" name="stav" value="<?= e($status) ?>"><?php endif ?>
  <label for="iq" class="sr">Hledat</label>
  <input type="search" id="iq" name="q" value="<?= e($q) ?>" placeholder="Firma, jméno, e-mail, telefon…">
  <button type="submit" class="btn-small btn-ghost">Hledat</button>
</form>
<?php if (!$rows): ?>
  <p class="box">Žádné poptávky<?= $status || $q ? ' pro tento filtr' : '' ?>.</p>
<?php else: ?>
<div class="tbl-wrap">
<table class="tbl">
  <thead><tr><th>Datum</th><th>Firma</th><th>Jméno</th><th>Kontakt</th><th>Přílohy</th><th>Stav</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= $r['status'] === 'new' ? 'row-new' : '' ?>">
      <td><?= e(format_date($r['created_at'])) ?></td>
      <td><a class="strong" href="/admin/poptavky/<?= (int) $r['id'] ?>"><?= e($r['company']) ?></a></td>
      <td><?= e($r['name']) ?></td>
      <td><?= $r['email'] !== '' ? '<a href="mailto:' . e($r['email']) . '">' . e($r['email']) . '</a>' : '' ?><?= $r['phone'] !== '' ? '<br><a href="' . e(tel_href($r['phone'])) . '">' . e($r['phone']) . '</a>' : '' ?></td>
      <td><?= (int) $r['files'] ?: '–' ?></td>
      <td><span class="status status-<?= e($r['status']) ?>"><?= e(INQUIRY_STATUSES[$r['status']] ?? $r['status']) ?></span></td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
</div>
<?php endif ?>
