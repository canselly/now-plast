<?php /** @var array $pg */
if ($pg['pages'] <= 1) return;
$cur = $pg['page'];
$range = array_unique(array_filter([1, $cur - 2, $cur - 1, $cur, $cur + 1, $cur + 2, $pg['pages']], fn($x) => $x >= 1 && $x <= $pg['pages']));
sort($range);
?>
<nav class="pager" aria-label="Sayfalar">
    <?php if ($cur > 1): ?><a class="pager__btn" href="<?= e(page_link($cur - 1)) ?>" aria-label="Önceki sayfa"><?= icon('chev-l') ?></a><?php endif; ?>
    <?php $prev = 0; foreach ($range as $p): ?>
        <?php if ($p - $prev > 1): ?><span class="pager__gap">…</span><?php endif; ?>
        <?php if ($p === $cur): ?><span class="pager__btn is-current" aria-current="page"><?= $p ?></span>
        <?php else: ?><a class="pager__btn" href="<?= e(page_link($p)) ?>"><?= $p ?></a><?php endif; ?>
    <?php $prev = $p; endforeach; ?>
    <?php if ($cur < $pg['pages']): ?><a class="pager__btn" href="<?= e(page_link($cur + 1)) ?>" aria-label="Sonraki sayfa"><?= icon('chev-r') ?></a><?php endif; ?>
</nav>
