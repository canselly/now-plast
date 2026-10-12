<?php

/* ---------- Genel ---------- */

function config(string $key, $default = null)
{
    return $GLOBALS['config'][$key] ?? $default;
}

function e($s): string
{
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    return rtrim((string) config('base_url', ''), '/');
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function full_url(string $path = ''): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https' : 'http') . '://' . $host . url($path);
}

function asset(string $path): string
{
    $file = ROOT . '/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : 0;
    return url($path) . ($v ? '?v=' . $v : '');
}

function upload_url(?string $path): string
{
    if (!$path) {
        return '';
    }
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    return url('uploads/' . ltrim($path, '/'));
}

function redirect(string $to, int $code = 302): void
{
    header('Location: ' . $to, true, $code);
    exit;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function current_path(): string
{
    return $GLOBALS['current_path'] ?? '';
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/** Yalnızca site içi yönlendirmelere izin verir. */
function safe_back(?string $to): string
{
    $to = (string) $to;
    if ($to !== '' && $to[0] === '/' && !str_starts_with($to, '//') && !str_contains($to, '\\')) {
        return $to;
    }
    return '';
}

/* ---------- Ayarlar ---------- */

function setting(string $key, $default = '')
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (rows('SELECT skey, svalue FROM settings') as $r) {
                $cache[$r['skey']] = $r['svalue'];
            }
        } catch (Throwable $e) {
        }
    }
    $v = $cache[$key] ?? '';
    return ($v === '' || $v === null) ? $default : $v;
}

function set_setting(string $key, ?string $value): void
{
    q('DELETE FROM settings WHERE skey = ?', [$key]);
    q('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$key, $value]);
}

/* ---------- Oturum, CSRF, flash ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Oturum süresi doldu. Lütfen sayfayı yenileyip tekrar deneyin.');
    }
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- Metin ---------- */

function slugify(string $s): string
{
    $map = ['ş' => 's', 'Ş' => 's', 'ı' => 'i', 'İ' => 'i', 'ğ' => 'g', 'Ğ' => 'g', 'ü' => 'u', 'Ü' => 'u',
        'ö' => 'o', 'Ö' => 'o', 'ç' => 'c', 'Ç' => 'c', 'â' => 'a', 'Â' => 'a', 'î' => 'i', 'û' => 'u'];
    $s = strtr($s, $map);
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('~[^a-z0-9]+~', '-', $s);
    $s = trim($s, '-');
    return $s !== '' ? mb_substr($s, 0, 180) : 'icerik';
}

/** Sayfa adresleri site kökünde yayınlandığı için sabit rotalarla çakışmamalı. */
function reserved_slugs(): array
{
    return ['urunler', 'urun', 'sektorler', 'sektor', 'haberler', 'haber', 'iletisim', 'ara', 'admin', 'uploads',
        'assets', 'talep', 'katalog', 'app', 'data', 'templates', 'install', 'sitemap-xml', 'robots-txt', 'index-php'];
}

function unique_slug(string $table, string $slug, int $exceptId = 0, bool $checkReserved = false): string
{
    $base = $slug;
    if ($checkReserved && in_array($base, reserved_slugs(), true)) {
        $base .= '-sayfa';
    }
    $candidate = $base;
    $i = 2;
    while (val("SELECT COUNT(*) FROM $table WHERE slug = ? AND id <> ?", [$candidate, $exceptId]) > 0) {
        $candidate = $base . '-' . $i++;
    }
    return $candidate;
}

function excerpt(?string $html, int $len = 160): string
{
    $t = trim(preg_replace('~\s+~u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8')));
    return mb_strlen($t) > $len ? rtrim(mb_substr($t, 0, $len)) . '…' : $t;
}

function fmt_num($v): string
{
    if ($v === null || $v === '') {
        return '';
    }
    $s = number_format((float) $v, 2, ',', '.');
    return rtrim(rtrim($s, '0'), ',');
}

