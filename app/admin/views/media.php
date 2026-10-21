<?php /** @var array $media @var bool $gd */ ?>
<div class="a-head">
  <div>
    <h1>Média</h1>
    <p class="help">Obrázky pro web. JPG, PNG a WebP do 10 MB, SVG jen pro logo. Po nahrání se vytvoří menší verze pro rychlé načítání.</p>
  </div>
</div>
<?php if (!$gd): ?><p class="warn-box">Na serveru chybí knihovna GD s podporou WebP, ukládají se jen originály (bez zmenšených verzí).</p><?php endif ?>

<form method="post" action="/admin/media/nahrat" enctype="multipart/form-data" class="box upload-box">
  <?= csrf_field() ?>
  <div class="f-field"><label for="m-file">Obrázky</label><input type="file" id="m-file" name="file[]" multiple accept=".jpg,.jpeg,.png,.webp,.svg" required></div>
  <div class="f-field"><label for="m-alt">Popis (alt) – nepovinné, jde doplnit i později</label><input type="text" id="m-alt" name="alt"></div>
  <button type="submit" class="btn-primary">Nahrát</button>
</form>

<div class="list-tools"><label for="media-search" class="sr">Hledat</label><input type="search" id="media-search" placeholder="Hledat podle názvu nebo popisu…" data-media-search></div>

<?php if (!$media): ?><p>Zatím žádné obrázky.</p><?php endif ?>
<div class="media-grid">
  <?php foreach ($media as $m): $thumb = media_url($m['variants'] ? $m['variants'][0][2] : $m['filename']); ?>
    <article class="media-card" id="m-<?= (int) $m['id'] ?>" data-search="<?= e(mb_strtolower($m['original_name'] . ' ' . $m['alt'])) ?>">
      <a href="<?= e(media_url($m['filename'])) ?>" target="_blank" rel="noopener" class="media-thumb"><img src="<?= e($thumb) ?>" alt="" loading="lazy"></a>
      <div class="media-meta">
        <p class="strong media-name"><?= e($m['original_name']) ?></p>
        <p class="help"><?= $m['width'] ? (int) $m['width'] . ' × ' . (int) $m['height'] . ' px · ' : '' ?><?= e(strtoupper(pathinfo($m['filename'], PATHINFO_EXTENSION))) ?><?= $m['variants'] ? ' · ' . count($m['variants']) . ' verze WebP' : '' ?></p>
        <form method="post" action="/admin/media/<?= (int) $m['id'] ?>" class="media-alt">
          <?= csrf_field() ?>
          <label for="alt-<?= (int) $m['id'] ?>" class="sub-label">Popis (alt)<?= $m['alt'] === '' ? ' <span class="badge-todo">chybí</span>' : '' ?></label>
          <input type="text" id="alt-<?= (int) $m['id'] ?>" name="alt" value="<?= e($m['alt']) ?>">
          <button type="submit" class="btn-small btn-ghost">Uložit popis</button>
        </form>
        <div class="media-usage">
          <span class="sub-label">Použito:</span>
          <?php if (!$m['usage']): ?><span class="help">nikde</span>
          <?php else: ?><ul><?php foreach ($m['usage'] as [$label, $url]): ?><li><a href="<?= e($url) ?>"><?= e($label) ?></a></li><?php endforeach ?></ul><?php endif ?>
        </div>
        <?php if (!$m['usage']): ?>
          <form method="post" action="/admin/media/<?= (int) $m['id'] ?>/smazat" data-confirm="Opravdu smazat obrázek <?= e($m['original_name']) ?>?">
            <?= csrf_field() ?><button type="submit" class="btn-small btn-danger">Smazat</button>
          </form>
        <?php else: ?>
          <p class="help">Použitý obrázek nejde smazat.</p>
        <?php endif ?>
      </div>
    </article>
  <?php endforeach ?>
</div>
