<?php
$siteName = setting('site_name', 'NowPlast');
$fullTitle = !empty($title) ? $title . ' | ' . $siteName : $siteName . ' — ' . setting('site_tagline', 'Plastik Enjeksiyon & Ambalaj');
$desc = !empty($meta_description) ? $meta_description : setting('meta_description');
$canonical = full_url(current_path());
$brand = setting('theme_color', '#1557ff');
if (!preg_match('~^#[0-9a-fA-F]{6}$~', $brand)) {
    $brand = '#1557ff';
}
$favicon = setting('favicon');
$ga = trim((string) setting('google_analytics'));
?><!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
    <?php if ($desc): ?><meta name="description" content="<?= e(mb_substr($desc, 0, 300)) ?>"><?php endif; ?>
    <?php if (!empty($noindex)): ?><meta name="robots" content="noindex, follow"><?php endif; ?>
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <?php if ($desc): ?><meta property="og:description" content="<?= e(mb_substr($desc, 0, 300)) ?>"><?php endif; ?>
    <meta property="og:url" content="<?= e($canonical) ?>">
    <?php if (!empty($og_image)): ?><meta property="og:image" content="<?= e(str_starts_with($og_image, 'http') ? $og_image : full_url(ltrim(substr($og_image, strlen(base_path())), '/'))) ?>"><?php endif; ?>
    <meta name="theme-color" content="<?= e($brand) ?>">
    <link rel="icon" href="<?= e($favicon ? upload_url($favicon) : asset('assets/img/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <style>:root{--brand:<?= e($brand) ?>}</style>
    <?php if ($ga !== '' && preg_match('~^G-[A-Z0-9]+$~', $ga)): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= e($ga) ?>');</script>
    <?php endif; ?>
    <script type="application/ld+json"><?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => setting('company_title', $siteName),
        'url' => full_url(''),
        'telephone' => setting('phone'),
        'email' => setting('email'),
        'address' => setting('address'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
</head>
<body class="<?= !empty($is_home) ? 'is-home' : 'is-inner' ?>">
<a class="skip" href="#main">İçeriğe geç</a>

<?= view('partials/header') ?>

<?php $fl = flashes(); if ($fl): ?>
    <div class="toasts" role="status" aria-live="polite">
        <?php foreach ($fl as [$type, $msg]): ?>
            <div class="toast toast--<?= e($type) ?>"><?= icon($type === 'success' ? 'check' : 'close') ?><span><?= e($msg) ?></span><button type="button" class="toast__x" aria-label="Kapat"><?= icon('close') ?></button></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main id="main">
    <?= $content ?>
</main>

<?= view('partials/footer') ?>
<?= view('partials/quote-modal') ?>

<?php if ($wa = whatsapp_href('Merhaba, ürünleriniz hakkında bilgi almak istiyorum.')): ?>
    <a class="wa-float" href="<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp ile yazın"><?= icon('whatsapp') ?></a>
<?php endif; ?>
<button class="to-top" type="button" aria-label="Yukarı çık"><?= icon('up') ?></button>

<div class="cookie" hidden>
    <p><?= e(setting('cookie_text', 'Sitemizde deneyiminizi iyileştirmek için zorunlu çerezler kullanıyoruz.')) ?>
        <?php if ($cp = row("SELECT slug FROM pages WHERE slug = 'cerez-politikasi' AND is_active = 1")): ?>
            <a href="<?= e(page_url($cp)) ?>">Çerez Politikası</a>
        <?php endif; ?>
    </p>
    <button type="button" class="btn btn--sm btn--primary" data-cookie-ok>Anladım</button>
</div>

<script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
</body>
</html>