function fmt_volume($ml): string
{
    if ($ml === null || $ml === '' || (float) $ml <= 0) {
        return '';
    }
    $ml = (float) $ml;
    return $ml >= 1000 ? fmt_num($ml / 1000) . ' L' : fmt_num($ml) . ' ml';
}

function fmt_date(?string $d): string
{
    if (!$d) {
        return '';
    }
    $months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    $t = strtotime($d);
    return $t ? date('j', $t) . ' ' . $months[(int) date('n', $t) - 1] . ' ' . date('Y', $t) : '';
}

function tel_href(string $phone): string
{
    return 'tel:' . preg_replace('~[^0-9+]~', '', $phone);
}

function whatsapp_href(string $text = ''): string
{
    $n = preg_replace('~[^0-9]~', '', (string) setting('whatsapp'));
    if ($n === '') {
        return '';
    }
    return 'https://wa.me/' . $n . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

/* ---------- Yüklemeler ---------- */

/**
 * Tek dosya yükler. Dönen dizi: ['path' => ?string, 'error' => ?string]
 * $kind: 'image' veya 'file' (pdf, görsel, doc).
 */
function handle_upload(array $file, string $kind = 'image'): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || empty($file['tmp_name'])) {
        return ['path' => null, 'error' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'Dosya çok büyük (sunucu sınırı: ' . ini_get('upload_max_filesize') . ').'
            : 'Dosya yüklenemedi (hata kodu ' . (int) $file['error'] . ').';
        return ['path' => null, 'error' => $msg];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['path' => null, 'error' => 'Geçersiz yükleme.'];
    }

    $images = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $docs = ['application/pdf' => 'pdf'];

    $info = @getimagesize($file['tmp_name']);
    $ext = null;
    if ($info && isset($images[$info['mime']])) {
        $ext = $images[$info['mime']];
    } elseif ($kind === 'file') {
        $head = (string) file_get_contents($file['tmp_name'], false, null, 0, 5);
        if ($head === '%PDF-') {
            $ext = $docs['application/pdf'];
        }
    }
    if (!$ext) {
        return ['path' => null, 'error' => $kind === 'image'
            ? 'Yalnızca JPG, PNG, WEBP veya GIF görsel yükleyebilirsiniz.'
            : 'Yalnızca PDF veya görsel dosya yükleyebilirsiniz.'];
    }
    if ($file['size'] > 20 * 1024 * 1024) {
        return ['path' => null, 'error' => 'Dosya 20 MB sınırını aşıyor.'];
    }

    $sub = date('Y/m');
    $dir = ROOT . '/uploads/' . $sub;
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        return ['path' => null, 'error' => 'uploads klasörüne yazılamıyor.'];
    }
    $original = pathinfo((string) $file['name'], PATHINFO_FILENAME);
    $name = substr(slugify($original), 0, 50) . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        return ['path' => null, 'error' => 'Dosya kaydedilemedi.'];
    }
    return ['path' => $sub . '/' . $name, 'error' => null];
}

