<?php

use frontend\widgets\Icon;
use yii\helpers\Html;

$this->title = Yii::t('app', 'Dashboard');
$cards = [
    ['posts', 'posts', Yii::t('app', 'Blog'), ['/admin/blog/index']],
    ['briefcase', 'samples', Yii::t('app', 'Sample Project'), ['/admin/sample/index']],
    ['inbox', 'contacts', Yii::t('app', 'Contact'), ['/admin/contact/index']],
    ['posts', 'orders', Yii::t('app', 'Order app'), ['/admin/order/index']],
    ['users', 'opportunities', Yii::t('app', 'Job opportunity'), ['/admin/opportunity/index']],
    ['pages', 'pages', Yii::t('app', 'Dynamic pages'), ['/admin/page/index']],
    ['image', 'media', Yii::t('app', 'Media library'), ['/admin/media/index']],
    ['help', 'faqs', Yii::t('app', 'FAQ management'), ['/admin/faqs/index']],
    ['users', 'users', Yii::t('app', 'Users'), ['/admin/user/index']],
];
$quickActionCatalog = [
    'create_post' => ['plus', Yii::t('app', 'Create Post'), ['/admin/blog/create'], Yii::$app->user->can('manageContent')],
    'create_sample' => ['plus', Yii::t('app', 'Create Sample'), ['/admin/sample/create'], Yii::$app->user->can('manageContent')],
    'pages' => ['pages', Yii::t('app', 'Dynamic pages'), ['/admin/page/index'], Yii::$app->user->can('managePages')],
    'media' => ['image', Yii::t('app', 'Media library'), ['/admin/media/index'], Yii::$app->user->can('manageMedia')],
    'menus' => ['menu', Yii::t('app', 'Menu management'), ['/admin/menu/index'], Yii::$app->user->can('manageMenus')],
    'faqs' => ['help', Yii::t('app', 'FAQ management'), ['/admin/faqs/index'], Yii::$app->user->can('manageContent')],
    'users' => ['users', Yii::t('app', 'User Management'), ['/admin/user/index'], Yii::$app->user->can('manageUsers')],
    'settings' => ['settings', Yii::t('app', 'General settings'), ['/admin/setting/index'], Yii::$app->user->can('manageSettings')],
    'system' => ['activity', Yii::t('app', 'System'), ['/admin/setting/system'], Yii::$app->user->can('manageSystem')],
];
$quickActionCatalog = array_filter($quickActionCatalog, static fn ($item) => $item[3]);
$selectedQuickLinks = $dashboardLayout['quick_links'] ?? [];
?>
<div class="page-header page-header-actions">
    <div><p class="text-overline"><?= Yii::t('app', 'Admin panel') ?></p><h1><?= Html::encode($this->title) ?></h1></div>
</div>
<section class="card dashboard-customizer"><div><strong><?= Yii::t('app','Customize dashboard') ?></strong><p class="text-muted"><?= Yii::t('app','Drag widgets to reorder them, or show and hide each widget.') ?></p></div><button type="button" class="btn btn-secondary" data-dashboard-customize><?= Yii::t('app','Customize') ?></button><div class="dashboard-widget-picker" data-dashboard-picker hidden></div></section>
<div class="dashboard-widgets" data-dashboard-widgets data-layout='<?= Html::encode(json_encode($dashboardLayout, JSON_UNESCAPED_SLASHES)) ?>' data-save-url="<?= \yii\helpers\Url::to(['/admin/dashboard/layout']) ?>" data-csrf-param="<?= Html::encode(Yii::$app->request->csrfParam) ?>" data-csrf-token="<?= Html::encode(Yii::$app->request->csrfToken) ?>" data-collapse-label="<?= Yii::t('app', 'Minimize widget') ?>" data-expand-label="<?= Yii::t('app', 'Expand widget') ?>">
<div class="dashboard-widget" data-widget="metrics" data-title="<?= Yii::t('app','Site statistics') ?>" draggable="true">
<div class="metric-grid">
    <?php foreach ($cards as [$icon, $key, $label, $url]): ?>
        <?php if ($counts[$key] === null) {
            continue;
        } ?>
        <?= Html::a(
            Icon::show($icon, ['class' => 'icon metric-icon'])
            . Html::tag('strong', (string) $counts[$key], ['class' => 'metric-value'])
            . Html::tag('span', $label, ['class' => 'metric-label']),
            $url,
            ['class' => 'metric-card']
        ) ?>
    <?php endforeach; ?>
