(function () {
    'use strict';
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
    var store = {
        get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
        set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) { } }
    };
    var lockScroll = function (on) { document.documentElement.style.overflow = on ? 'hidden' : ''; };

    /* Header gölgesi ve yukarı çık butonu */
    var header = $('[data-header]');
    var toTop = $('.to-top');
    var onScroll = function () {
        var y = window.scrollY;
        if (header) header.classList.toggle('is-stuck', y > 10);
        if (toTop) toTop.classList.toggle('is-visible', y > 700);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    if (toTop) toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    /* Mobil menü */
    var drawer = $('[data-drawer]');
    var openBtn = $('[data-drawer-open]');
    var closeDrawer = function () { if (!drawer) return; drawer.hidden = true; lockScroll(false); if (openBtn) openBtn.setAttribute('aria-expanded', 'false'); };
    if (drawer && openBtn) {
        openBtn.addEventListener('click', function () { drawer.hidden = false; lockScroll(true); openBtn.setAttribute('aria-expanded', 'true'); });
        $('[data-drawer-close]').addEventListener('click', closeDrawer);
        drawer.addEventListener('click', function (e) { if (e.target === drawer) closeDrawer(); });
        $$('.drawer__tog', drawer).forEach(function (b) {
            b.addEventListener('click', function () {
                var li = b.closest('li');
                var open = li.classList.toggle('is-open');
                b.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });
    }

    /* Arama katmanı */
    var search = $('[data-search]');
    var closeSearch = function () { if (search) { search.hidden = true; lockScroll(false); } };
    $$('[data-search-open]').forEach(function (b) {
        b.addEventListener('click', function () { search.hidden = false; lockScroll(true); setTimeout(function () { $('input', search).focus(); }, 30); });
    });
    if (search) {
        $('[data-search-close]', search).addEventListener('click', closeSearch);
        search.addEventListener('click', function (e) { if (e.target === search) closeSearch(); });
    }

    /* Teklif / numune modalı */
    var modal = $('[data-modal]');
    var texts = {
        sample: ['Numune talebi', 'Ücretsiz numune isteyin'],
        quote: ['Teklif talebi', 'Fiyat teklifi isteyin']
    };
    var openModal = function (type, pid, pname) {
        if (!modal) return;
        type = texts[type] ? type : 'quote';
        $('[data-qm-type]', modal).value = type;
        $('[data-qm-eyebrow]', modal).textContent = texts[type][0];
        $('[data-qm-title]', modal).textContent = texts[type][1];
        $('[data-qm-pid]', modal).value = pid || '';
        var prod = $('[data-qm-product]', modal);
        prod.hidden = !pname;
        prod.textContent = pname || '';
        closeDrawer();
        modal.hidden = false;
        lockScroll(true);
        setTimeout(function () { var f = $('input[name=name]', modal); if (f) f.focus(); }, 40);
    };
    var closeModal = function () { if (modal) { modal.hidden = true; lockScroll(false); } };
    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-quote]');
        if (t) { e.preventDefault(); openModal(t.getAttribute('data-quote'), t.getAttribute('data-pid'), t.getAttribute('data-pname')); }
    });
    if (modal) {
        $('[data-modal-close]', modal).addEventListener('click', closeModal);
        modal.addEventListener('mousedown', function (e) { if (e.target === modal) closeModal(); });
        var re = modal.getAttribute('data-reopen');
        if (re) {
            var pid = modal.getAttribute('data-reopen-pid');
            var src = pid && pid !== '0' ? $('[data-quote][data-pid="' + pid + '"]') : null;
            openModal(re, pid !== '0' ? pid : '', src ? src.getAttribute('data-pname') : '');
        }
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeModal(); closeSearch(); closeDrawer(); closeFilters(); }
    });

    /* Toast */
    $$('.toast').forEach(function (t, i) {
        var kill = function () { t.style.transition = 'opacity .3s'; t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 300); };
        $('.toast__x', t).addEventListener('click', kill);
        setTimeout(kill, 6000 + i * 500);
    });

    /* Hero slider */
    var slider = $('[data-slider]');
    if (slider) {
        var slides = $$('[data-slide]', slider);
        var dots = $$('[data-dot]', slider);
        var idx = 0, timer = null, ms = 6000;
        slider.style.setProperty('--slide-ms', ms + 'ms');
        var go = function (n) {
            if (slides.length < 2) return;
            slides[idx].classList.remove('is-active');
            slides[idx].setAttribute('aria-hidden', 'true');
            if (dots[idx]) dots[idx].classList.remove('is-active');
            idx = (n + slides.length) % slides.length;
            slides[idx].classList.add('is-active');
            slides[idx].setAttribute('aria-hidden', 'false');
            if (dots[idx]) { void dots[idx].offsetWidth; dots[idx].classList.add('is-active'); }
            restart();
        };
        var restart = function () { clearTimeout(timer); timer = setTimeout(function () { go(idx + 1); }, ms); };
        dots.forEach(function (d) { d.addEventListener('click', function () { go(+d.getAttribute('data-dot')); }); });
        var p = $('[data-prev]', slider), nx = $('[data-next]', slider);
        if (p) p.addEventListener('click', function () { go(idx - 1); });
        if (nx) nx.addEventListener('click', function () { go(idx + 1); });
        var sx = null;
        slider.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
        slider.addEventListener('touchend', function (e) {
            if (sx === null) return;
            var dx = e.changedTouches[0].clientX - sx;
            if (Math.abs(dx) > 50) go(idx + (dx < 0 ? 1 : -1));
            sx = null;
        });
        if (slides.length > 1) restart();
    }

    /* Yatay ray kontrolleri */
    $$('[data-rail]').forEach(function (rail) {
        var sec = rail.closest('section');
        var step = function (dir) { rail.scrollBy({ left: dir * rail.clientWidth * 0.8, behavior: 'smooth' }); };
        var pv = sec && $('[data-rail-prev]', sec), nv = sec && $('[data-rail-next]', sec);
        if (pv) pv.addEventListener('click', function () { step(-1); });
        if (nv) nv.addEventListener('click', function () { step(1); });
    });

    /* Ürün galerisi */
    var gal = $('[data-gallery]');
    if (gal) {
        var main = $('[data-gallery-main]', gal);
        $$('.gallery__thumb', gal).forEach(function (b) {
            b.addEventListener('click', function () {
                $$('.gallery__thumb', gal).forEach(function (x) { x.classList.remove('is-active'); });
                b.classList.add('is-active');
                main.style.opacity = '0';
                setTimeout(function () { main.src = b.getAttribute('data-src'); main.style.opacity = '1'; }, 150);
            });
        });
    }

    /* Sekmeler */
    $$('[data-tabs]').forEach(function (box) {
        $$('[data-tab]', box).forEach(function (b) {
            b.addEventListener('click', function () {
                $$('[data-tab]', box).forEach(function (x) { x.classList.toggle('is-active', x === b); });
                $$('[data-panel]', box).forEach(function (p) { p.classList.toggle('is-active', p.getAttribute('data-panel') === b.getAttribute('data-tab')); });
            });
        });
    });

    /* Filtreler (mobilde çekmece) */
    var filters = $('[data-filters]');
    function closeFilters() { if (filters && filters.classList.contains('is-open')) { filters.classList.remove('is-open'); lockScroll(false); } }
    var fo = $('[data-filters-open]');
    if (filters && fo) {
        fo.addEventListener('click', function () { filters.classList.add('is-open'); lockScroll(true); });
        $('[data-filters-close]', filters).addEventListener('click', closeFilters);
    }
    $$('form[data-autosubmit]').forEach(function (f) {
        $$('input[type=checkbox]', f).forEach(function (c) { c.addEventListener('change', function () { f.submit(); }); });
    });
    $$('[data-autosubmit-select]').forEach(function (s) { s.addEventListener('change', function () { s.form.submit(); }); });

    /* Izgara / liste görünümü */
    var grid = $('[data-view-target]');
    if (grid) {
        var setView = function (v) {
            grid.classList.toggle('is-list', v === 'list');
            $$('[data-view]').forEach(function (b) { b.classList.toggle('is-active', b.getAttribute('data-view') === v); });
            store.set('np-view', v);
        };
        $$('[data-view]').forEach(function (b) { b.addEventListener('click', function () { setView(b.getAttribute('data-view')); }); });
        if (store.get('np-view') === 'list' && window.innerWidth > 760) setView('list');
    }

    /* Sayaçlar ve görünür olunca beliren bölümler */
    var countUp = function (el) {
        var raw = el.getAttribute('data-count') || '';
        var m = raw.match(/^([^\d]*)([\d.,]+)(.*)$/);
        if (!m) return;
        var target = parseFloat(m[2].replace(/\./g, '').replace(',', '.'));
        if (!isFinite(target)) return;
        var useDots = m[2].indexOf('.') > -1;
        var t0 = null, dur = 1400;
        var fmt = function (n) { var s = Math.round(n).toString(); return useDots ? s.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : s; };
        var tick = function (ts) {
            if (!t0) t0 = ts;
            var k = Math.min(1, (ts - t0) / dur);
            el.textContent = m[1] + fmt(target * (1 - Math.pow(1 - k, 3))) + m[3];
            if (k < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    };
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (!en.isIntersecting) return;
                var el = en.target;
                if (el.hasAttribute('data-count')) countUp(el);
                else {
                    el.classList.add('is-in');
                    // Animasyon bitince sınıfları kaldır; kartların kendi hover efektleri çalışsın.
                    setTimeout(function () { el.classList.remove('reveal', 'is-in'); }, 800);
                }
                io.unobserve(el);
            });
        }, { threshold: 0.15 });
        $$('[data-count]').forEach(function (el) { io.observe(el); });
        $$('.sec-head, .cat-card, .sector, .process__step, .ncard, .about__media, .about__text').forEach(function (el) {
            el.classList.add('reveal');
            io.observe(el);
        });
    }

    /* Çerez bildirimi */
    var cookie = $('.cookie');
    if (cookie && !store.get('np-cookie')) {
        cookie.hidden = false;
        $('[data-cookie-ok]', cookie).addEventListener('click', function () { store.set('np-cookie', '1'); cookie.hidden = true; });
    }
})();
