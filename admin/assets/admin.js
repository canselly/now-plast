(function () {
    'use strict';
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
    var csrf = ($('meta[name=csrf]') || {}).content || '';
    var endpoint = function (r) { return location.pathname + '?r=' + r; };

    var snackTimer;
    var snack = function (msg, isErr) {
        var s = $('[data-snack]');
        if (!s) return;
        s.textContent = msg;
        s.classList.toggle('is-error', !!isErr);
        s.hidden = false;
        clearTimeout(snackTimer);
        snackTimer = setTimeout(function () { s.hidden = true; }, 2600);
    };
    var post = function (r, body) {
        return fetch(endpoint(r), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf, 'X-Requested-With': 'fetch' },
            credentials: 'same-origin',
            body: JSON.stringify(body)
        }).then(function (res) {
            return res.json().catch(function () { return { ok: false, error: 'Sunucu yanıtı okunamadı.' }; });
        });
    };

    /* Mobil yan menü */
    var side = $('[data-side]');
    $$('[data-side-open]').forEach(function (b) { b.addEventListener('click', function () { side.classList.add('is-open'); }); });
    $$('[data-side-close]').forEach(function (b) { b.addEventListener('click', function () { side.classList.remove('is-open'); }); });

    /* Silme onayı */
    document.addEventListener('submit', function (e) {
        var msg = e.target.getAttribute('data-confirm');
        if (msg && !confirm(msg)) e.preventDefault();
    });

    /* Mesaj satırına tıklayınca aç */
    $$('tr[data-href]').forEach(function (tr) {
        tr.addEventListener('click', function (e) { if (!e.target.closest('a,button,form')) location.href = tr.getAttribute('data-href'); });
    });

    /* Aç / kapa anahtarları (yayında, öne çıkan…) */
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-toggle]');
        if (!b) return;
        e.preventDefault();
        b.classList.add('is-busy');
        post('toggle', { entity: b.getAttribute('data-toggle'), id: +b.getAttribute('data-id'), field: b.getAttribute('data-field') })
            .then(function (j) {
                b.classList.remove('is-busy');
                if (!j.ok) return snack(j.error || 'Kaydedilemedi', true);
                b.classList.toggle('is-on', !!j.value);
                b.setAttribute('aria-pressed', j.value ? 'true' : 'false');
                snack(j.value ? 'Açıldı' : 'Kapatıldı');
            })
            .catch(function () { b.classList.remove('is-busy'); snack('Bağlantı hatası', true); });
    });

    /* Düz listelerde sürükle-bırak sıralama */
    if (window.Sortable) {
        $$('[data-sort-flat]').forEach(function (tbody) {
            Sortable.create(tbody, {
                handle: '.handle', animation: 160,
                onEnd: function (ev) {
                    if (ev.oldIndex === ev.newIndex) return;
                    var ids = $$('tr[data-id]', tbody).map(function (tr) { return +tr.getAttribute('data-id'); });
                    post('sort', { entity: tbody.getAttribute('data-sort-flat'), ids: ids }).then(function (j) {
                        snack(j.ok ? 'Sıralama kaydedildi' : (j.error || 'Hata'), !j.ok);
                    });
                }
            });
        });

        /* Ağaç (kategori, menü): iç içe sürükle-bırak */
        $$('[data-sort-tree]').forEach(function (box) {
            var entity = box.getAttribute('data-sort-tree');
            var save = function () {
                var lists = $$('ul.tree', box).map(function (ul) {
                    return {
                        parent: +ul.getAttribute('data-parent'),
                        ids: Array.prototype.slice.call(ul.children).map(function (li) { return +li.getAttribute('data-id'); })
                    };
                });
                post('sort', { entity: entity, lists: lists }).then(function (j) {
                    snack(j.ok ? 'Sıralama kaydedildi' : (j.error || 'Hata'), !j.ok);
                });
            };
            $$('ul.tree', box).forEach(function (ul) {
                Sortable.create(ul, {
                    group: ul.getAttribute('data-group'), handle: '.handle', animation: 160, fallbackOnBody: true, swapThreshold: 0.6,
                    onStart: function () { document.body.classList.add('is-dragging'); },
                    onEnd: function (ev) {
                        document.body.classList.remove('is-dragging');
                        if (ev.from === ev.to && ev.oldIndex === ev.newIndex) return;
                        save();
                    }
                });
            });
        });

        /* Ürün galerisi sıralama */
        $$('[data-gallery-sort]').forEach(function (g) {
            var input = g.parentNode.querySelector('input[name=gallery_order]');
            Sortable.create(g, {
                animation: 160,
                onEnd: function () {
                    input.value = $$('.gal__item', g).map(function (x) { return x.getAttribute('data-id'); }).join(',');
                    markDirty();
                }
            });
        });
    }

    /* Otomatik URL (slug) */
    var slugify = function (s) {
        var map = { 'ş': 's', 'Ş': 's', 'ı': 'i', 'İ': 'i', 'ğ': 'g', 'Ğ': 'g', 'ü': 'u', 'Ü': 'u', 'ö': 'o', 'Ö': 'o', 'ç': 'c', 'Ç': 'c' };
        return s.replace(/[şŞıİğĞüÜöÖçÇ]/g, function (c) { return map[c]; }).toLowerCase()
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 180);
    };
    $$('[data-slug-from]').forEach(function (slug) {
        var src = document.getElementById(slug.getAttribute('data-slug-from'));
        if (!src) return;
        var touched = slug.hasAttribute('data-slug-locked') || slug.value !== '';
        slug.addEventListener('input', function () { touched = slug.value !== ''; });
        src.addEventListener('input', function () { if (!touched) slug.value = slugify(src.value); });
    });

    /* Menü bağlantı seçici */
    $$('[data-linkpick]').forEach(function (box) {
        var type = $('select[name=link_type]', box);
        var sync = function () {
            $$('[data-for]', box).forEach(function (el) { el.hidden = el.getAttribute('data-for') !== type.value; });
        };
        type.addEventListener('change', sync);
        sync();
    });

    /* Renk seçici */
    $$('[data-color-for]').forEach(function (picker) {
        var txt = document.getElementById(picker.getAttribute('data-color-for'));
        picker.addEventListener('input', function () { txt.value = picker.value; markDirty(); });
        txt.addEventListener('input', function () { if (/^#[0-9a-f]{6}$/i.test(txt.value)) picker.value = txt.value; });
    });

    /* Dosya seçimi göstergesi ve sürükle-bırak */
    $$('.upl__drop').forEach(function (drop) {
        var input = $('input[type=file]', drop);
        var label = $('span', drop);
        var orig = label.textContent;
        input.addEventListener('change', function () {
            var n = input.files.length;
            drop.classList.toggle('has-file', n > 0);
            label.textContent = n === 0 ? orig : (n === 1 ? input.files[0].name : n + ' dosya seçildi');
        });
        ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.add('is-over'); }); });
        ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.remove('is-over'); }); });
    });

    /* Ayarlar sekmeleri */
    var tabsForm = $('[data-tabs-form]');
    if (tabsForm) {
        var show = function (k) {
            $$('[data-tab-link]', tabsForm).forEach(function (a) { a.classList.toggle('is-active', a.getAttribute('data-tab-link') === k); });
            $$('[data-tab-panel]', tabsForm).forEach(function (p) { p.hidden = p.getAttribute('data-tab-panel') !== k; });
            $('[data-tab-input]', tabsForm).value = k;
        };
        $$('[data-tab-link]', tabsForm).forEach(function (a) {
            a.addEventListener('click', function (e) { e.preventDefault(); show(a.getAttribute('data-tab-link')); history.replaceState(null, '', '#' + a.getAttribute('data-tab-link')); });
        });
        var h = location.hash.slice(1);
        if (h && $('[data-tab-panel="' + h + '"]', tabsForm)) show(h);
    }

    /* Zengin metin editörü (Quill) */
    var editors = [];
    if (window.Quill) {
        $$('textarea.js-editor').forEach(function (ta) {
            var holder = document.createElement('div');
            ta.parentNode.insertBefore(holder, ta);
            ta.hidden = true;
            var q = new Quill(holder, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ header: [2, 3, false] }],
                        ['bold', 'italic', 'underline', 'link'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['blockquote'],
                        ['clean']
                    ]
                }
            });
            if (ta.value) q.clipboard.dangerouslyPasteHTML(ta.value);
            q.on('text-change', markDirty);
            editors.push([q, ta]);
        });
    }

    /* Kaydedilmemiş değişiklik uyarısı */
    var dirty = false;
    function markDirty() { dirty = true; }
    $$('form[data-dirty-guard]').forEach(function (f) {
        f.addEventListener('input', markDirty);
        f.addEventListener('change', markDirty);
        f.addEventListener('submit', function () {
            editors.forEach(function (pair) {
                var q = pair[0], ta = pair[1];
                var html = q.root.innerHTML;
                ta.value = (q.getText().trim() === '' && html.indexOf('<img') === -1) ? '' : html;
            });
            dirty = false;
        });
    });
    window.addEventListener('beforeunload', function (e) {
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });
})();
