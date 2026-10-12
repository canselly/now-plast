<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (!db_installed()) {
    redirect(url('install.php'));
}
require __DIR__ . '/lib.php';

header('X-Frame-Options: SAMEORIGIN');
header('X-Robots-Tag: noindex, nofollow');

$r = (string) ($_GET['r'] ?? 'dashboard');

try {
    switch ($r) {
        case 'login':
            page_login();
            break;
        case 'logout':
            csrf_check();
            unset($_SESSION['admin_id']);
            session_regenerate_id(true);
            flash('success', 'Çıkış yaptınız.');
            redirect(admin_url(['r' => 'login']));
            break;
    }

    require_admin();

    switch ($r) {
        case 'dashboard':
            page_dashboard();
            break;
        case 'crud':
            $E = entity_or_404((string) ($_GET['e'] ?? ''));
            $a = (string) ($_GET['a'] ?? 'list');
            match ($a) {
                'edit' => crud_form($E),
                'save' => crud_save($E),
                'delete' => crud_delete($E),
                'copy' => crud_copy($E),
                default => crud_list($E),
            };
            break;
        case 'sort':
            ajax_sort();
            break;
        case 'toggle':
            ajax_toggle();
            break;
        case 'messages':
            page_messages();
            break;
        case 'settings':
            page_settings();
            break;
        case 'profile':
            page_profile();
            break;
        default:
            http_response_code(404);
            admin_page('Bulunamadı', '<div class="card"><p>Sayfa bulunamadı.</p></div>');
    }
} catch (Throwable $ex) {
    if (is_ajax()) {
        json_out(['ok' => false, 'error' => config('debug') ? $ex->getMessage() : 'Sunucu hatası.'], 500);
    }
    http_response_code(500);
    echo config('debug') ? '<pre style="padding:20px;white-space:pre-wrap">' . e($ex) . '</pre>' : 'Beklenmeyen bir hata oluştu.';
    error_log((string) $ex);
}

/* ==========================================================================
   Giriş
   ========================================================================== */

function page_login(): void
{
    if (current_admin()) {
        redirect(admin_url());
    }
    $error = null;
    $ip = client_ip();
    if (is_post()) {
        csrf_check();
        $attempts = login_attempts($ip);
        if ($attempts >= 5) {
            $error = 'Çok fazla hatalı deneme yapıldı. Lütfen 15 dakika sonra tekrar deneyin.';
        } else {
            $u = row('SELECT * FROM users WHERE username = ?', [trim((string) ($_POST['username'] ?? ''))]);
            if ($u && password_verify((string) ($_POST['password'] ?? ''), $u['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int) $u['id'];
                login_attempts($ip, 0);
                if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                    q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash((string) $_POST['password'], PASSWORD_DEFAULT), $u['id']]);
                }
                q('UPDATE users SET last_login = ? WHERE id = ?', [now(), $u['id']]);
                redirect(safe_back($_POST['back'] ?? '') ?: admin_url());
            }
            login_attempts($ip, $attempts + 1);
            usleep(400000);
            $left = 5 - ($attempts + 1);
            $error = 'Kullanıcı adı veya şifre hatalı.' . ($left > 0 && $left < 3 ? " ($left deneme hakkınız kaldı)" : '');
        }
    }
    $siteName = setting('site_name', 'NowPlast');
    ?><!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
    <title>Giriş · <?= e($siteName) ?> Yönetim</title>
    <link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('admin/assets/admin.css')) ?>">
    <?= admin_theme_style() ?>
</head>
<body class="login">
<main class="login__box">
    <div class="side__brand side__brand--dark">
        <?= admin_brand_mark() ?>
        <span><strong><?= e($siteName) ?></strong><small>Yönetim paneli</small></span>
    </div>
    <h1>Giriş yapın</h1>
    <?php foreach (flashes() as [$t, $m]): ?><div class="alert alert--<?= e($t) ?>"><?= e($m) ?></div><?php endforeach; ?>
    <?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="back" value="<?= e(safe_back($_GET['back'] ?? ($_POST['back'] ?? ''))) ?>">
        <div class="field"><label for="u">Kullanıcı adı</label><input id="u" name="username" required autocomplete="username" autofocus value="<?= e($_POST['username'] ?? '') ?>"></div>
        <div class="field"><label for="p">Şifre</label><input id="p" type="password" name="password" required autocomplete="current-password"></div>
        <button class="btn btn--primary btn--block" type="submit">Giriş yap</button>
    </form>
    <a class="login__back" href="<?= e(url('')) ?>">← Siteye dön</a>
