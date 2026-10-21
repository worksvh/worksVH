/* VH Works – klikací díl, filtr prací, drop zóna. Bez závislostí. */
(function () {
  'use strict';

  /* ---------- klikací díl ---------- */
  var part = document.querySelector('[data-part]');
  if (part) {
    var items = part.querySelectorAll('.part-list li');
    var detail = part.querySelector('.part-detail');
    var pins = Array.prototype.slice.call(part.querySelectorAll('.pin'));
    var chips = Array.prototype.slice.call(part.querySelectorAll('.chip'));
    var activate = function (i, focusEl) {
      var src = items[i];
      if (!src || !detail) return;
      ['pos', 'title', 'text', 'spec'].forEach(function (f) {
        var to = detail.querySelector('[data-f="' + f + '"]');
        var from = src.querySelector('[data-f="' + f + '"]');
        if (to) { to.innerHTML = from ? from.innerHTML : ''; to.hidden = !from; }
      });
      pins.concat(chips).forEach(function (b) {
        b.setAttribute('aria-pressed', String(+b.getAttribute('data-index') === i));
      });
      if (focusEl) focusEl.focus();
    };
    var onKey = function (list) {
      return function (e) {
        var k = e.key;
        if (['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp', 'Home', 'End'].indexOf(k) < 0) return;
        e.preventDefault();
        var cur = list.indexOf(e.currentTarget);
        var n = list.length;
        var next = k === 'Home' ? 0 : k === 'End' ? n - 1 : (cur + (k === 'ArrowRight' || k === 'ArrowDown' ? 1 : -1) + n) % n;
        activate(next, list[next]);
      };
    };
    [pins, chips].forEach(function (list) {
      list.forEach(function (b) {
        b.addEventListener('click', function () { activate(+b.getAttribute('data-index')); });
        b.addEventListener('keydown', onKey(list));
      });
    });
  }

  /* ---------- filtr prací ---------- */
  var root = document.querySelector('[data-filter-root]');
  if (root) {
    var links = root.querySelectorAll('[data-filter]');
    var cards = root.querySelectorAll('[data-cat]');
    var empty = root.querySelector('[data-empty]');
    var apply = function (slug) {
      var shown = 0;
      cards.forEach(function (c) {
        var on = !slug || c.getAttribute('data-cat') === slug;
        c.hidden = !on;
        if (on) shown++;
      });
      links.forEach(function (l) {
        if (l.getAttribute('data-filter') === slug) l.setAttribute('aria-current', 'true');
        else l.removeAttribute('aria-current');
      });
      if (empty) empty.hidden = shown > 0;
    };
    links.forEach(function (l) {
      l.setAttribute('role', 'button');
      l.addEventListener('click', function (e) {
        e.preventDefault();
        var slug = l.getAttribute('data-filter');
        apply(slug);
        if (window.history && history.replaceState) {
          history.replaceState(null, '', slug ? '?kategorie=' + encodeURIComponent(slug) : location.pathname);
        }
      });
    });
  }

  /* ---------- poptávkový formulář: soubory ---------- */
  var form = document.querySelector('[data-inquiry]');
  if (form) {
    var input = form.querySelector('.file-input');
    var zone = form.querySelector('[data-dropzone]');
    var list = form.querySelector('[data-file-list]');
    var err = form.querySelector('[data-file-error]');
    var maxFiles = +form.dataset.maxFiles, maxFile = +form.dataset.maxFile, maxTotal = +form.dataset.maxTotal;
    var types = form.dataset.types.split(',');
    var files = [];
    var canSet = typeof DataTransfer === 'function';
    var fmt = function (b) { return b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' kB'; };
    var problem = function () {
      if (files.length > maxFiles) return form.dataset.msgCount;
      var total = 0;
      for (var i = 0; i < files.length; i++) {
        var f = files[i];
        var ext = (f.name.split('.').pop() || '').toLowerCase();
        if (types.indexOf(ext) < 0 || f.name.indexOf('.') < 0) return form.dataset.msgType.replace('{soubor}', f.name);
        if (f.size > maxFile) return form.dataset.msgSize.replace('{soubor}', f.name);
        total += f.size;
      }
      if (total > maxTotal) return form.dataset.msgTotal;
      return '';
    };
    var sync = function () {
      if (canSet) {
        var dt = new DataTransfer();
        files.forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
      }
      list.innerHTML = '';
      files.forEach(function (f, i) {
        var li = document.createElement('li');
        var name = document.createElement('span');
        name.className = 'file-name';
        name.textContent = f.name + ' ';
        var size = document.createElement('span');
        size.className = 'file-size';
        size.textContent = fmt(f.size);
        name.appendChild(size);
        li.appendChild(name);
        if (canSet) {
          var rm = document.createElement('button');
          rm.type = 'button';
          rm.textContent = form.dataset.msgRemove;
          rm.setAttribute('aria-label', form.dataset.msgRemove + ': ' + f.name);
          rm.addEventListener('click', function () { files.splice(i, 1); sync(); input.focus(); });
          li.appendChild(rm);
        }
        list.appendChild(li);
      });
      var p = problem();
      err.textContent = p;
      err.hidden = !p;
      if (p) input.setAttribute('aria-invalid', 'true'); else input.removeAttribute('aria-invalid');
      var desc = 'k-soubor-limits' + (p ? ' k-soubor-err' : '');
      input.setAttribute('aria-describedby', desc);
    };
    var add = function (fileList) {
      var incoming = Array.prototype.slice.call(fileList);
      files = canSet ? files.concat(incoming) : incoming;
      sync();
    };
    input.addEventListener('change', function () { if (canSet) { var picked = input.files; files = files.concat(Array.prototype.slice.call(picked)); files = dedupe(files); sync(); } else add(input.files); });
    var dedupe = function (arr) {
      var seen = {};
      return arr.filter(function (f) { var k = f.name + '|' + f.size; if (seen[k]) return false; seen[k] = 1; return true; });
    };
    ['dragenter', 'dragover'].forEach(function (ev) {
      zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('is-over'); });
    });
    ['dragleave', 'dragend', 'drop'].forEach(function (ev) {
      zone.addEventListener(ev, function () { zone.classList.remove('is-over'); });
    });
    zone.addEventListener('drop', function (e) {
      e.preventDefault();
      if (e.dataTransfer && e.dataTransfer.files.length) {
        files = dedupe((canSet ? files : []).concat(Array.prototype.slice.call(e.dataTransfer.files)));
        sync();
      }
    });
    form.addEventListener('submit', function (e) {
      var p = problem();
      if (p) {
        e.preventDefault();
        err.textContent = p;
        err.hidden = false;
        input.focus();
        return;
      }
      var bad = form.querySelector(':invalid');
      if (bad) {
        // necháme vyhodnocení na serveru (přesné hlášky u polí), jen fokus na první chybu
        e.preventDefault();
        form.noValidate = true;
        bad.setAttribute('aria-invalid', 'true');
        bad.focus();
        if (bad.reportValidity) bad.reportValidity();
      }
    });
    var alert = form.querySelector('[data-autofocus]');
    var firstInvalid = form.querySelector('[aria-invalid="true"]');
    if (firstInvalid) firstInvalid.focus(); else if (alert) alert.focus();
  }

  /* ---------- lišta o cookies ---------- */
  var bar = document.querySelector('[data-cookie-bar]');
  if (bar) {
    var KEY = 'vhw-cookies-ok';
    var seen = false;
    try { seen = localStorage.getItem(KEY) === '1'; } catch (e) { /* soukromé okno */ }
    if (!seen) bar.hidden = false;
    var ok = bar.querySelector('[data-cookie-ok]');
    if (ok) ok.addEventListener('click', function () {
      try { localStorage.setItem(KEY, '1'); } catch (e) { /* nevadí */ }
      bar.hidden = true;
    });
  }
})();
