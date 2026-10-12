<?php /** @var array $n, $others */ ?>
<?= view('partials/page-hero', ['heading' => $n['title'], 'eyebrow' => fmt_date($n['published_at']), 'intro' => $n['summary'], 'breadcrumbs' => $breadcrumbs]) ?>

<section class="section section--tight">
    <div class="container narrow">
        <?php if ($n['image']): ?><figure class="article-cover"><img src="<?= e(upload_url($n['image'])) ?>" alt=""></figure><?php endif; ?>
        <article class="prose"><?= $n['content'] ?></article>
    </div>
</section>

<?php if ($others): ?>
<section class="section section--alt">
    <div class="container">
        <h2 class="sec-title">Diğer haberler</h2>
        <div class="news-grid"><?php foreach ($others as $o): ?><?= view('partials/news-card', ['n' => $o]) ?><?php endforeach; ?></div>
    </div>
</section>
<?php endif; ?>
