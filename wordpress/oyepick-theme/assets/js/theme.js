/* OyePick Store theme */
(function ($) {
  'use strict';
  var body = document.body;
  var S = window.OYEPICK || {};

  /* ---------- Drawer & mobile menu ---------- */
  function closeAll() { body.classList.remove('cart-open', 'menu-open'); }
  function openCart() { body.classList.add('cart-open'); }
  $(document).on('click', '#cartBtn', openCart);
  $(document).on('click', '#closeCart, #overlay', closeAll);
  $(document).on('click', '#menuBtn', function () { body.classList.add('menu-open'); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });
  // WooCommerce fires this after an AJAX add to cart; fragments refresh the drawer + counter.
  $(document.body).on('added_to_cart', function () { openCart(); });

  /* ---------- Hero slider ---------- */
  var slider = document.getElementById('slider');
  if (slider) {
    var slides = [].slice.call(slider.querySelectorAll('.slide'));
    var dots = document.getElementById('dots');
    var i = 0, timer, x0 = null;
    dots.innerHTML = slides.map(function (_, n) { return '<button type="button" aria-label="Slide ' + (n + 1) + '"><i></i></button>'; }).join('');
    var dotEls = [].slice.call(dots.children);
    var go = function (n) {
      i = (n + slides.length) % slides.length;
      slides.forEach(function (s, k) { s.classList.toggle('on', k === i); });
      dotEls.forEach(function (d, k) { d.classList.remove('on'); void d.offsetWidth; d.classList.toggle('on', k === i); });
      clearTimeout(timer);
      timer = setTimeout(function () { go(i + 1); }, 2000);
    };
    dotEls.forEach(function (d, k) { d.addEventListener('click', function () { go(k); }); });
    document.getElementById('prev').addEventListener('click', function () { go(i - 1); });
    document.getElementById('next').addEventListener('click', function () { go(i + 1); });
    slider.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
    slider.addEventListener('touchend', function (e) {
      if (x0 === null) return;
      var dx = e.changedTouches[0].clientX - x0;
      if (Math.abs(dx) > 45) go(i + (dx < 0 ? 1 : -1));
      x0 = null;
    });
    go(0);
  }

  /* ---------- Product filter tabs ---------- */
  $(document).on('click', '.tabs button', function () {
    var f = this.getAttribute('data-f');
    $(this).addClass('on').siblings().removeClass('on');
    $('#products .op-card').each(function () {
      this.hidden = !(f === 'all' || this.getAttribute('data-cat') === f);
    });
  });

  /* ---------- Wishlist heart (visual only) ---------- */
  $(document).on('click', '.op-card .wish', function () { this.classList.toggle('on'); });

  /* ---------- Reveal on scroll ---------- */
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: 0.1 });
    document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
  } else {
    document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('in'); });
  }

  /* ---------- Recent purchase notification (real orders, every N seconds) ---------- */
  var pop = document.getElementById('salePop');
  if (pop && S.popup && S.ordersUrl) {
    var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
    var ago = function (m) {
      if (m < 2) return S.i18n.justNow;
      if (m < 120) return m + ' ' + S.i18n.minsAgo;
      if (m < 2880) return Math.round(m / 60) + ' ' + S.i18n.hoursAgo;
      return Math.round(m / 1440) + ' ' + S.i18n.daysAgo;
    };
    var closed = false;
    try { closed = sessionStorage.getItem('op-pop-closed') === '1'; } catch (e) {}
    var list = [], n = 0;
    var tick = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>';
    var show = function () {
      if (closed || !list.length) return;
      var o = list[n++ % list.length];
      var body = '<b>' + esc(o.name) + '</b>' + (o.city ? ' ' + S.i18n.from + ' ' + esc(o.city) : '') + ' ' + S.i18n.purchased +
        '<a href="' + esc(o.url) + '">' + esc(o.product) + '</a>' +
        '<small>' + tick + S.i18n.verified + ' · ' + ago(o.mins) + '</small>';
      pop.innerHTML = '<img src="' + esc(o.image) + '" alt=""><div class="t">' + body + '</div>' +
        '<button class="x" type="button" aria-label="Close">✕</button>';
      pop.classList.add('show');
      pop.querySelector('.x').onclick = function () {
        closed = true; pop.classList.remove('show');
        try { sessionStorage.setItem('op-pop-closed', '1'); } catch (e) {}
      };
      setTimeout(function () { pop.classList.remove('show'); }, 6000);
    };
    // Real orders only (newest first).
    var build = function (d) {
      return (d && Array.isArray(d.orders)) ? d.orders : (Array.isArray(d) ? d : []);
    };
    var load = function () {
      return fetch(S.ordersUrl, { credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : []; })
        .then(function (d) {
          list = build(d); n = 0;
          list.forEach(function (o) { var im = new Image(); im.src = o.image; }); // preload, so the picture is ready when it pops up
        })
        .catch(function () { list = []; });
    };
    if (!closed) {
      setTimeout(function () {
        load().then(function () {
          // wait until the first picture has loaded (max 3 s) so it never pops up empty
          var first = list[0] ? new Image() : null, started = false;
          var start = function () {
            if (started) return; started = true;
            show();
            setInterval(show, (S.interval || 25) * 1000);
          };
          if (first) { first.onload = first.onerror = start; first.src = list[0].image; if (first.complete) start(); }
          setTimeout(start, 3000);
          setInterval(load, 10 * 60 * 1000); // pick up new orders every 10 minutes
        });
      }, (S.firstDelay || 6) * 1000);
    }
  }
})(jQuery);
