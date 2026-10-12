<?php /** @var array $page */ ?>
<?= view('partials/page-hero', ['heading' => $page['title'], 'eyebrow' => setting('site_name'), 'intro' => $page['summary'], 'breadcrumbs' => $breadcrumbs]) ?>

<section class="section section--tight">
    <div class="container page-body<?= $page['image'] ? ' page-body--media' : '' ?>">
        <article class="prose"><?= $page['content'] ?></article>
        <?php if ($page['image']): ?>
            <figure class="page-body__media"><img src="<?= e(upload_url($page['image'])) ?>" alt="" loading="lazy"></figure>
        <?php endif; ?>
    </div>
</section>
