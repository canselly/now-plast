<?php
/** @var array $list, $subcats; ?array $category, $sector */
$L = $list;
$baseAction = url(current_path());
?>
<?= view('partials/page-hero', compact('heading', 'breadcrumbs') + ['eyebrow' => $eyebrow ?? ($category ? 'Ürün Grubu' : 'Ürün Kataloğu'), 'intro' => $intro ?? null]) ?>

<?php if (!empty($subcats)): ?>
    <section class="subcats-wrap">
        <div class="container">
            <div class="subcats">
                <?php foreach ($subcats as $c): ?>
                    <a class="subcat" href="<?= e(category_url($c)) ?>">
                        <span class="subcat__img"><img src="<?= e($c['image'] ? upload_url($c['image']) : asset('assets/img/placeholder.svg')) ?>" alt="" loading="lazy"></span>
                        <span class="subcat__txt"><strong><?= e($c['name']) ?></strong><small><?= (int) ($c['count'] ?? 0) ?> ürün</small></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="section section--tight">
    <div class="container listing">
        <aside class="filters" data-filters>
            <div class="filters__head">
                <strong><?= icon('filter') ?> Filtrele</strong>
                <button type="button" class="icon-btn filters__close" data-filters-close aria-label="Filtreleri kapat"><?= icon('close') ?></button>
            </div>

            <?php if (!empty($all_sectors)): ?>
                <div class="fgroup">
                    <h3 class="fgroup__title">Sektörler</h3>
                    <ul class="ftree">
                        <?php foreach ($all_sectors as $s): ?>
                            <li><a class="<?= $sector && (int) $sector['id'] === (int) $s['id'] ? 'is-active' : '' ?>" href="<?= e(sector_url($s)) ?>"><?= e($s['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="fgroup">
                    <h3 class="fgroup__title">Kategoriler</h3>
                    <?php
                    $map = category_children_map();
                    $activeIds = $category ? array_merge(array_map(fn($a) => (int) $a['id'], category_ancestors($category)), [(int) $category['id']]) : [];
                    $tree = function (int $pid, int $depth) use (&$tree, $map, $activeIds, $category) {
                        if (empty($map[$pid])) return '';
                        $h = '<ul class="ftree' . ($depth ? ' ftree--sub' : '') . '">';
                        foreach ($map[$pid] as $c) {
                            $isOpen = in_array((int) $c['id'], $activeIds, true);
                            $isCur = $category && (int) $category['id'] === (int) $c['id'];
                            $h .= '<li><a class="' . ($isCur ? 'is-active' : ($isOpen ? 'is-open' : '')) . '" href="' . e(category_url($c)) . '">' . e($c['name']) . '</a>';
                            if ($isOpen) $h .= $tree((int) $c['id'], $depth + 1);
                            $h .= '</li>';
                        }
                        return $h . '</ul>';
                    };
                    ?>
                    <a class="ftree__all<?= !$category ? ' is-active' : '' ?>" href="<?= e(url('urunler')) ?>">Tüm ürünler</a>
                    <?= $tree(0, 0) ?>
                </div>
            <?php endif; ?>

            <form method="get" action="<?= e($baseAction) ?>" class="ffilters" data-autosubmit>
                <?php if ($L['q'] !== '' && current_path() === 'ara'): ?><input type="hidden" name="q" value="<?= e($L['q']) ?>"><?php endif; ?>
                <input type="hidden" name="sirala" value="<?= e($L['sort']) ?>">

                <?php if ($L['sector_opts']): ?>
                    <div class="fgroup">
                        <h3 class="fgroup__title">Sektör</h3>
                        <?php foreach ($L['sector_opts'] as $o): ?>
                            <label class="fcheck"><input type="checkbox" name="s[]" value="<?= (int) $o['id'] ?>"<?= in_array((int) $o['id'], $L['sel_sectors'], true) ? ' checked' : '' ?>><span><?= e($o['name']) ?></span><small><?= (int) $o['n'] ?></small></label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($L['materials']): ?>
                    <div class="fgroup">
                        <h3 class="fgroup__title">Hammadde</h3>
                        <?php foreach ($L['materials'] as $o): ?>
                            <label class="fcheck"><input type="checkbox" name="m[]" value="<?= e($o['v']) ?>"<?= in_array($o['v'], $L['sel_materials'], true) ? ' checked' : '' ?>><span><?= e($o['v']) ?></span><small><?= (int) $o['n'] ?></small></label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="fgroup">
                    <h3 class="fgroup__title">Hacim (ml)</h3>
                    <div class="frange">
                        <input type="number" name="vmin" min="0" step="any" placeholder="En az" value="<?= e($L['vmin'] ?? '') ?>" aria-label="En az hacim">
                        <span>–</span>
                        <input type="number" name="vmax" min="0" step="any" placeholder="En çok" value="<?= e($L['vmax'] ?? '') ?>" aria-label="En çok hacim">
                    </div>
                    <button class="btn btn--sm btn--dark btn--block" type="submit">Uygula</button>
                </div>
                <?php if ($L['has_filters'] && current_path() !== 'ara'): ?>
                    <a class="ffilters__clear" href="<?= e($baseAction) ?>">Filtreleri temizle</a>
                <?php endif; ?>
            </form>
        </aside>

        <div class="results">
            <?php if (!empty($search_pages)): ?>
                <div class="search-pages">
                    <p class="eyebrow">Sayfalar</p>
                    <?php foreach ($search_pages as $sp): ?>
                        <a href="<?= e(page_url($sp)) ?>"><strong><?= e($sp['title']) ?></strong><span><?= e($sp['summary'] ?: excerpt($sp['content'], 110)) ?></span></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="toolbar">
                <button type="button" class="btn btn--sm btn--ghost toolbar__filter" data-filters-open><?= icon('filter') ?> Filtreler</button>
                <p class="toolbar__count"><strong><?= (int) $L['pg']['total'] ?></strong> ürün</p>
                <form method="get" action="<?= e($baseAction) ?>" class="toolbar__sort">
                    <?php foreach ($_GET as $k => $v): if (in_array($k, ['sirala', 'sayfa', 'route'], true)) continue; ?>
                        <?php foreach ((array) $v as $vv): if (!is_scalar($vv)) continue; ?>
                            <input type="hidden" name="<?= e(is_array($v) ? $k . '[]' : $k) ?>" value="<?= e($vv) ?>">
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <label><span class="sr-only">Sırala</span>
                        <select name="sirala" data-autosubmit-select>
                            <?php foreach ($L['sort_labels'] as $k => $lbl): ?><option value="<?= e($k) ?>"<?= $L['sort'] === $k ? ' selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                </form>
                <div class="toolbar__view" role="group" aria-label="Görünüm">
                    <button type="button" class="icon-btn is-active" data-view="grid" aria-label="Izgara görünümü"><?= icon('grid') ?></button>
                    <button type="button" class="icon-btn" data-view="list" aria-label="Liste görünümü"><?= icon('list') ?></button>
                </div>
            </div>

            <?php if ($L['items']): ?>
                <div class="pgrid" data-view-target>
                    <?php foreach ($L['items'] as $p): ?><?= view('partials/product-card', ['p' => $p]) ?><?php endforeach; ?>
                </div>
                <?= view('partials/pagination', ['pg' => $L['pg']]) ?>
            <?php else: ?>
                <div class="empty">
                    <?= icon('search') ?>
                    <h2>Ürün bulunamadı</h2>
                    <p>Filtreleri değiştirmeyi deneyin ya da ihtiyacınızı bize yazın; size özel çözüm üretelim.</p>
                    <button type="button" class="btn btn--primary" data-quote="quote">Bize yazın</button>
                </div>
            <?php endif; ?>

            <?php if (!empty($description)): ?>
                <div class="prose listing__desc"><?= $description ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>
