<?= view('partials/page-hero', ['heading' => 'Haberler & Duyurular', 'eyebrow' => 'Güncel', 'breadcrumbs' => $breadcrumbs]) ?>

<section class="section section--tight">
    <div class="container">
        <?php if ($items): ?>
            <div class="news-grid">
                <?php foreach ($items as $n): ?><?= view('partials/news-card', ['n' => $n]) ?><?php endforeach; ?>
            </div>
            <?= view('partials/pagination', ['pg' => $pg]) ?>
        <?php else: ?>
            <p class="empty">Henüz haber eklenmemiş.</p>
        <?php endif; ?>
    </div>
</section>
