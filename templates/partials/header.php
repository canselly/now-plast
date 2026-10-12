<?php
$menu = menu_tree('header');
$siteName = setting('site_name', 'NowPlast');
$logo = setting('logo');
$phone = setting('phone');
$email = setting('email');
$socials = array_filter(['facebook' => setting('facebook'), 'instagram' => setting('instagram'), 'linkedin' => setting('linkedin'), 'youtube' => setting('youtube'), 'x' => setting('x')]);
?>
<div class="topbar">
    <div class="container topbar__in">
        <div class="topbar__info">
            <?php if ($phone): ?><a href="<?= e(tel_href($phone)) ?>"><?= icon('phone') ?><?= e($phone) ?></a><?php endif; ?>
            <?php if ($email): ?><a href="mailto:<?= e($email) ?>"><?= icon('mail') ?><?= e($email) ?></a><?php endif; ?>
            <?php if ($h = setting('working_hours')): ?><span><?= icon('clock') ?><?= e($h) ?></span><?php endif; ?>
        </div>
        <div class="topbar__social">
            <?php if (setting('catalog_pdf')): ?><a class="topbar__cat" href="<?= e(url('katalog')) ?>" target="_blank" rel="noopener"><?= icon('download') ?>E-Katalog</a><?php endif; ?>
            <?php foreach ($socials as $k => $v): ?>
                <a href="<?= e($v) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($k)) ?>"><?= icon($k) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<header class="header" data-header>
    <div class="container header__in">
        <a class="logo" href="<?= e(url('')) ?>" aria-label="<?= e($siteName) ?> anasayfa">
            <?php if ($logo): ?>
                <img src="<?= e(upload_url($logo)) ?>" alt="<?= e($siteName) ?>">
            <?php else: ?>
                <span class="logo__mark" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M6 9h20l-2.4 17.2a2 2 0 0 1-2 1.8H10.4a2 2 0 0 1-2-1.8z" fill="currentColor" opacity=".9"/><rect x="4" y="4" width="24" height="5" rx="2" fill="currentColor"/></svg></span>
                <span class="logo__text"><?= e($siteName) ?></span>
            <?php endif; ?>
        </a>

        <nav class="nav" aria-label="Ana menü">
            <ul class="nav__list">
                <?php foreach ($menu as $it): $has = !empty($it['children']); ?>
                    <li class="nav__item<?= $has ? ' has-sub' : '' ?><?= $it['mega'] ? ' has-mega' : '' ?><?= is_active_link($it['href']) ? ' is-active' : '' ?>">
                        <a class="nav__link" href="<?= e($it['href']) ?>"<?= $it['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>>
                            <?= e($it['title']) ?><?php if ($has): ?><?= icon('chev', 'ico ico--xs') ?><?php endif; ?>
                        </a>
                        <?php if ($has && $it['mega']): ?>
                            <div class="mega">
                                <div class="container mega__in">
                                    <div class="mega__cols">
                                        <?php foreach ($it['children'] as $col): ?>
                                            <div class="mega__col">
                                                <a class="mega__title" href="<?= e($col['href']) ?>">
                                                    <span class="mega__thumb"><img src="<?= e($col['image'] ? upload_url($col['image']) : asset('assets/img/placeholder.svg')) ?>" alt="" loading="lazy"></span>
                                                    <span><?= e($col['title']) ?></span>
                                                </a>
                                                <?php if ($col['children']): ?>
                                                    <ul>
                                                        <?php foreach ($col['children'] as $sub): ?>
                                                            <li><a href="<?= e($sub['href']) ?>"><?= e($sub['title']) ?></a></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="mega__promo">
                                        <p class="eyebrow">Özel üretim</p>
                                        <p class="mega__promo-title">Aradığınız ölçüyü bulamadınız mı?</p>
                                        <p>Ürününüze özel kalıp ve ambalaj tasarımı için mühendislerimizle görüşün.</p>
                                        <a class="btn btn--sm btn--primary" href="<?= e(url('iletisim')) ?>">Proje görüşmesi <?= icon('arrow') ?></a>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($has): ?>
                            <ul class="sub">
                                <?php foreach ($it['children'] as $sub): ?>
                                    <li><a href="<?= e($sub['href']) ?>"<?= $sub['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($sub['title']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="header__actions">
            <button class="icon-btn" type="button" data-search-open aria-label="Ürün ara"><?= icon('search') ?></button>
            <button class="btn btn--primary btn--sm header__cta" type="button" data-quote="quote">Teklif Al</button>
            <button class="icon-btn burger" type="button" data-drawer-open aria-label="Menüyü aç" aria-expanded="false"><?= icon('menu') ?></button>
        </div>
    </div>
</header>

<div class="search-ov" data-search hidden>
    <form class="search-ov__form container" action="<?= e(url('ara')) ?>" method="get" role="search">
        <?= icon('search') ?>
        <input type="search" name="q" placeholder="Ürün adı, kodu veya hacim ara…" aria-label="Ürün ara" autocomplete="off" required minlength="2">
        <button class="icon-btn" type="button" data-search-close aria-label="Kapat"><?= icon('close') ?></button>
    </form>
</div>

<div class="drawer" data-drawer hidden>
    <div class="drawer__panel" role="dialog" aria-label="Menü">
        <div class="drawer__head">
            <span class="logo__text"><?= e($siteName) ?></span>
            <button class="icon-btn" type="button" data-drawer-close aria-label="Menüyü kapat"><?= icon('close') ?></button>
        </div>
        <form class="drawer__search" action="<?= e(url('ara')) ?>" method="get">
            <?= icon('search') ?><input type="search" name="q" placeholder="Ürün ara…" aria-label="Ürün ara">
        </form>
        <ul class="drawer__nav">
            <?php foreach ($menu as $it): $has = !empty($it['children']); ?>
                <li class="<?= $has ? 'has-sub' : '' ?>">
                    <div class="drawer__row">
                        <a href="<?= e($it['href']) ?>"><?= e($it['title']) ?></a>
                        <?php if ($has): ?><button type="button" class="drawer__tog" aria-label="Alt menüyü aç" aria-expanded="false"><?= icon('chev') ?></button><?php endif; ?>
                    </div>
                    <?php if ($has): ?>
                        <ul>
                            <?php foreach ($it['children'] as $sub): ?>
                                <li><a href="<?= e($sub['href']) ?>"><?= e($sub['title']) ?></a>
                                    <?php if (!empty($sub['children'])): ?>
                                        <ul><?php foreach ($sub['children'] as $s2): ?><li><a href="<?= e($s2['href']) ?>"><?= e($s2['title']) ?></a></li><?php endforeach; ?></ul>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="drawer__foot">
            <button class="btn btn--primary btn--block" type="button" data-quote="quote">Teklif Al</button>
            <?php if ($phone): ?><a class="btn btn--ghost btn--block" href="<?= e(tel_href($phone)) ?>"><?= icon('phone') ?><?= e($phone) ?></a><?php endif; ?>
        </div>
    </div>
</div>
