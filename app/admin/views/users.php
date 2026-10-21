<?php /** @var array $users @var array $me @var array $errors */ ?>
<div class="a-head"><h1>Nastavení</h1></div>
<?= render('admin/settings-tabs', ['tab' => 'uzivatele']) ?>
<section class="box">
  <h2>Správci webu</h2>
  <table class="tbl">
    <thead><tr><th>E-mail</th><th>Jméno</th><th>Poslední přihlášení</th><th><span class="sr">Akce</span></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr><td><?= e($u['email']) ?><?= (int) $u['id'] === (int) $me['id'] ? ' <span class="pill pill-soft">vy</span>' : '' ?></td><td><?= e($u['name']) ?></td><td><?= e(format_date($u['last_login_at']) ?: '–') ?></td>
      <td><?php if ((int) $u['id'] !== (int) $me['id']): ?>
        <form method="post" action="/admin/nastaveni/uzivatele" data-confirm="Opravdu odebrat správce <?= e($u['email']) ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><button type="submit" class="btn-small btn-danger">Odebrat</button></form>
      <?php endif ?></td></tr>
    <?php endforeach ?>
    </tbody>
  </table>
</section>
<div class="two-col">
  <form method="post" action="/admin/nastaveni/uzivatele" class="box">
    <h2>Přidat správce</h2>
    <?php if (isset($errors['add'])): ?><p class="err-box" role="alert"><?= e($errors['add']) ?></p><?php endif ?>
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <div class="f-field"><label for="u-email">E-mail</label><input type="email" id="u-email" name="email" required autocomplete="off"></div>
    <div class="f-field"><label for="u-name">Jméno</label><input type="text" id="u-name" name="name"></div>
    <div class="f-field"><label for="u-pass">Heslo (min. 10 znaků)</label><input type="password" id="u-pass" name="password" minlength="10" required autocomplete="new-password"></div>
    <button type="submit" class="btn-primary">Přidat</button>
  </form>
  <form method="post" action="/admin/nastaveni/uzivatele" class="box">
    <h2>Změnit moje heslo</h2>
    <?php if (isset($errors['password'])): ?><p class="err-box" role="alert"><?= e($errors['password']) ?></p><?php endif ?>
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <div class="f-field"><label for="p-cur">Současné heslo</label><input type="password" id="p-cur" name="current" required autocomplete="current-password"></div>
    <div class="f-field"><label for="p-new">Nové heslo (min. 10 znaků)</label><input type="password" id="p-new" name="new" minlength="10" required autocomplete="new-password"></div>
    <div class="f-field"><label for="p-new2">Nové heslo znovu</label><input type="password" id="p-new2" name="new2" minlength="10" required autocomplete="new-password"></div>
    <button type="submit" class="btn-primary">Změnit heslo</button>
  </form>
</div>
