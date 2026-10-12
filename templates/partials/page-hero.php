<?php /** $heading, $eyebrow?, $intro?, $breadcrumbs */ ?>
<section class="phero">
    <div class="container">
        <?php if (!empty($breadcrumbs)): ?>
            <nav class="crumbs" aria-label="Sayfa yolu">
                <ol>
                    <li><a href="<?= e(url('')) ?>">Anasayfa</a></li>
                    <?php foreach ($breadcrumbs as [$label, $href]): ?>
                        <li><?php if ($href): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php else: ?><span aria-current="page"><?= e($label) ?></span><?php endif; ?></li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>
        <?php if (!empty($eyebrow)): ?><p class="eyebrow"><?= e($eyebrow) ?></p><?php endif; ?>
        <h1 class="phero__title"><?= e($heading) ?></h1>
        <?php if (!empty($intro)): ?><p class="phero__intro"><?= e($intro) ?></p><?php endif; ?>
    </div>
</section>
