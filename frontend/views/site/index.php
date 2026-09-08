<?php

use frontend\helpers\MediaUrl;
use frontend\widgets\Icon;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

/* @var $slides frontend\models\Carousel[] */
/* @var $sections frontend\models\HomeSection[] */
/* @var $portfolioItems frontend\models\Sample[] */
/* @var $posts frontend\models\Blog[] */
/* @var $faqs frontend\models\Faqs[] */
/* @var $stats array */
/* @var $siteTitle string */
/* @var $homeContent string */

$this->title = $siteTitle;
$contentSlide = null;
foreach ($slides as $candidate) {
    if ((int) $candidate->show_content === 1) {
        $contentSlide = $candidate;
        break;
    }
}
$metaDescription = $contentSlide ? trim(strip_tags((string) $contentSlide->getLocalized('text'))) : '';
$metaDescription = $metaDescription !== ''
    ? $metaDescription
    : Yii::t('app', 'From product strategy to secure delivery, we turn complex ideas into clear digital experiences.');
$this->registerMetaTag([
    'name' => 'description',
    'content' => mb_substr($metaDescription, 0, 160),
], 'description');
?>
<h1 class="sr-only"><?= Html::encode($this->title) ?></h1>
<section class="hero-slider" data-hero-slider aria-roledescription="carousel" aria-label="<?= Yii::t('app', 'Carousel') ?>">
    <div class="hero-slides">
        <?php foreach ($slides ?: [null] as $index => $slide): ?>
            <?php
            $showContent = $slide === null || (int) $slide->show_content === 1;
            $slideTitle = $slide ? $slide->getLocalized('title') : Yii::t('app', 'Digital products built for confident growth');
            $slideText = $slide ? $slide->getLocalized('text') : Yii::t('app', 'From product strategy to secure delivery, we turn complex ideas into clear digital experiences.');
            $slideEyebrow = $slide ? $slide->getLocalized('eyebrow') : Yii::t('app', 'Strategy, design and engineering');
            ?>
            <article class="hero hero-slide<?= $index === 0 ? ' is-active' : '' ?><?= !$showContent ? ' hero-slide-image-only' : '' ?>" data-hero-slide aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>">
                <?= Html::img(MediaUrl::image($slide ? $slide->image : null, 'img/portfolio/hero-studio.webp'), [
                    'alt' => $showContent ? '' : Html::encode($slideTitle),
                    'loading' => $index === 0 ? 'eager' : 'lazy',
                    'fetchpriority' => $index === 0 ? 'high' : 'auto',
                    'width' => 1536,
                    'height' => 1024,
                ]) ?>
                <?php if ($showContent): ?>
                    <div class="hero-content">
                        <?php if (trim((string) $slideEyebrow) !== ''): ?><p class="text-overline"><?= Html::encode($slideEyebrow) ?></p><?php endif; ?>
                        <?php if (trim((string) $slideTitle) !== ''): ?><h2><?= Html::encode($slideTitle) ?></h2><?php endif; ?>
                        <?php if (trim((string) $slideText) !== ''): ?><?= HtmlPurifier::process($slideText) ?><?php endif; ?>
                        <?php
                        $primaryLabel = $slide ? $slide->getLocalized('primary_button_label') : '';
                        $secondaryLabel = $slide ? $slide->getLocalized('secondary_button_label') : '';
                        $hasPrimary = $slide && trim((string) $primaryLabel) !== '' && preg_match('~^(?:https?://|/|#|mailto:).+~i', (string) $slide->link);
                        $hasSecondary = $slide && trim((string) $secondaryLabel) !== '' && preg_match('~^(?:https?://|/|#|mailto:).+~i', (string) $slide->secondary_link);
                        ?>
                        <div class="hero-actions">
                            <?= $hasPrimary
                                ? Html::a(Html::encode($primaryLabel), $slide->link, ['class' => 'd-btn d-btn-primary'])
                                : Html::a(Yii::t('app', 'Start a project'), ['/site/order'], ['class' => 'd-btn d-btn-primary']) ?>
                            <?= $hasSecondary
                                ? Html::a(Html::encode($secondaryLabel), $slide->secondary_link, ['class' => 'd-btn d-btn-outline hero-secondary-action'])
                                : Html::a(Yii::t('app', 'View selected work'), ['/site/sample'], ['class' => 'd-btn d-btn-outline hero-secondary-action']) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (count($slides) > 1): ?><div class="hero-slider-controls"><button class="d-btn d-btn-circle d-btn-ghost" type="button" data-hero-previous aria-label="<?= Yii::t('app', 'Previous slide') ?>"><?= \frontend\widgets\Icon::show('chevron-left') ?></button><div class="hero-slider-dots"><?php foreach ($slides as $index => $slide): ?><button type="button" data-hero-dot="<?= $index ?>" aria-label="<?= Yii::t('app', 'Go to slide {number}', ['number' => $index + 1]) ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>"></button><?php endforeach; ?></div><button class="d-btn d-btn-circle d-btn-ghost" type="button" data-hero-next aria-label="<?= Yii::t('app', 'Next slide') ?>"><?= \frontend\widgets\Icon::show('chevron-right') ?></button></div><?php endif; ?>
