<?php
/**
 * Yerel / sunucuya özel ayarlar.
 * Bu dosyayı "config.local.php" adıyla kopyalayıp düzenleyin. config.local.php GitHub'a gönderilmez.
 * Yalnızca değiştirmek istediğiniz değerleri yazmanız yeterli; geri kalanı config.php'den gelir.
 */
return [
    'db' => [
        'driver' => 'mysql',
        'host'   => 'localhost',
        'name'   => 'veritabani_adi',
        'user'   => 'veritabani_kullanicisi',
        'pass'   => 'veritabani_sifresi',
    ],
    // 'base_url' => '/alt-klasor',
    'debug' => false,
];
