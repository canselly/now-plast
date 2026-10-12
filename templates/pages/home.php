<?php
/** @var array $sliders, $categories, $sectors, $featured, $news, $certificates, $clients */
$stats = [];
for ($i = 1; $i <= 4; $i++) {
    if (setting("stat{$i}_value") !== '') {
        $stats[] = [setting("stat{$i}_value"), setting("stat{$i}_label")];
    }
}
$process = [
    ['cube', 'Ürün Tasarımı', 'İhtiyaca göre 3D tasarım, prototip ve hacim/istif hesapları.'],
    ['mold', 'Kalıp Üretimi', 'Yüksek hassasiyetli, çok gözlü enjeksiyon kalıpları.'],
    ['cog', 'Enjeksiyon', 'Robotik otomasyonlu, 7/24 çalışan modern makine parkuru.'],
    ['print', 'IML & Baskı', 'Kalıp içi etiketleme ile fotoğraf kalitesinde dekorasyon.'],
    ['shield', 'Kalite Kontrol', 'Gıdaya uygunluk, sızdırmazlık ve boyut testleri.'],
    ['truck', 'Sevkiyat', 'Hijyenik paketleme ve yurt içi / yurt dışı lojistik.'],
];
?>

<!-- HERO -->
<section class="hero" data-slider>
    <?php if ($sliders): ?>
        <div class="hero__track">
            <?php foreach ($sliders as $i => $s): ?>
                <div class="hero__slide<?= $i === 0 ? ' is-active' : '' ?>" data-slide aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">
                    <?php if ($s['image']): ?><img class="hero__bg" src="<?= e(upload_url($s['image'])) ?>" alt="" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>><?php endif; ?>
                    <div class="container hero__content">
                        <?php if ($s['eyebrow']): ?><p class="eyebrow eyebrow--light"><?= e($s['eyebrow']) ?></p><?php endif; ?>
                        <?php if ($s['title']): ?><<?= $i === 0 ? 'h1' : 'h2' ?> class="hero__title"><?= e($s['title']) ?></<?= $i === 0 ? 'h1' : 'h2' ?>><?php endif; ?>
                        <?php if ($s['subtitle']): ?><p class="hero__sub"><?= e($s['subtitle']) ?></p><?php endif; ?>
                        <div class="hero__actions">
                            <?php if ($s['button_text'] && $s['button_link']): ?>
                                <a class="btn btn--primary btn--lg" href="<?= e(link_href($s['button_link'])) ?>"><?= e($s['button_text']) ?> <?= icon('arrow') ?></a>
                            <?php endif; ?>
                            <button type="button" class="btn btn--outline-light btn--lg" data-quote="sample">Numune İste</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($sliders) > 1): ?>
            <div class="container hero__nav">
                <div class="hero__dots" role="tablist" aria-label="Slaytlar">
                    <?php foreach ($sliders as $i => $s): ?>
                        <button type="button" class="hero__dot<?= $i === 0 ? ' is-active' : '' ?>" data-dot="<?= $i ?>" aria-label="Slayt <?= $i + 1 ?>"><span></span></button>
                    <?php endforeach; ?>
                </div>
                <div class="hero__arrows">
                    <button type="button" class="icon-btn icon-btn--glass" data-prev aria-label="Önceki"><?= icon('chev-l') ?></button>
                    <button type="button" class="icon-btn icon-btn--glass" data-next aria-label="Sonraki"><?= icon('chev-r') ?></button>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="hero__slide is-active">
            <img class="hero__bg" src="<?= e(upload_url('demo/hero-1.svg')) ?>" alt="">
            <div class="container hero__content">
                <h1 class="hero__title"><?= e(setting('site_tagline', 'Plastik Enjeksiyon & Ambalaj')) ?></h1>
                <div class="hero__actions"><a class="btn btn--primary btn--lg" href="<?= e(url('urunler')) ?>">Ürünleri incele <?= icon('arrow') ?></a></div>
            </div>
        </div>
    <?php endif; ?>
</section>

