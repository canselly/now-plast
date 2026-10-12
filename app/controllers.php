<?php

function page_home(): void
{
    $topCats = [];
    foreach (category_children_map()[0] ?? [] as $c) {
        $c['count'] = category_product_count((int) $c['id']);
        $c['children'] = category_children_map()[(int) $c['id']] ?? [];
        $topCats[] = $c;
    }
    render_page('pages/home', [
        'sliders' => rows('SELECT * FROM sliders WHERE is_active = 1 ORDER BY sort_order, id'),
        'categories' => $topCats,
        'sectors' => rows('SELECT * FROM sectors WHERE is_active = 1 ORDER BY sort_order, name'),
        'featured' => rows('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id
                            WHERE p.is_active = 1 AND p.is_featured = 1 ORDER BY p.sort_order, p.id DESC LIMIT 12'),
        'news' => rows('SELECT * FROM news WHERE is_active = 1 ORDER BY published_at DESC, id DESC LIMIT 3'),
        'certificates' => rows('SELECT * FROM certificates WHERE is_active = 1 ORDER BY sort_order, id'),
        'clients' => rows('SELECT * FROM clients WHERE is_active = 1 ORDER BY sort_order, id'),
        'is_home' => true,
    ]);
}

/**
 * Ürün listesi: tüm ürünler, bir kategori (alt kategorileri dahil) veya bir sektör için.
 * Filtreler GET ile gelir: s[] (sektör id), m[] (hammadde), vmin/vmax (ml), q, sirala, sayfa.
 */
function product_listing(?array $category = null, ?array $sector = null): array
{
    $where = ['p.is_active = 1'];
    $params = [];

    if ($category) {
        $ids = category_descendant_ids((int) $category['id']);
        $where[] = 'p.category_id IN (' . in_placeholders($ids) . ')';
        array_push($params, ...$ids);
    }
    if ($sector) {
        $where[] = 'p.id IN (SELECT product_id FROM product_sectors WHERE sector_id = ?)';
        $params[] = (int) $sector['id'];
    }
    $scopeWhere = $where;
    $scopeParams = $params;

    $selSectors = array_values(array_filter(array_map('intval', (array) ($_GET['s'] ?? []))));
    if ($selSectors) {
        $where[] = 'p.id IN (SELECT product_id FROM product_sectors WHERE sector_id IN (' . in_placeholders($selSectors) . '))';
        array_push($params, ...$selSectors);
    }
    $selMaterials = array_values(array_filter(array_map(fn($v) => mb_substr(trim((string) $v), 0, 60), (array) ($_GET['m'] ?? []))));
    if ($selMaterials) {
        $where[] = 'p.material IN (' . in_placeholders($selMaterials) . ')';
        array_push($params, ...$selMaterials);
    }
    $vmin = is_numeric($_GET['vmin'] ?? null) ? (float) $_GET['vmin'] : null;
    $vmax = is_numeric($_GET['vmax'] ?? null) ? (float) $_GET['vmax'] : null;
    if ($vmin !== null) {
        $where[] = 'p.volume_ml >= ?';
        $params[] = $vmin;
    }
    if ($vmax !== null) {
        $where[] = 'p.volume_ml <= ?';
        $params[] = $vmax;
    }
    $q = trim((string) ($_GET['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(p.name LIKE ? OR p.code LIKE ? OR p.summary LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like);
    }

    $sorts = [
        'onerilen' => 'p.sort_order, p.id DESC',
        'hacim-artan' => 'CASE WHEN p.volume_ml IS NULL THEN 1 ELSE 0 END, p.volume_ml ASC, p.sort_order',
        'hacim-azalan' => 'CASE WHEN p.volume_ml IS NULL THEN 1 ELSE 0 END, p.volume_ml DESC, p.sort_order',
        'kod' => 'p.code ASC',
        'ad' => 'p.name ASC',
        'yeni' => 'p.id DESC',
    ];
    $sort = (string) ($_GET['sirala'] ?? 'onerilen');
    if (!isset($sorts[$sort])) {
        $sort = 'onerilen';
    }

    $w = implode(' AND ', $where);
    $total = (int) val("SELECT COUNT(*) FROM products p WHERE $w", $params);
    $per = max(6, min(96, (int) setting('products_per_page', 24)));
    $pg = pagination($total, $per, (int) ($_GET['sayfa'] ?? 1));

    $items = rows("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id
                   WHERE $w ORDER BY {$sorts[$sort]} LIMIT {$pg['per']} OFFSET {$pg['offset']}", $params);

    // Filtre seçenekleri yalnızca mevcut kapsamdaki ürünlerden üretilir.
    $sw = implode(' AND ', $scopeWhere);
    $materials = rows("SELECT p.material AS v, COUNT(*) AS n FROM products p WHERE $sw AND p.material IS NOT NULL AND p.material <> ''
                       GROUP BY p.material ORDER BY p.material", $scopeParams);
    $sectorOpts = $sector ? [] : rows("SELECT s.id, s.name, COUNT(*) AS n FROM sectors s
                       JOIN product_sectors ps ON ps.sector_id = s.id JOIN products p ON p.id = ps.product_id
                       WHERE s.is_active = 1 AND $sw GROUP BY s.id, s.name, s.sort_order ORDER BY s.sort_order, s.name", $scopeParams);

    return [
        'items' => $items,
        'pg' => $pg,
        'sort' => $sort,
        'sort_labels' => ['onerilen' => 'Önerilen', 'hacim-artan' => 'Hacim (artan)', 'hacim-azalan' => 'Hacim (azalan)',
            'kod' => 'Ürün kodu', 'ad' => 'Ürün adı', 'yeni' => 'En yeni'],
        'materials' => $materials,
        'sector_opts' => $sectorOpts,
        'sel_sectors' => $selSectors,
        'sel_materials' => $selMaterials,
        'vmin' => $vmin,
        'vmax' => $vmax,
        'q' => $q,
        'has_filters' => $selSectors || $selMaterials || $vmin !== null || $vmax !== null || $q !== '',
    ];
}

function page_products(): void
{
    $top = category_children_map()[0] ?? [];
    foreach ($top as &$c) {
        $c['count'] = category_product_count((int) $c['id']);
    }
    unset($c);
    render_page('pages/listing', [
        'title' => 'Ürünler',
        'meta_description' => 'Tüm plastik enjeksiyon ürünlerimiz: ' . implode(', ', array_column($top, 'name')),
        'heading' => 'Ürünlerimiz',
        'intro' => setting('products_intro', 'Gıda, kimya ve endüstriyel uygulamalar için geliştirdiğimiz plastik ambalaj ve enjeksiyon ürünlerini inceleyin.'),
        'subcats' => $top,
        'category' => null,
        'sector' => null,
        'breadcrumbs' => [['Ürünler', null]],
        'list' => product_listing(),
    ]);
}

function page_category(string $slug): void
{
    $cat = null;
    foreach (all_categories() as $c) {
        if ($c['slug'] === $slug) {
            $cat = $c;
            break;
        }
    }
    if (!$cat) {
        not_found();
    }
    $children = category_children_map()[(int) $cat['id']] ?? [];
    foreach ($children as &$c) {
        $c['count'] = category_product_count((int) $c['id']);
    }
    unset($c);

    $crumbs = [['Ürünler', url('urunler')]];
    foreach (category_ancestors($cat) as $a) {
        $crumbs[] = [$a['name'], category_url($a)];
    }
    $crumbs[] = [$cat['name'], null];

    render_page('pages/listing', [
        'title' => $cat['meta_title'] ?: $cat['name'],
        'meta_description' => $cat['meta_description'] ?: ($cat['summary'] ?: excerpt($cat['description'])),
        'og_image' => $cat['image'] ? upload_url($cat['image']) : null,
        'heading' => $cat['name'],
        'intro' => $cat['summary'],
        'description' => $cat['description'],
        'subcats' => $children,
        'category' => $cat,
        'sector' => null,
        'breadcrumbs' => $crumbs,
        'list' => product_listing($cat),
    ]);
}

function page_sectors(): void
{
    $sectors = rows('SELECT s.*, (SELECT COUNT(*) FROM product_sectors ps JOIN products p ON p.id = ps.product_id
                     WHERE ps.sector_id = s.id AND p.is_active = 1) AS count
                     FROM sectors s WHERE s.is_active = 1 ORDER BY s.sort_order, s.name');
    render_page('pages/sectors', [
        'title' => 'Sektörler',
        'meta_description' => 'Hizmet verdiğimiz sektörler ve sektöre özel ambalaj çözümlerimiz.',
        'sectors' => $sectors,
        'breadcrumbs' => [['Sektörler', null]],
    ]);
}

function page_sector(string $slug): void
{
    $sector = row('SELECT * FROM sectors WHERE slug = ? AND is_active = 1', [$slug]);
    if (!$sector) {
        not_found();
    }
    render_page('pages/listing', [
        'title' => $sector['meta_title'] ?: $sector['name'] . ' Ambalajları',
        'meta_description' => $sector['meta_description'] ?: ($sector['summary'] ?: excerpt($sector['description'])),
        'og_image' => $sector['image'] ? upload_url($sector['image']) : null,
        'heading' => $sector['name'],
        'eyebrow' => 'Sektörel Çözümler',
        'intro' => $sector['summary'],
        'description' => $sector['description'],
        'subcats' => [],
        'category' => null,
        'sector' => $sector,
        'all_sectors' => rows('SELECT id, name, slug, icon FROM sectors WHERE is_active = 1 ORDER BY sort_order, name'),
        'breadcrumbs' => [['Sektörler', url('sektorler')], [$sector['name'], null]],
        'list' => product_listing(null, $sector),
    ]);
}

function page_product(string $slug): void
{
    $p = row('SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p
              LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.is_active = 1', [$slug]);
    if (!$p) {
        not_found();
    }
    $images = [];
    if ($p['image']) {
        $images[] = upload_url($p['image']);
    }
    foreach (rows('SELECT path FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$p['id']]) as $im) {
        $images[] = upload_url($im['path']);
    }
    if (!$images) {
        $images[] = product_image($p);
    }

    $sectors = rows('SELECT s.* FROM sectors s JOIN product_sectors ps ON ps.sector_id = s.id
                     WHERE ps.product_id = ? AND s.is_active = 1 ORDER BY s.sort_order', [$p['id']]);

    $similar = [];
    if ($p['category_id']) {
        $similar = rows('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id
                         WHERE p.is_active = 1 AND p.category_id = ? AND p.id <> ?
                         ORDER BY ABS(COALESCE(p.volume_ml, 0) - ?), p.sort_order LIMIT 8',
            [$p['category_id'], $p['id'], (float) ($p['volume_ml'] ?? 0)]);
    }

    $crumbs = [['Ürünler', url('urunler')]];
    if ($p['category_id']) {
        $cat = row('SELECT * FROM categories WHERE id = ?', [$p['category_id']]);
        if ($cat) {
            foreach (category_ancestors($cat) as $a) {
                $crumbs[] = [$a['name'], category_url($a)];
            }
            $crumbs[] = [$cat['name'], category_url($cat)];
        }
    }
    $crumbs[] = [($p['code'] ? $p['code'] . ' ' : '') . $p['name'], null];

    render_page('pages/product', [
        'title' => $p['meta_title'] ?: trim(($p['code'] ? $p['code'] . ' - ' : '') . $p['name']),
        'meta_description' => $p['meta_description'] ?: ($p['summary'] ?: excerpt($p['description'])),
        'og_image' => $images[0] ?? null,
        'p' => $p,
        'images' => $images,
        'specs' => product_specs($p),
        'sectors' => $sectors,
        'similar' => $similar,
        'breadcrumbs' => $crumbs,
    ]);
}

function page_news_list(): void
{
    $total = (int) val('SELECT COUNT(*) FROM news WHERE is_active = 1');
    $pg = pagination($total, 9, (int) ($_GET['sayfa'] ?? 1));
    render_page('pages/news', [
        'title' => 'Haberler & Duyurular',
        'items' => rows("SELECT * FROM news WHERE is_active = 1 ORDER BY published_at DESC, id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}"),
        'pg' => $pg,
        'breadcrumbs' => [['Haberler', null]],
    ]);
}

function page_news_detail(string $slug): void
{
    $n = row('SELECT * FROM news WHERE slug = ? AND is_active = 1', [$slug]);
    if (!$n) {
        not_found();
    }
    render_page('pages/news-detail', [
        'title' => $n['meta_title'] ?: $n['title'],
        'meta_description' => $n['meta_description'] ?: ($n['summary'] ?: excerpt($n['content'])),
        'og_image' => $n['image'] ? upload_url($n['image']) : null,
        'n' => $n,
        'others' => rows('SELECT * FROM news WHERE is_active = 1 AND id <> ? ORDER BY published_at DESC, id DESC LIMIT 3', [$n['id']]),
        'breadcrumbs' => [['Haberler', url('haberler')], [$n['title'], null]],
    ]);
}

function page_static(string $slug): void
{
    $page = row('SELECT * FROM pages WHERE slug = ? AND is_active = 1', [$slug]);
    if (!$page) {
        not_found();
    }
    render_page('pages/page', [
        'title' => $page['meta_title'] ?: $page['title'],
        'meta_description' => $page['meta_description'] ?: ($page['summary'] ?: excerpt($page['content'])),
        'og_image' => $page['image'] ? upload_url($page['image']) : null,
        'page' => $page,
        'breadcrumbs' => [[$page['title'], null]],
    ]);
}

function page_contact(): void
{
    render_page('pages/contact', [
        'title' => 'İletişim',
        'meta_description' => 'Bize ulaşın: ' . setting('address') . ' ' . setting('phone'),
        'breadcrumbs' => [['İletişim', null]],
    ]);
}

function page_search(): void
{
    $q = trim((string) ($_GET['q'] ?? ''));
    $pages = [];
    if (mb_strlen($q) >= 2) {
        $like = '%' . $q . '%';
        $pages = rows('SELECT title, slug, summary, content FROM pages WHERE is_active = 1 AND (title LIKE ? OR content LIKE ?) LIMIT 5', [$like, $like]);
    }
    render_page('pages/listing', [
        'title' => $q !== '' ? '"' . $q . '" için arama sonuçları' : 'Ürün Arama',
        'heading' => $q !== '' ? '“' . $q . '”' : 'Ürün Arama',
        'eyebrow' => 'Arama sonuçları',
        'intro' => null,
        'subcats' => [],
        'category' => null,
        'sector' => null,
        'search_pages' => $pages,
        'noindex' => true,
        'breadcrumbs' => [['Arama', null]],
        'list' => product_listing(),
    ]);
}

function page_catalog(): void
{
    $f = setting('catalog_pdf');
    if (!$f) {
        not_found();
    }
    redirect(upload_url($f));
}

function page_sitemap(): void
{
    header('Content-Type: application/xml; charset=utf-8');
    $urls = [full_url(''), full_url('urunler'), full_url('sektorler'), full_url('haberler'), full_url('iletisim')];
    foreach (all_categories() as $c) $urls[] = full_url('urunler/' . $c['slug']);
    foreach (rows('SELECT slug FROM products WHERE is_active = 1') as $r) $urls[] = full_url('urun/' . $r['slug']);
    foreach (rows('SELECT slug FROM sectors WHERE is_active = 1') as $r) $urls[] = full_url('sektor/' . $r['slug']);
    foreach (rows('SELECT slug FROM pages WHERE is_active = 1') as $r) $urls[] = full_url($r['slug']);
    foreach (rows('SELECT slug FROM news WHERE is_active = 1') as $r) $urls[] = full_url('haber/' . $r['slug']);
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        echo '  <url><loc>' . e($u) . "</loc></url>\n";
    }
    echo '</urlset>';
}

function page_robots(): void
{
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nDisallow: /admin/\nDisallow: /ara\nSitemap: " . full_url('sitemap.xml') . "\n";
}

/** İletişim, numune ve teklif formlarının ortak işleyicisi. */
function handle_message_post(): void
{
    if (!is_post()) {
        redirect(url('iletisim'));
    }
    csrf_check();
    $back = safe_back($_POST['back'] ?? '') ?: url('iletisim');
    $type = in_array($_POST['type'] ?? '', ['contact', 'sample', 'quote'], true) ? $_POST['type'] : 'contact';
    $okMsg = $type === 'contact'
        ? 'Mesajınız bize ulaştı. En kısa sürede dönüş yapacağız.'
        : 'Talebiniz alındı. Satış ekibimiz en kısa sürede sizinle iletişime geçecek.';

    // Bot koruması: gizli alan doluysa veya form çok hızlı gönderildiyse sessizce yok say.
    $ts = (int) ($_POST['ts'] ?? 0);
    if (!empty($_POST['website']) || ($ts && time() - $ts < 3)) {
        flash('success', $okMsg);
        redirect($back);
    }
    if (time() - (int) ($_SESSION['last_msg_at'] ?? 0) < 20) {
        flash('error', 'Lütfen birkaç saniye bekleyip tekrar deneyin.');
        redirect($back);
    }

    $f = fn($k, $max) => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
    $d = [
        'type' => $type,
        'name' => $f('name', 120),
        'company' => $f('company', 190),
        'email' => $f('email', 190),
        'phone' => $f('phone', 40),
        'subject' => $f('subject', 190),
        'quantity' => $f('quantity', 60),
        'message' => $f('message', 5000),
        'product_id' => null,
        'ip' => client_ip(),
        'created_at' => now(),
        'is_read' => 0,
    ];
    $pid = (int) ($_POST['product_id'] ?? 0);
    if ($pid && val('SELECT id FROM products WHERE id = ?', [$pid])) {
        $d['product_id'] = $pid;
    }

    $errors = [];
    if ($d['name'] === '') $errors[] = 'Ad soyad alanı zorunludur.';
    if ($d['email'] === '' && $d['phone'] === '') $errors[] = 'E-posta veya telefon bilgisi giriniz.';
    if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta adresi giriniz.';
    if ($type === 'contact' && $d['message'] === '') $errors[] = 'Mesaj alanı zorunludur.';
    if (empty($_POST['kvkk'])) $errors[] = 'Aydınlatma metnini onaylamanız gerekiyor.';

    if ($errors) {
        $_SESSION['old_form'] = $d;
        flash('error', implode(' ', $errors));
        redirect($back . (str_contains($back, '#') ? '' : '#form'));
    }

    db_insert('messages', $d);
    $_SESSION['last_msg_at'] = time();
    unset($_SESSION['old_form']);
    notify_new_message($d);
    flash('success', $okMsg);
    redirect($back);
}

function notify_new_message(array $d): void
{
    $to = trim((string) setting('notify_email'));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $types = ['contact' => 'İletişim mesajı', 'sample' => 'Numune talebi', 'quote' => 'Teklif talebi'];
    $product = $d['product_id'] ? row('SELECT code, name FROM products WHERE id = ?', [$d['product_id']]) : null;
    $body = "Yeni " . mb_strtolower($types[$d['type']]) . "\n\n"
        . "Ad Soyad: {$d['name']}\nFirma: {$d['company']}\nE-posta: {$d['email']}\nTelefon: {$d['phone']}\n"
        . ($product ? "Ürün: {$product['code']} {$product['name']}\n" : '')
        . ($d['quantity'] ? "Adet: {$d['quantity']}\n" : '')
        . ($d['subject'] ? "Konu: {$d['subject']}\n" : '')
        . "\n{$d['message']}\n";
    $subject = '=?UTF-8?B?' . base64_encode('[' . setting('site_name', 'Web sitesi') . '] ' . $types[$d['type']]) . '?=';
    $headers = "Content-Type: text/plain; charset=UTF-8\r\n";
    if ($d['email'] && filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $headers .= 'Reply-To: ' . $d['email'] . "\r\n";
    }
    @mail($to, $subject, $body, $headers);
}
