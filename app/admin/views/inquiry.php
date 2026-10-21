<?php /** @var array $q @var array $files @var int $months */ ?>
<div class="a-head">
  <div>
    <p class="crumb"><a href="/admin/poptavky">← Poptávky</a></p>
    <h1>Poptávka č. <?= (int) $q['id'] ?> – <?= e($q['company']) ?></h1>
    <p class="help">Přijato <?= e(format_date($q['created_at'])) ?>. Souhlas se zpracováním udělen <?= e(format_date($q['consent_at'])) ?>.
      <?= $q['anonymized_at'] ? 'Anonymizováno ' . e(format_date($q['anonymized_at'])) . '.' : 'Po ' . $months . ' měsících se poptávka automaticky anonymizuje a přílohy smažou.' ?></p>
  </div>
</div>
<div class="edit-layout">
  <div>
    <section class="box">
      <dl class="dl">
        <div><dt>Firma</dt><dd><?= e($q['company']) ?></dd></div>
        <div><dt>Jméno</dt><dd><?= e($q['name']) ?></dd></div>
        <div><dt>E-mail</dt><dd><?= $q['email'] !== '' ? '<a href="mailto:' . e($q['email']) . '">' . e($q['email']) . '</a>' : '–' ?></dd></div>
        <div><dt>Telefon</dt><dd><?= $q['phone'] !== '' ? '<a href="' . e(tel_href($q['phone'])) . '">' . e($q['phone']) . '</a>' : '–' ?></dd></div>
        <div><dt>Počet kusů</dt><dd><?= e($q['quantity'] ?: '–') ?></dd></div>
        <div><dt>Potřebný termín</dt><dd><?= e($q['deadline'] ?: '–') ?></dd></div>
        <div class="dl-wide"><dt>Popis</dt><dd class="pre"><?= e($q['message']) ?></dd></div>
      </dl>
    </section>
    <section class="box">
      <h2>Přílohy</h2>
      <?php if (!$files): ?><p class="help">Bez příloh.</p><?php else: ?>
        <ul class="file-dl">
          <?php foreach ($files as $f): ?>
            <li><a href="/admin/poptavky/<?= (int) $q['id'] ?>/soubor/<?= (int) $f['id'] ?>"><?= e($f['original_name']) ?></a> <span class="help"><?= e(format_bytes((int) $f['size'])) ?></span></li>
          <?php endforeach ?>
        </ul>
      <?php endif ?>
    </section>
  </div>
  <aside class="edit-side">
    <form method="post" action="/admin/poptavky/<?= (int) $q['id'] ?>" class="box" data-edit-form>
      <?= csrf_field() ?>
      <div class="f-field"><label for="i-status">Stav</label>
        <select id="i-status" name="status">
          <?php foreach (INQUIRY_STATUSES as $k => $label): ?><option value="<?= e($k) ?>"<?= $q['status'] === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?>
        </select></div>
      <div class="f-field"><label for="i-note">Interní poznámka</label><textarea id="i-note" name="note" rows="6"><?= e($q['note']) ?></textarea><p class="help">Vidí ji jen správci webu.</p></div>
      <button type="submit" class="btn-primary">Uložit</button>
    </form>
    <form method="post" action="/admin/poptavky/<?= (int) $q['id'] ?>/smazat" data-confirm="Opravdu smazat poptávku i s přílohami? Nejde to vrátit.">
      <?= csrf_field() ?><button type="submit" class="btn-small btn-danger">Smazat poptávku</button>
    </form>
  </aside>
</div>
