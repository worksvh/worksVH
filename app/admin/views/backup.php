<?php /** @var array $files @var bool $zip */ ?>
<div class="a-head"><h1>Nastavení</h1></div>
<?= render('admin/settings-tabs', ['tab' => 'zaloha']) ?>
<section class="box">
  <h2>Záloha webu</h2>
  <p>Stáhne ZIP s databází (všechny texty, nastavení, sbírky a poptávky) a složkou s obrázky. Přílohy poptávek záloha záměrně neobsahuje.</p>
  <?php if (!$zip): ?>
    <p class="err-box">Na serveru chybí rozšíření PHP zip, zálohu nejde vytvořit.</p>
  <?php else: ?>
    <form method="post" action="/admin/nastaveni/zaloha"><?= csrf_field() ?><button type="submit" class="btn-primary">Stáhnout zálohu</button></form>
  <?php endif ?>
  <p class="help">Automatickou zálohu lze nastavit v cronu hostingu: <code>php app/cli.php backup</code> (uchovává posledních 14 záloh ve storage/backups).</p>
  <?php if ($files): ?>
    <h3>Automatické zálohy na serveru</h3>
    <ul><?php foreach ($files as $f): ?><li><?= e(basename($f)) ?> · <?= e(format_bytes((int) filesize($f))) ?></li><?php endforeach ?></ul>
  <?php endif ?>
</section>