</main>
</body>
</html><?php
    exit;
}

/* ==========================================================================
   Panel
   ========================================================================== */

function page_dashboard(): void
{
    $stats = [
        ['Ürün', (int) val('SELECT COUNT(*) FROM products'), (int) val('SELECT COUNT(*) FROM products WHERE is_active = 1') . ' yayında', admin_url(['r' => 'crud', 'e' => 'products']), 'cube'],
        ['Kategori', (int) val('SELECT COUNT(*) FROM categories'), (int) val('SELECT COUNT(*) FROM categories WHERE parent_id IS NULL OR parent_id = 0') . ' ana grup', admin_url(['r' => 'crud', 'e' => 'categories']), 'layers'],
        ['Okunmamış mesaj', (int) val('SELECT COUNT(*) FROM messages WHERE is_read = 0'), (int) val('SELECT COUNT(*) FROM messages') . ' toplam', admin_url(['r' => 'messages']), 'inbox'],
        ['Sayfa', (int) val('SELECT COUNT(*) FROM pages'), (int) val('SELECT COUNT(*) FROM news') . ' haber', admin_url(['r' => 'crud', 'e' => 'pages']), 'doc'],
    ];
    $msgs = rows('SELECT m.*, p.code AS pcode, p.name AS pname FROM messages m LEFT JOIN products p ON p.id = m.product_id ORDER BY m.id DESC LIMIT 8');
    $noImg = (int) val("SELECT COUNT(*) FROM products WHERE image IS NULL OR image = '' OR image LIKE 'demo/%'");
    $noCat = (int) val('SELECT COUNT(*) FROM products WHERE category_id IS NULL OR category_id = 0');
    $u = current_admin();
    ob_start(); ?>
    <p class="welcome">Merhaba <?= e($u['name'] ?: $u['username']) ?> 👋</p>
    <div class="stats">
        <?php foreach ($stats as [$l, $n, $sub, $href, $ic]): ?>
            <a class="stat" href="<?= e($href) ?>"><span class="stat__ico"><?= admin_icon($ic) ?></span><strong><?= $n ?></strong><span><?= e($l) ?></span><small><?= e($sub) ?></small></a>
        <?php endforeach; ?>
    </div>

    <div class="grid2">
        <section class="card card--flush">
            <div class="card__head card__head--pad"><h2>Son mesajlar ve talepler</h2><a class="btn btn--sm btn--ghost" href="<?= e(admin_url(['r' => 'messages'])) ?>">Tümü</a></div>
            <?php if ($msgs): ?>
                <table class="tbl tbl--msgs"><tbody>
                <?php foreach ($msgs as $m): ?>
                    <tr class="<?= (int) $m['is_read'] ? '' : 'is-unread' ?>">
                        <td><?= msg_type_badge($m['type']) ?></td>
                        <td><a class="tbl__title" href="<?= e(admin_url(['r' => 'messages', 'id' => $m['id']])) ?>"><?= e($m['name']) ?></a><span class="muted small block"><?= e($m['pcode'] ? $m['pcode'] . ' ' . $m['pname'] : ($m['company'] ?: $m['subject'])) ?></span></td>
                        <td class="muted small nowrap"><?= e(date('d.m.Y H:i', strtotime((string) $m['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php else: ?><p class="nodata">Henüz mesaj yok.</p><?php endif; ?>
        </section>

        <div class="stack">
            <section class="card">
                <div class="card__head"><h2>Hızlı işlemler</h2></div>
                <div class="quick">
                    <a href="<?= e(admin_url(['r' => 'crud', 'e' => 'products', 'a' => 'edit'])) ?>"><?= admin_icon('plus') ?> Yeni ürün</a>
                    <a href="<?= e(admin_url(['r' => 'crud', 'e' => 'categories', 'a' => 'edit'])) ?>"><?= admin_icon('plus') ?> Yeni kategori</a>
                    <a href="<?= e(admin_url(['r' => 'crud', 'e' => 'pages', 'a' => 'edit'])) ?>"><?= admin_icon('plus') ?> Yeni sayfa</a>
                    <a href="<?= e(admin_url(['r' => 'crud', 'e' => 'menu'])) ?>"><?= admin_icon('menu') ?> Menüyü düzenle</a>
                    <a href="<?= e(admin_url(['r' => 'crud', 'e' => 'sliders'])) ?>"><?= admin_icon('print') ?> Slider</a>
                    <a href="<?= e(admin_url(['r' => 'settings'])) ?>"><?= admin_icon('settings') ?> İletişim & harita</a>
                </div>
            </section>
            <?php if ($noImg || $noCat): ?>
                <section class="card card--warn">
                    <div class="card__head"><h2>Yapılacaklar</h2></div>
                    <ul class="todo">
                        <?php if ($noImg): ?><li><a href="<?= e(admin_url(['r' => 'crud', 'e' => 'products'])) ?>"><?= $noImg ?> ürün demo görsel kullanıyor veya görseli yok — gerçek fotoğrafları yükleyin.</a></li><?php endif; ?>
                        <?php if ($noCat): ?><li><a href="<?= e(admin_url(['r' => 'crud', 'e' => 'products', 'f_category_id' => '__none'])) ?>"><?= $noCat ?> ürün bir kategoriye bağlı değil.</a></li><?php endif; ?>
                    </ul>
                </section>
            <?php endif; ?>
            <section class="card">
                <div class="card__head"><h2>Sistem</h2></div>
                <dl class="kv">
                    <dt>Veritabanı</dt><dd><?= e(strtoupper(db_driver())) ?></dd>
                    <dt>PHP</dt><dd><?= e(PHP_VERSION) ?></dd>
                    <dt>Yükleme sınırı</dt><dd><?= e(ini_get('upload_max_filesize')) ?></dd>
                    <dt>Hata ayıklama</dt><dd><?= config('debug') ? '<span class="tag tag--off">Açık — canlıda kapatın</span>' : 'Kapalı' ?></dd>
                </dl>
            </section>
        </div>
    </div>
    <?php
    admin_page('Panel', ob_get_clean(), 'dashboard');
}

/* ==========================================================================
   Mesajlar
   ========================================================================== */

function msg_types(): array
{
    return ['contact' => 'İletişim', 'sample' => 'Numune', 'quote' => 'Teklif'];
}

function msg_type_badge(string $t): string
{
    return '<span class="tag tag--' . e($t) . '">' . e(msg_types()[$t] ?? $t) . '</span>';
}

function page_messages(): void
{
    if (is_post()) {
        csrf_check();
        $act = (string) ($_POST['act'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        if ($act === 'delete' && $id) {
            q('DELETE FROM messages WHERE id = ?', [$id]);
            flash('success', 'Mesaj silindi.');
        } elseif ($act === 'unread' && $id) {
            q('UPDATE messages SET is_read = 0 WHERE id = ?', [$id]);
            flash('success', 'Okunmadı olarak işaretlendi.');
        } elseif ($act === 'read_all') {
            q('UPDATE messages SET is_read = 1 WHERE is_read = 0');
            flash('success', 'Tüm mesajlar okundu olarak işaretlendi.');
        }
        redirect(admin_url(['r' => 'messages']));
    }

    if (($_GET['export'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="talepler-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Tarih', 'Tür', 'Ad Soyad', 'Firma', 'E-posta', 'Telefon', 'Ürün', 'Adet', 'Konu', 'Mesaj'], ';');
        foreach (rows('SELECT m.*, p.code AS pcode, p.name AS pname FROM messages m LEFT JOIN products p ON p.id = m.product_id ORDER BY m.id DESC') as $m) {
            $row = [$m['created_at'], msg_types()[$m['type']] ?? $m['type'], $m['name'], $m['company'], $m['email'], $m['phone'],
                trim(($m['pcode'] ?? '') . ' ' . ($m['pname'] ?? '')), $m['quantity'], $m['subject'], $m['message']];
            // Excel formül enjeksiyonuna karşı
            $row = array_map(fn($c) => preg_match('~^[=+\-@]~', (string) $c) ? "'" . $c : $c, $row);
            fputcsv($out, $row, ';');
        }
        fclose($out);
        exit;
    }

    $id = (int) ($_GET['id'] ?? 0);
    if ($id) {
        $m = row('SELECT m.*, p.code AS pcode, p.name AS pname, p.slug AS pslug FROM messages m LEFT JOIN products p ON p.id = m.product_id WHERE m.id = ?', [$id]);
        if (!$m) {
            flash('error', 'Mesaj bulunamadı.');
            redirect(admin_url(['r' => 'messages']));
        }
        if (!(int) $m['is_read']) q('UPDATE messages SET is_read = 1 WHERE id = ?', [$id]);
        $subject = rawurlencode('Re: ' . ($m['subject'] ?: (msg_types()[$m['type']] . ' talebiniz')));
        ob_start(); ?>
        <div class="formbar"><a class="btn btn--text" href="<?= e(admin_url(['r' => 'messages'])) ?>">← Mesajlar</a>
            <div class="formbar__right">
                <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button class="btn btn--ghost" name="act" value="unread">Okunmadı yap</button></form>
                <form method="post" data-confirm="Mesaj silinsin mi?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button class="btn btn--danger" name="act" value="delete"><?= admin_icon('trash') ?> Sil</button></form>
            </div>
        </div>
        <div class="layout layout--side">
            <section class="card msg">
                <div class="msg__head"><?= msg_type_badge($m['type']) ?><span class="muted"><?= e(date('d.m.Y H:i', strtotime((string) $m['created_at']))) ?></span></div>
                <h2><?= e($m['subject'] ?: (msg_types()[$m['type']] . ' talebi')) ?></h2>
                <?php if ($m['pname']): ?>
                    <p class="msg__product">Ürün: <a href="<?= e(url('urun/' . $m['pslug'])) ?>" target="_blank"><strong><?= e(trim($m['pcode'] . ' ' . $m['pname'])) ?></strong></a><?= $m['quantity'] ? ' · Adet: <strong>' . e($m['quantity']) . '</strong>' : '' ?></p>
                <?php elseif ($m['quantity']): ?><p class="msg__product">Adet: <strong><?= e($m['quantity']) ?></strong></p><?php endif; ?>
                <div class="msg__body"><?= nl2br(e($m['message'] ?: '(mesaj yok)')) ?></div>
            </section>
            <aside class="card">
                <dl class="kv kv--stack">
                    <dt>Ad soyad</dt><dd><?= e($m['name']) ?></dd>
                    <?php if ($m['company']): ?><dt>Firma</dt><dd><?= e($m['company']) ?></dd><?php endif; ?>
                    <?php if ($m['email']): ?><dt>E-posta</dt><dd><a href="mailto:<?= e($m['email']) ?>?subject=<?= e($subject) ?>"><?= e($m['email']) ?></a></dd><?php endif; ?>
                    <?php if ($m['phone']): ?><dt>Telefon</dt><dd><a href="<?= e(tel_href($m['phone'])) ?>"><?= e($m['phone']) ?></a></dd><?php endif; ?>
                    <dt>IP</dt><dd class="muted"><?= e($m['ip']) ?></dd>
                </dl>
                <div class="stack-btns">
                    <?php if ($m['email']): ?><a class="btn btn--primary btn--block" href="mailto:<?= e($m['email']) ?>?subject=<?= e($subject) ?>"><?= icon('mail') ?> E-posta ile yanıtla</a><?php endif; ?>
                    <?php if ($m['phone']): $wa = preg_replace('~[^0-9]~', '', $m['phone']); if (str_starts_with($wa, '0')) $wa = '9' . $wa; ?>
                        <a class="btn btn--ghost btn--block" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> WhatsApp</a>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
        <?php
        admin_page('Mesaj', ob_get_clean(), 'messages');
        return;
    }

    $filter = (string) ($_GET['f'] ?? '');
    $where = '';
    $params = [];
    if ($filter === 'unread') $where = 'WHERE m.is_read = 0';
    elseif (isset(msg_types()[$filter])) { $where = 'WHERE m.type = ?'; $params[] = $filter; }
    $total = (int) val("SELECT COUNT(*) FROM messages m $where", $params);
    $pg = pagination($total, 30, (int) ($_GET['p'] ?? 1));
    $list = rows("SELECT m.*, p.code AS pcode, p.name AS pname FROM messages m LEFT JOIN products p ON p.id = m.product_id $where ORDER BY m.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $params);
    $tabs = ['' => 'Tümü', 'unread' => 'Okunmamış'] + msg_types();

    ob_start(); ?>
    <div class="bar">
        <nav class="tabs">
            <?php foreach ($tabs as $k => $l): ?><a class="<?= $filter === $k ? 'is-active' : '' ?>" href="<?= e(admin_url(['r' => 'messages'] + ($k !== '' ? ['f' => $k] : []))) ?>"><?= e($l) ?></a><?php endforeach; ?>
        </nav>
        <div class="bar__right">
            <form method="post"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" name="act" value="read_all">Tümünü okundu yap</button></form>
            <a class="btn btn--ghost btn--sm" href="<?= e(admin_url(['r' => 'messages', 'export' => 'csv'])) ?>"><?= icon('download') ?> Excel (CSV)</a>
        </div>
    </div>
    <div class="card card--flush">
        <?php if ($list): ?>
            <table class="tbl tbl--msgs">
                <thead><tr><th>Tür</th><th>Gönderen</th><th>Ürün / Konu</th><th>Mesaj</th><th>Tarih</th></tr></thead>
                <tbody>
                <?php foreach ($list as $m): $href = admin_url(['r' => 'messages', 'id' => $m['id']]); ?>
                    <tr class="<?= (int) $m['is_read'] ? '' : 'is-unread' ?>" data-href="<?= e($href) ?>">
                        <td><?= msg_type_badge($m['type']) ?></td>
                        <td><a class="tbl__title" href="<?= e($href) ?>"><?= e($m['name']) ?></a><span class="muted small block"><?= e($m['company'] ?: ($m['email'] ?: $m['phone'])) ?></span></td>
                        <td class="small"><?= e($m['pcode'] ? trim($m['pcode'] . ' ' . $m['pname']) : ($m['subject'] ?: '—')) ?></td>
                        <td class="muted small"><?= e(excerpt($m['message'], 80)) ?></td>
                        <td class="muted small nowrap"><?= e(date('d.m.Y H:i', strtotime((string) $m['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?><p class="nodata">Bu bölümde mesaj yok.</p><?php endif; ?>
    </div>
    <?php if ($pg['pages'] > 1): ?>
        <nav class="pages"><?php for ($i = 1; $i <= $pg['pages']; $i++): ?><a class="<?= $i === $pg['page'] ? 'is-current' : '' ?>" href="<?= e(admin_url(['r' => 'messages', 'f' => $filter, 'p' => $i])) ?>"><?= $i ?></a><?php endfor; ?></nav>
    <?php endif; ?>
    <?php
    admin_page('Mesajlar & Talepler', ob_get_clean(), 'messages');
}

/* ==========================================================================
   Site ayarları
   ========================================================================== */

function settings_schema(): array
{
    $pages = ['' => '— Yok —'];
    foreach (rows('SELECT slug, title FROM pages ORDER BY title') as $p) $pages[$p['slug']] = $p['title'];
    return [
        'genel' => ['Genel', [
            ['site_name', 'Site / marka adı', 'text'],
            ['site_tagline', 'Slogan', 'text', 'Tarayıcı sekmesinde ve anasayfa başlığında kullanılır.'],
            ['logo', 'Logo', 'image', 'Şeffaf PNG önerilir. Yüklenmezse marka adı yazı olarak gösterilir.'],
            ['logo_footer', 'Alt bölüm logosu (koyu zemin)', 'image', 'Koyu arka planda okunacak açık renkli logo.'],
            ['favicon', 'Favicon', 'image', 'Kare, en az 64×64 px.'],
            ['theme_color', 'Tema rengi', 'color', 'Butonlar, bağlantılar ve vurgu öğelerinin rengi.'],
            ['meta_description', 'Site açıklaması (SEO)', 'textarea', 'Google sonuçlarında görünen genel açıklama.'],
            ['google_analytics', 'Google Analytics ölçüm kimliği', 'text', 'ör. G-XXXXXXX'],
        ]],
        'iletisim' => ['İletişim & Harita', [
            ['company_title', 'Firma ünvanı', 'text'],
            ['address', 'Adres', 'textarea'],
            ['phone', 'Telefon', 'text'],
            ['phone2', 'Telefon 2 / Faks', 'text'],
            ['email', 'E-posta', 'text'],
            ['whatsapp', 'WhatsApp numarası', 'text', 'Ülke koduyla: 905xxxxxxxxx. Boş bırakılırsa WhatsApp butonu gizlenir.'],
            ['working_hours', 'Çalışma saatleri', 'text', 'ör. Hafta içi 08:30 – 18:00'],
            ['map_lat', 'Harita enlem', 'text', 'Google Haritalar\'da konuma sağ tıklayıp ilk sayıyı kopyalayın (ör. 37.9439).'],
            ['map_lng', 'Harita boylam', 'text', 'İkinci sayı (ör. 32.5027).'],
            ['map_embed_url', 'Özel harita yerleştirme adresi', 'text', 'İsteğe bağlı: Google Haritalar › Paylaş › Harita yerleştir kodundaki src="…" adresi. Doluysa enlem/boylam yerine kullanılır.'],
            ['notify_email', 'Bildirim e-postası', 'text', 'Yeni mesaj ve talepler bu adrese de gönderilir (sunucunuzun e-posta göndermesi gerekir).'],
        ]],
        'sosyal' => ['Sosyal medya', [
            ['facebook', 'Facebook', 'text'], ['instagram', 'Instagram', 'text'], ['linkedin', 'LinkedIn', 'text'],
            ['youtube', 'YouTube', 'text'], ['x', 'X (Twitter)', 'text'],
        ]],
        'anasayfa' => ['Anasayfa', [
            ['home_about_title', 'Hakkımızda başlığı', 'text'],
            ['home_about_text', 'Hakkımızda metni', 'textarea'],
            ['home_about_image', 'Hakkımızda görseli', 'image'],
            ['stat1_value', 'Rakam 1', 'text', 'ör. 25+'], ['stat1_label', 'Rakam 1 açıklaması', 'text', 'ör. Yıllık tecrübe'],
            ['stat2_value', 'Rakam 2', 'text'], ['stat2_label', 'Rakam 2 açıklaması', 'text'],
            ['stat3_value', 'Rakam 3', 'text'], ['stat3_label', 'Rakam 3 açıklaması', 'text'],
            ['stat4_value', 'Rakam 4', 'text'], ['stat4_label', 'Rakam 4 açıklaması', 'text'],
            ['cta_title', 'Alt çağrı bandı başlığı', 'text'],
            ['cta_text', 'Alt çağrı bandı metni', 'textarea'],
        ]],
        'diger' => ['Katalog & Diğer', [
            ['catalog_pdf', 'E-Katalog (PDF)', 'file', 'Yüklenirse üst barda ve ürün sayfalarında "E-Katalog" bağlantısı görünür.'],
            ['products_intro', 'Ürünler sayfası giriş metni', 'textarea'],
            ['products_per_page', 'Sayfa başına ürün', 'text', '6 ile 96 arası.'],
            ['footer_text', 'Alt bölüm tanıtım metni', 'textarea'],
            ['cookie_text', 'Çerez bildirimi metni', 'textarea'],
        ]],
    ];
}

function page_settings(): void
{
    $schema = settings_schema();
    if (is_post()) {
        csrf_check();
        $errors = [];
        foreach ($schema as [$label, $fields]) {
            foreach ($fields as $f) {
                [$key, $lbl, $type] = $f;
                if ($type === 'image' || $type === 'file') {
                    $up = handle_upload($_FILES[$key] ?? [], $type);
                    if ($up['error']) {
                        $errors[] = $lbl . ': ' . $up['error'];
                    } elseif ($up['path']) {
                        delete_upload(setting($key));
                        set_setting($key, $up['path']);
                    } elseif (!empty($_POST[$key . '__remove'])) {
                        delete_upload(setting($key));
                        set_setting($key, '');
                    }
                    continue;
                }
                if (!array_key_exists($key, $_POST)) continue;
                $v = trim((string) $_POST[$key]);
                if ($type === 'color' && $v !== '' && !preg_match('~^#[0-9a-fA-F]{6}$~', $v)) {
                    $errors[] = 'Tema rengi #RRGGBB biçiminde olmalı.';
                    continue;
                }
                if (in_array($key, ['facebook', 'instagram', 'linkedin', 'youtube', 'x'], true) && $v !== '' && !preg_match('~^https?://~i', $v)) {
                    $v = 'https://' . ltrim($v, '/');
                }
                set_setting($key, $v);
            }
        }
        flash($errors ? 'error' : 'success', $errors ? implode(' ', $errors) : 'Ayarlar kaydedildi.');
        redirect(admin_url(['r' => 'settings', 'tab' => $_POST['_tab'] ?? '']));
    }

    $tab = (string) ($_GET['tab'] ?? 'genel');
    if (!isset($schema[$tab])) $tab = 'genel';
    $fake = ['key' => 'settings'];
    ob_start(); ?>
    <form method="post" enctype="multipart/form-data" class="form" data-tabs-form data-dirty-guard>
        <?= csrf_field() ?>
        <input type="hidden" name="_tab" value="<?= e($tab) ?>" data-tab-input>
        <div class="formbar">
            <nav class="tabs">
                <?php foreach ($schema as $k => [$label]): ?><a href="#<?= e($k) ?>" data-tab-link="<?= e($k) ?>" class="<?= $k === $tab ? 'is-active' : '' ?>"><?= e($label) ?></a><?php endforeach; ?>
            </nav>
            <button class="btn btn--primary" type="submit">Kaydet</button>
        </div>
        <?php foreach ($schema as $k => [$label, $fields]): ?>
            <section class="card" data-tab-panel="<?= e($k) ?>"<?= $k === $tab ? '' : ' hidden' ?>>
                <div class="fields">
                    <?php foreach ($fields as $f):
                        [$key, $lbl, $type] = $f;
                        echo field_html($fake, ['name' => $key, 'label' => $lbl, 'type' => $type, 'help' => $f[3] ?? null, 'rows' => 3], setting($key), null, null);
                    endforeach; ?>
                    <?php if ($k === 'iletisim' && ($src = map_embed_src())): ?>
                        <div class="field field--full"><label>Harita önizlemesi</label><iframe class="mapprev" src="<?= e($src) ?>" loading="lazy" title="Harita"></iframe></div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </form>
    <?php
    admin_page('Site ayarları', ob_get_clean(), 'settings');
}

/* ==========================================================================
   Profil
   ========================================================================== */

function page_profile(): void
{
    $u = row('SELECT * FROM users WHERE id = ?', [current_admin()['id']]);
    if (is_post()) {
        csrf_check();
        $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 120);
        $cur = (string) ($_POST['current'] ?? '');
        $new = (string) ($_POST['new'] ?? '');
        $new2 = (string) ($_POST['new2'] ?? '');
        $data = ['name' => $name ?: null];
        $err = null;
        if ($new !== '' || $cur !== '') {
            if (!password_verify($cur, $u['password_hash'])) $err = 'Mevcut şifre hatalı.';
            elseif (mb_strlen($new) < 8) $err = 'Yeni şifre en az 8 karakter olmalı.';
            elseif ($new !== $new2) $err = 'Yeni şifreler eşleşmiyor.';
            else $data['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
        }
        if ($err) {
            flash('error', $err);
        } else {
            db_update('users', (int) $u['id'], $data);
            if (isset($data['password_hash'])) session_regenerate_id(true);
            flash('success', 'Profiliniz güncellendi.');
        }
        redirect(admin_url(['r' => 'profile']));
    }
    ob_start(); ?>
    <form method="post" class="form narrow">
        <?= csrf_field() ?>
        <section class="card">
            <div class="card__head"><h2>Hesap</h2></div>
            <div class="fields">
                <div class="field"><label>Kullanıcı adı</label><input value="<?= e($u['username']) ?>" disabled></div>
                <div class="field"><label for="n">Ad soyad</label><input id="n" name="name" value="<?= e($u['name']) ?>"></div>
            </div>
        </section>
        <section class="card">
            <div class="card__head"><h2>Şifre değiştir</h2><span class="muted">Değiştirmek istemiyorsanız boş bırakın.</span></div>
            <div class="fields">
                <div class="field"><label for="c">Mevcut şifre</label><input id="c" type="password" name="current" autocomplete="current-password"></div>
                <div class="field field--half"><label for="nw">Yeni şifre</label><input id="nw" type="password" name="new" minlength="8" autocomplete="new-password"></div>
                <div class="field field--half"><label for="nw2">Yeni şifre (tekrar)</label><input id="nw2" type="password" name="new2" minlength="8" autocomplete="new-password"></div>
            </div>
        </section>
        <button class="btn btn--primary" type="submit">Kaydet</button>
    </form>
    <?php
    admin_page('Profilim', ob_get_clean(), '');
}