/** $_FILES['x'] çoklu yüklemesini tek dosya dizilerine çevirir. */
function normalize_files(?array $f): array
{
    if (!$f || !is_array($f['name'] ?? null)) {
        return [];
    }
    $out = [];
    foreach ($f['name'] as $i => $n) {
        $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

function delete_upload(?string $path): void
{
    // Demo görselleri birden çok kayıtta ortak kullanıldığı için silinmez.
    if (!$path || str_starts_with($path, 'demo/') || str_contains($path, '..')) {
        return;
    }
    $f = ROOT . '/uploads/' . $path;
    if (is_file($f)) {
        @unlink($f);
    }
}

function copy_upload(?string $path): ?string
{
    if (!$path || str_starts_with($path, 'demo/') || str_contains($path, '..')) {
        return $path;
    }
    $src = ROOT . '/uploads/' . $path;
    if (!is_file($src)) {
        return null;
    }
    $sub = date('Y/m');
    $dir = ROOT . '/uploads/' . $sub;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $name = pathinfo($path, PATHINFO_FILENAME);
    $name = preg_replace('~-[0-9a-f]{8}$~', '', $name) . '-' . bin2hex(random_bytes(4)) . '.' . pathinfo($path, PATHINFO_EXTENSION);
    return copy($src, $dir . '/' . $name) ? $sub . '/' . $name : null;
}

/* ---------- Görünüm ---------- */

function view(string $tpl, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    include ROOT . '/templates/' . $tpl . '.php';
    return ob_get_clean();
}

function render_page(string $tpl, array $data = []): void
{
    $data['content'] = view($tpl, $data);
    echo view('layout', $data);
}

function not_found(): void
{
    http_response_code(404);
    render_page('pages/404', ['title' => 'Sayfa bulunamadı', 'noindex' => true]);
    exit;
}

function pagination(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / max(1, $perPage)));
    $page = min(max(1, $page), $pages);
    return ['total' => $total, 'per' => $perPage, 'page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage];
}

function page_link(int $p): string
{
    $q = $_GET;
    unset($q['route']);
    $q['sayfa'] = $p;
    if ($p <= 1) {
        unset($q['sayfa']);
    }
    $path = url(current_path());
    return $path . ($q ? '?' . http_build_query($q) : '');
}

/* ---------- Bağlantılar ---------- */

function category_url(array $c): string { return url('urunler/' . $c['slug']); }
function product_url(array $p): string { return url('urun/' . $p['slug']); }
function sector_url(array $s): string { return url('sektor/' . $s['slug']); }
function page_url(array $p): string { return url($p['slug']); }
function news_url(array $n): string { return url('haber/' . $n['slug']); }

function route_options(): array
{
    return ['home' => 'Anasayfa', 'products' => 'Ürünler', 'sectors' => 'Sektörler', 'news' => 'Haberler',
        'contact' => 'İletişim', 'catalog' => 'Katalog (PDF)', 'search' => 'Ürün Arama'];
}

function route_url(string $r): string
{
    return match ($r) {
        'products' => url('urunler'),
        'sectors' => url('sektorler'),
        'news' => url('haberler'),
        'contact' => url('iletisim'),
        'catalog' => url('katalog'),
        'search' => url('ara'),
        default => url(''),
    };
}

function link_href(?string $v): string
{
    $v = trim((string) $v);
    if ($v === '') {
        return '#';
    }
    if (preg_match('~^(https?:|mailto:|tel:|#|/)~i', $v)) {
        return $v;
    }
    return url($v);
}

function is_active_link(string $href): bool
{
    if ($href === '' || $href[0] === '#' || preg_match('~^(https?:|mailto:|tel:)~i', $href)) {
        return false;
    }
    $path = trim((string) parse_url($href, PHP_URL_PATH), '/');
    $base = trim(base_path(), '/');
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = trim(substr($path, strlen($base)), '/');
    }
    $cur = current_path();
    if ($path === '') {
        return $cur === '';
    }
    return $cur === $path || str_starts_with($cur, $path . '/')
        || ($path === 'urunler' && str_starts_with($cur, 'urun/'));
}

/* ---------- Kategoriler ---------- */

function all_categories(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = rows('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name');
    }
    return $cache;
}

function category_children_map(): array
{
    $map = [];
    foreach (all_categories() as $c) {
        $map[(int) ($c['parent_id'] ?? 0)][] = $c;
    }
    return $map;
}

function category_descendant_ids(int $id): array
{
    $map = category_children_map();
    $out = [$id];
    $stack = [$id];
    while ($stack) {
        $x = array_pop($stack);
        foreach ($map[$x] ?? [] as $ch) {
            $out[] = (int) $ch['id'];
            $stack[] = (int) $ch['id'];
        }
    }
    return $out;
}