<!-- ÜRÜN GRUPLARI -->
<?php if ($categories): ?>
<section class="section">
    <div class="container">
        <div class="sec-head">
            <div>
                <p class="eyebrow">Ürün Grupları</p>
                <h2 class="sec-title">İhtiyacınıza uygun ambalajı seçin</h2>
            </div>
            <a class="link-arrow" href="<?= e(url('urunler')) ?>">Tüm ürünler <?= icon('arrow') ?></a>
        </div>
        <div class="cat-grid">
            <?php foreach ($categories as $i => $c): ?>
                <a class="cat-card<?= $i === 0 ? ' cat-card--wide' : '' ?>" href="<?= e(category_url($c)) ?>">
                    <div class="cat-card__media"><img src="<?= e($c['image'] ? upload_url($c['image']) : asset('assets/img/placeholder.svg')) ?>" alt="" loading="lazy"></div>
                    <div class="cat-card__body">
                        <span class="cat-card__count"><?= (int) $c['count'] ?> ürün</span>
                        <h3><?= e($c['name']) ?></h3>
                        <?php if ($c['children']): ?>
                            <p class="cat-card__subs"><?= e(implode(' · ', array_column($c['children'], 'name'))) ?></p>
                        <?php elseif ($c['summary']): ?>
                            <p class="cat-card__subs"><?= e($c['summary']) ?></p>
                        <?php endif; ?>
                        <span class="cat-card__go"><?= icon('arrow') ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- HAKKIMIZDA + RAKAMLAR -->
<section class="section section--alt">
    <div class="container about">
        <div class="about__media">
            <img src="<?= e(setting('home_about_image') ? upload_url(setting('home_about_image')) : upload_url('demo/factory.svg')) ?>" alt="" loading="lazy">
            <?php if ($stats): ?>
                <div class="about__badge"><strong data-count="<?= e($stats[0][0]) ?>"><?= e($stats[0][0]) ?></strong><span><?= e($stats[0][1]) ?></span></div>
            <?php endif; ?>
        </div>
        <div class="about__text">
            <p class="eyebrow">Hakkımızda</p>
            <h2 class="sec-title"><?= e(setting('home_about_title', 'Plastikte mühendislik, ambalajda güven')) ?></h2>
            <div class="prose"><?= nl2br(e(setting('home_about_text'))) ?></div>
            <?php if ($stats): ?>
                <dl class="stats">
                    <?php foreach ($stats as [$v, $l]): ?>
                        <div class="stat"><dt><?= e($l) ?></dt><dd data-count="<?= e($v) ?>"><?= e($v) ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
            <?php if ($about = row("SELECT slug FROM pages WHERE slug = 'hakkimizda' AND is_active = 1")): ?>
                <a class="btn btn--dark" href="<?= e(page_url($about)) ?>">Bizi tanıyın <?= icon('arrow') ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ÜRETİM SÜRECİ -->
<section class="section section--ink">
    <div class="container">
        <div class="sec-head sec-head--light">
            <div>
                <p class="eyebrow eyebrow--light">Anahtar teslim üretim</p>
                <h2 class="sec-title">Fikirden rafa, tüm süreç tek çatı altında</h2>
            </div>
        </div>
        <ol class="process">
            <?php foreach ($process as $i => [$ic, $t, $d]): ?>
                <li class="process__step">
                    <span class="process__num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <span class="process__ico"><?= icon($ic) ?></span>
                    <h3><?= e($t) ?></h3>
                    <p><?= e($d) ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<!-- SEKTÖRLER -->
<?php if ($sectors): ?>
<section class="section">
    <div class="container">
        <div class="sec-head">
            <div>
                <p class="eyebrow">Sektörler</p>
                <h2 class="sec-title">Sektörünüze özel ambalaj çözümleri</h2>
            </div>
            <a class="link-arrow" href="<?= e(url('sektorler')) ?>">Tüm sektörler <?= icon('arrow') ?></a>
        </div>
        <div class="sector-grid">
            <?php foreach ($sectors as $s): ?>
                <a class="sector" href="<?= e(sector_url($s)) ?>">
                    <?php if ($s['image']): ?><img class="sector__bg" src="<?= e(upload_url($s['image'])) ?>" alt="" loading="lazy"><?php endif; ?>
                    <span class="sector__ico"><?= sector_icon($s['icon']) ?></span>
                    <h3><?= e($s['name']) ?></h3>
                    <?php if ($s['summary']): ?><p><?= e($s['summary']) ?></p><?php endif; ?>
                    <span class="sector__go"><?= icon('arrow') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ÖNE ÇIKAN ÜRÜNLER -->