</div>
</div>
<?php if ($analytics !== null): ?>
    <div class="dashboard-widget" data-widget="analytics" data-title="<?= Yii::t('app','Visitor analytics') ?>" draggable="true">
    <?php
    $maxDailyViews = max(1, ...array_map(static fn ($row) => (int) $row['page_views'], $analytics['daily'] ?: [['page_views' => 0]]));
    $maxCountryViews = max(1, ...array_map(static fn ($row) => (int) $row['page_views'], $analytics['countries'] ?: [['page_views' => 0]]));
    $countryNames = [
        'IR' => Yii::t('app', 'Iran'), 'US' => Yii::t('app', 'United States'),
        'DE' => Yii::t('app', 'Germany'), 'CA' => Yii::t('app', 'Canada'),
        'GB' => Yii::t('app', 'United Kingdom'), 'TR' => Yii::t('app', 'Turkey'),
        'AE' => Yii::t('app', 'United Arab Emirates'), 'ZZ' => Yii::t('app', 'Unknown country'),
    ];
    ?>
    <section class="analytics-panel">
        <div class="dashboard-section-title analytics-heading">
            <div><p class="text-overline"><?= Yii::t('app', 'Audience overview') ?></p><h2><?= Yii::t('app', 'Visitor analytics') ?></h2></div>
            <span class="status-pill"><?= Yii::t('app', 'Last {days} days', ['days' => $analytics['days']]) ?></span>
        </div>
        <div class="analytics-summary">
            <?php foreach ([
                ['users', 'visitors', Yii::t('app', 'Unique visitors')],
                ['activity', 'page_views', Yii::t('app', 'Page views')],
                ['inbox', 'inquiries', Yii::t('app', 'New inquiries')],
            ] as [$icon, $key, $label]): ?>
                <?php $trend = $analytics['trend'][$key]; ?>
                <article class="card analytics-kpi">
                    <span class="analytics-kpi-icon"><?= Icon::show($icon) ?></span>
                    <div><span><?= Html::encode($label) ?></span><strong><?= Yii::$app->formatter->asInteger($analytics['totals'][$key]) ?></strong></div>
                    <small class="trend <?= $trend < 0 ? 'is-down' : 'is-up' ?>"><?= $trend >= 0 ? '↑' : '↓' ?> <?= Html::encode(abs($trend)) ?>%</small>
                </article>
            <?php endforeach; ?>
            <article class="card analytics-kpi">
                <span class="analytics-kpi-icon"><?= Icon::show('pages') ?></span>
                <div><span><?= Yii::t('app', 'Pages per visitor') ?></span><strong><?= $analytics['totals']['visitors'] ? Yii::$app->formatter->asDecimal($analytics['totals']['page_views'] / $analytics['totals']['visitors'], 1) : '0' ?></strong></div>
            </article>
            <article class="card analytics-kpi analytics-kpi-focus">
                <span class="analytics-kpi-icon"><?= Icon::show('check') ?></span>
                <div><span><?= Yii::t('app', 'Visitor-to-inquiry rate') ?></span><strong><?= Yii::$app->formatter->asDecimal($analytics['totals']['inquiry_rate'], 1) ?>%</strong></div>
            </article>
        </div>
        <div class="analytics-chart card" aria-label="<?= Yii::t('app', 'Daily page views') ?>">
            <div class="dashboard-section-title"><h3><?= Yii::t('app', 'Traffic trend') ?></h3><span class="text-muted"><?= Yii::t('app', 'Daily page views') ?></span></div>
            <?php if ($analytics['daily']): ?><div class="bar-chart"><?php foreach ($analytics['daily'] as $day): ?><div class="bar-column" title="<?= Html::encode($day['visit_date'] . ': ' . $day['page_views']) ?>"><span class="bar-value"><?= (int) $day['page_views'] ?></span><span class="bar" style="height: <?= max(4, ((int) $day['page_views'] / $maxDailyViews) * 100) ?>%"></span><small><?= Yii::$app->formatter->asDate($day['visit_date'], 'php:m/d') ?></small></div><?php endforeach; ?></div><?php else: ?><p class="empty-state"><?= Yii::t('app', 'No visitor data has been recorded yet.') ?></p><?php endif; ?>
        </div>
        <div class="dashboard-detail-grid">
            <section class="card">
                <div class="dashboard-section-title"><h3><?= Yii::t('app', 'Top countries') ?></h3><span class="text-muted"><?= Yii::t('app', 'Page views') ?></span></div>
                <?php if ($analytics['countries']): ?><ul class="rank-list"><?php foreach ($analytics['countries'] as $country): ?><li><div><strong><?= Html::encode($countryNames[$country['country_code']] ?? $country['country_code']) ?></strong><span class="rank-track"><i style="width: <?= ((int) $country['page_views'] / $maxCountryViews) * 100 ?>%"></i></span></div><b><?= Yii::$app->formatter->asInteger($country['page_views']) ?></b></li><?php endforeach; ?></ul><?php else: ?><p class="empty-state"><?= Yii::t('app', 'No visitor data has been recorded yet.') ?></p><?php endif; ?>
            </section>
            <section class="card">
                <div class="dashboard-section-title"><h3><?= Yii::t('app', 'Most visited pages') ?></h3><span class="text-muted"><?= Yii::t('app', 'Unique visitors') ?></span></div>
                <?php if ($analytics['pages']): ?><ul class="activity-list"><?php foreach ($analytics['pages'] as $page): ?><li><div><strong class="ltr"><?= Html::encode($page['path']) ?></strong><small><?= Yii::t('app', '{count} page views', ['count' => Yii::$app->formatter->asInteger($page['page_views'])]) ?></small></div><b><?= Yii::$app->formatter->asInteger($page['visitors']) ?></b></li><?php endforeach; ?></ul><?php else: ?><p class="empty-state"><?= Yii::t('app', 'No visitor data has been recorded yet.') ?></p><?php endif; ?>
            </section>
        </div>
    </section>
    </div>
