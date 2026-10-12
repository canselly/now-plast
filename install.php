<?php
require __DIR__ . '/app/bootstrap.php';
require ROOT . '/app/schema.php';
require ROOT . '/app/seed.php';

$lock = ROOT . '/data/installed.lock';
$installed = is_file($lock) || (db_installed() && (int) val('SELECT COUNT(*) FROM users') > 0);

$errors = [];
$dbError = null;
try {
    db();
} catch (Throwable $ex) {
    $dbError = $ex->getMessage();
}

if (!$installed && !$dbError && is_post()) {
    csrf_check();
    $site = trim((string) ($_POST['site_name'] ?? ''));
    $user = trim((string) ($_POST['username'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password2'] ?? '');
    if ($site === '') $errors[] = 'Site adı zorunludur.';
    if (!preg_match('~^[a-zA-Z0-9_.-]{3,60}$~', $user)) $errors[] = 'Kullanıcı adı 3-60 karakter olmalı; harf, rakam, nokta, tire ve alt çizgi içerebilir.';
    if (mb_strlen($pass) < 8) $errors[] = 'Şifre en az 8 karakter olmalıdır.';
    if ($pass !== $pass2) $errors[] = 'Şifreler eşleşmiyor.';

    if (!$errors) {
        try {
            foreach (schema_statements(db_driver()) as $sql) {
                db()->exec($sql);
            }
            foreach (schema_indexes() as $sql) {
                try { db()->exec($sql); } catch (Throwable $ignored) { /* indeks zaten var */ }
            }
            db()->beginTransaction();
            seed_settings($site);
            db_insert('users', ['username' => $user, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'name' => 'Site Yöneticisi', 'created_at' => now()]);
            if (!empty($_POST['demo'])) {
                seed_demo();
            }
            db()->commit();
            if (!is_dir(ROOT . '/data')) mkdir(ROOT . '/data', 0775, true);
            file_put_contents($lock, date('c'));
            flash('success', 'Kurulum tamamlandı. Yeni hesabınızla giriş yapabilirsiniz.');
            redirect(url('admin/index.php?r=login'));
        } catch (Throwable $ex) {
            if (db()->inTransaction()) db()->rollBack();
            $errors[] = 'Kurulum sırasında hata: ' . $ex->getMessage();
        }
    }
}

$checks = [
    ['PHP 8.0 veya üzeri', version_compare(PHP_VERSION, '8.0.0', '>='), PHP_VERSION],
    ['PDO ' . strtoupper(db_driver()) . ' eklentisi', in_array(db_driver(), PDO::getAvailableDrivers(), true), implode(', ', PDO::getAvailableDrivers())],
    ['mbstring eklentisi', extension_loaded('mbstring'), ''],
    ['uploads/ klasörü yazılabilir', is_writable(ROOT . '/uploads'), ''],
    ['data/ klasörü yazılabilir', is_dir(ROOT . '/data') ? is_writable(ROOT . '/data') : is_writable(ROOT), ''],
];
?><!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
    <title>Kurulum</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('admin/assets/admin.css')) ?>">
    <style>.login__box{width:min(520px,100%)} .chk{list-style:none;padding:0;margin:0 0 18px;display:grid;gap:6px;font-size:13.5px} .chk li{display:flex;justify-content:space-between;gap:10px} .ok{color:var(--success);font-weight:700} .no{color:var(--danger);font-weight:700}</style>
</head>
<body class="login">
<main class="login__box">
    <h1>Site kurulumu</h1>
    <?php if ($installed): ?>
        <div class="alert alert--success">Kurulum daha önce tamamlanmış.</div>
        <p class="muted">Güvenlik için canlı sunucuda <code>install.php</code> dosyasını silebilirsiniz.</p>
        <p><a class="btn btn--primary btn--block" href="<?= e(url('admin/')) ?>">Yönetim paneline git</a></p>
        <p><a class="btn btn--ghost btn--block" href="<?= e(url('')) ?>">Siteyi görüntüle</a></p>
    <?php else: ?>
        <ul class="chk">
            <?php foreach ($checks as [$l, $ok, $info]): ?>
                <li><span><?= e($l) ?> <?php if ($info): ?><small class="muted">(<?= e($info) ?>)</small><?php endif; ?></span><span class="<?= $ok ? 'ok' : 'no' ?>"><?= $ok ? '✓' : '✗' ?></span></li>
            <?php endforeach; ?>
        </ul>
        <?php if ($dbError): ?>
            <div class="alert alert--error">Veritabanına bağlanılamadı: <?= e($dbError) ?><br><small>config.php dosyasındaki veritabanı bilgilerini kontrol edin.</small></div>
        <?php else: ?>
            <?php foreach ($errors as $er): ?><div class="alert alert--error"><?= e($er) ?></div><?php endforeach; ?>
            <form method="post" class="form">
                <?= csrf_field() ?>
                <div class="field"><label for="s">Site / marka adı</label><input id="s" name="site_name" required value="<?= e($_POST['site_name'] ?? 'NowPlast') ?>"></div>
                <div class="field"><label for="u">Yönetici kullanıcı adı</label><input id="u" name="username" required autocomplete="username" value="<?= e($_POST['username'] ?? 'admin') ?>"></div>
                <div class="field"><label for="p">Şifre (en az 8 karakter)</label><input id="p" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
                <div class="field"><label for="p2">Şifre (tekrar)</label><input id="p2" type="password" name="password2" required minlength="8" autocomplete="new-password"></div>
                <label class="check check--sm"><input type="checkbox" name="demo" value="1" <?= !is_post() || !empty($_POST['demo']) ? 'checked' : '' ?>><span>Örnek içerik yükle (kategoriler, ürünler, sayfalar, menüler, slider)</span></label>
                <button class="btn btn--primary btn--block" type="submit">Kurulumu başlat</button>
            </form>
            <p class="muted small">Veritabanı: <strong><?= e(strtoupper(db_driver())) ?></strong> — değiştirmek için config.php dosyasını düzenleyin.</p>
        <?php endif; ?>
    <?php endif; ?>
</main>
</body>
</html>
