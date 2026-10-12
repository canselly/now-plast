<?php
require __DIR__ . '/entities.php';

/* ==========================================================================
   Yardımcılar
   ========================================================================== */

function admin_url(array $q = []): string
{
    return url('admin/index.php') . ($q ? '?' . http_build_query($q) : '');
}

function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function current_admin(): ?array
{
    static $u = false;
    if ($u === false) {
        $u = !empty($_SESSION['admin_id']) ? row('SELECT id, username, name FROM users WHERE id = ?', [$_SESSION['admin_id']]) : null;
    }
    return $u;
}

function require_admin(): void
{
    if (current_admin()) {
        return;
    }
    if (is_ajax()) {
        json_out(['ok' => false, 'error' => 'Oturum kapandı, lütfen tekrar giriş yapın.'], 401);
    }
    $back = $_SERVER['REQUEST_URI'] ?? '';
    redirect(admin_url(['r' => 'login', 'back' => safe_back($back)]));
}

function resolve_options($opt): array
{
    if (is_array($opt)) {
        return $opt;
    }
    if (is_callable($opt)) {
        return (array) $opt();
    }
    return [];
}

function entity_or_404(string $key): array
{
    $all = entities();
    if (!isset($all[$key])) {
        http_response_code(404);
        exit('Bilinmeyen içerik türü.');
    }
    return $all[$key];
}

function entity_field(array $E, string $name): ?array
{
    foreach ($E['fields'] as $f) {
        if ($f['name'] === $name) return $f;
    }
    return null;
}

/** Pasif olanlar dahil tüm alt kategoriler (admin filtresi için). */
function admin_descendants(string $table, int $id): array
{
    $map = [];
    foreach (rows("SELECT id, parent_id FROM $table") as $r) {
        $map[(int) ($r['parent_id'] ?? 0)][] = (int) $r['id'];
    }
    $out = [$id];
    $stack = [$id];
    while ($stack) {
        $x = array_pop($stack);
        foreach ($map[$x] ?? [] as $c) {
            if (!in_array($c, $out, true)) {
                $out[] = $c;
                $stack[] = $c;
            }
        }
    }
    return $out;
}

function admin_theme_style(): string
{
    $c = (string) setting('theme_color', '#1557ff');
    return preg_match('~^#[0-9a-fA-F]{6}$~', $c)
        ? '<style>:root{--brand:' . $c . ';--brand-soft:color-mix(in srgb,' . $c . ' 10%,#fff)}.btn--primary:hover{background:color-mix(in srgb,' . $c . ' 82%,#000)}</style>'
        : '';
}

function admin_brand_mark(): string
{
    $fav = setting('favicon');
    if ($fav) {
        return '<span class="side__mark side__mark--img"><img src="' . e(upload_url($fav)) . '" alt=""></span>';
    }
    return '<span class="side__mark"><svg viewBox="0 0 32 32"><path d="M6 9h20l-2.4 17.2a2 2 0 0 1-2 1.8H10.4a2 2 0 0 1-2-1.8z" fill="currentColor" opacity=".9"/><rect x="4" y="4" width="24" height="5" rx="2" fill="currentColor"/></svg></span>';
}

function admin_icon(string $name): string
{
    $extra = [
        'home' => '<path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/>',
        'inbox' => '<path d="M3 13h5l2 3h4l2-3h5"/><path d="M5 5h14l2 8v6H3v-6z"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'copy' => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'drag' => '<circle cx="9" cy="6" r="1.2"/><circle cx="15" cy="6" r="1.2"/><circle cx="9" cy="12" r="1.2"/><circle cx="15" cy="12" r="1.2"/><circle cx="9" cy="18" r="1.2"/><circle cx="15" cy="18" r="1.2"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'link' => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
    ];
    if (isset($extra[$name])) {
        return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $extra[$name] . '</svg>';
    }
    return icon($name);
}

/* ==========================================================================
   Giriş denemesi sınırı (IP başına, dosya tabanlı)
   ========================================================================== */

function login_attempts_file(): string
{
    return ROOT . '/data/login_attempts.json';
}

function login_attempts(string $ip, ?int $set = null): int
{
    $file = login_attempts_file();
    if (!is_dir(dirname($file))) {
        @mkdir(dirname($file), 0775, true);
    }
    $fh = @fopen($file, 'c+');
    if (!$fh) {
        return 0;
    }
    flock($fh, LOCK_EX);
    $data = json_decode((string) stream_get_contents($fh), true) ?: [];
    $now = time();
    foreach ($data as $k => $v) {
        if ($now - ($v['t'] ?? 0) > 900) unset($data[$k]); // 15 dk sonra sıfırlanır
    }
    $key = hash('sha256', $ip);
    if ($set !== null) {
        if ($set === 0) unset($data[$key]);
        else $data[$key] = ['n' => $set, 't' => $now];
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($data));
    }
    $n = (int) ($data[$key]['n'] ?? 0);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $n;
}

/* ==========================================================================
   Yerleşim
   ========================================================================== */

function admin_nav(): array
{
    $unread = (int) val('SELECT COUNT(*) FROM messages WHERE is_read = 0');
    return [
        ['', [['dashboard', 'Panel', 'home', admin_url()]]],
        ['Katalog', [
            ['products', 'Ürünler', 'cube', admin_url(['r' => 'crud', 'e' => 'products'])],
            ['categories', 'Kategoriler', 'layers', admin_url(['r' => 'crud', 'e' => 'categories'])],
            ['sectors', 'Sektörler', 'grid', admin_url(['r' => 'crud', 'e' => 'sectors'])],
        ]],
        ['İçerik', [
            ['pages', 'Sayfalar', 'doc', admin_url(['r' => 'crud', 'e' => 'pages'])],
            ['menu', 'Menüler', 'menu', admin_url(['r' => 'crud', 'e' => 'menu'])],
            ['sliders', 'Slider', 'print', admin_url(['r' => 'crud', 'e' => 'sliders'])],
            ['news', 'Haberler', 'quote', admin_url(['r' => 'crud', 'e' => 'news'])],
            ['certificates', 'Belgeler', 'shield', admin_url(['r' => 'crud', 'e' => 'certificates'])],
            ['clients', 'Referanslar', 'check', admin_url(['r' => 'crud', 'e' => 'clients'])],
        ]],
        ['İletişim', [
            ['messages', 'Mesajlar & Talepler', 'inbox', admin_url(['r' => 'messages']), $unread],
        ]],
        ['Sistem', [
            ['settings', 'Site ayarları', 'settings', admin_url(['r' => 'settings'])],
            ['users', 'Yöneticiler', 'user', admin_url(['r' => 'crud', 'e' => 'users'])],
        ]],
    ];
}