function category_ancestors(array $cat): array
{
    $byId = [];
    foreach (all_categories() as $c) {
        $byId[(int) $c['id']] = $c;
    }
    $chain = [];
    $pid = (int) ($cat['parent_id'] ?? 0);
    $guard = 0;
    while ($pid && isset($byId[$pid]) && $guard++ < 20) {
        array_unshift($chain, $byId[$pid]);
        $pid = (int) ($byId[$pid]['parent_id'] ?? 0);
    }
    return $chain;
}

function category_product_count(int $id): int
{
    $ids = category_descendant_ids($id);
    return (int) val('SELECT COUNT(*) FROM products WHERE is_active = 1 AND category_id IN (' . in_placeholders($ids) . ')', $ids);
}

/* ---------- Menü ---------- */

function menu_href(array $i): string
{
    $v = (string) ($i['link_value'] ?? '');
    switch ($i['link_type']) {
        case 'route':
            return route_url($v);
        case 'page':
            $p = row('SELECT slug FROM pages WHERE id = ?', [(int) $v]);
            return $p ? page_url($p) : '#';
        case 'category':
            $c = row('SELECT slug FROM categories WHERE id = ?', [(int) $v]);
            return $c ? category_url($c) : '#';
        case 'sector':
            $s = row('SELECT slug FROM sectors WHERE id = ?', [(int) $v]);
            return $s ? sector_url($s) : '#';
        case 'none':
            return '#';
        default:
            return link_href($v);
    }
}

function category_menu_nodes(int $parent, int $depth): array
{
    $map = category_children_map();
    $out = [];
    foreach ($map[$parent] ?? [] as $c) {
        $out[] = [
            'title' => $c['name'],
            'href' => category_url($c),
            'image' => $c['image'],
            'new_tab' => false,
            'mega' => false,
            'children' => $depth > 1 ? category_menu_nodes((int) $c['id'], $depth - 1) : [],
        ];
    }
    return $out;
}

function menu_tree(string $location): array
{
    try {
        $items = rows('SELECT * FROM menu_items WHERE location = ? AND is_active = 1 ORDER BY sort_order, id', [$location]);
    } catch (Throwable $e) {
        return [];
    }
    $map = [];
    foreach ($items as $i) {
        $map[(int) ($i['parent_id'] ?? 0)][] = $i;
    }
    $build = function (int $pid) use (&$build, $map) {
        $out = [];
        foreach ($map[$pid] ?? [] as $i) {
            $node = [
                'title' => $i['title'],
                'href' => menu_href($i),
                'new_tab' => (int) $i['new_tab'] === 1,
                'mega' => false,
                'image' => null,
                'children' => $build((int) $i['id']),
            ];
            if ($i['auto_children'] === 'categories') {
                $node['mega'] = true;
                $node['children'] = array_merge($node['children'], category_menu_nodes(0, 2));
            } elseif ($i['auto_children'] === 'sectors') {
                foreach (rows('SELECT name, slug FROM sectors WHERE is_active = 1 ORDER BY sort_order, name') as $s) {
                    $node['children'][] = ['title' => $s['name'], 'href' => sector_url($s), 'new_tab' => false, 'mega' => false, 'image' => null, 'children' => []];
                }
            }
            $out[] = $node;
        }
        return $out;
    };
    return $build(0);
}

/* ---------- Ürünler ---------- */

function product_image(array $p): string
{
    return !empty($p['image']) ? upload_url($p['image']) : asset('assets/img/placeholder.svg');
}

