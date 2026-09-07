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

/* ===== booking page: mo-kwenta sa total samtang mag-usab ang petsa ===== */
(function () {
  const box = document.getElementById('book-total');
  if (!box) return;                       // dili booking page, undang na

  const rate     = Number(box.dataset.rate) || 0;
  const delivFee = Number(box.dataset.delivery) || 0;
  const amount   = document.getElementById('total-amount');
  const pickup   = document.getElementById('pickup_date');
  const ret      = document.getElementById('return_date');
  const deliv    = document.querySelector('input[name="delivery"]');

  /* linya sa ubos sa total nga mo-ingon pila ka adlaw */
  const note = document.createElement('span');
  note.className = 'book-days';
  amount.parentNode.insertBefore(note, amount.nextSibling);

  function peso(n) {
    return '\u20B1' + n.toLocaleString('en-PH');
  }

  function recalc() {
    const from = new Date(pickup.value);
    const to   = new Date(ret.value);

    /* invalid o baliktad ang petsa, i-zero lang */
    if (!pickup.value || !ret.value || isNaN(from) || isNaN(to) || to <= from) {
      amount.textContent = peso(0);
      note.textContent   = '';
      return;
    }

    const ms   = to - from;
    const days = Math.round(ms / 86400000);   // 86400000 ms = usa ka adlaw
    let total  = days * rate;
    if (deliv && deliv.checked) total += delivFee;

    amount.textContent = peso(total);
    note.textContent   = days + (days === 1 ? ' day' : ' days') + ' \u00D7 ' + peso(rate);
  }

  /* dili pwede mag-pili ug petsa nga lumabay na */
  const today = new Date().toISOString().slice(0, 10);
  pickup.min = today;
  ret.min    = today;

  /* kung mausab ang pickup, ang return dili pwede mas sayo pa niini */
  pickup.addEventListener('change', function () {
    ret.min = pickup.value || today;
    recalc();
  });

  ret.addEventListener('change', recalc);
  if (deliv) deliv.addEventListener('change', recalc);

  recalc();   // sa pag-load, basin naay daan nga input
})();
