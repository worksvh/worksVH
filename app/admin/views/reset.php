<?php ob_start(); ?>
<h1>Nové heslo</h1>
<?php if (!$valid): ?>
  <p class="err-box" role="alert">Odkaz je neplatný nebo vypršel (platí 1 hodinu a jen jednou). Požádejte o nový.</p>
  <p><a href="/admin/zapomenute-heslo">Poslat nový odkaz</a></p>
<?php else: ?>
  <?php if ($error): ?><p class="err-box" role="alert"><?= e($error) ?></p><?php endif ?>
  <form method="post" action="/admin/obnova-hesla" class="auth-form">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="f-field"><label for="r-p1">Nové heslo (min. 10 znaků)</label><input type="password" id="r-p1" name="password" autocomplete="new-password" minlength="10" required autofocus></div>
    <div class="f-field"><label for="r-p2">Nové heslo znovu</label><input type="password" id="r-p2" name="password2" autocomplete="new-password" minlength="10" required></div>
    <button type="submit" class="btn-primary">Nastavit heslo</button>
  </form>
<?php endif ?>
<?php echo render('admin/auth-layout', ['title' => 'Nové heslo', 'body' => ob_get_clean()]);