function admin_page(string $title, string $body, string $active = '', array $opts = []): void
{
    $u = current_admin();
    $siteName = setting('site_name', 'NowPlast');
    $editor = !empty($opts['editor']);
    ?><!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> · <?= e($siteName) ?> Yönetim</title>
    <link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <?php if ($editor): ?><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css"><?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('admin/assets/admin.css')) ?>">
    <?= admin_theme_style() ?>
</head>
<body class="adm">
<aside class="side" data-side>
    <a class="side__brand" href="<?= e(admin_url()) ?>">
        <?= admin_brand_mark() ?>
        <span><strong><?= e($siteName) ?></strong><small>Yönetim paneli</small></span>
    </a>
    <nav class="side__nav">
        <?php foreach (admin_nav() as [$group, $items]): ?>
            <?php if ($group): ?><p class="side__group"><?= e($group) ?></p><?php endif; ?>
            <?php foreach ($items as $it): ?>
                <a class="side__link<?= $active === $it[0] ? ' is-active' : '' ?>" href="<?= e($it[3]) ?>">
                    <?= admin_icon($it[2]) ?><span><?= e($it[1]) ?></span>
                    <?php if (!empty($it[4])): ?><b class="side__badge"><?= (int) $it[4] ?></b><?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    <a class="side__site" href="<?= e(url('')) ?>" target="_blank" rel="noopener"><?= admin_icon('external') ?> Siteyi görüntüle</a>
</aside>
<div class="side-ov" data-side-close></div>

<div class="main">
    <header class="top">
        <button type="button" class="ibtn top__burger" data-side-open aria-label="Menü"><?= icon('menu') ?></button>
        <h1 class="top__title"><?= e($title) ?></h1>
        <div class="top__user">
            <a href="<?= e(admin_url(['r' => 'profile'])) ?>" class="top__me"><span class="avatar"><?= e(mb_strtoupper(mb_substr($u['name'] ?: $u['username'], 0, 1))) ?></span><span class="top__name"><?= e($u['name'] ?: $u['username']) ?></span></a>
            <form method="post" action="<?= e(admin_url(['r' => 'logout'])) ?>"><?= csrf_field() ?><button class="ibtn" type="submit" title="Çıkış yap" aria-label="Çıkış yap"><?= admin_icon('logout') ?></button></form>
        </div>
    </header>
    <div class="content">
        <?php foreach (flashes() as [$type, $msg]): ?>
            <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
        <?php endforeach; ?>
        <?= $body ?>
    </div>
</div>

<div class="snack" data-snack hidden></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<?php if ($editor): ?><script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script><?php endif; ?>
<script src="<?= e(asset('admin/assets/admin.js')) ?>"></script>
</body>
</html><?php
}

/* ==========================================================================
   CRUD: liste
   ========================================================================== */

