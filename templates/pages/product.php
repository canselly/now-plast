<?php
/** @var array $p, $images, $specs, $sectors, $similar, $breadcrumbs */
$fullName = trim(($p['code'] ? $p['code'] . ' ' : '') . $p['name']);
$wa = whatsapp_href('Merhaba, ' . $fullName . ' ürünü hakkında bilgi almak istiyorum. ' . full_url('urun/' . $p['slug']));
?>
<section class="pdetail">
    <div class="container">
        <nav class="crumbs" aria-label="Sayfa yolu">
            <ol>
                <li><a href="<?= e(url('')) ?>">Anasayfa</a></li>
                <?php foreach ($breadcrumbs as [$label, $href]): ?>
                    <li><?php if ($href): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php else: ?><span aria-current="page"><?= e($label) ?></span><?php endif; ?></li>
                <?php endforeach; ?>
            </ol>
        </nav>

        <div class="pdetail__grid">
            <div class="gallery" data-gallery>
                <div class="gallery__main">
                    <img src="<?= e($images[0]) ?>" alt="<?= e($fullName) ?>" data-gallery-main>
                    <?php if ($p['is_new']): ?><span class="badge badge--new">Yeni</span><?php endif; ?>
                </div>
                <?php if (count($images) > 1): ?>
                    <div class="gallery__thumbs">
                        <?php foreach ($images as $i => $src): ?>
                            <button type="button" class="gallery__thumb<?= $i === 0 ? ' is-active' : '' ?>" data-src="<?= e($src) ?>" aria-label="Görsel <?= $i + 1 ?>"><img src="<?= e($src) ?>" alt="" loading="lazy"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="pinfo">
                <?php if ($p['category_name']): ?><a class="eyebrow" href="<?= e(url('urunler/' . $p['category_slug'])) ?>"><?= e($p['category_name']) ?></a><?php endif; ?>
                <h1 class="pinfo__title"><?= e($p['name']) ?></h1>
                <?php if ($p['code']): ?><p class="pinfo__code">Ürün kodu <span><?= e($p['code']) ?></span></p><?php endif; ?>
                <?php if ($p['summary']): ?><p class="pinfo__summary"><?= e($p['summary']) ?></p><?php endif; ?>

                <?php
                $highlight = array_slice(array_filter([
                    'Hacim' => $specs['Hacim'] ?? null,
                    'Ağız Çapı' => $specs['Ağız Çapı'] ?? null,
                    'Yükseklik' => $specs['Yükseklik'] ?? null,
                    'Hammadde' => $specs['Hammadde'] ?? null,
                ]), 0, 4);
                ?>
                <?php if ($highlight): ?>
                    <dl class="pinfo__key">
                        <?php foreach ($highlight as $k => $v): ?><div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div><?php endforeach; ?>
                    </dl>
                <?php endif; ?>

                <div class="pinfo__actions">
                    <button type="button" class="btn btn--primary btn--lg" data-quote="sample" data-pid="<?= (int) $p['id'] ?>" data-pname="<?= e($fullName) ?>"><?= icon('flask') ?> Numune Talep Et</button>
                    <button type="button" class="btn btn--dark btn--lg" data-quote="quote" data-pid="<?= (int) $p['id'] ?>" data-pname="<?= e($fullName) ?>"><?= icon('quote') ?> Teklif İste</button>
                </div>
                <div class="pinfo__links">
                    <?php if ($wa): ?><a href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> WhatsApp ile sor</a><?php endif; ?>
                    <?php if ($p['datasheet']): ?><a href="<?= e(upload_url($p['datasheet'])) ?>" target="_blank" rel="noopener"><?= icon('doc') ?> Teknik föy (PDF)</a><?php endif; ?>
                    <?php if (setting('catalog_pdf')): ?><a href="<?= e(url('katalog')) ?>" target="_blank" rel="noopener"><?= icon('download') ?> E-Katalog</a><?php endif; ?>
                </div>

                <?php if ($sectors): ?>
                    <div class="pinfo__sectors">
                        <span>Kullanım alanları</span>
                        <div class="chips">
                            <?php foreach ($sectors as $s): ?><a class="chip" href="<?= e(sector_url($s)) ?>"><?= sector_icon($s['icon']) ?><?= e($s['name']) ?></a><?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="ptabs" data-tabs>
            <div class="ptabs__nav" role="tablist">
                <?php if ($specs): ?><button type="button" role="tab" class="is-active" data-tab="specs">Teknik Özellikler</button><?php endif; ?>
                <?php if (trim(strip_tags((string) $p['description'])) !== ''): ?><button type="button" role="tab" class="<?= $specs ? '' : 'is-active' ?>" data-tab="desc">Ürün Açıklaması</button><?php endif; ?>
            </div>
            <?php if ($specs): ?>
                <div class="ptabs__panel is-active" data-panel="specs" role="tabpanel">
                    <table class="spec-table">
                        <tbody>
                        <?php if ($p['code']): ?><tr><th>Ürün Kodu</th><td><?= e($p['code']) ?></td></tr><?php endif; ?>
                        <?php foreach ($specs as $k => $v): ?><tr><th><?= e($k) ?></th><td><?= e($v) ?></td></tr><?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="note">* Derin dondurucu, yüksek sıcaklık veya özel kullanım koşulları için lütfen satış temsilcinizle görüşün. Ölçüler ±%2 tolerans içindedir.</p>
                </div>
            <?php endif; ?>
            <?php if (trim(strip_tags((string) $p['description'])) !== ''): ?>
                <div class="ptabs__panel prose<?= $specs ? '' : ' is-active' ?>" data-panel="desc" role="tabpanel"><?= $p['description'] ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($similar): ?>
<section class="section section--alt">
    <div class="container">
        <div class="sec-head">
            <div>
                <p class="eyebrow">Benzer Ürünler</p>
                <h2 class="sec-title">Aynı gruptaki diğer ölçüler</h2>
            </div>
            <div class="rail-ctrl">
                <button type="button" class="icon-btn icon-btn--line" data-rail-prev aria-label="Geri"><?= icon('chev-l') ?></button>
                <button type="button" class="icon-btn icon-btn--line" data-rail-next aria-label="İleri"><?= icon('chev-r') ?></button>
            </div>
        </div>
        <div class="rail" data-rail>
            <?php foreach ($similar as $s): ?><?= view('partials/product-card', ['p' => $s]) ?><?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $fullName,
    'sku' => $p['code'],
    'description' => $p['summary'] ?: excerpt($p['description'], 300),
    'image' => array_map(fn($i) => str_starts_with($i, 'http') ? $i : full_url(ltrim(substr(parse_url($i, PHP_URL_PATH), strlen(base_path())), '/')), $images),
    'brand' => ['@type' => 'Brand', 'name' => setting('site_name')],
    'category' => $p['category_name'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
