<?php
/**
 * Varsayılan site yapılandırması.
 *
 * Bu dosyayı DEĞİŞTİRMEYİN. Kendi ayarlarınız (MySQL bilgileri, alt klasör, debug)
 * için config.local.example.php dosyasını config.local.php adıyla kopyalayıp onu düzenleyin.
 * config.local.php GitHub'a gönderilmez, böylece şifreleriniz paylaşılmaz.
 *
 * Hiç yerel ayar yoksa site SQLite ile çalışır (ek kurulum gerekmez).
 */
$config = [
    'db' => [
        'driver'      => 'sqlite',                       // 'mysql' veya 'sqlite'
        'sqlite_path' => __DIR__ . '/data/site.sqlite',

        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'nowplast',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // Site bir alt klasörde çalışıyorsa: '/klasor-adi'. Kök dizinde ise boş bırakın.
    'base_url' => '',

    // Hata ayrıntılarını ekranda gösterir. Canlı sunucuda config.local.php içinde false yapın.
    'debug' => true,
];

if (is_file(__DIR__ . '/config.local.php')) {
    $local = require __DIR__ . '/config.local.php';
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

return $config;
