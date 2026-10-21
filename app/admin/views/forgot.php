<?php ob_start(); ?>
<h1>Obnova hesla</h1>
<?php if ($sent): ?>
  <p role="status">Pokud e-mail patří správci webu, poslali jsme na něj odkaz pro nastavení nového hesla. Odkaz platí 1 hodinu.</p>
<?php else: ?>
  <p>Zadejte e-mail, kterým se přihlašujete. Pošleme vám odkaz pro nastavení nového hesla.</p>
  <form method="post" action="/admin/zapomenute-heslo" class="auth-form">
    <?= csrf_field() ?>
    <div class="f-field"><label for="f-email">E-mail</label><input type="email" id="f-email" name="email" autocomplete="username" required autofocus></div>
    <button type="submit" class="btn-primary">Poslat odkaz</button>
  </form>
<?php endif ?>
<p><a href="/admin/prihlaseni">← Zpět na přihlášení</a></p>
<?php echo render('admin/auth-layout', ['title' => 'Obnova hesla', 'body' => ob_get_clean()]);
