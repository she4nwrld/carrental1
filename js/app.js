/* usa ra ka slider function, gamiton sa vehicles ug sa reviews */
(function () {
  'use strict';

  var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* i-parehas ni sa media queries sa css */
  function perView() {
    if (window.innerWidth <= 640) return 1;
    if (window.innerWidth <= 980) return 2;
    return 3;
  }

  function slider(opt) {
    var root     = document.getElementById(opt.root);
    var row      = document.getElementById(opt.row);
    var dotBox   = document.getElementById(opt.dots);
    var controls = document.getElementById(opt.controls);
    if (!root || !row) return;

    var cards  = row.querySelectorAll(opt.card);
    var arrows = root.querySelectorAll('[data-dir]');
    var page = 0, timer = null, hold = null, i;

    /* walay i-slide, so tangtangon ang arrows ug ang dot row */
    if (cards.length === 0) {
      for (i = 0; i < arrows.length; i++) { arrows[i].hidden = true; }
      if (controls) { controls.hidden = true; }
      return;
    }

    function pages() {
      return Math.ceil(cards.length / perView());
    }

    function gap() {
      var style = window.getComputedStyle(row);
      return parseFloat(style.columnGap || style.gap) || 0;
    }

    /* usa ka card slot = ang card apil ang gap sunod niini */
    function slide() {
      var per  = perView();
      var step = cards[0].getBoundingClientRect().width + gap();
      row.style.transform = 'translateX(-' + (page * per * step) + 'px)';

      /* ang cards nga wala sa screen dili dapat makuha sa tab key */
      for (var c = 0; c < cards.length; c++) {
        var on = c >= page * per && c < (page + 1) * per;
        cards[c].setAttribute('aria-hidden', on ? 'false' : 'true');
        var link = cards[c].querySelector('a');
        if (link) { link.tabIndex = on ? 0 : -1; }
      }

      if (dotBox) {
        for (var d = 0; d < dotBox.children.length; d++) {
          dotBox.children[d].className = 'dot' + (d === page ? ' on' : '');
        }
      }
    }

    function buildDots() {
      if (!dotBox) return;
      dotBox.innerHTML = '';
      for (var d = 0; d < pages(); d++) {
        var dot = document.createElement('span');
        dot.className = 'dot' + (d === page ? ' on' : '');
        dotBox.appendChild(dot);
      }
    }

    function goTo(next) {
      var total = pages();
      page = (next + total) % total;   /* mo-wrap sa duha ka direksyon */
      slide();
    }

    function stop() {
      if (timer) { window.clearInterval(timer); timer = null; }
    }

    function start() {
      stop();
      if (calm || pages() < 2) return;
      timer = window.setInterval(function () { goTo(page + 1); }, opt.delay);
    }

    function setup() {
      var many = pages() > 1;

      /* usa ra ka page sa cards, so walay buhat ang arrows ug dots */
      for (var a = 0; a < arrows.length; a++) { arrows[a].disabled = !many; }
      if (controls) { controls.hidden = !many; }

      if (page > pages() - 1) { page = pages() - 1; }
      buildDots();
      slide();

      if (many) { start(); } else { stop(); }
    }

    for (i = 0; i < arrows.length; i++) {
      arrows[i].addEventListener('click', function () {
        goTo(this.dataset.dir === 'next' ? page + 1 : page - 1);
        start();   /* ang pag-klik mo-reset sa oras para dili mo-jump dayon */
      });
    }

    /* mo-hunong kung naa nagbasa o nag-tab sa usa ka card */
    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) { stop(); } else { start(); }
    });

    window.addEventListener('resize', function () {
      window.clearTimeout(hold);
      hold = window.setTimeout(setup, 150);
    });

    setup();
  }

  slider({ root: 'carCarousel', row: 'carRow', dots: 'carDots',
           controls: 'carControls', card: '.car', delay: 5000 });

  slider({ root: 'rvCarousel',  row: 'rvRow',  dots: 'rvDots',
           controls: 'rvControls', card: '.rv', delay: 6000 });

})();

