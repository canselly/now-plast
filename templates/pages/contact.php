<?= view('partials/page-hero', ['heading' => 'İletişim', 'eyebrow' => 'Bize Ulaşın', 'intro' => 'Sorularınız, numune ve teklif talepleriniz için bize ulaşın. Fabrikamızı ziyaret etmek isterseniz randevu oluşturabilirsiniz.', 'breadcrumbs' => $breadcrumbs]) ?>

<section class="section section--tight">
    <div class="container contact">
        <div class="contact__info">
            <h2 class="h3"><?= e(setting('company_title', setting('site_name'))) ?></h2>
            <?= view('partials/contact-list') ?>
            <div class="contact__btns">
                <a class="btn btn--dark" href="<?= e(map_directions_href()) ?>" target="_blank" rel="noopener"><?= icon('pin') ?> Yol tarifi al</a>
                <?php if ($wa = whatsapp_href('Merhaba, bilgi almak istiyorum.')): ?>
                    <a class="btn btn--ghost" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> WhatsApp</a>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <h2 class="h3">Mesaj gönderin</h2>
            <?= view('partials/contact-form') ?>
        </div>
    </div>
</section>

<?php if ($src = map_embed_src()): ?>
    <section class="map map--full" aria-label="Konum haritası">
        <iframe src="<?= e($src) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Konum haritası" allowfullscreen></iframe>
    </section>
<?php endif; ?>
