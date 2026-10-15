// main.js — theme, nav, reveal-on-scroll, filters, card tilt, skill bars.
// Respects prefers-reduced-motion. No build step.
(function () {
  'use strict';
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---- Theme (dark default, persisted) ----
  var root = document.documentElement;
  try {
    var saved = localStorage.getItem('pf-theme');
    if (saved === 'light' || saved === 'dark') root.setAttribute('data-theme', saved);
    else root.setAttribute('data-theme', root.getAttribute('data-theme') || 'dark');
  } catch (e) { /* private mode */ }
  document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var cur = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
      root.setAttribute('data-theme', cur);
      try { localStorage.setItem('pf-theme', cur); } catch (e) {}
      btn.setAttribute('aria-pressed', cur === 'light' ? 'true' : 'false');
    });
  });

  // ---- Mobile menu ----
  var menuBtn = document.querySelector('[data-menu]');
  var links = document.querySelector('[data-nav-links]');
  if (menuBtn && links) {
    menuBtn.addEventListener('click', function () {
      var open = links.classList.toggle('open');
      menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    links.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { links.classList.remove('open'); });
    });
  }

  // ---- Active nav link on scroll ----
  var secs = Array.prototype.slice.call(document.querySelectorAll('main section[id]'));
  var navAs = Array.prototype.slice.call(document.querySelectorAll('[data-nav-links] a[href^="#"]'));
  if ('IntersectionObserver' in window && secs.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          navAs.forEach(function (a) {
            a.classList.toggle('active', a.getAttribute('href') === '#' + en.target.id);
          });
        }
      });
    }, { rootMargin: '-40% 0px -55% 0px' });
    secs.forEach(function (s) { io.observe(s); });
  }

  // ---- Reveal on scroll ----
  var rev = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && !reduce) {
    var rio = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('in'); rio.unobserve(en.target); }
      });
    }, { threshold: 0.12 });
    rev.forEach(function (el) { rio.observe(el); });
  } else {
    rev.forEach(function (el) { el.classList.add('in'); });
  }

  // ---- Skill bars animate when visible ----
  var bars = document.querySelectorAll('.bar > span[data-level]');
  function fillBars(scope) {
    (scope || document).querySelectorAll('.bar > span[data-level]').forEach(function (s) {
      s.style.width = Math.max(0, Math.min(100, parseInt(s.getAttribute('data-level'), 10) || 0)) + '%';
    });
  }
  if ('IntersectionObserver' in window && !reduce) {
    var bio = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { fillBars(en.target); bio.unobserve(en.target); }
      });
    }, { threshold: 0.2 });
    document.querySelectorAll('.skill-group').forEach(function (g) { bio.observe(g); });
  } else { fillBars(); }

  // ---- Project filtering (buttons + keyboard-friendly) ----
  var fbtns = document.querySelectorAll('.filter-btn');
  var cards = document.querySelectorAll('[data-category]');
  function applyFilter(cat) {
    cards.forEach(function (c) {
      var show = cat === 'all' || c.getAttribute('data-category') === cat;
      c.style.display = show ? '' : 'none';
    });
    var empty = document.querySelector('[data-empty]');
    if (empty) {
      var any = Array.prototype.some.call(cards, function (c) { return c.style.display !== 'none'; });
      empty.style.display = any ? 'none' : '';
    }
  }
  fbtns.forEach(function (b) {
    b.addEventListener('click', function () {
      fbtns.forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
      b.setAttribute('aria-pressed', 'true');
      applyFilter(b.getAttribute('data-filter'));
    });
  });

  // ---- Subtle 3D tilt on .tilt cards (pointer only, skipped on touch/reduced motion) ----
  if (!reduce && window.matchMedia('(pointer: fine)').matches) {
    document.querySelectorAll('.tilt').forEach(function (card) {
      var raf = null;
      card.addEventListener('pointermove', function (ev) {
        if (raf) return;
        raf = requestAnimationFrame(function () {
          var r = card.getBoundingClientRect();
          var x = (ev.clientX - r.left) / r.width - 0.5;
          var y = (ev.clientY - r.top) / r.height - 0.5;
          card.style.transform = 'perspective(900px) rotateY(' + (x * 7) + 'deg) rotateX(' + (-y * 7) + 'deg) translateY(-3px)';
          raf = null;
        });
      });
      card.addEventListener('pointerleave', function () { card.style.transform = ''; });
    });
  }

  // ---- Footer year ----
  document.querySelectorAll('[data-year]').forEach(function (el) { el.textContent = new Date().getFullYear(); });

  // ---- Robot speech helper (used by robot-three.js via CustomEvent) ----
  window.pfSay = function (msg) {
    var s = document.querySelector('[data-speech]');
    if (!s) return;
    s.textContent = msg;
    s.classList.add('show');
    clearTimeout(s._t);
    s._t = setTimeout(function () { s.classList.remove('show'); }, 3200);
  };
})();
