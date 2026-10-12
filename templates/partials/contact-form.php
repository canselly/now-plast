<?php
$old = $_SESSION['old_form'] ?? [];
$kvkk = row("SELECT slug FROM pages WHERE slug = 'kvkk' AND is_active = 1");
?>
<form class="form card-form" id="form" method="post" action="<?= e(url('talep')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="contact">
    <input type="hidden" name="back" value="<?= e(url(current_path())) ?>#form">
    <input type="hidden" name="ts" value="<?= time() ?>">
    <div class="hp" aria-hidden="true"><label>Web sitesi <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <div class="form__grid">
        <label class="field"><span>Ad Soyad *</span><input name="name" required maxlength="120" autocomplete="name" value="<?= e($old['name'] ?? '') ?>"></label>
        <label class="field"><span>Firma</span><input name="company" maxlength="190" autocomplete="organization" value="<?= e($old['company'] ?? '') ?>"></label>
        <label class="field"><span>E-posta *</span><input type="email" name="email" required maxlength="190" autocomplete="email" value="<?= e($old['email'] ?? '') ?>"></label>
        <label class="field"><span>Telefon</span><input type="tel" name="phone" maxlength="40" autocomplete="tel" value="<?= e($old['phone'] ?? '') ?>"></label>
        <label class="field field--full"><span>Konu</span>
            <select name="subject">
                <?php foreach (['Genel bilgi', 'Ürün bilgisi', 'Fiyat teklifi', 'Özel kalıp / proje', 'Satış sonrası', 'İnsan kaynakları'] as $s): ?>
                    <option<?= ($old['subject'] ?? '') === $s ? ' selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field field--full"><span>Mesajınız *</span><textarea name="message" rows="5" required maxlength="5000"><?= e($old['message'] ?? '') ?></textarea></label>
    </div>
    <label class="check"><input type="checkbox" name="kvkk" value="1" required>
        <span><?php if ($kvkk): ?><a href="<?= e(page_url($kvkk)) ?>" target="_blank">KVKK Aydınlatma Metni</a>'ni<?php else: ?>KVKK Aydınlatma Metni'ni<?php endif; ?> okudum, kişisel verilerimin işlenmesini kabul ediyorum.</span></label>
    <button class="btn btn--primary" type="submit">Mesajı Gönder <?= icon('arrow') ?></button>
</form>