/* ===== booking page: live total ===== */
(function () {
  'use strict';

  const form = document.getElementById('bookForm');
  if (!form) return;

  const rate     = Number(form.dataset.rate) || 0;
  const delivFee = Number(form.dataset.delivery) || 0;

  let promos = {};
  try { promos = JSON.parse(form.dataset.promos || '{}'); } catch (err) { promos = {}; }

  const pickup = document.getElementById('pickup_date');
  const ret    = document.getElementById('return_date');
  const code   = document.getElementById('discount_code');
  const deliv  = form.querySelector('input[name="delivery"]');

  const elDays  = document.getElementById('q-days');
  const elSub   = document.getElementById('q-sub');
  const elDisc  = document.getElementById('q-disc');
  const elDel   = document.getElementById('q-del');
  const rowDisc = document.getElementById('q-disc-row');
  const rowDel  = document.getElementById('q-del-row');
  const amount  = document.getElementById('total-amount');

  if (!pickup || !ret || !amount) return;


  const now0  = new Date();
  const today = now0.getFullYear() + '-'
              + String(now0.getMonth() + 1).padStart(2, '0') + '-'
              + String(now0.getDate()).padStart(2, '0');

  function peso(n) {
    return '\u20B1' + Math.round(n).toLocaleString('en-PH');
  }

  function ymd(d) {
    return d.getFullYear() + '-'
         + String(d.getMonth() + 1).padStart(2, '0') + '-'
         + String(d.getDate()).padStart(2, '0');
  }

  function midnight(value) {
    if (!value) return null;
    const d = new Date(value + 'T00:00:00');
    return isNaN(d) ? null : d;
  }

  /* ---------- same rules as quotePrice() in helpers.php ---------- */
  function activeCodes(days, advance) {
    if (!code || !code.value.trim()) return [];

    const picked = code.value.toUpperCase()
      .split(/[\s,+;]+/)
      .filter(Boolean)
      .filter(function (c, i, arr) { return arr.indexOf(c) === i; })
      .slice(0, 2)
      .map(function (c) {
        return promos[c] ? Object.assign({ code: c }, promos[c]) : null;
      });

    if (picked.indexOf(null) !== -1) return [];

    const blocked = picked.some(function (p) {
      if (picked.length > 1 && !p.stackable) return true;
      if (p.minDays > 0 && days > 0 && days < p.minDays) return true;
      if (p.minAdv > 0 && advance < p.minAdv) return true;
      return false;
    });

    return blocked ? [] : picked;
  }

  function recalc() {
    const from = midnight(pickup.value);
    const to   = midnight(ret.value);
    const now  = midnight(today);

    let days = 0;
    if (from && to && to > from) {
      days = Math.round((to - from) / 86400000);
    }

    const advance = from ? Math.round((from - now) / 86400000) : -1;
    const live    = activeCodes(days, advance);

    let billable = days;
    let free = 0;
    live.forEach(function (p) { free += p.freeDays; });
    if (free > 0 && days > 1) {
      billable = days - Math.min(free, days - 1);
    }

    const sub = rate * billable;

    let pct = 0;
    live.forEach(function (p) { pct += p.percent; });
    if (pct > 50) pct = 50;
    const disc = Math.round(sub * pct / 100);

    const del = (deliv && deliv.checked) ? delivFee : 0;

    if (elDays) elDays.textContent = billable + (billable === 1 ? ' day' : ' days');
    if (elSub)  elSub.textContent  = peso(sub);
    if (elDisc) elDisc.textContent = '\u2212' + peso(disc);
    if (elDel)  elDel.textContent  = peso(del);

    if (rowDisc) rowDisc.hidden = disc === 0;
    if (rowDel)  rowDel.hidden  = del === 0;

    amount.textContent = peso(Math.max(0, sub - disc) + del);
  }

  /* ---------- date guards ---------- */
  pickup.min = today;
  if (!ret.min) ret.min = today;

  pickup.addEventListener('change', function () {
    ret.min = pickup.value || today;

    if (pickup.value && ret.value && ret.value <= pickup.value) {
      const next = midnight(pickup.value);
      next.setDate(next.getDate() + 1);
      ret.value = ymd(next);
    }
    recalc();
  });

  ret.addEventListener('change', recalc);
  if (deliv) deliv.addEventListener('change', recalc);
  if (code) {
    code.addEventListener('input', recalc);
    code.addEventListener('change', recalc);
  }

  recalc();
})();
