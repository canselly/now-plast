<?php
/** Kurulumda isteğe bağlı yüklenen örnek içerik. Admin panelinden düzenlenebilir / silinebilir. */

function seed_settings(string $siteName): void
{
    $s = [
        'site_name' => $siteName,
        'site_tagline' => 'Plastik Enjeksiyon & Ambalaj Çözümleri',
        'company_title' => $siteName . ' Plastik Ambalaj San. ve Tic. A.Ş.',
        'meta_description' => 'Plastik enjeksiyon yöntemiyle gıda, boya, kimya ve endüstriyel sektörler için IML baskılı kova, kase, kap ve kapak üretimi.',
        'theme_color' => '#1557ff',
        'address' => "Organize Sanayi Bölgesi, 1. Cadde No: 1\nSelçuklu / Konya",
        'phone' => '+90 (332) 000 00 00',
        'email' => 'info@ornekfirma.com.tr',
        'working_hours' => 'Hafta içi 08:30 – 18:00',
        'whatsapp' => '',
        'home_about_title' => 'Plastikte mühendislik, ambalajda güven',
        'home_about_text' => "Kurulduğumuz günden bu yana plastik enjeksiyon yöntemiyle gıda, boya, kimya ve endüstriyel sektörler için sert plastik ambalajlar üretiyoruz.\n\nKalıp tasarımından IML baskıya, kalite kontrolden sevkiyata kadar tüm süreci kendi tesisimizde yönetiyor; müşterilerimize ölçüye, hijyene ve raf görünümüne özel çözümler sunuyoruz.",
        'stat1_value' => '25+', 'stat1_label' => 'Yıllık tecrübe',
        'stat2_value' => '1.200+', 'stat2_label' => 'Ürün çeşidi',
        'stat3_value' => '40', 'stat3_label' => 'Enjeksiyon makinesi',
        'stat4_value' => '30+', 'stat4_label' => 'İhracat yapılan ülke',
        'products_per_page' => '24',
        'cookie_text' => 'Sitemizde deneyiminizi iyileştirmek için zorunlu çerezler kullanıyoruz.',
    ];
    // Firma logosu proje ile birlikte geliyorsa varsayılan olarak kullanılır.
    if (is_file(ROOT . '/uploads/brand/logo.png')) {
        $s['logo'] = 'brand/logo.png';
        $s['theme_color'] = '#1f73e0';
    }
    if (is_file(ROOT . '/uploads/brand/favicon.png')) {
        $s['favicon'] = 'brand/favicon.png';
    }
    foreach ($s as $k => $v) {
        set_setting($k, $v);
    }
}

