<?php /** @var array $n */ ?>
<article class="ncard">
    <a class="ncard__media" href="<?= e(news_url($n)) ?>" tabindex="-1" aria-hidden="true">
        <img src="<?= e($n['image'] ? upload_url($n['image']) : upload_url('demo/news.svg')) ?>" alt="" loading="lazy">
    </a>
    <div class="ncard__body">
        <time datetime="<?= e($n['published_at']) ?>"><?= e(fmt_date($n['published_at'])) ?></time>
        <h3><a href="<?= e(news_url($n)) ?>"><?= e($n['title']) ?></a></h3>
        <p><?= e($n['summary'] ?: excerpt($n['content'], 120)) ?></p>
    </div>
</article>
