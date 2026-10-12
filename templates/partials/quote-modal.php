<?php
$old = $_SESSION['old_form'] ?? [];
unset($_SESSION['old_form']); // Sayfadaki son form bileşeni; eski girdiler yalnızca bir kez gösterilir.
$reopen = $old && ($old['type'] ?? '') !== 'contact';
$kvkk = row("SELECT slug FROM pages WHERE slug = 'kvkk' AND is_active = 1");
?>
<div class="modal" data-modal hidden<?php if ($reopen): ?> data-reopen="<?= e($old['type']) ?>" data-reopen-pid="<?= (int) ($old['product_id'] ?? 0) ?>"<?php endif; ?>>
    <div class="modal__box" role="dialog" aria-modal="true" aria-labelledby="qm-title">
        <button type="button" class="icon-btn modal__x" data-modal-close aria-label="Kapat"><?= icon('close') ?></button>
        <p class="eyebrow" data-qm-eyebrow>Teklif talebi</p>
        <h2 id="qm-title" data-qm-title>Fiyat teklifi isteyin</h2>
        <p class="modal__product" data-qm-product hidden></p>
        <form class="form" method="post" action="<?= e(url('talep')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="quote" data-qm-type>
            <input type="hidden" name="product_id" value="" data-qm-pid>
            <input type="hidden" name="back" value="<?= e(url(current_path())) ?>">
            <input type="hidden" name="ts" value="<?= time() ?>">
            <div class="hp" aria-hidden="true"><label>Web sitesi <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <div class="form__grid">
                <label class="field"><span>Ad Soyad *</span><input name="name" required maxlength="120" autocomplete="name" value="<?= e($old['name'] ?? '') ?>"></label>
                <label class="field"><span>Firma</span><input name="company" maxlength="190" autocomplete="organization" value="<?= e($old['company'] ?? '') ?>"></label>
                <label class="field"><span>E-posta</span><input type="email" name="email" maxlength="190" autocomplete="email" value="<?= e($old['email'] ?? '') ?>"></label>
                <label class="field"><span>Telefon</span><input type="tel" name="phone" maxlength="40" autocomplete="tel" value="<?= e($old['phone'] ?? '') ?>"></label>
                <label class="field field--full" data-qm-qty><span>Tahmini adet / yıllık ihtiyaç</span><input name="quantity" maxlength="60" placeholder="ör. 50.000 adet" value="<?= e($old['quantity'] ?? '') ?>"></label>
                <label class="field field--full"><span>Mesajınız</span><textarea name="message" rows="4" maxlength="5000" placeholder="Baskı, kapak, renk ve teslimat tercihlerinizi yazabilirsiniz."><?= e($old['message'] ?? '') ?></textarea></label>
            </div>
            <label class="check"><input type="checkbox" name="kvkk" value="1" required>
                <span><?php if ($kvkk): ?><a href="<?= e(page_url($kvkk)) ?>" target="_blank">KVKK Aydınlatma Metni</a>'ni<?php else: ?>KVKK Aydınlatma Metni'ni<?php endif; ?> okudum, kişisel verilerimin işlenmesini kabul ediyorum.</span></label>
            <button class="btn btn--primary btn--block" type="submit">Gönder <?= icon('arrow') ?></button>
        </form>
    </div>
</div>
