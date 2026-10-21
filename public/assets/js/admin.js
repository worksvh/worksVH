/* VH Works – administrace. Bez knihoven. */
(function () {
  'use strict';
  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var csrf = document.body.getAttribute('data-csrf');
  var PH = /\[[^\[\]]+\]/;

  /* ---------- potvrzení ---------- */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.hasAttribute('data-confirm') && !window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
  });

  /* ---------- fokus na chyby ---------- */
  var af = $('[data-autofocus]');
  if (af) af.focus();

  /* ---------- neuložené změny ---------- */
  var dirty = false;
  var editForm = $('[data-edit-form]');
  var note = $('[data-dirty-note]');
  var markDirty = function () {
    dirty = true;
    if (note) note.hidden = false;
  };
  if (editForm) {
    editForm.addEventListener('input', markDirty);
    editForm.addEventListener('change', markDirty);
    editForm.addEventListener('submit', function (e) {
      syncRte();
      var sub = e.submitter;
      if (!sub || !sub.hasAttribute('data-preview')) dirty = false;
    });
    window.addEventListener('beforeunload', function (e) {
      if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });
  }

  /* ---------- štítek „K doplnění“ a počítadlo znaků ---------- */
  var updateBadge = function (field) {
    if (!field) return;
    var badge = $('[data-todo-badge]', field);
    if (!badge) return;
    var hasImg = $('[data-img-id]', field);
    var on = $$('[data-ph-check]', field).some(function (el) {
      var v = el.isContentEditable ? el.textContent : el.value;
      return PH.test(v || '');
    });
    if (hasImg && hasImg.value) on = false;
    badge.hidden = !on;
  };
  document.addEventListener('input', function (e) {
    var el = e.target;
    if (el.closest && el.closest('[data-field]') && (el.hasAttribute('data-ph-check') || el.isContentEditable)) {
      updateBadge(el.closest('[data-field]'));
    }
    if (el.hasAttribute && el.hasAttribute('data-count')) count(el);
  });
  var count = function (el) {
    var c = el._counter;
    if (!c) {
      c = el._counter = document.createElement('span');
      c.className = 'char-count';
      c.setAttribute('aria-live', 'polite');
      el.insertAdjacentElement('afterend', c);
    }
    var max = +el.getAttribute('maxlength');
    c.textContent = el.value.length + ' / ' + max;
    c.classList.toggle('over', el.value.length >= max);
  };
  $$('[data-count]').forEach(count);

  /* ---------- rich text ---------- */
  var syncRte = function () {
    $$('[data-rte]').forEach(function (r) {
      $('[data-rte-value]', r).value = $('.rte-area', r).innerHTML;
    });
  };
  $$('[data-rte]').forEach(function (r) {
    var area = $('.rte-area', r);
    try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (err) { /* nic */ }
    $$('[data-cmd]', r).forEach(function (b) {
      b.addEventListener('mousedown', function (e) { e.preventDefault(); });
      b.addEventListener('click', function () {
        var cmd = b.getAttribute('data-cmd');
        area.focus();
        if (cmd === 'link') {
          var url = window.prompt('Adresa odkazu (https://… nebo /kontakt):', 'https://');
          if (!url || url === 'https://') return;
          if (!/^(https?:\/\/|\/|mailto:|tel:|#)/i.test(url)) url = 'https://' + url;
          document.execCommand('createLink', false, url);
        } else if (cmd === 'formatBlock') {
          document.execCommand('formatBlock', false, 'p');
        } else {
          document.execCommand(cmd, false, null);
        }
        markDirty();
        updateBadge(r.closest('[data-field]'));
      });
    });
    area.addEventListener('paste', function (e) {
      // vkládáme jen čistý text, formátování z Wordu apod. by nadělalo nepořádek
      var text = (e.clipboardData || window.clipboardData).getData('text/plain');
      e.preventDefault();
      document.execCommand('insertText', false, text);
    });
    area.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') { e.preventDefault(); document.execCommand('bold'); }
    });
  });

  /* ---------- řazení přetažením (obecné) ---------- */
  var sortable = function (container, itemSel, handleSel, onChange) {
    var dragged = null;
    container.addEventListener('mousedown', function (e) {
      var h = e.target.closest(handleSel);
      if (h && container.contains(h)) h.closest(itemSel).setAttribute('draggable', 'true');
    });
    container.addEventListener('mouseup', function () {
      $$(itemSel, container).forEach(function (i) { i.setAttribute('draggable', 'false'); });
    });
    container.addEventListener('dragstart', function (e) {
      var it = e.target.closest && e.target.closest(itemSel);
      if (!it || it.getAttribute('draggable') !== 'true' || it.parentNode !== container) return;
      dragged = it;
      it.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', ''); } catch (err) { /* IE */ }
    });
    container.addEventListener('dragover', function (e) {
      if (!dragged) return;
      var over = e.target.closest && e.target.closest(itemSel);
      if (!over || over === dragged || over.parentNode !== container) return;
      e.preventDefault();
      var r = over.getBoundingClientRect();
      var after = e.clientY > r.top + r.height / 2;
      $$(itemSel, container).forEach(function (i) { i.classList.remove('drop-before', 'drop-after'); });
      over.classList.add(after ? 'drop-after' : 'drop-before');
    });
    container.addEventListener('drop', function (e) {
      if (!dragged) return;
      e.preventDefault();
      var over = $(itemSel + '.drop-before, ' + itemSel + '.drop-after', container);
      if (over) {
        if (over.classList.contains('drop-after')) over.after(dragged); else over.before(dragged);
        onChange();
      }
    });
    container.addEventListener('dragend', function () {
      if (dragged) dragged.classList.remove('is-dragging');
      dragged = null;
      $$(itemSel, container).forEach(function (i) { i.classList.remove('drop-before', 'drop-after'); i.setAttribute('draggable', 'false'); });
    });
  };
  var moveEl = function (el, dir) {
    if (dir < 0 && el.previousElementSibling) el.previousElementSibling.before(el);
    else if (dir > 0 && el.nextElementSibling) el.nextElementSibling.after(el);
  };

  /* ---------- tags ---------- */
  $$('[data-tags]').forEach(function (box) {
    var field = box.closest('[data-field]');
    var tpl = $('[data-tag-template]', field);
    $('[data-tag-add]', field).addEventListener('click', function () {
      box.appendChild(tpl.content.cloneNode(true));
      var inputs = $$('input', box);
      inputs[inputs.length - 1].focus();
      markDirty();
    });
    box.addEventListener('click', function (e) {
      var row = e.target.closest('[data-tag-row]');
      if (!row) return;
      if (e.target.closest('[data-tag-remove]')) { row.remove(); markDirty(); updateBadge(field); }
      var mv = e.target.closest('[data-move]');
      if (mv) { moveEl(row, +mv.getAttribute('data-move')); mv.focus(); markDirty(); }
    });
  });

  /* ---------- opakovač ---------- */
  var uid = 0;
  $$('[data-repeater]').forEach(function (rep) {
    var field = rep.closest('[data-field]');
    var tpl = $('[data-rep-template]', field);
    var add = $('[data-rep-add]', field);
    var min = +rep.getAttribute('data-min'), max = +rep.getAttribute('data-max');
    var refresh = function () {
      var items = $$(':scope > [data-rep-item]', rep);
      items.forEach(function (it, i) {
        $('[data-rep-num]', it).textContent = i + 1;
        var rm = $('[data-rep-remove]', it);
        if (rm) rm.disabled = items.length <= min;
      });
      if (add) add.hidden = items.length >= max;
    };
    var titleOf = function (it) {
      var first = $('.rep-body input[type="text"]', it);
      $('[data-rep-title]', it).textContent = first ? first.value : '';
    };
    if (add) add.addEventListener('click', function () {
      var html = tpl.innerHTML.replace(/__i__/g, 'n' + Date.now() + (uid++));
      var wrap = document.createElement('div');
      wrap.innerHTML = html;
      var it = wrap.firstElementChild;
      rep.appendChild(it);
      initImageFields(it);
      refresh();
      var first = $('input:not([type="hidden"]), textarea', it);
      if (first) first.focus();
      markDirty();
    });
    rep.addEventListener('click', function (e) {
      var it = e.target.closest('[data-rep-item]');
      if (!it || it.parentNode !== rep) return;
      if (e.target.closest('[data-rep-remove]')) {
        if (window.confirm('Smazat tuto položku?')) { it.remove(); refresh(); markDirty(); }
        return;
      }
      var mv = e.target.closest('[data-move]');
      if (mv && mv.closest('[data-rep-item]') === it) { moveEl(it, +mv.getAttribute('data-move')); refresh(); mv.focus(); markDirty(); }
    });
    rep.addEventListener('input', function (e) {
      var it = e.target.closest('[data-rep-item]');
      if (it) titleOf(it);
    });
    sortable(rep, '[data-rep-item]', '[data-drag-handle]', function () { refresh(); markDirty(); });
    refresh();
  });

  /* ---------- slug ---------- */
  var slugify = function (s) {
    return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  };
  $$('[data-slug-from]').forEach(function (slug) {
    var src = document.getElementById(slug.getAttribute('data-slug-from'));
    var auto = slug.getAttribute('data-slug-auto') === '1';
    slug.addEventListener('input', function () { auto = false; });
    slug.addEventListener('change', function () { slug.value = slugify(slug.value); });
    if (src) src.addEventListener('input', function () { if (auto) slug.value = slugify(src.value); });
  });

  /* ---------- výběr obrázku ---------- */
  var picker = $('#media-picker');
  var pickerCache = null;
  var pickerTarget = null;
  var thumbHtml = function (url) { var i = document.createElement('img'); i.src = url; i.alt = ''; return i; };
  var renderPicker = function () {
    var grid = $('[data-picker-grid]', picker);
    var q = ($('[data-picker-search]', picker).value || '').toLowerCase();
    var allowSvg = pickerTarget && pickerTarget.getAttribute('data-svg') === '1';
    grid.innerHTML = '';
    var list = (pickerCache || []).filter(function (m) {
      return (allowSvg || !m.svg) && (!q || (m.name + ' ' + m.alt).toLowerCase().indexOf(q) >= 0);
    });
    if (!list.length) { var p = document.createElement('p'); p.className = 'help'; p.textContent = 'Žádné obrázky. Nahrajte nový.'; grid.appendChild(p); }
    list.forEach(function (m) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'picker-item';
      b.setAttribute('role', 'listitem');
      b.appendChild(thumbHtml(m.thumb));
      var s = document.createElement('span'); s.textContent = m.name; b.appendChild(s);
      var s2 = document.createElement('span'); s2.className = 'help'; s2.textContent = m.size; b.appendChild(s2);
      b.setAttribute('aria-label', 'Vybrat ' + m.name + (m.alt ? ' – ' + m.alt : ''));
      b.addEventListener('click', function () { choose(m); });
      grid.appendChild(b);
    });
  };
  var loadPicker = function () {
    return fetch('/admin/media/seznam.json', { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); }).then(function (d) { pickerCache = d.items; renderPicker(); });
  };
  var choose = function (m) {
    var f = pickerTarget;
    $('[data-img-id]', f).value = m.id;
    var prev = $('[data-img-preview]', f);
    prev.innerHTML = '';
    prev.appendChild(thumbHtml(m.thumb));
    var alt = $('[data-img-alt]', f);
    if (!alt.value || PH.test(alt.value)) alt.value = m.alt || '';
    var nm = $('[data-img-name]', f);
    nm.textContent = m.name + (m.size ? ' · ' + m.size : '');
    nm.hidden = false;
    $('[data-img-clear]', f).hidden = false;
    picker.close();
    updateBadge(f.closest('[data-field]'));
    markDirty();
    alt.focus();
  };
  var initImageFields = function (root) {
    $$('[data-image-field]', root).forEach(function (f) {
      if (f._init) return;
      f._init = true;
      $('[data-img-pick]', f).addEventListener('click', function () {
        pickerTarget = f;
        $('[data-picker-svg-note]', picker).hidden = f.getAttribute('data-svg') !== '1';
        $('[data-picker-error]', picker).hidden = true;
        picker.showModal();
        if (pickerCache) renderPicker(); else loadPicker();
      });
      $('[data-img-clear]', f).addEventListener('click', function () {
        $('[data-img-id]', f).value = '';
        $('[data-img-preview]', f).innerHTML = '<span>Bez obrázku</span>';
        $('[data-img-name]', f).hidden = true;
        this.hidden = true;
        updateBadge(f.closest('[data-field]'));
        markDirty();
      });
    });
  };
  if (picker) {
    initImageFields(document);
    $('[data-picker-close]', picker).addEventListener('click', function () { picker.close(); });
    $('[data-picker-search]', picker).addEventListener('input', renderPicker);
    picker.addEventListener('click', function (e) { if (e.target === picker) picker.close(); });
    $('[data-picker-file]', picker).addEventListener('change', function () {
      var input = this;
      if (!input.files.length) return;
      var err = $('[data-picker-error]', picker);
      var fd = new FormData();
      fd.append('file', input.files[0]);
      fd.append('_csrf', csrf);
      err.hidden = true;
      fetch('/admin/media/nahrat', { method: 'POST', body: fd, headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          input.value = '';
          if (d.items && d.items.length) {
            pickerCache = d.items.concat(pickerCache || []);
            choose(d.items[0]);
          } else {
            err.textContent = (d.errors || ['Nahrání se nepovedlo.']).join(' ');
            err.hidden = false;
          }
        })
        .catch(function () { err.textContent = 'Nahrání se nepovedlo (soubor může být příliš velký).'; err.hidden = false; });
    });
  }

  /* ---------- seznam položek: hledání a řazení ---------- */
  var search = $('[data-list-search]');
  var table = $('[data-sortable]');
  if (search && table) {
    search.addEventListener('input', function () {
      var q = search.value.toLowerCase().trim();
      $$('tbody tr', table).forEach(function (tr) { tr.hidden = q && tr.getAttribute('data-search').indexOf(q) < 0; });
    });
  }
  if (table) {
    var tbody = $('tbody', table);
    var state = $('[data-sort-state]');
    var save = function () {
      $$('tr', tbody).forEach(function (tr, i) { $('[data-row-num]', tr).textContent = i + 1; });
      var ids = $$('tr', tbody).map(function (tr) { return tr.getAttribute('data-id'); });
      if (state) state.textContent = 'Ukládám pořadí…';
      fetch(table.getAttribute('data-sort-url'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf, Accept: 'application/json' },
        body: JSON.stringify({ ids: ids })
      }).then(function (r) {
        if (state) state.textContent = r.ok ? 'Pořadí uloženo.' : 'Pořadí se nepodařilo uložit.';
      });
    };
    tbody.addEventListener('click', function (e) {
      var mv = e.target.closest('[data-row-move]');
      if (!mv) return;
      moveEl(mv.closest('tr'), +mv.getAttribute('data-row-move'));
      mv.focus();
      save();
    });
    sortable(tbody, 'tr', '[data-row-handle]', save);
  }

  /* ---------- média: hledání ---------- */
  var ms = $('[data-media-search]');
  if (ms) ms.addEventListener('input', function () {
    var q = ms.value.toLowerCase().trim();
    $$('.media-card').forEach(function (c) { c.hidden = q && c.getAttribute('data-search').indexOf(q) < 0; });
  });
})();
