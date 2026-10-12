<?= view('partials/page-hero', ['heading' => 'Sektörler', 'eyebrow' => 'Uygulama alanları', 'intro' => 'Her sektörün hijyen, dayanım ve raf görünürlüğü ihtiyacı farklıdır. Size uygun ambalaj ailesini sektörünüze göre keşfedin.', 'breadcrumbs' => $breadcrumbs]) ?>

<section class="section section--tight">
    <div class="container">
        <?php if ($sectors): ?>
            <div class="sector-grid sector-grid--lg">
                <?php foreach ($sectors as $s): ?>
                    <a class="sector" href="<?= e(sector_url($s)) ?>">
                        <?php if ($s['image']): ?><img class="sector__bg" src="<?= e(upload_url($s['image'])) ?>" alt="" loading="lazy"><?php endif; ?>
                        <span class="sector__ico"><?= sector_icon($s['icon']) ?></span>
                        <h2><?= e($s['name']) ?></h2>
                        <?php if ($s['summary']): ?><p><?= e($s['summary']) ?></p><?php endif; ?>
                        <span class="sector__count"><?= (int) $s['count'] ?> ürün</span>
                        <span class="sector__go"><?= icon('arrow') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty">Henüz sektör eklenmemiş.</p>
        <?php endif; ?>
    </div>
</section>
