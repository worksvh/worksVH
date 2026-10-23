/* VH Works – klikací díl, filtr prací, lišta o cookies. Bez závislostí. (Formulář poptávky je v React komponentě.) */
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
