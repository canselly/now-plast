<?php /** @var array $p */ ?>
<article class="pcard">
    <a class="pcard__media" href="<?= e(product_url($p)) ?>" tabindex="-1" aria-hidden="true">
        <img src="<?= e(product_image($p)) ?>" alt="" loading="lazy">
        <?php if (!empty($p['is_new'])): ?><span class="badge badge--new">Yeni</span><?php endif; ?>
    </a>
    <div class="pcard__body">
        <?php if (!empty($p['code'])): ?><span class="pcard__code"><?= e($p['code']) ?></span><?php endif; ?>
        <h3 class="pcard__title"><a href="<?= e(product_url($p)) ?>"><?= e($p['name']) ?></a></h3>
        <?php if (!empty($p['category_name'])): ?><p class="pcard__cat"><?= e($p['category_name']) ?></p><?php endif; ?>
        <ul class="pcard__specs">
            <?php if ($v = fmt_volume($p['volume_ml'] ?? null)): ?><li><span>Hacim</span><strong><?= e($v) ?></strong></li><?php endif; ?>
            <?php if ((float) ($p['top_diameter_mm'] ?? 0) > 0): ?><li><span>Ağız Ø</span><strong><?= e(fmt_num($p['top_diameter_mm'])) ?> mm</strong></li><?php
            elseif ((float) ($p['width_mm'] ?? 0) > 0 && (float) ($p['length_mm'] ?? 0) > 0): ?><li><span>Ölçü</span><strong><?= e(fmt_num($p['width_mm'])) ?>×<?= e(fmt_num($p['length_mm'])) ?></strong></li><?php endif; ?>
            <?php if (!empty($p['material'])): ?><li><span>Malzeme</span><strong><?= e($p['material']) ?></strong></li><?php endif; ?>
        </ul>
        <div class="pcard__foot">
            <a class="pcard__more" href="<?= e(product_url($p)) ?>">Detaylı incele <?= icon('arrow') ?></a>
            <button type="button" class="pcard__ask" data-quote="sample" data-pid="<?= (int) $p['id'] ?>" data-pname="<?= e(trim(($p['code'] ?? '') . ' ' . $p['name'])) ?>" title="Numune talep et" aria-label="<?= e($p['name']) ?> için numune talep et"><?= icon('flask') ?></button>
        </div>
    </div>
</article>