<?php if ($featured): ?>
<section class="section section--alt">
    <div class="container">
        <div class="sec-head">
            <div>
                <p class="eyebrow">Öne Çıkanlar</p>
                <h2 class="sec-title">En çok tercih edilen ürünler</h2>
            </div>
            <div class="rail-ctrl">
                <button type="button" class="icon-btn icon-btn--line" data-rail-prev aria-label="Geri"><?= icon('chev-l') ?></button>
                <button type="button" class="icon-btn icon-btn--line" data-rail-next aria-label="İleri"><?= icon('chev-r') ?></button>
            </div>
        </div>
        <div class="rail" data-rail>
            <?php foreach ($featured as $p): ?><?= view('partials/product-card', ['p' => $p]) ?><?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- BELGELER & REFERANSLAR -->
<?php if ($certificates || $clients): ?>
<section class="section section--tight">
    <div class="container trust">
        <?php if ($certificates): ?>
            <div class="trust__certs">
                <p class="eyebrow">Kalite Belgelerimiz</p>
                <ul class="certs">
                    <?php foreach ($certificates as $c): ?>
                        <li>
                            <?php $href = $c['file'] ? upload_url($c['file']) : ($c['image'] ? upload_url($c['image']) : null); ?>
                            <<?= $href ? 'a href="' . e($href) . '" target="_blank" rel="noopener"' : 'div' ?> class="cert">
                                <?php if ($c['image']): ?><img src="<?= e(upload_url($c['image'])) ?>" alt="" loading="lazy"><?php else: ?><?= icon('shield') ?><?php endif; ?>
                                <span><?= e($c['title']) ?></span>
                            </<?= $href ? 'a' : 'div' ?>>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if ($clients): ?>
            <div class="trust__clients">
                <p class="eyebrow">Bize güvenen markalar</p>
                <div class="marquee"><div class="marquee__track">
                    <?php for ($k = 0; $k < 2; $k++): foreach ($clients as $c): ?>
                        <span class="client"<?= $k ? ' aria-hidden="true"' : '' ?>>
                            <?php if ($c['logo']): ?><img src="<?= e(upload_url($c['logo'])) ?>" alt="<?= e($c['name']) ?>" loading="lazy"><?php else: ?><?= e($c['name']) ?><?php endif; ?>
                        </span>
                    <?php endforeach; endfor; ?>
                </div></div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- HABERLER -->
<?php if ($news): ?>
<section class="section">
    <div class="container">
        <div class="sec-head">
            <div>
                <p class="eyebrow">Haberler & Duyurular</p>
                <h2 class="sec-title">Bizden haberler</h2>
            </div>
            <a class="link-arrow" href="<?= e(url('haberler')) ?>">Tüm haberler <?= icon('arrow') ?></a>
        </div>
        <div class="news-grid">
            <?php foreach ($news as $n): ?><?= view('partials/news-card', ['n' => $n]) ?><?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- BİZE ULAŞIN -->
<section class="section section--alt" id="iletisim">
    <div class="container contact-home">
        <div class="contact-home__info">
            <p class="eyebrow">Bize Ulaşın</p>
            <h2 class="sec-title">Projenizi konuşalım</h2>
            <p class="muted">Ürün, numune veya özel kalıp talepleriniz için formu doldurun; satış ekibimiz aynı iş günü içinde dönüş yapsın.</p>
            <?= view('partials/contact-list') ?>
            <?php if ($src = map_embed_src()): ?>
                <div class="map map--sm"><iframe src="<?= e($src) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Konum haritası" allowfullscreen></iframe></div>
            <?php endif; ?>
        </div>
        <?= view('partials/contact-form') ?>
    </div>
</section>