function seed_demo(): void
{
    $now = now();

    /* Sektörler */
    $sectors = [
        ['Dondurma', 'icecream', 'Derin dondurucuya dayanıklı, IML baskılı dondurma kapları.'],
        ['Süt Ürünleri', 'milk', 'Yoğurt, peynir ve tereyağı için mühürlü, hijyenik ambalajlar.'],
        ['Boya & Yapı Kimyasalları', 'paint', 'Darbeye dayanıklı, istiflenebilir kulplu kovalar.'],
        ['Kimya & Temizlik', 'clean', 'Kimyasala dayanıklı HDPE ve PP ambalajlar.'],
        ['Gıda & Hazır Yemek', 'food', 'Mikrodalgaya uygun, sızdırmaz gıda kapları.'],
        ['Şekerleme & Lokum', 'candy', 'Raf görünürlüğü yüksek şeffaf ve baskılı kaplar.'],
        ['Kozmetik', 'cosmetic', 'Krem, jöle ve bakım ürünleri için şık kaplar.'],
        ['Deniz Ürünleri', 'fish', 'Salamura ve marine ürünler için sızdırmaz kovalar.'],
    ];
    $sid = [];
    foreach ($sectors as $i => [$name, $icon, $sum]) {
        $sid[$icon] = db_insert('sectors', ['name' => $name, 'slug' => slugify($name), 'icon' => $icon, 'summary' => $sum,
            'description' => '<p>' . e($name) . ' sektörüne yönelik ambalaj çözümlerimiz; gıdaya uygunluk, sızdırmazlık ve istif dayanımı testlerinden geçirilerek üretilir. Ürününüze özel baskı ve kapak seçenekleri için bizimle iletişime geçin.</p>',
            'sort_order' => $i + 1, 'is_active' => 1]);
    }

    /* Kategoriler */
    $cat = function (string $name, ?int $parent, string $img, string $sum, int $order) {
        return db_insert('categories', ['parent_id' => $parent, 'name' => $name, 'slug' => slugify($name), 'summary' => $sum,
            'image' => 'demo/' . $img . '.svg', 'sort_order' => $order, 'is_active' => 1]);
    };
    $gida = $cat('Sert Plastik Ambalajlar', null, 'round', 'Yuvarlak, kare, dikdörtgen ve oval formda IML baskılı gıda ambalajları.', 1);
    $yuv = $cat('Yuvarlak Ambalajlar', $gida, 'round', 'Yüksek sızdırmazlık ve geniş baskı alanı sunan yuvarlak kova ve kaplar.', 1);
    $kare = $cat('Kare Ambalajlar', $gida, 'square', 'Rafta ve kolide alan tasarrufu sağlayan kare formlu kaplar.', 2);
    $dik = $cat('Dikdörtgen Ambalajlar', $gida, 'rect', 'Peynir, tatlı ve hazır yemek için dikdörtgen kaplar.', 3);
    $oval = $cat('Oval Ambalajlar', $gida, 'oval', 'Şık görünümlü, kolay açılır oval ambalajlar.', 4);
    $kase = $cat('IML Kaseler & Kaplar', null, 'cup', 'Dondurma, tatlı ve süt ürünleri için IML etiketli kaseler.', 2);
    $endus = $cat('Endüstriyel Kovalar', null, 'round', 'Boya, kimya ve yapı malzemeleri için kulplu, mühürlü kovalar.', 3);
    $kapak = $cat('Plastik Kapaklar', null, 'lid', 'Mühürlü, kaşıklı ve kilitli kapak seçenekleri.', 4);
    $ozel = $cat('Özel Projeler', null, 'cup', 'Markanıza özel kalıp ve ambalaj tasarımı.', 5);

    /* Ürünler */
    $order = 0;
    $p = function (array $d, array $sectorKeys) use (&$order, $sid, $now) {
        $d += ['material' => 'PP', 'decoration' => 'IML', 'is_active' => 1, 'is_featured' => 0, 'is_new' => 0];
        $d['slug'] = unique_slug('products', slugify(($d['code'] ?? '') . ' ' . $d['name']));
        $d['sort_order'] = ++$order;
        $d['created_at'] = $d['updated_at'] = $now;
        $d['description'] = $d['description'] ?? '<p>Gıdaya uygun, birinci sınıf hammaddeden üretilen ' . e(mb_strtolower($d['name'])) . '; kalıp içi etiketleme (IML) ile fotoğraf kalitesinde baskı imkânı sunar. İstiflenebilir yapısı sayesinde depolama ve sevkiyatta yer tasarrufu sağlar.</p><ul><li>Derin dondurucuya ve mikrodalgaya uygun seçenekler</li><li>Kurcalanmaya karşı mühürlü kapak</li><li>Müşteriye özel renk ve baskı</li></ul>';
        $id = db_insert('products', $d);
        foreach ($sectorKeys as $k) {
            if (isset($sid[$k])) db_insert('product_sectors', ['product_id' => $id, 'sector_id' => $sid[$k]]);
        }
        return $id;
    };

    foreach ([[250, 95, 62, 12], [500, 118, 72, 18], [750, 118, 98, 22], [1000, 140, 102, 28], [2000, 175, 125, 45], [3000, 195, 145, 62], [5000, 228, 178, 95]] as $i => [$v, $dia, $h, $g]) {
        $p(['category_id' => $yuv, 'code' => sprintf('NP-Y%04d', $v), 'name' => fmt_volume($v) . ' Yuvarlak Kova', 'summary' => 'Ø' . $dia . ' mm ağız çaplı, mühürlü kapaklı yuvarlak ambalaj.',
            'volume_ml' => $v, 'top_diameter_mm' => $dia, 'bottom_diameter_mm' => $dia - 18, 'height_mm' => $h, 'weight_g' => $g,
            'lid' => 'Mühürlü kapak' . ($v >= 2000 ? ', plastik kulplu' : ''), 'packaging' => (string) max(40, (int) (12000 / $v * 10)),
            'image' => 'demo/round.svg', 'is_featured' => in_array($v, [500, 1000, 3000], true) ? 1 : 0, 'is_new' => $v === 750 ? 1 : 0],
            $v <= 1000 ? ['milk', 'food', 'candy'] : ['milk', 'food', 'fish']);
    }
    foreach ([[460, 89, 99, 18], [750, 104, 105, 24], [1000, 116, 110, 30], [2000, 146, 132, 48]] as [$v, $w, $h, $g]) {
        $p(['category_id' => $kare, 'code' => sprintf('NP-K%04d', $v), 'name' => fmt_volume($v) . ' Kare Kap', 'summary' => 'Rafta alan tasarrufu sağlayan kare formlu, IML baskılı kap.',
            'volume_ml' => $v, 'width_mm' => $w, 'length_mm' => $w, 'height_mm' => $h, 'weight_g' => $g, 'lid' => 'Mühürlü kapak',
            'image' => 'demo/square.svg', 'is_featured' => $v === 1000 ? 1 : 0], ['milk', 'food', 'icecream']);
    }
    foreach ([[500, 150, 110, 52, 20], [1000, 182, 128, 66, 30], [1750, 212, 150, 78, 42], [2500, 230, 165, 92, 55]] as [$v, $l, $w, $h, $g]) {
        $p(['category_id' => $dik, 'code' => sprintf('NP-D%04d', $v), 'name' => fmt_volume($v) . ' Dikdörtgen Kap', 'summary' => 'Peynir, tatlı ve hazır yemek için dikdörtgen kap.',
            'volume_ml' => $v, 'width_mm' => $w, 'length_mm' => $l, 'height_mm' => $h, 'weight_g' => $g, 'lid' => 'Kilitli kapak',
            'image' => 'demo/rect.svg', 'is_featured' => $v === 1750 ? 1 : 0], ['icecream', 'food', 'candy']);
    }
    foreach ([[550, 160, 115, 60, 21], [1000, 190, 135, 72, 32], [2000, 230, 165, 88, 50]] as [$v, $l, $w, $h, $g]) {
        $p(['category_id' => $oval, 'code' => sprintf('NP-O%04d', $v), 'name' => fmt_volume($v) . ' Oval Kap', 'summary' => 'Şık görünümlü, kolay açılır oval ambalaj.',
            'volume_ml' => $v, 'width_mm' => $w, 'length_mm' => $l, 'height_mm' => $h, 'weight_g' => $g, 'lid' => 'Mühürlü kapak',
            'image' => 'demo/oval.svg'], ['icecream', 'candy', 'cosmetic']);
    }
    foreach ([[125, 75, 58, 6], [160, 80, 64, 7], [200, 88, 66, 8], [300, 95, 78, 10]] as [$v, $dia, $h, $g]) {
        $p(['category_id' => $kase, 'code' => sprintf('NP-C%04d', $v), 'name' => $v . ' cc IML Kase', 'summary' => 'Kaşıklı kapak seçeneğiyle dondurma ve tatlı kasesi.',
            'volume_ml' => $v, 'top_diameter_mm' => $dia, 'height_mm' => $h, 'weight_g' => $g, 'lid' => 'Kaşıklı kapak / folyo',
            'image' => 'demo/cup.svg', 'is_featured' => $v === 200 ? 1 : 0, 'is_new' => $v === 125 ? 1 : 0], ['icecream', 'milk']);
    }
    foreach ([[5000, 228, 182, 210], [10000, 270, 230, 380], [18000, 298, 330, 620], [20000, 305, 350, 680]] as [$v, $dia, $h, $g]) {
        $p(['category_id' => $endus, 'code' => sprintf('NP-E%05d', $v), 'name' => fmt_volume($v) . ' Endüstriyel Kova', 'summary' => 'Darbeye dayanıklı, metal veya plastik kulplu endüstriyel kova.',
            'volume_ml' => $v, 'top_diameter_mm' => $dia, 'height_mm' => $h, 'weight_g' => $g, 'material' => $v >= 18000 ? 'HDPE' : 'PP',
            'decoration' => 'Ofset baskı', 'lid' => 'Contalı, mühürlü kapak', 'extra_specs' => "Kulp: Metal veya plastik\nUN sertifikası: Talep üzerine",
            'image' => 'demo/round.svg', 'is_featured' => $v === 18000 ? 1 : 0], ['paint', 'clean', 'fish']);
    }
    foreach ([['Ø95 Mühürlü Kapak', 95], ['Ø118 Mühürlü Kapak', 118], ['Ø140 Kaşıklı Kapak', 140], ['Ø228 Contalı Kova Kapağı', 228]] as $i => [$n, $dia]) {
        $p(['category_id' => $kapak, 'code' => sprintf('NP-L%03d', $dia), 'name' => $n, 'summary' => 'Kurcalanmaya karşı emniyet şeritli kapak.',
            'top_diameter_mm' => $dia, 'weight_g' => round($dia / 9, 1), 'decoration' => 'IML / baskısız', 'image' => 'demo/lid.svg'], ['milk', 'food', 'paint']);
    }
    $p(['category_id' => $ozel, 'code' => 'NP-X001', 'name' => 'Lokum Kutusu (Özel Tasarım)', 'summary' => 'Marka özel kalıp ile üretilen, IML baskılı lokum ambalajı.',
        'volume_ml' => 900, 'image' => 'demo/square.svg', 'is_new' => 1], ['candy']);
    $p(['category_id' => $ozel, 'code' => 'NP-X002', 'name' => 'Kozmetik Krem Kabı', 'summary' => 'Çift cidarlı, vida kapaklı kozmetik kap.',
        'volume_ml' => 250, 'material' => 'PP', 'decoration' => 'Serigrafi', 'image' => 'demo/cup.svg'], ['cosmetic']);

    /* Sayfalar */
    $pages = [
        ['Hakkımızda', 'Plastik enjeksiyonda çeyrek asırlık tecrübe.', '<p>Firmamız, plastik enjeksiyon yöntemiyle sert plastik ambalaj üretimi alanında faaliyet göstermektedir. Gıda, süt ürünleri, dondurma, boya ve kimya sektörlerindeki yüzlerce markaya kova, kase, kap ve kapak tedarik ediyoruz.</p><h2>Vizyonumuz</h2><p>Sürdürülebilir üretim anlayışıyla, Türkiye\'nin ve bölgenin en güvenilir ambalaj çözüm ortağı olmak.</p><h2>Misyonumuz</h2><p>Müşterilerimizin ürünlerini en iyi şekilde koruyan, rafta fark yaratan ve çevreye duyarlı ambalajlar üretmek.</p><h2>Neden biz?</h2><ul><li>Kendi kalıphanemizde tasarım ve kalıp üretimi</li><li>IML (kalıp içi etiketleme) ve ofset baskı</li><li>Gıda güvenliği sertifikalı hijyenik üretim</li><li>Hızlı numune ve esnek sipariş miktarları</li></ul>', 'demo/factory.svg'],
        ['Kalite Politikası', 'Kalite, hijyen ve sürdürülebilirlik ilkelerimiz.', '<p>Tüm üretim süreçlerimiz uluslararası kalite ve gıda güvenliği standartlarına uygun olarak yürütülür.</p><ul><li>Hammadde girişinden sevkiyata kadar izlenebilirlik</li><li>Her partide boyut, sızdırmazlık ve düşme testleri</li><li>Sürekli iyileştirme ve çalışan eğitimi</li></ul>', null],
        ['Üretim & Teknoloji', 'Makine parkurumuz ve üretim kabiliyetlerimiz.', '<p>Tesisimizde robotik otomasyonlu enjeksiyon makineleri, IML robotları ve kalıphane bulunmaktadır.</p><h2>Kabiliyetlerimiz</h2><ul><li>50 – 650 ton arası enjeksiyon makineleri</li><li>Çok gözlü, sıcak yolluklu kalıplar</li><li>Kalıp içi etiketleme (IML) ve ofset baskı</li><li>3D tasarım ve prototipleme</li></ul>', 'demo/hero-2.svg'],
        ['İnsan Kaynakları', 'Ekibimize katılın.', '<p>Başarımızın temelinde ekibimiz var. Açık pozisyonlarımız ve genel başvurular için özgeçmişinizi iletişim sayfamızdaki formdan veya e-posta adresimizden gönderebilirsiniz.</p>', null],
        ['KVKK Aydınlatma Metni', null, '<p>6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında, web sitemiz üzerindeki formlar aracılığıyla paylaştığınız ad, soyad, firma, e-posta ve telefon bilgileriniz; talebinize yanıt verilmesi, teklif ve numune süreçlerinin yürütülmesi amacıyla işlenmektedir.</p><p>Kişisel verileriniz, yasal yükümlülükler dışında üçüncü kişilerle paylaşılmaz. Kanunun 11. maddesi kapsamındaki haklarınızı kullanmak için bizimle iletişime geçebilirsiniz.</p><p><em>Bu metin örnektir; hukuk danışmanınızla birlikte güncelleyiniz.</em></p>', null, 'kvkk'],
        ['Çerez Politikası', null, '<p>Web sitemizde yalnızca sitenin çalışması için gerekli olan zorunlu çerezler kullanılmaktadır. Analiz çerezleri yalnızca Google Analytics etkinleştirildiğinde kullanılır.</p><p><em>Bu metin örnektir; hukuk danışmanınızla birlikte güncelleyiniz.</em></p>', null, 'cerez-politikasi'],
    ];
    $pid = [];
    foreach ($pages as $i => $pg) {
        [$title, $sum, $content, $img] = $pg;
        $slug = $pg[4] ?? slugify($title);
        $pid[$slug] = db_insert('pages', ['title' => $title, 'slug' => $slug, 'summary' => $sum, 'content' => $content, 'image' => $img,
            'is_active' => 1, 'sort_order' => $i + 1, 'updated_at' => $now]);
    }

    /* Menüler */
    $m = function (string $loc, ?int $parent, string $title, string $type, $value, int $order, ?string $auto = null) {
        return db_insert('menu_items', ['location' => $loc, 'parent_id' => $parent, 'title' => $title, 'link_type' => $type,
            'link_value' => $value === null ? null : (string) $value, 'auto_children' => $auto, 'new_tab' => 0, 'is_active' => 1, 'sort_order' => $order]);
    };
    $m('header', null, 'Anasayfa', 'route', 'home', 1);
    $kur = $m('header', null, 'Kurumsal', 'none', null, 2);
    $m('header', $kur, 'Hakkımızda', 'page', $pid['hakkimizda'], 1);
    $m('header', $kur, 'Üretim & Teknoloji', 'page', $pid['uretim-teknoloji'], 2);
    $m('header', $kur, 'Kalite Politikası', 'page', $pid['kalite-politikasi'], 3);
    $m('header', $kur, 'İnsan Kaynakları', 'page', $pid['insan-kaynaklari'], 4);
    $m('header', null, 'Ürünler', 'route', 'products', 3, 'categories');
    $m('header', null, 'Sektörler', 'route', 'sectors', 4, 'sectors');
    $m('header', null, 'Haberler', 'route', 'news', 5);
    $m('header', null, 'İletişim', 'route', 'contact', 6);

    $m('footer', null, 'Hakkımızda', 'page', $pid['hakkimizda'], 1);
    $m('footer', null, 'Kalite Politikası', 'page', $pid['kalite-politikasi'], 2);
    $m('footer', null, 'İnsan Kaynakları', 'page', $pid['insan-kaynaklari'], 3);
    $m('footer', null, 'Haberler', 'route', 'news', 4);
    $m('footer', null, 'KVKK Aydınlatma Metni', 'page', $pid['kvkk'], 5);
    $m('footer', null, 'Çerez Politikası', 'page', $pid['cerez-politikasi'], 6);
    $m('footer', null, 'İletişim', 'route', 'contact', 7);

    /* Slider */
    $slides = [
        ['Plastik enjeksiyonda 25 yıllık tecrübe', 'Ambalajınız, markanızın ilk izlenimi', 'Gıda, süt ürünleri ve kimya sektörleri için IML baskılı, hijyenik ve dayanıklı plastik ambalajlar.', 'Ürünleri keşfedin', 'urunler', 'demo/hero-1.svg'],
        ['Kalıptan rafa', 'Anahtar teslim ambalaj üretimi', 'Ürün tasarımı, kalıp üretimi, enjeksiyon ve IML baskı — tüm süreç tek çatı altında.', 'Üretim gücümüz', 'uretim-teknoloji', 'demo/hero-2.svg'],
        ['IML teknolojisi', 'Rafta fark yaratan baskı kalitesi', 'Kalıp içi etiketleme ile fotoğraf kalitesinde, nem ve soğuğa dayanıklı dekorasyon.', 'Kaseleri inceleyin', 'urunler/iml-kaseler-kaplar', 'demo/hero-3.svg'],
    ];
    foreach ($slides as $i => [$eyebrow, $title, $sub, $btn, $link, $img]) {
        db_insert('sliders', ['eyebrow' => $eyebrow, 'title' => $title, 'subtitle' => $sub, 'button_text' => $btn, 'button_link' => $link, 'image' => $img, 'sort_order' => $i + 1, 'is_active' => 1]);
    }

    /* Haberler */
    $news = [
        ['Yeni IML robot hattımız devreye alındı', 'Kalıp içi etiketleme kapasitemizi iki katına çıkaran yeni hattımız üretime başladı.', '-12 days'],
        ['Fuarda yeni ürün ailemizi tanıttık', 'Uluslararası ambalaj fuarında sürdürülebilir ambalaj serimizi ziyaretçilerle buluşturduk.', '-40 days'],
        ['Gıda güvenliği belgemizi yeniledik', 'Yapılan denetimler sonucunda gıda güvenliği sertifikamız başarıyla yenilendi.', '-75 days'],
    ];
    foreach ($news as [$t, $s, $d]) {
        db_insert('news', ['title' => $t, 'slug' => slugify($t), 'summary' => $s, 'content' => '<p>' . e($s) . '</p><p>Detaylı bilgi için bizimle iletişime geçebilirsiniz.</p>',
            'published_at' => date('Y-m-d', strtotime($d)), 'is_active' => 1]);
    }

    /* Belgeler ve referanslar */
    foreach (['ISO 9001:2015', 'ISO 22000', 'BRCGS Packaging', 'ISO 14001', 'Helal Sertifikası'] as $i => $c) {
        db_insert('certificates', ['title' => $c, 'sort_order' => $i + 1, 'is_active' => 1]);
    }
    foreach (['Lezzet Gıda', 'Anadolu Süt', 'Renkli Boya', 'Kristal Kimya', 'Tatlıca', 'Mavi Deniz', 'Kar Dondurma'] as $i => $c) {
        db_insert('clients', ['name' => $c, 'sort_order' => $i + 1, 'is_active' => 1]);
    }
}