</section>

<ul class="home-trust-strip" aria-label="<?= Yii::t('app', 'How we work') ?>">
    <li><?= Icon::show('check') ?><span><strong><?= Yii::t('app', 'Clear scope') ?></strong><small><?= Yii::t('app', 'Shared goals before delivery starts') ?></small></span></li>
    <li><?= Icon::show('activity') ?><span><strong><?= Yii::t('app', 'Measurable outcomes') ?></strong><small><?= Yii::t('app', 'Decisions tied to product signals') ?></small></span></li>
    <li><?= Icon::show('settings') ?><span><strong><?= Yii::t('app', 'Security by design') ?></strong><small><?= Yii::t('app', 'Risk considered throughout delivery') ?></small></span></li>
</ul>

<?php foreach ($sections as $section): ?>
<section class="section home-section home-section-<?= Html::encode($section->type) ?>" aria-labelledby="home-section-<?= (int)$section->id ?>">
    <div class="section-heading"><p class="text-overline"><?= Html::encode($section->getLocalized('subtitle')) ?></p><h2 id="home-section-<?= (int)$section->id ?>"><?= Html::encode($section->getLocalized('title')) ?></h2></div>
    <?php if ($section->getLocalized('content')): ?><div class="home-section-intro prose"><?= HtmlPurifier::process($section->getLocalized('content')) ?></div><?php endif; ?>
    <?php if ($section->type === 'features'): ?><div class="card-grid home-feature-grid"><article class="card"><span class="card-icon">01</span><h3><?= Yii::t('app','Product strategy') ?></h3><p><?= Yii::t('app', 'Align user needs, business value and a focused delivery roadmap.') ?></p></article><article class="card"><span class="card-icon">02</span><h3><?= Yii::t('app','Web development') ?></h3><p><?= Yii::t('app', 'Build fast, accessible experiences on a maintainable foundation.') ?></p></article><article class="card"><span class="card-icon">03</span><h3><?= Yii::t('app','Ongoing support') ?></h3><p><?= Yii::t('app', 'Learn from real usage and improve the product after launch.') ?></p></article></div>
    <?php elseif ($section->type === 'stats'): ?><div class="home-stats"><div><strong><?= Yii::$app->formatter->asInteger($stats['projects']) ?></strong><span><?= Yii::t('app','Completed projects') ?></span></div><div><strong><?= Yii::$app->formatter->asInteger($stats['articles']) ?></strong><span><?= Yii::t('app','Published articles') ?></span></div><div><strong><?= Yii::$app->formatter->asInteger($stats['answers']) ?></strong><span><?= Yii::t('app','Helpful answers') ?></span></div></div>
    <?php elseif ($section->type === 'portfolio'): ?><div class="card-grid"><?php foreach ($portfolioItems as $item): ?><article class="card content-card home-portfolio-card"><div class="home-portfolio-image"><?= Html::img(MediaUrl::image($item->image, 'img/portfolio/commerce-experience.webp'), ['alt' => Html::encode($item->getLocalized('title')), 'loading' => 'lazy', 'width' => 768, 'height' => 512]) ?></div><div class="home-portfolio-body"><h3><?= Html::encode($item->getLocalized('title')) ?></h3><div class="content-card-summary"><?= HtmlPurifier::process($item->getLocalized('content')) ?></div></div></article><?php endforeach; ?></div><p class="section-action"><?= Html::a(Yii::t('app','View all'),['/site/sample'],['class'=>'d-btn d-btn-outline']) ?></p>
    <?php elseif ($section->type === 'posts'): ?><div class="card-grid"><?php foreach ($posts as $post): ?><article class="card content-card"><h3><?= Html::a(Html::encode($post->getLocalized('title')), ['/blog/view', 'id' => $post->id]) ?></h3><p class="content-card-summary"><?= Html::encode($post->getLocalized('description')) ?></p></article><?php endforeach; ?></div><p class="section-action"><?= Html::a(Yii::t('app','View all'),['/blog/index'],['class'=>'d-btn d-btn-outline']) ?></p>
    <?php elseif ($section->type === 'faqs'): ?><div class="faq-list"><?php foreach ($faqs as $faq): ?><details class="card faq-item"><summary><span><?= Html::encode($faq->getLocalized('question')) ?></span><span class="faq-toggle" aria-hidden="true"><?= Icon::show('chevron-down') ?></span></summary><div class="faq-answer"><?= HtmlPurifier::process($faq->getLocalized('answer')) ?></div></details><?php endforeach; ?></div>
    <?php elseif ($section->type === 'cta'): ?><div class="home-cta-actions"><?= Html::a(Yii::t('app','Contact'),['/site/contact'],['class'=>'d-btn d-btn-primary']) ?><?= Html::a(Yii::t('app','Order app'),['/site/order'],['class'=>'d-btn d-btn-outline']) ?></div><?php endif; ?>
</section>
<?php endforeach; ?>

<?php if ($homeContent !== ''): ?>
    <section class="card prose" aria-label="<?= Yii::t('app', 'About') ?>">
        <?= HtmlPurifier::process($homeContent) ?>
    </section>
<?php endif; ?>