<?php endif; ?>
<div class="dashboard-widget" data-widget="quick_actions" data-title="<?= Yii::t('app','Quick actions') ?>" draggable="true">
<section class="card section-shortcut">
    <div><h2><?= Yii::t('app', 'Quick actions') ?></h2><p class="text-muted"><?= Yii::t('app', 'Common management tasks are available here.') ?></p></div>
    <div class="action-row">
        <?php foreach ($quickActionCatalog as $quickLink => [$icon, $label, $url]): ?><?php $isSelected = in_array($quickLink, $selectedQuickLinks, true); ?><?= Html::a(Icon::show($icon) . $label, $url, ['class' => 'btn btn-secondary' . ($isSelected ? '' : ' is-hidden'), 'data-quick-action' => $quickLink, 'hidden' => !$isSelected]) ?><?php endforeach; ?>
    </div>
    <details class="quick-link-customizer"><summary class="btn btn-secondary"><?= Icon::show('settings') . Yii::t('app', 'Customize') ?></summary><div class="quick-link-picker"><?php foreach ($quickActionCatalog as $key => [$icon, $label]): ?><label><input type="checkbox" class="d-toggle d-toggle-sm" data-quick-link="<?= Html::encode($key) ?>" <?= in_array($key, $selectedQuickLinks, true) ? 'checked' : '' ?>><?= Icon::show($icon) ?><span><?= Html::encode($label) ?></span></label><?php endforeach; ?></div></details>
</section>
</div>
<div class="dashboard-widget" data-widget="recent_activity" data-title="<?= Yii::t('app','Recent activity') ?>" draggable="true">
<div class="dashboard-detail-grid">
    <section class="card">
        <div class="dashboard-section-title"><h2><?= Yii::t('app', 'Latest posts') ?></h2><?= Html::a(Yii::t('app', 'View all'), ['/admin/blog/index']) ?></div>
        <?php if ($latestPosts): ?><ul class="activity-list"><?php foreach ($latestPosts as $post): ?><li><div><strong><?= Html::encode($post->getLocalized('title')) ?></strong><small><?= Html::encode($post->category ? $post->category->getLocalized('title') : '') ?></small></div><time><?= Yii::$app->formatter->asDatetime($post->created_at) ?></time></li><?php endforeach; ?></ul><?php else: ?><p class="empty-state"><?= Yii::t('app', 'No posts have been created yet.') ?></p><?php endif; ?>
    </section>
    <?php if (Yii::$app->user->can('viewSubmissions')): ?><section class="card">
        <div class="dashboard-section-title"><h2><?= Yii::t('app', 'Latest contact messages') ?></h2><?= Html::a(Yii::t('app', 'View all'), ['/admin/contact/index']) ?></div>
        <?php if ($latestContacts): ?><ul class="activity-list"><?php foreach ($latestContacts as $contact): ?><li><div><strong><?= Html::encode($contact->subject ?: $contact->name) ?></strong><small><?= Html::encode($contact->email) ?></small></div><time><?= Yii::$app->formatter->asDatetime($contact->created_at) ?></time></li><?php endforeach; ?></ul><?php else: ?><p class="empty-state"><?= Yii::t('app', 'There are no contact messages.') ?></p><?php endif; ?>
    </section><?php endif; ?>
</div>
</div>
<div class="dashboard-widget" data-widget="system_status" data-title="<?= Yii::t('app','System status') ?>" draggable="true">
<section class="card system-overview">
    <div><span><?= Yii::t('app', 'Environment') ?></span><strong><?= Html::encode(strtoupper($systemStatus['environment'])) ?></strong></div>
    <div><span><?= Yii::t('app', 'Maintenance mode') ?></span><strong class="status-pill <?= $systemStatus['maintenance'] ? 'is-warning' : 'is-success' ?>"><?= $systemStatus['maintenance'] ? Yii::t('app', 'Enabled') : Yii::t('app', 'Disabled') ?></strong></div>
    <div><span><?= Yii::t('app', 'Active calendar') ?></span><strong><?= $systemStatus['calendar'] === 'jalali' ? Yii::t('app', 'Jalali calendar') : Yii::t('app', 'Gregorian calendar') ?></strong></div>
    <div><span><?= Yii::t('app', 'Current time') ?></span><strong><?= Yii::$app->formatter->asDatetime(time()) ?></strong></div>
</section>
</div>
</div>
