<?php

use frontend\widgets\Icon;
use yii\helpers\Html;

$this->title = 'درباره سیستم';
$this->params['breadcrumbs'][] = $this->title;
$categoryLabels = [
    'افزوده‌شده' => ['label' => '✨ امکانات جدید', 'class' => 'is-new'],
    'اصلاح‌شده' => ['label' => '🐛 رفع مشکلات', 'class' => 'is-fix'],
    'تغییرکرده' => ['label' => '⚡ بهبودها', 'class' => 'is-improvement'],
    'امنیتی' => ['label' => '🔐 تغییرات امنیتی', 'class' => 'is-security'],
];
?>

<div class="about-system-page">
    <header class="page-header">
        <p class="text-overline">Kaman CMS</p>
        <h1><?= Html::encode($this->title) ?></h1>
        <p><?= Html::encode('معرفی سامانه، نسخه فعلی و تازه‌ترین تغییرات Kaman CMS') ?></p>
    </header>

    <section class="about-system-hero card" aria-labelledby="about-system-summary">
        <div class="about-system-brand">
            <span class="about-system-mark" aria-hidden="true">K</span>
            <div>
                <p class="text-overline">Content management system</p>
                <h2 id="about-system-summary"><?= Html::encode($systemInfo['name']) ?></h2>
                <p class="text-muted">یک CMS چندزبانه و قابل توسعه برای مدیریت محتوای حرفه‌ای</p>
            </div>
        </div>
        <dl class="about-system-meta">
            <div><dt>نسخه فعلی</dt><dd class="ltr"><?= Html::encode($systemInfo['version']) ?></dd></div>
            <div><dt>وضعیت نسخه</dt><dd><span class="status-pill is-success"><?= Html::encode($systemInfo['status']) ?></span></dd></div>
            <div><dt>آخرین انتشار</dt><dd class="ltr"><?= Html::encode(Yii::$app->formatter->asDate($systemInfo['releaseDate'])) ?></dd></div>
        </dl>
    </section>

    <div class="about-system-grid">
        <section class="card about-system-section" aria-labelledby="about-kaman-title">
            <div class="about-system-section-heading"><span class="card-icon"><?= Icon::show('info') ?></span><div><p class="text-overline">About the project</p><h2 id="about-kaman-title">درباره Kaman CMS</h2></div></div>
            <p>Kaman CMS یک سیستم مدیریت محتوای چندزبانه است که با هدف فراهم‌کردن بستری امن، سریع و قابل نگهداری برای انتشار و مدیریت محتوای وب توسعه داده شده است.</p>
            <p class="text-muted">هسته سیستم بر پایه PHP و Yii Framework ساخته شده و از معماری MVC، پایگاه داده رابطه‌ای، رابط کاربری واکنش‌گرا و پشتیبانی RTL/LTR استفاده می‌کند.</p>
        </section>

        <section class="card about-system-section" aria-labelledby="technical-title">
            <div class="about-system-section-heading"><span class="card-icon"><?= Icon::show('settings') ?></span><div><p class="text-overline">Runtime details</p><h2 id="technical-title">مشخصات فنی</h2></div></div>
            <dl class="technical-list">
                <?php foreach ($technicalInfo as $label => $value) : ?>
                    <div><dt><?= Html::encode($label) ?></dt><dd class="ltr"><?= Html::encode($value) ?></dd></div>
                <?php endforeach; ?>
            </dl>
        </section>
    </div>

    <section class="card about-system-section release-notes" aria-labelledby="release-notes-title">
        <div class="about-system-section-heading"><span class="card-icon"><?= Icon::show('activity') ?></span><div><p class="text-overline">Release notes</p><h2 id="release-notes-title">آخرین تغییرات</h2></div></div>
        <div class="release-notes-grid">
            <?php foreach ($categoryLabels as $source => $category) : ?>
                <article class="release-note-group <?= Html::encode($category['class']) ?>">
                    <h3><?= Html::encode($category['label']) ?></h3>
                    <?php if (!empty($releaseNotes[$source])) : ?>
                        <ul>
                            <?php foreach ($releaseNotes[$source] as $note) : ?>
                                <li><?= Html::encode($note) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="text-muted">موردی برای این دسته ثبت نشده است.</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card about-system-section" aria-labelledby="useful-links-title">
        <div class="about-system-section-heading"><span class="card-icon"><?= Icon::show('external') ?></span><div><p class="text-overline">Resources</p><h2 id="useful-links-title">لینک‌های مفید</h2></div></div>
        <div class="about-system-links">
            <?php foreach (['github' => 'GitHub', 'documentation' => 'مستندات', 'changelog' => 'Changelog', 'issues' => 'Issue Tracker'] as $key => $label) : ?>
                <?= Html::a(Icon::show('external') . Html::tag('span', Html::encode($label)), $systemInfo['links'][$key], ['target' => '_blank', 'rel' => 'noopener noreferrer', 'class' => 'about-system-link']) ?>
            <?php endforeach; ?>
        </div>
    </section>
</div>