function product_specs(array $p): array
{
    $s = [];
    $mm = fn($v) => fmt_num($v) . ' mm';
    if ((float) ($p['volume_ml'] ?? 0) > 0) $s['Hacim'] = fmt_volume($p['volume_ml']);
    if ((float) ($p['height_mm'] ?? 0) > 0) $s['Yükseklik'] = $mm($p['height_mm']);
    if ((float) ($p['top_diameter_mm'] ?? 0) > 0) $s['Ağız Çapı'] = $mm($p['top_diameter_mm']);
    if ((float) ($p['bottom_diameter_mm'] ?? 0) > 0) $s['Taban Çapı'] = $mm($p['bottom_diameter_mm']);
    if ((float) ($p['width_mm'] ?? 0) > 0) $s['En'] = $mm($p['width_mm']);
    if ((float) ($p['length_mm'] ?? 0) > 0) $s['Boy'] = $mm($p['length_mm']);
    if ((float) ($p['weight_g'] ?? 0) > 0) $s['Gramaj'] = fmt_num($p['weight_g']) . ' g';
    if (!empty($p['material'])) $s['Hammadde'] = $p['material'];
    if (!empty($p['decoration'])) $s['Baskı / Dekorasyon'] = $p['decoration'];
    if (!empty($p['lid'])) $s['Kapak'] = $p['lid'];
    if (!empty($p['packaging'])) $s['Koli İçi Adet'] = $p['packaging'];
    foreach (preg_split('~\R~', (string) ($p['extra_specs'] ?? '')) as $line) {
        if (str_contains($line, ':')) {
            [$k, $v] = array_map('trim', explode(':', $line, 2));
            if ($k !== '' && $v !== '') $s[$k] = $v;
        }
    }
    return $s;
}

/* ---------- Harita ---------- */

function map_embed_src(): string
{
    $custom = trim((string) setting('map_embed_url'));
    if ($custom !== '' && preg_match('~^https://(www\.)?google\.[a-z.]+/maps/embed~i', $custom)) {
        return $custom;
    }
    $lat = trim((string) setting('map_lat'));
    $lng = trim((string) setting('map_lng'));
    if (is_numeric($lat) && is_numeric($lng)) {
        return 'https://maps.google.com/maps?q=' . $lat . ',' . $lng . '&hl=tr&z=15&output=embed';
    }
    $addr = trim((string) setting('address'));
    return $addr !== '' ? 'https://maps.google.com/maps?q=' . rawurlencode($addr) . '&hl=tr&z=15&output=embed' : '';
}

function map_directions_href(): string
{
    $lat = trim((string) setting('map_lat'));
    $lng = trim((string) setting('map_lng'));
    if (is_numeric($lat) && is_numeric($lng)) {
        return 'https://www.google.com/maps/dir/?api=1&destination=' . $lat . ',' . $lng;
    }
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode((string) setting('address'));
}

/* ---------- Sektör simgeleri ---------- */

function sector_icon_options(): array
{
    return ['icecream' => 'Dondurma', 'milk' => 'Süt Ürünleri', 'paint' => 'Boya', 'chemical' => 'Kimya',
        'food' => 'Gıda', 'candy' => 'Şekerleme', 'cosmetic' => 'Kozmetik', 'clean' => 'Temizlik',
        'fish' => 'Deniz Ürünleri', 'industry' => 'Endüstri', 'agri' => 'Tarım', 'box' => 'Genel'];
}

