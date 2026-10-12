<ul class="clist">
    <?php if ($a = setting('address')): ?>
        <li><span class="clist__ico"><?= icon('pin') ?></span><div><strong>Adres</strong><a href="<?= e(map_directions_href()) ?>" target="_blank" rel="noopener"><?= nl2br(e($a)) ?></a></div></li>
    <?php endif; ?>
    <?php if ($p = setting('phone')): ?>
        <li><span class="clist__ico"><?= icon('phone') ?></span><div><strong>Telefon</strong><a href="<?= e(tel_href($p)) ?>"><?= e($p) ?></a><?php if ($p2 = setting('phone2')): ?><a href="<?= e(tel_href($p2)) ?>"><?= e($p2) ?></a><?php endif; ?></div></li>
    <?php endif; ?>
    <?php if ($m = setting('email')): ?>
        <li><span class="clist__ico"><?= icon('mail') ?></span><div><strong>E-posta</strong><a href="mailto:<?= e($m) ?>"><?= e($m) ?></a></div></li>
    <?php endif; ?>
    <?php if ($h = setting('working_hours')): ?>
        <li><span class="clist__ico"><?= icon('clock') ?></span><div><strong>Çalışma saatleri</strong><span><?= e($h) ?></span></div></li>
    <?php endif; ?>
</ul>
