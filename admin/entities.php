<?php
/**
 * Admin panelinde yönetilen içerik türleri.
 * Her tür için liste sütunları ve form alanları burada tanımlanır; ortak CRUD motoru (lib.php) bunları kullanır.
 *
 * Alan tipleri: text, textarea, html, number, email, url, date, select, checkbox, slug, image, file,
 *               multicheck (ara tablo), gallery (ürün görselleri), password, menulink, color
 */

function opt_category_tree(): array
{
    $all = rows('SELECT id, parent_id, name FROM categories ORDER BY sort_order, name');
    $map = [];
    foreach ($all as $c) {
        $map[(int) ($c['parent_id'] ?? 0)][] = $c;
    }
    $out = [];
    $walk = function (int $pid, int $depth) use (&$walk, &$out, $map) {
        foreach ($map[$pid] ?? [] as $c) {
            $out[$c['id']] = str_repeat('— ', $depth) . $c['name'];
            $walk((int) $c['id'], $depth + 1);
        }
    };
    $walk(0, 0);
    return $out;
}

function opt_menu_parents(): array
{
    $out = [];
    foreach (rows('SELECT id, title, location FROM menu_items WHERE parent_id IS NULL OR parent_id = 0 ORDER BY location, sort_order') as $m) {
        $out[$m['id']] = ($m['location'] === 'footer' ? 'Alt menü › ' : 'Üst menü › ') . $m['title'];
    }
    return $out;
}

function opt_pairs(string $sql): array
{
    $out = [];
    foreach (rows($sql) as $r) {
        $out[$r['id']] = $r['name'];
    }
    return $out;
}

