<?php
// PHP yerleşik sunucusu (php -S) ile çalışırken mevcut statik dosyaları ve admin klasörünü doğrudan sun.
if (PHP_SAPI === 'cli-server') {
    $reqPath = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
    $file = __DIR__ . $reqPath;
    if ($reqPath !== '/' && !preg_match('~^/(app|data|templates)(/|$)~', $reqPath)
        && (is_file($file) || (is_dir($file) && is_file(rtrim($file, '/') . '/index.php')))) {
        return false;
    }
}

require __DIR__ . '/app/bootstrap.php';

if (!db_installed()) {
    redirect(url('install.php'));
}

require ROOT . '/app/controllers.php';

$uri = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$base = base_path();
if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}
$path = trim($uri, '/');
if ($path === 'index.php') {
    $path = '';
}
$GLOBALS['current_path'] = $path;
$seg = $path === '' ? [] : explode('/', $path);
$n = count($seg);

try {
    switch (true) {
        case $n === 0:
            page_home();
            break;
        case $seg[0] === 'urunler' && $n === 1:
            page_products();
            break;
        case $seg[0] === 'urunler' && $n === 2:
            page_category($seg[1]);
            break;
        case $seg[0] === 'urun' && $n === 2:
            page_product($seg[1]);
            break;
        case $seg[0] === 'sektorler' && $n === 1:
            page_sectors();
            break;
        case $seg[0] === 'sektor' && $n === 2:
            page_sector($seg[1]);
            break;
        case $seg[0] === 'haberler' && $n === 1:
            page_news_list();
            break;
        case $seg[0] === 'haber' && $n === 2:
            page_news_detail($seg[1]);
            break;
        case $seg[0] === 'iletisim' && $n === 1:
            page_contact();
            break;
        case $seg[0] === 'ara' && $n === 1:
            page_search();
            break;
        case $seg[0] === 'talep' && $n === 1:
            handle_message_post();
            break;
        case $seg[0] === 'katalog' && $n === 1:
            page_catalog();
            break;
        case $seg[0] === 'sitemap.xml' && $n === 1:
            page_sitemap();
            break;
        case $seg[0] === 'robots.txt' && $n === 1:
            page_robots();
            break;
        case $n === 1:
            page_static($seg[0]);
            break;
        default:
            not_found();
    }
} catch (Throwable $ex) {
    http_response_code(500);
    if (config('debug')) {
        echo '<pre style="padding:20px;white-space:pre-wrap">' . e($ex) . '</pre>';
    } else {
        error_log((string) $ex);
        echo 'Beklenmeyen bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
    }
}
