<nav class="tabs" aria-label="Části nastavení">
  <?php foreach (schema()['settings'] as $k => $ss): ?>
    <a href="/admin/nastaveni/<?= e($k) ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= e($ss['label']) ?></a>
  <?php endforeach ?>
  <a href="/admin/nastaveni/uzivatele"<?= $tab === 'uzivatele' ? ' aria-current="page"' : '' ?>>Uživatelé</a>
  <a href="/admin/nastaveni/zaloha"<?= $tab === 'zaloha' ? ' aria-current="page"' : '' ?>>Záloha</a>
</nav>
