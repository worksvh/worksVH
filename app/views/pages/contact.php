<?php
/** @var array $page @var array $values @var array $errors @var ?string $formError */
$h = $page['hero'];
$f = $page['form'];
$c = setting('company');
$val = static fn(string $k) => e((string) ($values[$k] ?? ''));
$field = static function (string $name, string $id, string $label, string $type, array $opts = []) use ($values, $errors): string {
    $err = $errors[$name] ?? null;
    $req = !empty($opts['required']);
    $attrs = 'id="' . $id . '" name="' . $name . '"' . ($req ? ' required aria-required="true"' : '')
        . (!empty($opts['autocomplete']) ? ' autocomplete="' . $opts['autocomplete'] . '"' : '')
        . ($err ? ' aria-invalid="true" aria-describedby="' . $id . '-err"' : '');
    $html = '<div class="field' . (!empty($opts['wide']) ? ' field--wide' : '') . '">';
    $html .= '<label for="' . $id . '" class="mono-sm">' . t($label) . ($req ? ' <span class="req" aria-hidden="true">*</span>' : '') . '</label>';
    if ($type === 'textarea') {
        $html .= '<textarea ' . $attrs . ' rows="5" maxlength="10000">' . e((string) ($values[$name] ?? '')) . '</textarea>';
    } else {
        $html .= '<input type="' . $type . '" ' . $attrs . ' maxlength="200" value="' . e((string) ($values[$name] ?? '')) . '">';
    }
    if ($err) {
        $html .= '<p class="field-error" id="' . $id . '-err">' . e($err) . '</p>';
    }
    return $html . '</div>';
};
$accept = '.' . implode(',.', array_keys(INQ_FILE_TYPES));
?>
<div class="wrap contact-layout">
  <section class="contact-info" aria-labelledby="kontakt-nadpis">
    <div>
      <?php if (ph($h['label']) !== ''): ?><div class="mono mono-blue eyebrow"><?= t($h['label']) ?></div><?php endif ?>
      <h1 id="kontakt-nadpis" class="h1 h1--contact"><?= t($h['title']) ?></h1>
      <a class="big-phone" href="<?= e(tel_href($c['phone'])) ?>"><?= e($c['phone']) ?></a>
    </div>
    <dl class="contact-dl">
      <?php if (ph($c['email']) !== ''): ?>
        <div><dt class="mono-sm"><?= t($h['l_email']) ?></dt><dd><?= has_placeholder($c['email']) ? t($c['email']) : '<a href="mailto:' . e($c['email']) . '">' . e($c['email']) . '</a>' ?></dd></div>
      <?php endif ?>
      <div><dt class="mono-sm"><?= t($h['l_address']) ?></dt><dd><?= t($c['street']) ?><br><?= t(trim($c['zip'] . ' ' . $c['city'])) ?></dd></div>
      <?php if (ph($c['hours']) !== ''): ?><div><dt class="mono-sm"><?= t($h['l_hours']) ?></dt><dd><?= tbr($c['hours']) ?></dd></div><?php endif ?>
      <?php if (ph($c['ico']) !== '' || ph($c['dic']) !== '' || ph($c['operator']) !== ''): ?>
        <div><dt class="mono-sm"><?= t($h['l_billing']) ?></dt><dd>
          <?php if (ph($c['operator']) !== ''): ?><?= t($c['operator']) ?><br><?php endif ?>
          <?php if (ph($c['operator_address']) !== ''): ?><?= t($c['operator_address']) ?><br><?php endif ?>
          <?php if (ph($c['ico']) !== ''): ?><?= t($c['ico_label']) ?> <?= t($c['ico']) ?><br><?php endif ?>
          <?php if (ph($c['dic']) !== ''): ?><?= t($c['dic_label']) ?> <?= t($c['dic']) ?><?php endif ?>
        </dd></div>
      <?php endif ?>
    </dl>
    <?php partial('map', ['class' => 'map--contact']) ?>
  </section>

  <section id="poptavka" class="panel inquiry" aria-labelledby="poptavka-nadpis">
    <div class="panel-head mono-sm"><span><?= t($f['head_left']) ?></span><span><?= t($f['head_right']) ?></span></div>
    <form class="inquiry-form" method="post" action="/kontakt#poptavka" enctype="multipart/form-data" novalidate data-inquiry
          data-max-files="<?= INQ_MAX_FILES ?>" data-max-file="<?= INQ_MAX_FILE_BYTES ?>" data-max-total="<?= INQ_MAX_TOTAL_BYTES ?>"
          data-types="<?= e(implode(',', array_keys(INQ_FILE_TYPES))) ?>"
          data-msg-count="<?= e(inq_msg('files_count', ['max' => INQ_MAX_FILES])) ?>"
          data-msg-size="<?= e(inq_msg('files_size', ['max' => format_bytes(INQ_MAX_FILE_BYTES)])) ?>"
          data-msg-total="<?= e(inq_msg('files_total', ['max' => format_bytes(INQ_MAX_TOTAL_BYTES)])) ?>"
          data-msg-type="<?= e(inq_msg('files_type', ['povolene' => strtoupper(implode(', ', array_keys(INQ_FILE_TYPES)))])) ?>"
          data-msg-remove="<?= e(inq_msg('remove')) ?>">
      <h2 id="poptavka-nadpis" class="h2-detail field--wide"><?= t($f['title']) ?></h2>
      <?php if ($formError): ?>
        <div class="form-alert field--wide" role="alert" tabindex="-1" data-autofocus><?= e($formError) ?></div>
      <?php endif ?>
      <?= csrf_field() ?>
      <div class="hp" aria-hidden="true">
        <label for="k-web">Nevyplňujte</label>
        <input type="text" id="k-web" name="web" tabindex="-1" autocomplete="off">
      </div>
      <?= $field('company', 'k-firma', $f['f_company'], 'text', ['required' => true, 'autocomplete' => 'organization']) ?>
      <?= $field('name', 'k-jmeno', $f['f_name'], 'text', ['required' => true, 'autocomplete' => 'name']) ?>
      <?= $field('email', 'k-email', $f['f_email'], 'email', ['required' => true, 'autocomplete' => 'email']) ?>
      <?= $field('phone', 'k-tel', $f['f_phone'], 'tel', ['autocomplete' => 'tel']) ?>
      <?= $field('quantity', 'k-ks', $f['f_quantity'], 'text') ?>
      <?= $field('deadline', 'k-termin', $f['f_deadline'], 'text') ?>
      <?= $field('message', 'k-popis', $f['f_message'], 'textarea', ['required' => true, 'wide' => true]) ?>

      <div class="field--wide">
        <label for="k-soubor" class="dropzone" data-dropzone>
          <span class="dropzone-main">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#141517" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 16V4"/><path d="M7 9l5-5 5 5"/><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg>
            <span class="dropzone-text">
              <span class="strong"><?= t($f['files_title']) ?></span>
              <span class="mono-sm"><?= t($f['files_formats']) ?></span>
            </span>
          </span>
          <span class="mono dropzone-btn"><?= t($f['files_button']) ?></span>
          <input id="k-soubor" class="file-input" type="file" name="soubory[]" multiple accept="<?= e($accept) ?>"
                 aria-describedby="k-soubor-limits<?= isset($errors['files']) ? ' k-soubor-err' : '' ?>"<?= isset($errors['files']) ? ' aria-invalid="true"' : '' ?>>
        </label>
        <p class="files-limits" id="k-soubor-limits"><?= t($f['files_limits']) ?></p>
        <ul class="file-list" data-file-list aria-live="polite"></ul>
        <p class="field-error" id="k-soubor-err" data-file-error<?= isset($errors['files']) ? '' : ' hidden' ?>><?= e($errors['files'] ?? '') ?></p>
      </div>

      <div class="field--wide consent">
        <input type="checkbox" id="k-souhlas" name="consent" value="1" required aria-required="true"<?= !empty($values['consent']) ? ' checked' : '' ?><?= isset($errors['consent']) ? ' aria-invalid="true" aria-describedby="k-souhlas-err"' : '' ?>>
        <label for="k-souhlas"><?= t($f['consent']) ?> <a href="/ochrana-osobnich-udaju" target="_blank"><?= t($f['consent_link']) ?></a>. <span class="req" aria-hidden="true">*</span></label>
        <?php if (isset($errors['consent'])): ?><p class="field-error" id="k-souhlas-err"><?= e($errors['consent']) ?></p><?php endif ?>
      </div>

      <div class="field--wide form-foot">
        <?php if (ph($f['note']) !== ''): ?><span class="form-note"><?= tbr($f['note']) ?></span><?php endif ?>
        <button type="submit" class="btn btn-dark btn-lg"><?= t($f['submit']) ?></button>
      </div>
    </form>
  </section>
</div>