function sector_icon(?string $key): string
{
    $p = [
        'icecream' => '<path d="M8 11a4 4 0 1 1 8 0"/><path d="M7 11h10l-5 10z"/><path d="M10 7.5a3 3 0 0 1 4 0"/>',
        'milk' => '<path d="M9 2h6v3l2 3v13a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V8l2-3z"/><path d="M7 12h10"/>',
        'paint' => '<rect x="4" y="7" width="16" height="14" rx="2"/><path d="M4 11h16"/><path d="M8 7a4 4 0 0 1 8 0"/>',
        'chemical' => '<path d="M9 3h6"/><path d="M10 3v6L4.5 19a1.5 1.5 0 0 0 1.3 2h12.4a1.5 1.5 0 0 0 1.3-2L14 9V3"/><path d="M7 15h10"/>',
        'food' => '<path d="M4 11h16a8 8 0 0 1-16 0z"/><path d="M8 7c0-1.5 1-1.5 1-3"/><path d="M12 7c0-1.5 1-1.5 1-3"/><path d="M16 7c0-1.5 1-1.5 1-3"/>',
        'candy' => '<circle cx="12" cy="12" r="4"/><path d="M8.5 9.5 4 7v10l4.5-2.5"/><path d="M15.5 9.5 20 7v10l-4.5-2.5"/>',
        'cosmetic' => '<rect x="8" y="9" width="8" height="12" rx="2"/><path d="M10 9V5h4v4"/><path d="M11 3h2"/>',
        'clean' => '<path d="M9 3h5l1 4H8z"/><path d="M7 7h9l1 3v10a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1z"/><path d="M17 5h3"/>',
        'fish' => '<path d="M3 12c3-5 9-6 13-3l5-3v12l-5-3c-4 3-10 2-13-3z"/><circle cx="8" cy="11" r=".8" fill="currentColor"/>',
        'industry' => '<path d="M3 21V10l6 4V10l6 4V5h6v16z"/><path d="M7 17h2M12 17h2M17 17h2"/>',
        'agri' => '<path d="M12 21V9"/><path d="M12 13c-4 0-6-3-6-6 4 0 6 3 6 6z"/><path d="M12 11c0-3 2-6 6-6 0 3-2 6-6 6z"/>',
        'box' => '<path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4"/><path d="M12 11v10"/>',
    ];
    $inner = $p[$key ?? ''] ?? $p['box'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}

function icon(string $name, string $class = 'ico'): string
{
    $p = [
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'chev' => '<path d="m6 9 6 6 6-6"/>',
        'chev-r' => '<path d="m9 6 6 6-6 6"/>',
        'chev-l' => '<path d="m15 6-6 6 6 6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'pin' => '<path d="M12 21s-7-6.1-7-11.5a7 7 0 0 1 14 0C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
        'list' => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>',
        'download' => '<path d="M12 4v11M7 10l5 5 5-5"/><path d="M5 20h14"/>',
        'filter' => '<path d="M4 5h16l-6 7v6l-4 2v-8z"/>',
        'check' => '<path d="m5 12 5 5 9-10"/>',
        'up' => '<path d="m6 15 6-6 6 6"/>',
        'flask' => '<path d="M9 3h6M10 3v6L5 19a1.5 1.5 0 0 0 1.3 2h11.4a1.5 1.5 0 0 0 1.3-2l-5-10V3"/>',
        'quote' => '<path d="M4 5h16v11H8l-4 4z"/><path d="M8 9h8M8 12h5"/>',
        'whatsapp' => '<path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3z"/><path d="M9 8.5c0 3.5 3 6.5 6.5 6.5l1-1.6-2-1-1 1a4 4 0 0 1-2.4-2.4l1-1-1-2z"/>',
        'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H7v4h3v7h4v-7h3l1-4h-4V8.5a.5.5 0 0 1 .5-.5"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" fill="currentColor"/>',
        'linkedin' => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 10v7"/>',
        'youtube' => '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="m10 9 5 3-5 3z"/>',
        'x' => '<path d="M4 4l16 16M20 4 4 20"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/>',
        'shield' => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'cog' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
        'leaf' => '<path d="M5 19c0-8 6-14 15-14 0 9-6 15-14 15"/><path d="M5 19 13 11"/>',
        'truck' => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'print' => '<path d="M7 8V3h10v5"/><rect x="3" y="8" width="18" height="9" rx="2"/><path d="M7 14h10v7H7z"/>',
        'mold' => '<rect x="3" y="4" width="18" height="6" rx="1"/><rect x="3" y="14" width="18" height="6" rx="1"/><path d="M8 10v4M16 10v4"/>',
        'cube' => '<path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/>',
        'doc' => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 13h6M9 17h6"/>',
    ];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}
