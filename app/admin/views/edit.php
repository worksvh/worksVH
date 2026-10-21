<?php
/** @var array $ent @var array $errors @var array $revisions */
$isItem = $ent['type'] === 'item';
$cs = $ent['cs'] ?? null;
$webUrl = $ent['web'];
$webBase = explode('#', $webUrl)[0];
?>
<div class="a-head">
  <div>
    <?php if ($isItem): ?><p class="crumb"><a href="/admin/<?= e($cs['path']) ?>">← <?= e($cs['label']) ?></a></p><?php endif ?>
    <h1><?= e($ent['title']) ?></h1>
    <?php if ($ent['updated_at']): ?><p class="help">Naposledy uloženo <?= e(format_date($ent['updated_at'])) ?></p><?php endif ?>
  </div>
  <?php if (!$isItem || $ent['id']): ?><a class="btn-small btn-ghost" href="<?= e($webUrl) ?>" target="_blank" rel="noopener">Zobrazit na webu ↗</a><?php endif ?>
</div>

<?php if (!empty($ent['settingsTab'])) { echo render('admin/settings-tabs', ['tab' => $ent['settingsTab']]); } ?>

<?php if ($errors): ?>
  <div class="err-box" role="alert" tabindex="-1" data-autofocus>
    <strong>Neuloženo – formulář obsahuje chyby (<?= count($errors) ?>).</strong>
    <ul>
      <?php foreach ($errors as $path => $msg): ?><li><a href="#<?= e(f_id(explode('.', $path))) ?>"><?= e($msg) ?></a></li><?php endforeach ?>
    </ul>
  </div>
<?php endif ?>

<div class="edit-layout">
<form method="post" action="<?= e($ent['url']) ?>" class="edit-form" data-edit-form novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="_type" value="<?= e($ent['type']) ?>">
  <input type="hidden" name="_key" value="<?= e($ent['key']) ?>">

  <?php if ($isItem && (!empty($cs['slug']) || !empty($cs['visibility']))): ?>
  <section class="group">
    <h2>Zobrazení</h2>
    <?php if (!empty($cs['visibility'])): ?>
      <div class="f-field f-toggle">
        <input type="hidden" name="visible" value="0">
        <label class="switch" for="f-visible"><input type="checkbox" id="f-visible" name="visible" value="1"<?= $ent['visible'] ? ' checked' : '' ?>><span class="switch-ui" aria-hidden="true"></span><span class="f-label-text">Zobrazit na webu</span></label>
        <p class="help">Vypnuté položky na webu nejsou vidět, ale zůstanou uložené.</p>
      </div>
    <?php endif ?>
    <?php if (!empty($cs['slug'])): ?>
      <div class="f-field<?= isset($errors['slug']) ? ' has-error' : '' ?>">
        <label for="f-slug"><span class="f-label-text">Adresa (slug)</span></label>
        <input type="text" id="f-slug" name="slug" value="<?= e($ent['slug']) ?>" data-slug-from="f-<?= e($cs['slug']) ?>" data-slug-auto="<?= $ent['slug'] === '' ? '1' : '0' ?>" aria-describedby="f-slug-help" pattern="[a-z0-9-]*">
        <p class="help" id="f-slug-help"><?= $ent['collection'] === 'services' ? 'Kotva na stránce Služby, např. /sluzby#konstrukce.' : 'Používá se v adrese filtru, např. /prace?kategorie=cnc.' ?> Vyplní se samo z názvu, jen malá písmena bez diakritiky a pomlčky.</p>
      </div>
    <?php endif ?>
  </section>
  <?php endif ?>

  <?php foreach ($ent['groups'] as $g => $group): ?>
  <section class="group" id="g-<?= e($g) ?>">
    <div class="group-head">
      <h2><?= e($group['label']) ?></h2>
      <?php if (!empty($group['anchor'])): ?><a class="small-link" href="<?= e($webBase . '#' . $group['anchor']) ?>" target="_blank" rel="noopener">Zobrazit na webu ↗</a><?php endif ?>
    </div>
    <?php if (!empty($group['help'])): ?><p class="help group-help"><?= e($group['help']) ?></p><?php endif ?>
    <?= $ent['flat'] ? render_fields($group['fields'], $ent['data'], [], $errors) : render_fields($group['fields'], $ent['data'][$g] ?? [], [$g], $errors) ?>
  </section>
  <?php endforeach ?>

  <div class="save-bar">
    <button type="submit" class="btn-primary">Uložit</button>
    <?php if ($ent['type'] !== 'settings' || in_array($ent['key'], ['company', 'display', 'menu', 'cta', 'notfound', 'form_msgs'], true)): ?>
      <button type="submit" class="btn-small btn-ghost" formaction="/admin/nahled" formtarget="_blank" data-preview>Náhled</button>
    <?php endif ?>
    <span class="save-state" data-dirty-note hidden>Máte neuložené změny</span>
  </div>
</form>

<aside class="edit-side">
  <?php if ($isItem && $ent['id']): ?>
    <form method="post" action="/admin/<?= e($cs['path']) ?>/<?= (int) $ent['id'] ?>/smazat" data-confirm="Opravdu smazat tuto položku? Smazání nejde vrátit.">
      <?= csrf_field() ?>
      <button type="submit" class="btn-small btn-danger">Smazat <?= e($cs['singular']) ?></button>
    </form>
  <?php endif ?>
  <section class="box history">
    <h2>Historie</h2>
    <?php if (!$revisions): ?>
      <p class="help">Zatím žádné uložené verze.</p>
    <?php else: ?>
      <p class="help">Posledních <?= REVISIONS_KEEP ?> uložení. Obnovením se vybraná verze uloží jako aktuální.</p>
      <ol class="rev-list">
        <?php foreach ($revisions as $i => $r): ?>
          <li>
            <span><?= e(format_date($r['created_at'])) ?><?= $i === 0 ? ' <span class="pill pill-soft">aktuální</span>' : '' ?><br><span class="help"><?= e($r['name'] ?: ($r['email'] ?? 'systém')) ?></span></span>
            <?php if ($i > 0): ?>
              <form method="post" action="/admin/historie/<?= (int) $r['id'] ?>/obnovit" data-confirm="Obnovit verzi z <?= e(format_date($r['created_at'])) ?>? Neuložené změny ve formuláři se ztratí.">
                <?= csrf_field() ?><button type="submit" class="btn-small btn-ghost">Obnovit</button>
              </form>
            <?php endif ?>
          </li>
        <?php endforeach ?>
      </ol>
    <?php endif ?>
  </section>
</aside>
</div>