function entities(): array
{
    static $e = null;
    if ($e !== null) {
        return $e;
    }
    $seo = [
        ['name' => 'meta_title', 'label' => 'SEO başlığı', 'type' => 'text', 'group' => 'seo', 'help' => 'Boş bırakılırsa ad kullanılır.'],
        ['name' => 'meta_description', 'label' => 'SEO açıklaması', 'type' => 'textarea', 'rows' => 2, 'group' => 'seo', 'help' => 'Google sonuçlarında görünen kısa açıklama (≈155 karakter).'],
    ];

    $e = [
        'products' => [
            'title' => 'Ürünler', 'singular' => 'Ürün', 'icon' => 'cube',
            'table' => 'products', 'order' => 'sort_order ASC, id DESC',
            'sortable' => true, 'copyable' => true, 'per_page' => 100,
            'search' => ['name', 'code'],
            'filters' => ['category_id' => ['label' => 'Tüm kategoriler', 'options' => 'opt_category_tree', 'descendants' => true]],
            'list' => [
                ['image', '', 'image'],
                ['code', 'Kod', 'mono'],
                ['name', 'Ürün adı', 'title'],
                ['category_id', 'Kategori', 'lookup:opt_category_tree'],
                ['volume_ml', 'Hacim', 'volume'],
                ['is_featured', 'Öne çıkan', 'toggle'],
                ['is_active', 'Yayında', 'toggle'],
            ],
            'timestamps' => true,
            'view_url' => fn($r) => url('urun/' . $r['slug']),
            'fields' => array_merge([
                ['name' => 'name', 'label' => 'Ürün adı', 'type' => 'text', 'required' => true],
                ['name' => 'code', 'label' => 'Ürün kodu', 'type' => 'text', 'half' => true, 'placeholder' => 'ör. NP-1000'],
                ['name' => 'slug', 'label' => 'Adres (URL)', 'type' => 'slug', 'from' => 'name', 'half' => true],
                ['name' => 'summary', 'label' => 'Kısa açıklama', 'type' => 'textarea', 'rows' => 2],
                ['name' => 'description', 'label' => 'Detaylı açıklama', 'type' => 'html'],

                ['name' => 'volume_ml', 'label' => 'Hacim (ml)', 'type' => 'number', 'group' => 'specs', 'third' => true],
                ['name' => 'height_mm', 'label' => 'Yükseklik (mm)', 'type' => 'number', 'group' => 'specs', 'third' => true],
                ['name' => 'weight_g', 'label' => 'Gramaj (g)', 'type' => 'number', 'group' => 'specs', 'third' => true],
                ['name' => 'top_diameter_mm', 'label' => 'Ağız çapı (mm)', 'type' => 'number', 'group' => 'specs', 'third' => true],
                ['name' => 'bottom_diameter_mm', 'label' => 'Taban çapı (mm)', 'type' => 'number', 'group' => 'specs', 'third' => true],
                ['name' => 'width_mm', 'label' => 'En (mm)', 'type' => 'number', 'group' => 'specs', 'third' => true],
                ['name' => 'length_mm', 'label' => 'Boy (mm)', 'type' => 'number', 'group' => 'specs', 'third' => true],
                ['name' => 'material', 'label' => 'Hammadde', 'type' => 'text', 'group' => 'specs', 'third' => true, 'datalist' => ['PP', 'HDPE', 'LDPE', 'PS', 'PET', 'ABS']],
                ['name' => 'decoration', 'label' => 'Baskı / dekorasyon', 'type' => 'text', 'group' => 'specs', 'third' => true, 'datalist' => ['IML', 'Ofset baskı', 'Serigrafi', 'Baskısız']],
                ['name' => 'lid', 'label' => 'Kapak', 'type' => 'text', 'group' => 'specs', 'half' => true, 'placeholder' => 'ör. Mühürlü kapak, kulplu'],
                ['name' => 'packaging', 'label' => 'Koli içi adet', 'type' => 'text', 'group' => 'specs', 'half' => true],
                ['name' => 'extra_specs', 'label' => 'Ek özellikler', 'type' => 'textarea', 'rows' => 3, 'group' => 'specs', 'help' => 'Her satıra bir özellik: "Özellik: Değer" (ör. Sıcaklık dayanımı: -20 / +90 °C)'],

                ['name' => 'gallery', 'label' => 'Ek görseller', 'type' => 'gallery', 'group' => 'media'],

                ['name' => 'category_id', 'label' => 'Kategori', 'type' => 'select', 'options' => 'opt_category_tree', 'empty' => '— Kategori seçin —', 'side' => true],
                ['name' => 'sectors', 'label' => 'Sektörler', 'type' => 'multicheck', 'side' => true,
                    'pivot' => ['table' => 'product_sectors', 'local' => 'product_id', 'foreign' => 'sector_id'],
                    'options' => fn() => opt_pairs('SELECT id, name FROM sectors ORDER BY sort_order, name')],
                ['name' => 'image', 'label' => 'Ana görsel', 'type' => 'image', 'side' => true],
                ['name' => 'datasheet', 'label' => 'Teknik föy (PDF)', 'type' => 'file', 'side' => true],
                ['name' => 'is_active', 'label' => 'Yayında', 'type' => 'checkbox', 'default' => 1, 'side' => true],
                ['name' => 'is_featured', 'label' => 'Anasayfada öne çıkar', 'type' => 'checkbox', 'side' => true],
                ['name' => 'is_new', 'label' => '"Yeni" etiketi göster', 'type' => 'checkbox', 'side' => true],
            ], $seo),
        ],

        'categories' => [
            'title' => 'Kategoriler', 'singular' => 'Kategori', 'icon' => 'layers',
            'table' => 'categories', 'order' => 'sort_order ASC, name ASC',
            'tree' => true, 'sortable' => true,
            'view_url' => fn($r) => url('urunler/' . $r['slug']),
            'before_delete' => function ($r) {
                $n = (int) val('SELECT COUNT(*) FROM products WHERE category_id = ?', [$r['id']]);
                return $n ? "Bu kategoride $n ürün var. Önce ürünleri başka bir kategoriye taşıyın." : null;
            },
            'fields' => array_merge([
                ['name' => 'name', 'label' => 'Kategori adı', 'type' => 'text', 'required' => true],
                ['name' => 'slug', 'label' => 'Adres (URL)', 'type' => 'slug', 'from' => 'name'],
                ['name' => 'summary', 'label' => 'Kısa açıklama', 'type' => 'textarea', 'rows' => 2, 'help' => 'Kategori sayfasının başlığı altında görünür.'],
                ['name' => 'description', 'label' => 'Detaylı açıklama (sayfa altı)', 'type' => 'html'],
                ['name' => 'parent_id', 'label' => 'Üst kategori', 'type' => 'select', 'options' => 'opt_category_tree', 'empty' => '— Ana kategori —', 'side' => true, 'tree_parent' => true],
                ['name' => 'image', 'label' => 'Görsel', 'type' => 'image', 'side' => true],
                ['name' => 'is_active', 'label' => 'Yayında', 'type' => 'checkbox', 'default' => 1, 'side' => true],
            ], $seo),
        ],

        'sectors' => [
            'title' => 'Sektörler', 'singular' => 'Sektör', 'icon' => 'grid',
            'table' => 'sectors', 'order' => 'sort_order ASC, name ASC', 'sortable' => true,
            'list' => [['icon', '', 'sector_icon'], ['name', 'Sektör', 'title'], ['summary', 'Açıklama', 'muted'], ['is_active', 'Yayında', 'toggle']],
            'view_url' => fn($r) => url('sektor/' . $r['slug']),
            'before_delete' => function ($r) {
                q('DELETE FROM product_sectors WHERE sector_id = ?', [$r['id']]);
                return null;
            },
            'fields' => array_merge([
                ['name' => 'name', 'label' => 'Sektör adı', 'type' => 'text', 'required' => true],
                ['name' => 'slug', 'label' => 'Adres (URL)', 'type' => 'slug', 'from' => 'name'],
                ['name' => 'summary', 'label' => 'Kısa açıklama', 'type' => 'textarea', 'rows' => 2],
                ['name' => 'description', 'label' => 'Detaylı açıklama', 'type' => 'html'],
                ['name' => 'icon', 'label' => 'Simge', 'type' => 'select', 'options' => 'sector_icon_options', 'side' => true],
                ['name' => 'image', 'label' => 'Arka plan görseli', 'type' => 'image', 'side' => true],
                ['name' => 'is_active', 'label' => 'Yayında', 'type' => 'checkbox', 'default' => 1, 'side' => true],
            ], $seo),
        ],

        'pages' => [
            'title' => 'Sayfalar', 'singular' => 'Sayfa', 'icon' => 'doc',
            'table' => 'pages', 'order' => 'sort_order ASC, title ASC', 'sortable' => true,
            'title_field' => 'title', 'reserved_slugs' => true,
            'list' => [['title', 'Başlık', 'title'], ['slug', 'Adres', 'slug'], ['updated_at', 'Güncelleme', 'date'], ['is_active', 'Yayında', 'toggle']],
            'search' => ['title'],
            'view_url' => fn($r) => url($r['slug']),
            'stamp_updated' => true,
            'fields' => array_merge([
                ['name' => 'title', 'label' => 'Sayfa başlığı', 'type' => 'text', 'required' => true],
                ['name' => 'slug', 'label' => 'Adres (URL)', 'type' => 'slug', 'from' => 'title', 'help' => 'Sayfa site.com/adres şeklinde yayınlanır.'],
                ['name' => 'summary', 'label' => 'Giriş metni', 'type' => 'textarea', 'rows' => 2],
                ['name' => 'content', 'label' => 'İçerik', 'type' => 'html'],
                ['name' => 'image', 'label' => 'Yan görsel', 'type' => 'image', 'side' => true],
                ['name' => 'is_active', 'label' => 'Yayında', 'type' => 'checkbox', 'default' => 1, 'side' => true],
            ], $seo),
        ],

        'menu' => [
            'title' => 'Menüler', 'singular' => 'Menü öğesi', 'icon' => 'menu',
            'table' => 'menu_items', 'order' => 'sort_order ASC, id ASC',
            'tree' => true, 'sortable' => true, 'tree_group' => 'location', 'title_field' => 'title',
            'groups' => ['header' => 'Üst menü (header)', 'footer' => 'Alt menü (footer · Kurumsal sütunu)'],
            'fields' => [
                ['name' => 'title', 'label' => 'Menüde görünen ad', 'type' => 'text', 'required' => true],
                ['name' => 'link', 'label' => 'Bağlantı', 'type' => 'menulink'],
                ['name' => 'auto_children', 'label' => 'Alt menüyü otomatik doldur', 'type' => 'select', 'empty' => 'Hayır',
                    'options' => ['categories' => 'Ürün kategorileri (mega menü)', 'sectors' => 'Sektörler'],
                    'help' => 'Seçilirse bu öğenin altında kategoriler veya sektörler otomatik listelenir.'],
                ['name' => 'location', 'label' => 'Menü konumu', 'type' => 'select', 'options' => ['header' => 'Üst menü', 'footer' => 'Alt menü'], 'side' => true, 'default' => 'header'],
                ['name' => 'parent_id', 'label' => 'Üst öğe', 'type' => 'select', 'options' => 'opt_menu_parents', 'empty' => '— En üst seviye —', 'side' => true, 'tree_parent' => true],
                ['name' => 'new_tab', 'label' => 'Yeni sekmede aç', 'type' => 'checkbox', 'side' => true],
                ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox', 'default' => 1, 'side' => true],
            ],
        ],

        'sliders' => [
            'title' => 'Slider', 'singular' => 'Slayt', 'icon' => 'print',
            'table' => 'sliders', 'order' => 'sort_order ASC, id ASC', 'sortable' => true, 'title_field' => 'title',
            'list' => [['image', '', 'image-wide'], ['title', 'Başlık', 'title'], ['subtitle', 'Alt metin', 'muted'], ['is_active', 'Yayında', 'toggle']],
            'fields' => [
                ['name' => 'eyebrow', 'label' => 'Üst etiket', 'type' => 'text', 'placeholder' => 'ör. 25 yıllık tecrübe'],
                ['name' => 'title', 'label' => 'Başlık', 'type' => 'text'],
                ['name' => 'subtitle', 'label' => 'Alt metin', 'type' => 'textarea', 'rows' => 2],
                ['name' => 'button_text', 'label' => 'Buton yazısı', 'type' => 'text', 'half' => true],
                ['name' => 'button_link', 'label' => 'Buton bağlantısı', 'type' => 'text', 'half' => true, 'placeholder' => 'urunler veya https://…'],
                ['name' => 'image', 'label' => 'Arka plan görseli', 'type' => 'image', 'side' => true, 'help' => 'Önerilen: 1920×1000 px, yatay.'],
                ['name' => 'is_active', 'label' => 'Yayında', 'type' => 'checkbox', 'default' => 1, 'side' => true],
            ],
        ],

        'news' => [
            'title' => 'Haberler', 'singular' => 'Haber', 'icon' => 'quote',
            'table' => 'news', 'order' => 'published_at DESC, id DESC', 'title_field' => 'title',
            'list' => [['image', '', 'image'], ['title', 'Başlık', 'title'], ['published_at', 'Tarih', 'date'], ['is_active', 'Yayında', 'toggle']],
            'search' => ['title'],
            'view_url' => fn($r) => url('haber/' . $r['slug']),
            'fields' => array_merge([
                ['name' => 'title', 'label' => 'Başlık', 'type' => 'text', 'required' => true],
                ['name' => 'slug', 'label' => 'Adres (URL)', 'type' => 'slug', 'from' => 'title'],
                ['name' => 'summary', 'label' => 'Özet', 'type' => 'textarea', 'rows' => 2],
                ['name' => 'content', 'label' => 'İçerik', 'type' => 'html'],
                ['name' => 'published_at', 'label' => 'Yayın tarihi', 'type' => 'date', 'side' => true, 'default' => fn() => date('Y-m-d')],
                ['name' => 'image', 'label' => 'Kapak görseli', 'type' => 'image', 'side' => true],
                ['name' => 'is_active', 'label' => 'Yayında', 'type' => 'checkbox', 'default' => 1, 'side' => true],
            ], $seo),
        ],

        'certificates' => [
            'title' => 'Belgeler', 'singular' => 'Belge', 'icon' => 'shield',
            'table' => 'certificates', 'order' => 'sort_order ASC, id ASC', 'sortable' => true, 'title_field' => 'title',
            'list' => [['image', '', 'image'], ['title', 'Belge', 'title'], ['is_active', 'Yayında', 'toggle']],
            'fields' => [
                ['name' => 'title', 'label' => 'Belge adı', 'type' => 'text', 'required' => true, 'placeholder' => 'ör. ISO 9001:2015'],
                ['name' => 'image', 'label' => 'Logo / görsel', 'type' => 'image', 'side' => true],
                ['name' => 'file', 'label' => 'Belge dosyası (PDF)', 'type' => 'file', 'side' => true],
                ['name' => 'is_active', 'label' => 'Yayında', 'type' => 'checkbox', 'default' => 1, 'side' => true],
            ],
        ],

        'clients' => [
            'title' => 'Referanslar', 'singular' => 'Referans', 'icon' => 'check',
            'table' => 'clients', 'order' => 'sort_order ASC, id ASC', 'sortable' => true,
            'list' => [['logo', '', 'image'], ['name', 'Firma', 'title'], ['is_active', 'Yayında', 'toggle']],
            'fields' => [
                ['name' => 'name', 'label' => 'Firma adı', 'type' => 'text', 'required' => true],
                ['name' => 'url', 'label' => 'Web sitesi', 'type' => 'url'],
                ['name' => 'logo', 'label' => 'Logo', 'type' => 'image', 'side' => true, 'help' => 'Logo yoksa firma adı yazı olarak gösterilir.'],
                ['name' => 'is_active', 'label' => 'Yayında', 'type' => 'checkbox', 'default' => 1, 'side' => true],
            ],
        ],

        'users' => [
            'title' => 'Yöneticiler', 'singular' => 'Yönetici', 'icon' => 'shield',
            'table' => 'users', 'order' => 'id ASC', 'title_field' => 'username', 'stamp_created' => true,
            'list' => [['username', 'Kullanıcı adı', 'title'], ['name', 'Ad soyad', 'text'], ['last_login', 'Son giriş', 'date']],
            'before_delete' => function ($r) {
                if ((int) $r['id'] === (int) ($_SESSION['admin_id'] ?? 0)) return 'Kendi hesabınızı silemezsiniz.';
                if ((int) val('SELECT COUNT(*) FROM users') <= 1) return 'Son yönetici silinemez.';
                return null;
            },
            'fields' => [
                ['name' => 'username', 'label' => 'Kullanıcı adı', 'type' => 'text', 'required' => true, 'unique' => true],
                ['name' => 'name', 'label' => 'Ad soyad', 'type' => 'text'],
                ['name' => 'password_hash', 'label' => 'Şifre', 'type' => 'password', 'help' => 'En az 8 karakter. Düzenlerken boş bırakırsanız değişmez.'],
            ],
        ],
    ];

    // Varsayılan liste: ad + yayın durumu
    foreach ($e as $k => &$def) {
        $def['key'] = $k;
        $def['title_field'] = $def['title_field'] ?? 'name';
        if (!isset($def['list'])) {
            $def['list'] = [[$def['title_field'], 'Ad', 'title'], ['is_active', 'Yayında', 'toggle']];
        }
    }
    unset($def);
    return $e;
}
