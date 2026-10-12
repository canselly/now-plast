<?php
$siteName = setting('site_name', 'NowPlast');
$footerMenu = menu_tree('footer');
$topCats = category_children_map()[0] ?? [];
$socials = array_filter(['facebook' => setting('facebook'), 'instagram' => setting('instagram'), 'linkedin' => setting('linkedin'), 'youtube' => setting('youtube'), 'x' => setting('x')]);
$logoFooter = setting('logo_footer');
?>
<section class="cta-band">
    <div class="container cta-band__in">
        <div>
            <p class="eyebrow eyebrow--light">Numune & Teklif</p>
            <h2><?= e(setting('cta_title', 'Ürününüz için doğru ambalajı birlikte seçelim.')) ?></h2>
            <p><?= e(setting('cta_text', 'Numune talep edin, ihtiyacınıza uygun hacim, baskı ve kapak seçeneklerini 24 saat içinde size iletelim.')) ?></p>
        </div>
        <div class="cta-band__actions">
            <button type="button" class="btn btn--light" data-quote="sample">Numune Talep Et</button>
            <?php if ($p = setting('phone')): ?><a class="btn btn--outline-light" href="<?= e(tel_href($p)) ?>"><?= icon('phone') ?><?= e($p) ?></a><?php endif; ?>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container footer__grid">
        <div class="footer__brand">
            <a class="logo logo--light" href="<?= e(url('')) ?>">
                <?php if ($logoFooter): ?>
                    <img src="<?= e(upload_url($logoFooter)) ?>" alt="<?= e($siteName) ?>">
                <?php elseif ($logoMain = setting('logo')): ?>
                    <span class="logo__badge"><img src="<?= e(upload_url($logoMain)) ?>" alt="<?= e($siteName) ?>"></span>
                <?php else: ?>
                    <span class="logo__mark" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M6 9h20l-2.4 17.2a2 2 0 0 1-2 1.8H10.4a2 2 0 0 1-2-1.8z" fill="currentColor" opacity=".9"/><rect x="4" y="4" width="24" height="5" rx="2" fill="currentColor"/></svg></span>
                    <span class="logo__text"><?= e($siteName) ?></span>
                <?php endif; ?>
            </a>
            <p><?= e(setting('footer_text', 'Plastik enjeksiyon ile gıda ve endüstriyel ambalaj üretimi. Kalıp tasarımından IML baskıya kadar tüm süreç tek çatı altında.')) ?></p>
            <?php if ($socials): ?>
                <div class="footer__social">
                    <?php foreach ($socials as $k => $v): ?><a href="<?= e($v) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($k)) ?>"><?= icon($k) ?></a><?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <h3 class="footer__h">Ürün Grupları</h3>
            <ul class="footer__links">
                <?php foreach ($topCats as $c): ?><li><a href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?></a></li><?php endforeach; ?>
                <li><a href="<?= e(url('urunler')) ?>">Tüm ürünler</a></li>
            </ul>
        </div>

        <div>
            <h3 class="footer__h">Kurumsal</h3>
            <ul class="footer__links">
                <?php foreach ($footerMenu as $it): ?>
                    <li><a href="<?= e($it['href']) ?>"<?= $it['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($it['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div>
            <h3 class="footer__h">İletişim</h3>
            <ul class="footer__contact">
                <?php if ($a = setting('address')): ?><li><?= icon('pin') ?><a href="<?= e(map_directions_href()) ?>" target="_blank" rel="noopener"><?= nl2br(e($a)) ?></a></li><?php endif; ?>
                <?php if ($p = setting('phone')): ?><li><?= icon('phone') ?><a href="<?= e(tel_href($p)) ?>"><?= e($p) ?></a></li><?php endif; ?>
                <?php if ($p2 = setting('phone2')): ?><li><?= icon('phone') ?><a href="<?= e(tel_href($p2)) ?>"><?= e($p2) ?></a></li><?php endif; ?>
                <?php if ($m = setting('email')): ?><li><?= icon('mail') ?><a href="mailto:<?= e($m) ?>"><?= e($m) ?></a></li><?php endif; ?>
                <?php if ($h = setting('working_hours')): ?><li><?= icon('clock') ?><span><?= e($h) ?></span></li><?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="container footer__bottom">
        <span>© <?= date('Y') ?> <?= e(setting('company_title', $siteName)) ?>. Tüm hakları saklıdır.</span>
        <a href="<?= e(url('sitemap.xml')) ?>">Site haritası</a>
    </div>
</footer>
