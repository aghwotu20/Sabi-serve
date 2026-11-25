/* SabiServe — front-end behaviour */
(function () {
  'use strict';

  /* Mobile nav */
  var burger = document.querySelector('[data-hamburger]');
  if (burger) burger.addEventListener('click', function () { document.body.classList.toggle('menu-open'); });

  /* Flash dismiss */
  document.querySelectorAll('.flash-close').forEach(function (b) {
    b.addEventListener('click', function () { var f = b.closest('.flash'); if (f) f.remove(); });
  });
  setTimeout(function () { document.querySelectorAll('.flash').forEach(function (f) { f.style.opacity = '0'; setTimeout(function(){f.remove();}, 400); }); }, 6000);

  /* Animated counters */
  var counters = document.querySelectorAll('[data-count]');
  if (counters.length && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target, target = +el.dataset.count, dur = 1400, start = null;
        function tick(ts) {
          if (!start) start = ts;
          var p = Math.min((ts - start) / dur, 1), ease = 1 - Math.pow(2, -8 * p);
          el.firstChild.nodeValue = Math.round(target * (p === 1 ? 1 : ease)).toLocaleString();
          if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
        io.unobserve(el);
      });
    }, { threshold: 0.4 });
    counters.forEach(function (c) { io.observe(c); });
  }

  /* FAQ accordion */
  document.querySelectorAll('.faq-q').forEach(function (btn) {
    btn.addEventListener('click', function () { btn.closest('.faq-item').classList.toggle('open'); });
  });

  /* Password visibility toggle */
  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.querySelector(btn.dataset.togglePassword);
      if (input) { input.type = input.type === 'password' ? 'text' : 'password'; btn.setAttribute('aria-pressed', input.type === 'text'); }
    });
  });

  /* Role selector (register / login hint) */
  document.querySelectorAll('[data-role-group]').forEach(function (group) {
    var hidden = group.querySelector('input[type="hidden"]');
    group.querySelectorAll('.role-tab').forEach(function (tab) {
      tab.addEventListener('click', function () {
        group.querySelectorAll('.role-tab').forEach(function (t) { t.setAttribute('aria-pressed', 'false'); });
        tab.setAttribute('aria-pressed', 'true');
        if (hidden) hidden.value = tab.dataset.role;
      });
    });
  });

  /* Confirm destructive actions */
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) { if (!window.confirm(el.dataset.confirm)) e.preventDefault(); });
  });
})();
