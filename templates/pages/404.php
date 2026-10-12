<section class="section">
    <div class="container empty empty--page">
        <p class="big404">404</p>
        <h1>Aradığınız sayfa bulunamadı</h1>
        <p>Sayfa taşınmış veya kaldırılmış olabilir. Ürünlerimize göz atabilir ya da arama yapabilirsiniz.</p>
        <form class="inline-search" action="<?= e(url('ara')) ?>" method="get">
            <input type="search" name="q" placeholder="Ürün ara…" aria-label="Ürün ara" required minlength="2">
            <button class="btn btn--primary" type="submit"><?= icon('search') ?> Ara</button>
        </form>
        <p><a class="link-arrow" href="<?= e(url('urunler')) ?>">Ürünlere git <?= icon('arrow') ?></a></p>
    </div>
</section>
