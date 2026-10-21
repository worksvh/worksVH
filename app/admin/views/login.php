<?php ob_start(); ?>
<h1>Přihlášení do administrace</h1>
<?php if ($error): ?><p class="err-box" role="alert"><?= e($error) ?></p><?php endif ?>
<form method="post" action="/admin/prihlaseni" class="auth-form">
  <?= csrf_field() ?>
  <input type="hidden" name="zpet" value="<?= e($back) ?>">
  <div class="f-field"><label for="l-email">E-mail</label><input type="email" id="l-email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus></div>
  <div class="f-field"><label for="l-pass">Heslo</label><input type="password" id="l-pass" name="password" autocomplete="current-password" required></div>
  <button type="submit" class="btn-primary">Přihlásit se</button>
</form>
<p><a href="/admin/zapomenute-heslo">Zapomněli jste heslo?</a></p>
<?php echo render('admin/auth-layout', ['title' => 'Přihlášení', 'body' => ob_get_clean()]);