function crud_list(array $E): void
{
    if (!empty($E['tree'])) {
        crud_tree($E);
        return;
    }
    $t = $E['table'];
    $where = [];
    $params = [];
    $q = trim((string) ($_GET['q'] ?? ''));
    if ($q !== '' && !empty($E['search'])) {
        $where[] = '(' . implode(' OR ', array_map(fn($c) => "$c LIKE ?", $E['search'])) . ')';
        foreach ($E['search'] as $_) $params[] = '%' . $q . '%';
    }
    $fvals = [];
    foreach ($E['filters'] ?? [] as $col => $fd) {
        $v = (string) ($_GET['f_' . $col] ?? '');
        if ($v === '') continue;
        $fvals[$col] = $v;
        if ($v === '__none') {
            $where[] = "($col IS NULL OR $col = 0)";
        } elseif (!empty($fd['descendants'])) {
            $ids = admin_descendants('categories', (int) $v);
            $where[] = "$col IN (" . in_placeholders($ids) . ')';
            array_push($params, ...$ids);
        } else {
            $where[] = "$col = ?";
            $params[] = $v;
        }
    }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $total = (int) val("SELECT COUNT(*) FROM $t $w", $params);
    $pg = pagination($total, (int) ($E['per_page'] ?? 50), (int) ($_GET['p'] ?? 1));
    $items = rows("SELECT * FROM $t $w ORDER BY {$E['order']} LIMIT {$pg['per']} OFFSET {$pg['offset']}", $params);
    $sortable = !empty($E['sortable']) && $q === '';

    ob_start(); ?>
    <div class="bar">
        <form class="bar__filters" method="get">
            <input type="hidden" name="r" value="crud"><input type="hidden" name="e" value="<?= e($E['key']) ?>">
            <?php if (!empty($E['search'])): ?>
                <label class="search"><?= icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Ara…"></label>
            <?php endif; ?>
            <?php foreach ($E['filters'] ?? [] as $col => $fd): ?>
                <select name="f_<?= e($col) ?>" onchange="this.form.submit()">
                    <option value=""><?= e($fd['label']) ?></option>
                    <option value="__none"<?= ($fvals[$col] ?? '') === '__none' ? ' selected' : '' ?>>(Kategorisiz)</option>
                    <?php foreach (resolve_options($fd['options']) as $k => $lbl): ?>
                        <option value="<?= e($k) ?>"<?= (string) ($fvals[$col] ?? '') === (string) $k ? ' selected' : '' ?>><?= e($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endforeach; ?>
            <?php if ($q !== '' || $fvals): ?><a class="btn btn--text" href="<?= e(admin_url(['r' => 'crud', 'e' => $E['key']])) ?>">Temizle</a><?php endif; ?>
        </form>
        <a class="btn btn--primary" href="<?= e(admin_url(['r' => 'crud', 'e' => $E['key'], 'a' => 'edit'])) ?>"><?= admin_icon('plus') ?> Yeni <?= e(mb_strtolower($E['singular'])) ?></a>
    </div>

    <?php if ($sortable && $items): ?>
        <p class="hint"><?= admin_icon('drag') ?> Sırayı değiştirmek için satırları tutup sürükleyin. Sıralama sitede aynen uygulanır<?= $fvals ? ' (filtrelenmiş görünümde yalnızca görünen kayıtlar kendi aralarında sıralanır)' : '' ?>.</p>
    <?php endif; ?>

    <div class="card card--flush">
        <?php if ($items): ?>
            <table class="tbl">
                <thead><tr>
                    <?php if ($sortable): ?><th class="tbl__drag"></th><?php endif; ?>
                    <?php foreach ($E['list'] as [$col, $label, $fmt]): ?><th class="<?= in_array($fmt, ['toggle'], true) ? 'tbl__c' : '' ?>"><?= e($label) ?></th><?php endforeach; ?>
                    <th class="tbl__act"></th>
                </tr></thead>
                <tbody<?= $sortable ? ' data-sort-flat="' . e($E['key']) . '"' : '' ?>>
                <?php foreach ($items as $r): ?>
                    <tr data-id="<?= (int) $r['id'] ?>">
                        <?php if ($sortable): ?><td class="tbl__drag"><span class="handle" title="Sürükle"><?= admin_icon('drag') ?></span></td><?php endif; ?>
                        <?php foreach ($E['list'] as [$col, $label, $fmt]): ?>
                            <td class="<?= $fmt === 'toggle' ? 'tbl__c' : '' ?>"><?= list_cell($E, $r, $col, $fmt) ?></td>
                        <?php endforeach; ?>
                        <td class="tbl__act"><?= row_actions($E, $r) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="nodata">
                <p><?= $q !== '' || $fvals ? 'Aramanıza uygun kayıt bulunamadı.' : 'Henüz kayıt yok.' ?></p>
                <a class="btn btn--primary" href="<?= e(admin_url(['r' => 'crud', 'e' => $E['key'], 'a' => 'edit'])) ?>"><?= admin_icon('plus') ?> İlk kaydı ekleyin</a>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($pg['pages'] > 1): ?>
        <nav class="pages">
            <?php for ($i = 1; $i <= $pg['pages']; $i++): $qq = $_GET; $qq['p'] = $i; ?>
                <a class="<?= $i === $pg['page'] ? 'is-current' : '' ?>" href="?<?= e(http_build_query($qq)) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
    <p class="count"><?= $total ?> kayıt</p>
    <?php
    admin_page($E['title'], ob_get_clean(), $E['key']);
}

function list_cell(array $E, array $r, string $col, string $fmt): string
{
    $v = $r[$col] ?? null;
    if (str_starts_with($fmt, 'lookup:')) {
        static $cache = [];
        $fn = substr($fmt, 7);
        $cache[$fn] = $cache[$fn] ?? resolve_options($fn);
        $label = $v !== null && $v !== '' ? ($cache[$fn][$v] ?? '—') : '—';
        return '<span class="muted">' . e(ltrim(preg_replace('~^(— )+~u', '', $label))) . '</span>';
    }
    switch ($fmt) {
        case 'image':
        case 'image-wide':
            $src = $v ? upload_url($v) : '';
            return '<span class="thumb' . ($fmt === 'image-wide' ? ' thumb--wide' : '') . '">' . ($src ? '<img src="' . e($src) . '" alt="" loading="lazy">' : '') . '</span>';
        case 'title':
            $href = admin_url(['r' => 'crud', 'e' => $E['key'], 'a' => 'edit', 'id' => $r['id']]);
            return '<a class="tbl__title" href="' . e($href) . '">' . e($v !== null && $v !== '' ? $v : '(başlıksız)') . '</a>';
        case 'mono':
            return '<code>' . e($v) . '</code>';
        case 'muted':
            return '<span class="muted">' . e(excerpt($v, 70)) . '</span>';
        case 'slug':
            return '<span class="muted">/' . e($v) . '</span>';
        case 'volume':
            return e(fmt_volume($v));
        case 'date':
            return '<span class="muted">' . e($v ? fmt_date($v) : '—') . '</span>';
        case 'sector_icon':
            return '<span class="sico">' . sector_icon($v) . '</span>';
        case 'toggle':
            return '<button type="button" class="switch' . ((int) $v ? ' is-on' : '') . '" data-toggle="' . e($E['key']) . '" data-id="' . (int) $r['id'] . '" data-field="' . e($col) . '" aria-pressed="' . ((int) $v ? 'true' : 'false') . '"><span></span></button>';
        default:
            return e($v);
    }
}

function row_actions(array $E, array $r): string
{
    $h = '<div class="acts">';
    if (!empty($E['view_url'])) {
        $h .= '<a class="ibtn" href="' . e($E['view_url']($r)) . '" target="_blank" rel="noopener" title="Sitede gör">' . admin_icon('eye') . '</a>';
    }
    if (!empty($E['copyable'])) {
        $h .= '<form method="post" action="' . e(admin_url(['r' => 'crud', 'e' => $E['key'], 'a' => 'copy', 'id' => $r['id']])) . '">' . csrf_field()
            . '<button class="ibtn" type="submit" title="Kopyala">' . admin_icon('copy') . '</button></form>';
    }
    $h .= '<a class="ibtn" href="' . e(admin_url(['r' => 'crud', 'e' => $E['key'], 'a' => 'edit', 'id' => $r['id']])) . '" title="Düzenle">' . admin_icon('edit') . '</a>';
    $h .= '<form method="post" action="' . e(admin_url(['r' => 'crud', 'e' => $E['key'], 'a' => 'delete', 'id' => $r['id']])) . '" data-confirm="Bu kaydı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.">'
        . csrf_field() . '<button class="ibtn ibtn--danger" type="submit" title="Sil">' . admin_icon('trash') . '</button></form>';
    return $h . '</div>';
}

/* Ağaç yapılı listeler (kategoriler, menüler): iç içe sürükle-bırak */
function crud_tree(array $E): void
{
    $all = rows("SELECT * FROM {$E['table']} ORDER BY {$E['order']}");
    $groups = $E['groups'] ?? ['' => ''];
    $gcol = $E['tree_group'] ?? null;
    $tf = $E['title_field'];

    $render = function (array $map, int $pid, string $group) use (&$render, $E, $tf): string {
        $h = '<ul class="tree" data-parent="' . $pid . '" data-group="' . e($E['key'] . ':' . $group) . '">';
        foreach ($map[$pid] ?? [] as $r) {
            $id = (int) $r['id'];
            $meta = '';
            if ($E['key'] === 'menu') {
                $meta = '<span class="tag">' . e(menu_link_label($r)) . '</span>';
                if ($r['auto_children']) $meta .= '<span class="tag tag--brand">+ ' . ($r['auto_children'] === 'categories' ? 'kategoriler' : 'sektörler') . '</span>';
            } elseif ($E['key'] === 'categories') {
                $n = (int) val('SELECT COUNT(*) FROM products WHERE category_id = ?', [$id]);
                $meta = '<span class="tag">' . $n . ' ürün</span>';
            }
            if (isset($r['is_active']) && !(int) $r['is_active']) $meta .= '<span class="tag tag--off">Pasif</span>';
            $thumb = isset($r['image']) ? '<span class="thumb thumb--sm">' . ($r['image'] ? '<img src="' . e(upload_url($r['image'])) . '" alt="">' : '') . '</span>' : '';
            $h .= '<li data-id="' . $id . '"><div class="tree__row">'
                . '<span class="handle" title="Sürükle">' . admin_icon('drag') . '</span>' . $thumb
                . '<a class="tree__title" href="' . e(admin_url(['r' => 'crud', 'e' => $E['key'], 'a' => 'edit', 'id' => $id])) . '">' . e($r[$tf]) . '</a>'
                . '<span class="tree__meta">' . $meta . '</span>'
                . (isset($r['is_active']) ? list_cell($E, $r, 'is_active', 'toggle') : '')
                . '<a class="ibtn" title="Alt öğe ekle" href="' . e(admin_url(['r' => 'crud', 'e' => $E['key'], 'a' => 'edit', 'parent' => $id] + ($E['tree_group'] ?? null ? ['group' => $r[$E['tree_group']]] : []))) . '">' . admin_icon('plus') . '</a>'
                . row_actions($E, $r)
                . '</div>' . $render($map, $id, $group) . '</li>';
        }
        return $h . '</ul>';
    };

    ob_start(); ?>
    <div class="bar">
        <p class="hint hint--inline"><?= admin_icon('drag') ?> Öğeleri sürükleyerek sıralayın; bir öğeyi başka bir öğenin altına bırakarak alt öğe yapın.</p>
        <a class="btn btn--primary" href="<?= e(admin_url(['r' => 'crud', 'e' => $E['key'], 'a' => 'edit'])) ?>"><?= admin_icon('plus') ?> Yeni <?= e(mb_strtolower($E['singular'])) ?></a>
    </div>
    <?php foreach ($groups as $gkey => $glabel):
        $map = [];
        foreach ($all as $r) {
            if ($gcol && $r[$gcol] !== $gkey) continue;
            $map[(int) ($r['parent_id'] ?? 0)][] = $r;
        }
        ?>
        <section class="card">
            <?php if ($glabel): ?>
                <div class="card__head"><h2><?= e($glabel) ?></h2>
                    <a class="btn btn--sm btn--ghost" href="<?= e(admin_url(['r' => 'crud', 'e' => $E['key'], 'a' => 'edit', 'group' => $gkey])) ?>"><?= admin_icon('plus') ?> Ekle</a></div>
            <?php endif; ?>
            <div data-sort-tree="<?= e($E['key']) ?>">
                <?= $render($map, 0, (string) $gkey) ?>
            </div>
            <?php if (empty($map[0])): ?><p class="nodata">Henüz öğe yok.</p><?php endif; ?>
        </section>
    <?php endforeach;
    admin_page($E['title'], ob_get_clean(), $E['key']);
}

function menu_link_label(array $r): string
{
    $v = (string) $r['link_value'];
    switch ($r['link_type']) {
        case 'route': return 'Sayfa: ' . (route_options()[$v] ?? $v);
        case 'page': return 'Sayfa: ' . (val('SELECT title FROM pages WHERE id = ?', [(int) $v]) ?: '?');
        case 'category': return 'Kategori: ' . (val('SELECT name FROM categories WHERE id = ?', [(int) $v]) ?: '?');
        case 'sector': return 'Sektör: ' . (val('SELECT name FROM sectors WHERE id = ?', [(int) $v]) ?: '?');
        case 'none': return 'Bağlantısız başlık';
        default: return $v !== '' ? $v : 'Bağlantı yok';
    }
}

/* ==========================================================================
   CRUD: form
   ========================================================================== */

function crud_form(array $E): void
{
    $id = (int) ($_GET['id'] ?? 0);
    $rec = $id ? row("SELECT * FROM {$E['table']} WHERE id = ?", [$id]) : null;
    if ($id && !$rec) {
        flash('error', 'Kayıt bulunamadı.');
        redirect(admin_url(['r' => 'crud', 'e' => $E['key']]));
    }
    $old = $_SESSION['old_input'] ?? null;
    unset($_SESSION['old_input']);

    $prefill = [];
    if (!$rec) {
        if (!empty($_GET['parent'])) $prefill['parent_id'] = (int) $_GET['parent'];
        if (!empty($_GET['group']) && !empty($E['tree_group'])) $prefill[$E['tree_group']] = (string) $_GET['group'];
        if (!empty($prefill['parent_id']) && !empty($E['tree_group'])) {
            $p = row("SELECT {$E['tree_group']} AS g FROM {$E['table']} WHERE id = ?", [$prefill['parent_id']]);
            if ($p) $prefill[$E['tree_group']] = $p['g'];
        }
    }
    $value = function (array $f) use ($rec, $old, $prefill) {
        $n = $f['name'];
        if ($old !== null && array_key_exists($n, $old)) return $old[$n];
        if ($rec) return $rec[$n] ?? null;
        if (array_key_exists($n, $prefill)) return $prefill[$n];
        $d = $f['default'] ?? null;
        return $d instanceof Closure ? $d() : $d;
    };

    $buckets = ['main' => [], 'specs' => [], 'media' => [], 'seo' => [], 'side' => []];
    $editor = false;
    foreach ($E['fields'] as $f) {
        if ($f['type'] === 'html') $editor = true;
        $b = !empty($f['side']) ? 'side' : ($f['group'] ?? 'main');
        $buckets[$b][] = $f;
    }
    $render = fn(array $list) => implode('', array_map(fn($f) => field_html($E, $f, $value($f), $rec, $old), $list));
    $title = $rec ? ($E['singular'] . ': ' . ($rec[$E['title_field']] ?? '')) : 'Yeni ' . mb_strtolower($E['singular']);

    ob_start(); ?>
    <form class="form" method="post" enctype="multipart/form-data" action="<?= e(admin_url(['r' => 'crud', 'e' => $E['key'], 'a' => 'save'])) ?>" data-dirty-guard>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="formbar">
            <a class="btn btn--text" href="<?= e(admin_url(['r' => 'crud', 'e' => $E['key']])) ?>">← <?= e($E['title']) ?></a>
            <div class="formbar__right">
                <?php if ($rec && !empty($E['view_url'])): ?><a class="btn btn--ghost" href="<?= e($E['view_url']($rec)) ?>" target="_blank" rel="noopener"><?= admin_icon('eye') ?> Sitede gör</a><?php endif; ?>
                <button class="btn btn--ghost" type="submit" name="after" value="list">Kaydet ve listeye dön</button>
                <button class="btn btn--primary" type="submit" name="after" value="stay">Kaydet</button>
            </div>
        </div>
        <div class="layout<?= $buckets['side'] ? ' layout--side' : '' ?>">
            <div class="layout__main">
                <section class="card"><div class="fields"><?= $render($buckets['main']) ?></div></section>
                <?php if ($buckets['specs']): ?><section class="card"><div class="card__head"><h2>Teknik özellikler</h2><span class="muted">Boş bırakılan alanlar sitede gösterilmez.</span></div><div class="fields"><?= $render($buckets['specs']) ?></div></section><?php endif; ?>
                <?php if ($buckets['media']): ?><section class="card"><div class="card__head"><h2>Görsel galerisi</h2></div><div class="fields"><?= $render($buckets['media']) ?></div></section><?php endif; ?>
                <?php if ($buckets['seo']): ?>
                    <details class="card card--fold"<?= ($rec['meta_title'] ?? '') || ($rec['meta_description'] ?? '') ? ' open' : '' ?>>
                        <summary><h2>SEO ayarları</h2><span class="muted">İsteğe bağlı</span></summary>
                        <div class="fields"><?= $render($buckets['seo']) ?></div>
                    </details>
                <?php endif; ?>
            </div>
            <?php if ($buckets['side']): ?>
                <aside class="layout__side"><section class="card"><div class="fields"><?= $render($buckets['side']) ?></div></section>
                    <?php if ($rec && !empty($rec['updated_at'])): ?><p class="muted small">Son güncelleme: <?= e(date('d.m.Y H:i', strtotime($rec['updated_at']))) ?></p><?php endif; ?>
                </aside>
            <?php endif; ?>
        </div>
    </form>
    <?php
    admin_page($title, ob_get_clean(), $E['key'], ['editor' => $editor]);
}

function field_html(array $E, array $f, $v, ?array $rec, ?array $old): string
{
    $n = $f['name'];
    $id = 'f_' . $n;
    $label = e($f['label']) . (!empty($f['required']) ? ' <b class="req">*</b>' : '');
    $help = !empty($f['help']) ? '<small class="help">' . e($f['help']) . '</small>' : '';
    $cls = 'field' . (!empty($f['half']) ? ' field--half' : '') . (!empty($f['third']) ? ' field--third' : '');
    $req = !empty($f['required']) ? ' required' : '';
    $ph = !empty($f['placeholder']) ? ' placeholder="' . e($f['placeholder']) . '"' : '';

    switch ($f['type']) {
        case 'textarea':
            $in = '<textarea id="' . $id . '" name="' . e($n) . '" rows="' . (int) ($f['rows'] ?? 4) . '"' . $req . $ph . '>' . e($v) . '</textarea>';
            break;
        case 'html':
            $in = '<textarea id="' . $id . '" name="' . e($n) . '" class="js-editor" rows="10">' . e($v) . '</textarea>';
            break;
        case 'number':
            $in = '<input id="' . $id . '" type="text" inputmode="decimal" name="' . e($n) . '" value="' . e($v !== null && $v !== '' ? fmt_num($v) : '') . '"' . $req . $ph . '>';
            break;
        case 'date':
            $in = '<input id="' . $id . '" type="date" name="' . e($n) . '" value="' . e($v ? substr((string) $v, 0, 10) : '') . '"' . $req . '>';
            break;
        case 'email':
        case 'url':
            $in = '<input id="' . $id . '" type="' . $f['type'] . '" name="' . e($n) . '" value="' . e($v) . '"' . $req . $ph . '>';
            break;
        case 'color':
            $in = '<div class="color"><input type="color" value="' . e($v ?: '#1557ff') . '" data-color-for="' . $id . '"><input id="' . $id . '" type="text" name="' . e($n) . '" value="' . e($v) . '" pattern="#[0-9a-fA-F]{6}" placeholder="#1557ff"></div>';
            break;
        case 'slug':
            $in = '<div class="prefix"><span>' . e(slug_prefix($E)) . '</span><input id="' . $id . '" name="' . e($n) . '" value="' . e($v) . '" data-slug-from="f_' . e($f['from']) . '"' . ($rec ? ' data-slug-locked' : '') . ' placeholder="otomatik oluşturulur"></div>';
            break;
        case 'select':
            $opts = resolve_options($f['options'] ?? []);
            $in = '<select id="' . $id . '" name="' . e($n) . '"' . $req . '>';
            if (array_key_exists('empty', $f)) $in .= '<option value="">' . e($f['empty']) . '</option>';
            foreach ($opts as $k => $lbl) {
                if (!empty($f['tree_parent']) && $rec && (string) $k === (string) $rec['id']) continue;
                $in .= '<option value="' . e($k) . '"' . ((string) $v === (string) $k ? ' selected' : '') . '>' . e($lbl) . '</option>';
            }
            $in .= '</select>';
            break;
        case 'checkbox':
            return '<div class="field field--check"><label class="check"><input type="checkbox" name="' . e($n) . '" value="1"' . ((int) $v ? ' checked' : '') . '><span class="check__sw"></span><span>' . e($f['label']) . '</span></label>' . $help . '</div>';
        case 'image':
        case 'file':
            $isImg = $f['type'] === 'image';
            $prev = '';
            if ($v) {
                $u = upload_url($v);
                $prev = '<div class="upl__cur">' . ($isImg ? '<a href="' . e($u) . '" target="_blank"><img src="' . e($u) . '" alt=""></a>' : '<a class="upl__file" href="' . e($u) . '" target="_blank">' . icon('doc') . e(basename($v)) . '</a>')
                    . '<label class="check check--sm"><input type="checkbox" name="' . e($n) . '__remove" value="1"><span>Kaldır</span></label></div>';
            }
            $in = '<div class="upl" data-upl>' . $prev . '<label class="upl__drop"><input type="file" name="' . e($n) . '" accept="' . ($isImg ? 'image/jpeg,image/png,image/webp,image/gif' : 'application/pdf,image/*') . '"><span>' . ($v ? 'Değiştirmek için dosya seçin' : 'Dosya seçin veya sürükleyin') . '</span><small>' . ($isImg ? 'JPG, PNG, WEBP · en fazla ' . e(ini_get('upload_max_filesize')) : 'PDF · en fazla ' . e(ini_get('upload_max_filesize'))) . '</small></label></div>';
            break;
        case 'multicheck':
            $opts = resolve_options($f['options']);
            $sel = $old !== null ? array_map('intval', (array) ($old[$n] ?? [])) : ($rec ? array_map('intval', array_column(rows("SELECT {$f['pivot']['foreign']} AS v FROM {$f['pivot']['table']} WHERE {$f['pivot']['local']} = ?", [$rec['id']]), 'v')) : []);
            $in = '<div class="checks">';
            foreach ($opts as $k => $lbl) {
                $in .= '<label class="check check--sm"><input type="checkbox" name="' . e($n) . '[]" value="' . e($k) . '"' . (in_array((int) $k, $sel, true) ? ' checked' : '') . '><span>' . e($lbl) . '</span></label>';
            }
            if (!$opts) $in .= '<span class="muted small">Önce sektör ekleyin.</span>';
            $in .= '</div>';
            break;
        case 'gallery':
            $imgs = $rec ? rows('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$rec['id']]) : [];
            $in = '<div class="gal" data-gallery-sort>';
            foreach ($imgs as $im) {
                $in .= '<div class="gal__item" data-id="' . (int) $im['id'] . '"><img src="' . e(upload_url($im['path'])) . '" alt=""><label class="gal__rm" title="Sil"><input type="checkbox" name="gallery_remove[]" value="' . (int) $im['id'] . '">' . admin_icon('trash') . '</label></div>';
            }
            $in .= '</div><input type="hidden" name="gallery_order" value="' . e(implode(',', array_column($imgs, 'id'))) . '">'
                . '<label class="upl__drop"><input type="file" name="gallery_new[]" multiple accept="image/jpeg,image/png,image/webp,image/gif"><span>Birden fazla görsel seçebilirsiniz</span><small>Görselleri sürükleyerek sıralayın; silmek için çöp kutusunu işaretleyin.</small></label>';
            return '<div class="field field--full">' . $in . '</div>';
        case 'password':
            $in = '<input id="' . $id . '" type="password" name="' . e($n) . '" autocomplete="new-password" minlength="8"' . ($rec ? '' : ' required') . '>';
            break;
        case 'menulink':
            return menulink_html($rec, $old);
        default:
            $dl = '';
            if (!empty($f['datalist'])) {
                $dl = '<datalist id="dl_' . e($n) . '">' . implode('', array_map(fn($o) => '<option value="' . e($o) . '">', $f['datalist'])) . '</datalist>';
            }
            $in = '<input id="' . $id . '" type="text" name="' . e($n) . '" value="' . e($v) . '"' . $req . $ph . ($dl ? ' list="dl_' . e($n) . '"' : '') . '>' . $dl;
    }
    return '<div class="' . $cls . '"><label for="' . $id . '">' . $label . '</label>' . $in . $help . '</div>';
}

function slug_prefix(array $E): string
{
    $map = ['products' => 'urun/', 'categories' => 'urunler/', 'sectors' => 'sektor/', 'news' => 'haber/', 'pages' => ''];
    return '/' . ($map[$E['key']] ?? '');
}

function menulink_html(?array $rec, ?array $old): string
{
    $type = (string) ($old['link_type'] ?? ($rec['link_type'] ?? 'route'));
    $val = (string) ($old !== null ? ($old['link_value_' . $type] ?? '') : ($rec['link_value'] ?? ''));
    $types = ['route' => 'Sabit sayfa', 'page' => 'İçerik sayfası', 'category' => 'Ürün kategorisi', 'sector' => 'Sektör', 'url' => 'Özel bağlantı', 'none' => 'Bağlantı yok (yalnızca başlık)'];
    $sel = function (string $name, array $opts, string $for) use ($type, $val) {
        $h = '<select name="link_value_' . $name . '" data-for="' . $for . '"' . ($type !== $for ? ' hidden' : '') . '>';
        foreach ($opts as $k => $l) $h .= '<option value="' . e($k) . '"' . ($type === $for && (string) $val === (string) $k ? ' selected' : '') . '>' . e($l) . '</option>';
        return $h . '</select>';
    };
    $pages = [];
    foreach (rows('SELECT id, title FROM pages ORDER BY title') as $p) $pages[$p['id']] = $p['title'];
    $h = '<div class="field"><label>Bağlantı türü</label><div class="linkpick" data-linkpick><select name="link_type">';
    foreach ($types as $k => $l) $h .= '<option value="' . $k . '"' . ($type === $k ? ' selected' : '') . '>' . e($l) . '</option>';
    $h .= '</select>';
    $h .= $sel('route', route_options(), 'route');
    $h .= $sel('page', $pages ?: ['' => '(sayfa yok)'], 'page');
    $h .= $sel('category', opt_category_tree() ?: ['' => '(kategori yok)'], 'category');
    $h .= $sel('sector', opt_pairs('SELECT id, name FROM sectors ORDER BY sort_order, name') ?: ['' => '(sektör yok)'], 'sector');
    $h .= '<input type="text" name="link_value_url" data-for="url" placeholder="https://… veya /sayfa-adresi" value="' . e($type === 'url' ? $val : '') . '"' . ($type !== 'url' ? ' hidden' : '') . '>';
    $h .= '</div></div>';
    return $h;
}

/* ==========================================================================
   CRUD: kaydet / sil / kopyala
   ========================================================================== */

function crud_save(array $E): void
{
    csrf_check();
    $t = $E['table'];
    $id = (int) ($_POST['id'] ?? 0);
    $rec = $id ? row("SELECT * FROM $t WHERE id = ?", [$id]) : null;
    if ($id && !$rec) {
        flash('error', 'Kayıt bulunamadı.');
        redirect(admin_url(['r' => 'crud', 'e' => $E['key']]));
    }

    $data = [];
    $errors = [];
    $after = [];
    $oldFiles = [];

    foreach ($E['fields'] as $f) {
        $n = $f['name'];
        switch ($f['type']) {
            case 'checkbox':
                $data[$n] = isset($_POST[$n]) ? 1 : 0;
                break;
            case 'number':
                $raw = trim((string) ($_POST[$n] ?? ''));
                $norm = str_replace(' ', '', $raw);
                if (preg_match('~^\d{1,3}(\.\d{3})+(,\d+)?$~', $norm)) $norm = str_replace('.', '', $norm); // 1.250,5
                $norm = str_replace(',', '.', $norm);
                if ($raw === '') $data[$n] = null;
                elseif (is_numeric($norm)) $data[$n] = $norm + 0;
                else $errors[] = $f['label'] . ' sayı olmalıdır.';
                break;
            case 'select':
                $v = (string) ($_POST[$n] ?? '');
                $data[$n] = $v === '' ? null : $v;
                if (!empty($f['tree_parent']) && $v !== '' && $id) {
                    if ((int) $v === $id || in_array((int) $v, admin_descendants($t, $id), true)) {
                        $errors[] = 'Bir öğe kendisinin veya kendi alt öğesinin altına taşınamaz.';
                    }
                }
                break;
            case 'slug':
                break; // aşağıda
            case 'image':
            case 'file':
                $up = handle_upload($_FILES[$n] ?? [], $f['type']);
                if ($up['error']) {
                    $errors[] = $f['label'] . ': ' . $up['error'];
                } elseif ($up['path']) {
                    $data[$n] = $up['path'];
                    if ($rec && $rec[$n]) $oldFiles[] = $rec[$n];
                } elseif (!empty($_POST[$n . '__remove'])) {
                    $data[$n] = null;
                    if ($rec && $rec[$n]) $oldFiles[] = $rec[$n];
                }
                break;
            case 'password':
                $v = (string) ($_POST[$n] ?? '');
                if ($v !== '') {
                    if (mb_strlen($v) < 8) $errors[] = 'Şifre en az 8 karakter olmalıdır.';
                    else $data[$n] = password_hash($v, PASSWORD_DEFAULT);
                } elseif (!$id) {
                    $errors[] = 'Şifre zorunludur.';
                }
                break;
            case 'menulink':
                $type = (string) ($_POST['link_type'] ?? 'url');
                if (!in_array($type, ['route', 'page', 'category', 'sector', 'url', 'none'], true)) $type = 'url';
                $data['link_type'] = $type;
                $data['link_value'] = $type === 'none' ? null : mb_substr(trim((string) ($_POST['link_value_' . $type] ?? '')), 0, 255);
                break;
            case 'multicheck':
            case 'gallery':
                $after[] = $f;
                break;
            case 'html':
                $v = trim((string) ($_POST[$n] ?? ''));
                $data[$n] = ($v === '' || $v === '<p><br></p>') ? null : $v;
                break;
            default:
                $v = trim((string) ($_POST[$n] ?? ''));
                $data[$n] = $v === '' ? null : $v;
        }
        if (!empty($f['required']) && array_key_exists($n, $data) && ($data[$n] === null || $data[$n] === '')) {
            $errors[] = $f['label'] . ' alanı zorunludur.';
        }
        if (!empty($f['unique']) && ($data[$n] ?? null) !== null
            && val("SELECT COUNT(*) FROM $t WHERE $n = ? AND id <> ?", [$data[$n], $id]) > 0) {
            $errors[] = $f['label'] . ' zaten kullanılıyor.';
        }
    }

    foreach ($E['fields'] as $f) {
        if ($f['type'] !== 'slug') continue;
        $src = trim((string) ($_POST[$f['name']] ?? ''));
        if ($src === '') $src = (string) ($data[$f['from']] ?? '');
        $data[$f['name']] = unique_slug($t, slugify($src), $id, !empty($E['reserved_slugs']));
    }

    if ($errors) {
        // Yeni yüklenen dosyalar kayda bağlanmadığı için temizlenir.
        foreach ($E['fields'] as $f) {
            if (in_array($f['type'], ['image', 'file'], true) && !empty($data[$f['name']]) && (!$rec || $data[$f['name']] !== $rec[$f['name']])) {
                delete_upload($data[$f['name']]);
            }
        }
        $_SESSION['old_input'] = array_diff_key($_POST, array_flip(['_csrf', 'password_hash']));
        flash('error', implode(' ', array_unique($errors)));
        redirect(admin_url(['r' => 'crud', 'e' => $E['key'], 'a' => 'edit'] + ($id ? ['id' => $id] : [])));
    }

    if (!empty($E['timestamps'])) {
        $data['updated_at'] = now();
        if (!$id) $data['created_at'] = now();
    }
    if (!empty($E['stamp_updated'])) $data['updated_at'] = now();
    if (!empty($E['stamp_created']) && !$id) $data['created_at'] = now();

    if ($id) {
        db_update($t, $id, $data);
    } else {
        if (!empty($E['sortable'])) {
            $data['sort_order'] = (int) val("SELECT COALESCE(MAX(sort_order), 0) FROM $t") + 1;
        }
        $id = db_insert($t, $data);
    }
    foreach ($oldFiles as $of) delete_upload($of);

    foreach ($after as $f) {
        if ($f['type'] === 'multicheck') {
            $p = $f['pivot'];
            q("DELETE FROM {$p['table']} WHERE {$p['local']} = ?", [$id]);
            $valid = array_map('intval', array_keys(resolve_options($f['options'])));
            foreach (array_unique(array_map('intval', (array) ($_POST[$f['name']] ?? []))) as $fid) {
                if (in_array($fid, $valid, true)) db_insert($p['table'], [$p['local'] => $id, $p['foreign'] => $fid]);
            }
        } elseif ($f['type'] === 'gallery') {
            gallery_save($id, $errors);
        }
    }

    if ($errors) flash('error', implode(' ', $errors));
    flash('success', $E['singular'] . ' kaydedildi.');
    redirect(($_POST['after'] ?? '') === 'list'
        ? admin_url(['r' => 'crud', 'e' => $E['key']])
        : admin_url(['r' => 'crud', 'e' => $E['key'], 'a' => 'edit', 'id' => $id]));
}

function gallery_save(int $pid, array &$errors): void
{
    $remove = array_map('intval', (array) ($_POST['gallery_remove'] ?? []));
    foreach ($remove as $rid) {
        $im = row('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [$rid, $pid]);
        if ($im) {
            delete_upload($im['path']);
            q('DELETE FROM product_images WHERE id = ?', [$rid]);
        }
    }
    $order = array_filter(array_map('intval', explode(',', (string) ($_POST['gallery_order'] ?? ''))));
    $i = 0;
    foreach ($order as $oid) {
        q('UPDATE product_images SET sort_order = ? WHERE id = ? AND product_id = ?', [++$i, $oid, $pid]);
    }
    $next = (int) val('SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = ?', [$pid]);
    foreach (normalize_files($_FILES['gallery_new'] ?? null) as $file) {
        $up = handle_upload($file, 'image');
        if ($up['error']) $errors[] = ($file['name'] ?: 'Görsel') . ': ' . $up['error'];
        elseif ($up['path']) db_insert('product_images', ['product_id' => $pid, 'path' => $up['path'], 'sort_order' => ++$next]);
    }
}

function crud_delete(array $E): void
{
    csrf_check();
    if (!is_post()) redirect(admin_url(['r' => 'crud', 'e' => $E['key']]));
    $t = $E['table'];
    $id = (int) ($_GET['id'] ?? 0);
    $rec = row("SELECT * FROM $t WHERE id = ?", [$id]);
    if (!$rec) {
        flash('error', 'Kayıt bulunamadı.');
        redirect(admin_url(['r' => 'crud', 'e' => $E['key']]));
    }
    if (!empty($E['before_delete'])) {
        $err = $E['before_delete']($rec);
        if ($err) {
            flash('error', $err);
            redirect(admin_url(['r' => 'crud', 'e' => $E['key']]));
        }
    }
    foreach ($E['fields'] as $f) {
        if (in_array($f['type'], ['image', 'file'], true)) delete_upload($rec[$f['name']] ?? null);
        if ($f['type'] === 'multicheck') q("DELETE FROM {$f['pivot']['table']} WHERE {$f['pivot']['local']} = ?", [$id]);
        if ($f['type'] === 'gallery') {
            foreach (rows('SELECT path FROM product_images WHERE product_id = ?', [$id]) as $im) delete_upload($im['path']);
            q('DELETE FROM product_images WHERE product_id = ?', [$id]);
        }
    }
    if (!empty($E['tree'])) {
        // Alt öğeler bir üst seviyeye taşınır, silinmez.
        q("UPDATE $t SET parent_id = ? WHERE parent_id = ?", [$rec['parent_id'] ?: null, $id]);
    }
    q("DELETE FROM $t WHERE id = ?", [$id]);
    flash('success', $E['singular'] . ' silindi.');
    redirect(admin_url(['r' => 'crud', 'e' => $E['key']]));
}

function crud_copy(array $E): void
{
    csrf_check();
    if (empty($E['copyable'])) redirect(admin_url(['r' => 'crud', 'e' => $E['key']]));
    $t = $E['table'];
    $id = (int) ($_GET['id'] ?? 0);
    $rec = row("SELECT * FROM $t WHERE id = ?", [$id]);
    if (!$rec) redirect(admin_url(['r' => 'crud', 'e' => $E['key']]));

    $data = $rec;
    unset($data['id']);
    $data['name'] = $rec['name'] . ' (kopya)';
    $data['slug'] = unique_slug($t, $rec['slug'] . '-kopya');
    $data['is_active'] = 0;
    $data['sort_order'] = (int) val("SELECT COALESCE(MAX(sort_order), 0) FROM $t") + 1;
    foreach ($E['fields'] as $f) {
        if (in_array($f['type'], ['image', 'file'], true)) $data[$f['name']] = copy_upload($rec[$f['name']] ?? null);
    }
    if (!empty($E['timestamps'])) $data['created_at'] = $data['updated_at'] = now();
    $newId = db_insert($t, $data);

    foreach ($E['fields'] as $f) {
        if ($f['type'] === 'multicheck') {
            $p = $f['pivot'];
            foreach (rows("SELECT {$p['foreign']} AS v FROM {$p['table']} WHERE {$p['local']} = ?", [$id]) as $r) {
                db_insert($p['table'], [$p['local'] => $newId, $p['foreign'] => $r['v']]);
            }
        }
        if ($f['type'] === 'gallery') {
            foreach (rows('SELECT path, sort_order FROM product_images WHERE product_id = ?', [$id]) as $im) {
                if ($np = copy_upload($im['path'])) db_insert('product_images', ['product_id' => $newId, 'path' => $np, 'sort_order' => $im['sort_order']]);
            }
        }
    }
    flash('success', 'Kopya oluşturuldu (yayında değil). Bilgileri düzenleyip yayına alabilirsiniz.');
    redirect(admin_url(['r' => 'crud', 'e' => $E['key'], 'a' => 'edit', 'id' => $newId]));
}

/* ==========================================================================
   AJAX: sıralama ve aç/kapa
   ========================================================================== */

function ajax_sort(): void
{
    csrf_check();
    $in = json_decode((string) file_get_contents('php://input'), true) ?: [];
    $E = entity_or_404((string) ($in['entity'] ?? ''));
    if (empty($E['sortable'])) json_out(['ok' => false, 'error' => 'Bu içerik sıralanamaz.'], 400);
    $t = $E['table'];
    $pdo = db();
    $pdo->beginTransaction();
    try {
        if (!empty($E['tree'])) {
            foreach ((array) ($in['lists'] ?? []) as $list) {
                $pid = (int) ($list['parent'] ?? 0);
                $ids = array_values(array_filter(array_map('intval', (array) ($list['ids'] ?? []))));
                foreach ($ids as $i => $id) {
                    if ($id === $pid) continue;
                    q("UPDATE $t SET parent_id = ?, sort_order = ? WHERE id = ?", [$pid ?: null, $i + 1, $id]);
                }
            }
        } else {
            $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($in['ids'] ?? [])))));
            if ($ids) {
                // Önce tüm tabloyu mevcut sırasıyla 1..n numaralandır, sonra görünen kayıtları kendi sıra numaraları içinde yeniden diz.
                $i = 0;
                foreach (rows("SELECT id FROM $t ORDER BY {$E['order']}") as $r) {
                    q("UPDATE $t SET sort_order = ? WHERE id = ?", [++$i, $r['id']]);
                }
                $slots = array_map('intval', array_column(rows("SELECT sort_order FROM $t WHERE id IN (" . in_placeholders($ids) . ') ORDER BY sort_order', $ids), 'sort_order'));
                foreach ($ids as $k => $id) {
                    if (isset($slots[$k])) q("UPDATE $t SET sort_order = ? WHERE id = ?", [$slots[$k], $id]);
                }
            }
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        json_out(['ok' => false, 'error' => 'Sıralama kaydedilemedi.'], 500);
    }
    json_out(['ok' => true]);
}

function ajax_toggle(): void
{
    csrf_check();
    $in = json_decode((string) file_get_contents('php://input'), true) ?: [];
    $E = entity_or_404((string) ($in['entity'] ?? ''));
    $field = (string) ($in['field'] ?? '');
    $ok = false;
    foreach ($E['fields'] as $f) {
        if ($f['name'] === $field && $f['type'] === 'checkbox') $ok = true;
    }
    if (!$ok) json_out(['ok' => false, 'error' => 'Geçersiz alan.'], 400);
    $id = (int) ($in['id'] ?? 0);
    $cur = val("SELECT $field FROM {$E['table']} WHERE id = ?", [$id]);
    if ($cur === null) json_out(['ok' => false, 'error' => 'Kayıt bulunamadı.'], 404);
    $new = (int) $cur ? 0 : 1;
    q("UPDATE {$E['table']} SET $field = ? WHERE id = ?", [$new, $id]);
    json_out(['ok' => true, 'value' => $new]);
}
