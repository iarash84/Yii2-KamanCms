<?php

use common\widgets\Alert;
use frontend\assets\AppAsset;
use frontend\models\Setting;
use frontend\models\MenuItem;
use frontend\models\Contact;
use frontend\models\Order;
use frontend\models\Opportunity;
use frontend\widgets\Icon;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;

AppAsset::register($this);
$languageManager = Yii::$app->languageManager;
$isRtl = $languageManager->isRtl();
$settingTypes = ['CompanyName', 'Address', 'Email', 'PhoneNumber', 'Facebook', 'Twitter', 'Linkedin', 'Instagram', 'Youtube', 'Telegram', 'Aparat'];
$settingRows = Setting::find()->with('translations')->where(['type' => $settingTypes])->indexBy('type')->all();
$settingValue = static function ($type) use ($settingRows) {
    return isset($settingRows[$type]) ? $settingRows[$type]->getLocalizedContent() : '';
};
$socialLinks = [];
foreach (['Facebook', 'Twitter', 'Linkedin', 'Instagram', 'Youtube', 'Telegram', 'Aparat'] as $network) {
    $url = trim((string) $settingValue($network));
    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)
        && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
        $socialLinks[$network] = $url;
    }
}
$siteName = trim((string) $settingValue('CompanyName')) ?: Yii::t('app', 'Website');
$brandInitial = mb_strtoupper(mb_substr($siteName, 0, 1));
$route = Yii::$app->controller->route;
$isAdmin = Yii::$app->controller->module !== null
    && Yii::$app->controller->module->id === 'admin';
$current = static function ($routePrefix) use ($route) {
    return strpos($route, $routePrefix) === 0 ? 'page' : null;
};
$groupCurrent = static function (array $routePrefixes) use ($route) {
    foreach ($routePrefixes as $routePrefix) {
        if (strpos($route, $routePrefix) === 0) {
            return true;
        }
    }
    return false;
};
$mainMenu = $isAdmin ? [] : MenuItem::activeRoots('main');
$footerMenu = $isAdmin ? [] : MenuItem::activeRoots('footer');
$submissionNotifications = [];
$canViewSubmissions = !Yii::$app->user->isGuest && Yii::$app->user->can('viewSubmissions');
if ($canViewSubmissions) {
    $notificationSources = [
        ['model' => Contact::class, 'label' => Yii::t('app', 'Contact'), 'url' => ['/admin/contact/index']],
        ['model' => Order::class, 'label' => Yii::t('app', 'Service requests'), 'url' => ['/admin/order/index']],
        ['model' => Opportunity::class, 'label' => Yii::t('app', 'Job opportunity'), 'url' => ['/admin/opportunity/index']],
    ];
    foreach ($notificationSources as $source) {
        foreach ($source['model']::find()->where(['read_at' => null])->orderBy(['created_at' => SORT_DESC])->limit(5)->all() as $model) {
            $title = $model instanceof Contact
                ? (trim((string) $model->subject) ?: trim((string) $model->name))
                : trim((string) $model->name);
            $summary = $model instanceof Contact
                ? trim((string) $model->body)
                : ($model instanceof Order ? trim((string) $model->description) : trim((string) $model->email));
            $createdAt = is_numeric($model->created_at) ? (int) $model->created_at : (strtotime((string) $model->created_at) ?: 0);
            $submissionNotifications[] = [
                'label' => $source['label'],
                'title' => $title ?: Yii::t('app', 'New notification'),
                'summary' => $summary,
                'created_at' => $createdAt,
                'url' => array_merge($source['url'], ['id' => $model->id]),
            ];
        }
    }
    usort($submissionNotifications, static fn ($left, $right) => $right['created_at'] <=> $left['created_at']);
    $submissionNotifications = array_slice($submissionNotifications, 0, 5);
}
$unreadSubmissionCount = count($submissionNotifications);
$identity = Yii::$app->user->isGuest ? null : Yii::$app->user->identity;
$identityName = $identity === null ? '' : (trim((string) $identity->full_name) ?: $identity->username);

$this->registerMetaTag(['name' => 'content-language', 'content' => $languageManager->getLocale()]);
foreach ($languageManager->languages as $code => $language) {
    $this->registerLinkTag([
        'rel' => 'alternate',
        'hreflang' => $language['locale'],
        'href' => $languageManager->getLanguageUrl($code),
    ]);
}
$this->registerLinkTag([
    'rel' => 'alternate',
    'hreflang' => 'x-default',
    'href' => $languageManager->getLanguageUrl($languageManager->defaultLanguage),
]);
$this->registerLinkTag([
    'rel' => 'canonical',
    'href' => $this->params['canonicalUrl'] ?? $languageManager->getLanguageUrl($languageManager->activeLanguage),
]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Html::encode($languageManager->getLocale()) ?>" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>" data-theme="site-light">
<head>
    <script>(function(){try{var k='color-theme',v=localStorage.getItem(k)||'system',a=['system','site-light','site-dark'],m={light:'site-light',dark:'site-dark'};v=m[v]||v;if(a.indexOf(v)<0)v='system';var d=v==='system'?(matchMedia('(prefers-color-scheme: dark)').matches?'site-dark':'site-light'):v;document.documentElement.dataset.theme=d;document.documentElement.dataset.themePreference=v;}catch(e){document.documentElement.dataset.theme='site-light';}}());</script>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4f5fd7">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="<?= $isAdmin ? 'admin-interface' : 'public-interface' ?>">
<?php $this->beginBody() ?>
<div class="site-shell">
    <a class="skip-link" href="#main-content"><?= Yii::t('app', 'Skip to main content') ?></a>
    <header class="site-header">
        <div class="container header-row">
            <?= Html::a(
                Html::tag('span', Html::encode($brandInitial), ['class' => 'brand-mark', 'aria-hidden' => 'true'])
                . Html::tag('span', Html::encode($siteName)),
                ['/site/index'],
                ['class' => 'brand', 'aria-label' => Yii::t('app', 'Home')]
            ) ?>
            <button class="nav-toggle d-btn d-btn-square d-btn-ghost" type="button" data-nav-toggle aria-expanded="false"
                    aria-controls="primary-navigation" aria-label="<?= Yii::t('app', 'Open menu') ?>"><?= Icon::show('menu') ?></button>
            <nav id="primary-navigation" class="primary-nav" data-primary-nav
                 aria-label="<?= Yii::t('app', 'Main navigation') ?>">
                <ul class="nav-list">
                    <?php if (!$isAdmin): ?>
                        <?php if ($mainMenu): ?>
                            <?php foreach ($mainMenu as $menuItem): ?>
                                <li class="<?= $menuItem->children ? 'nav-menu' : '' ?>">
                                    <?php if ($menuItem->children): ?>
                                        <details>
                                            <summary class="nav-summary"><?= Html::encode($menuItem->getLocalized('label')) ?></summary>
                                            <ul class="nav-submenu">
                                                <?php foreach ($menuItem->children as $child): ?>
                                                    <li><?= Html::a(Html::encode($child->getLocalized('label')), $child->getPublicUrl(), ['target' => $child->target, 'rel' => $child->target === '_blank' ? 'noopener noreferrer' : null]) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </details>
                                    <?php else: ?>
                                        <?= Html::a(Html::encode($menuItem->getLocalized('label')), $menuItem->getPublicUrl(), ['target' => $menuItem->target, 'rel' => $menuItem->target === '_blank' ? 'noopener noreferrer' : null]) ?>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><?= Html::a(Yii::t('app', 'Home'), ['/site/index'], ['aria-current' => $route === 'site/index' ? 'page' : null]) ?></li>
                            <li><?= Html::a(Yii::t('app', 'Blog'), ['/blog/index'], ['aria-current' => $current('blog/')]) ?></li>
                            <li><?= Html::a(Yii::t('app', 'Sample Project'), ['/site/sample'], ['aria-current' => $current('site/sample')]) ?></li>
                            <li><?= Html::a(Yii::t('app', 'About'), ['/site/about'], ['aria-current' => $current('site/about')]) ?></li>
                            <li><?= Html::a(Yii::t('app', 'Contact'), ['/site/contact'], ['aria-current' => $current('site/contact')]) ?></li>
                            <li><?= Html::a(Yii::t('app', 'Request a service'), ['/site/order'], ['class' => 'd-btn d-btn-primary d-btn-sm']) ?></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!$isAdmin): ?>
                        <li class="header-primary-action"><?= Html::a(Yii::t('app', 'Request a service'), ['/site/order'], ['class' => 'd-btn d-btn-primary d-btn-sm']) ?></li>
                        <li class="header-icon-action"><?= Html::a(Icon::show('search') . Html::tag('span', Yii::t('app', 'Search'), ['class' => 'sr-only']), ['/search/index'], ['aria-label' => Yii::t('app', 'Search')]) ?></li>
                    <?php endif; ?>
                    <?php if (!$isAdmin): ?><li><?= $this->render('_appearance', ['inSidebar' => false]) ?></li><?php endif; ?>
                    <?php if ($canViewSubmissions): ?><li class="notification-control"><details><summary class="d-btn d-btn-square d-btn-ghost" aria-label="<?= Yii::t('app', 'Notifications') ?>"><?= Icon::show('bell') ?><?php if ($unreadSubmissionCount): ?><span class="notification-badge"><?= (int) $unreadSubmissionCount ?></span><?php endif; ?></summary><div class="notification-menu"><?php if ($submissionNotifications): ?><div class="notification-menu-heading"><h2><?= Yii::t('app', 'Notifications') ?></h2><?= Html::a(Icon::show('chevron-right'), ['/admin/notification/index'], ['class' => 'd-btn d-btn-sm d-btn-square d-btn-ghost', 'title' => Yii::t('app', 'View all notifications'), 'aria-label' => Yii::t('app', 'View all notifications')]) ?></div><?php foreach ($submissionNotifications as $notification): ?><?= Html::a('<span class="notification-menu-item-content"><strong>' . Html::encode($notification['title']) . '</strong><small>' . Html::encode($notification['label']) . ' · ' . Html::encode(Yii::$app->formatter->asRelativeTime($notification['created_at'])) . '</small>' . ($notification['summary'] !== '' ? '<em>' . Html::encode(mb_strimwidth($notification['summary'], 0, 90, '...')) . '</em>' : '') . '</span>' . Icon::show('chevron-left'), $notification['url'], ['class' => 'notification-menu-item']) ?><?php endforeach; ?><?php endif; ?><?= Html::a(Yii::t('app', 'View all notifications'), ['/admin/notification/index'], ['class' => 'notification-menu-footer']) ?></div></details></li><?php endif; ?>
                    <?php if (!Yii::$app->user->isGuest): ?>
                        <li class="nav-menu">
                            <details>
                                <summary class="nav-summary user-account-summary">
                                    <span class="user-account-avatar" aria-hidden="true">
                                        <?php if (trim((string) $identity->avatar) !== ''): ?>
                                            <?= Html::img(Url::to('@web/' . ltrim($identity->avatar, '/')), ['alt' => '']) ?>
                                        <?php else: ?>
                                            <?= Html::encode(mb_strtoupper(mb_substr($identityName, 0, 1))) ?>
                                        <?php endif; ?>
                                    </span>
                                    <span><strong><?= Html::encode($identityName) ?></strong><small><?= Html::encode($identity->job_title ?: '@' . $identity->username) ?></small></span>
                                </summary>
                                <ul class="nav-submenu">
                                    <li><?= Html::a(Yii::t('app', 'Admin panel'), ['/admin']) ?></li>
                                    <li><?= Html::a(Yii::t('app', 'Edit profile'), ['/admin/user/profile']) ?></li>
                                    <li><?= Html::a(Yii::t('app', 'Change Password'), ['/admin/user/change']) ?></li>
                                    <li><?= Html::a(Yii::t('app', 'Logout'), ['/site/logout'], ['data-method' => 'post']) ?></li>
                                </ul>
                            </details>
                        </li>
                    <?php endif; ?>
                    <?php if (!$isAdmin): ?>
                        <li class="language-nav" aria-label="<?= Yii::t('app', 'Language') ?>">
                            <?php foreach ($languageManager->languages as $code => $language): ?>
                                <?= Html::a(Html::encode($language['label']), $languageManager->getLanguageUrl($code), [
                                    'lang' => $language['locale'],
                                    'hreflang' => $language['locale'],
                                    'class' => $code === $languageManager->activeLanguage ? 'active' : null,
                                    'aria-current' => $code === $languageManager->activeLanguage ? 'true' : null,
                                ]) ?>
                            <?php endforeach; ?>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main id="main-content" class="site-main" tabindex="-1">
        <div class="container <?= $isAdmin ? 'admin-shell' : 'content-shell' ?>">
            <?php if ($isAdmin): ?>
                <button class="d-btn d-btn-square d-btn-ghost admin-sidebar-toggle" type="button" data-admin-sidebar-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="<?= Yii::t('app', 'Open admin navigation') ?>"><?= Icon::show('menu') ?></button>
                <aside id="admin-sidebar" class="admin-sidebar card" data-admin-sidebar aria-label="<?= Yii::t('app', 'Admin panel') ?>">
                    <div class="admin-sidebar-header">
                        <span class="admin-sidebar-mark" aria-hidden="true"><?= Html::encode($brandInitial) ?></span>
                        <div>
                            <span class="admin-sidebar-context"><?= Html::encode($siteName) ?></span>
                            <h2><?= Yii::t('app', 'Admin panel') ?></h2>
                        </div>
                    </div>
                    <div class="admin-nav-search">
                        <label class="sr-only" for="admin-nav-search"><?= Yii::t('app', 'Search admin navigation') ?></label>
                        <input id="admin-nav-search" class="d-input d-input-bordered" type="search" data-admin-nav-search placeholder="<?= Yii::t('app', 'Search admin navigation') ?>" autocomplete="off">
                    </div>
                    <ul class="admin-nav">
                        <li><?= Html::a(Icon::show('dashboard') . Yii::t('app', 'Dashboard'), ['/admin'], ['aria-current' => $current('admin/dashboard')]) ?></li>
                        <?php if (Yii::$app->user->can('manageContent') || Yii::$app->user->can('manageMenus') || Yii::$app->user->can('managePages') || Yii::$app->user->can('manageMedia')): ?>
                            <li>
                                <details class="admin-nav-group" data-admin-nav-group <?= $groupCurrent(['admin/home-section', 'admin/carousel', 'admin/menu', 'admin/page', 'admin/media']) ? 'open' : '' ?>>
                                    <summary><?= Icon::show('pages') ?><span><?= Yii::t('app', 'Site structure') ?></span><?= Icon::show('chevron-down', ['class' => 'icon admin-nav-chevron']) ?></summary>
                                    <ul class="admin-nav-submenu">
                                        <?php if (Yii::$app->user->can('manageContent')): ?>
                                            <li><?= Html::a(Yii::t('app', 'Homepage sections'), ['/admin/home-section/index'], ['aria-current' => $current('admin/home-section')]) ?></li>
                                            <li><?= Html::a(Yii::t('app', 'Carousel'), ['/admin/carousel/index'], ['aria-current' => $current('admin/carousel')]) ?></li>
                                        <?php endif; ?>
                                        <?php if (Yii::$app->user->can('manageMenus')): ?>
                                            <li><?= Html::a(Yii::t('app', 'Menu management'), ['/admin/menu/index'], ['aria-current' => $current('admin/menu')]) ?></li>
                                        <?php endif; ?>
                                        <?php if (Yii::$app->user->can('managePages')): ?>
                                            <li><?= Html::a(Yii::t('app', 'Dynamic pages'), ['/admin/page/index'], ['aria-current' => $current('admin/page')]) ?></li>
                                        <?php endif; ?>
                                        <?php if (Yii::$app->user->can('manageMedia')): ?>
                                            <li><?= Html::a(Yii::t('app', 'Media library'), ['/admin/media/index'], ['aria-current' => $current('admin/media')]) ?></li>
                                        <?php endif; ?>
                                    </ul>
                                </details>
                            </li>
                        <?php endif; ?>
                        <?php if (Yii::$app->user->can('manageContent')): ?>
                            <li>
                                <details class="admin-nav-group" data-admin-nav-group <?= $groupCurrent(['admin/blog', 'admin/category', 'admin/sample', 'admin/faqs']) ? 'open' : '' ?>>
                                    <summary><?= Icon::show('posts') ?><span><?= Yii::t('app', 'Content') ?></span><?= Icon::show('chevron-down', ['class' => 'icon admin-nav-chevron']) ?></summary>
                                    <ul class="admin-nav-submenu">
                                        <li><?= Html::a(Yii::t('app', 'Blog'), ['/admin/blog/index'], ['aria-current' => $current('admin/blog')]) ?></li>
                                        <li><?= Html::a(Yii::t('app', 'Category'), ['/admin/category/index'], ['aria-current' => $current('admin/category')]) ?></li>
                                        <li><?= Html::a(Yii::t('app', 'Sample Project'), ['/admin/sample/index'], ['aria-current' => $current('admin/sample')]) ?></li>
                                        <li><?= Html::a(Yii::t('app', 'FAQ management'), ['/admin/faqs/index'], ['aria-current' => $current('admin/faqs')]) ?></li>
                                    </ul>
                                </details>
                            </li>
                        <?php endif; ?>
                        <?php if (Yii::$app->user->can('viewSubmissions')): ?>
                            <li>
                                <details class="admin-nav-group" data-admin-nav-group <?= $groupCurrent(['admin/contact', 'admin/order', 'admin/opportunity']) ? 'open' : '' ?>>
                                    <summary><?= Icon::show('inbox') ?><span><?= Yii::t('app', 'Requests') ?></span><?= Icon::show('chevron-down', ['class' => 'icon admin-nav-chevron']) ?></summary>
                                    <ul class="admin-nav-submenu">
                                        <li><?= Html::a(Yii::t('app', 'Contact'), ['/admin/contact/index'], ['aria-current' => $current('admin/contact')]) ?></li>
                                        <li><?= Html::a(Yii::t('app', 'Service requests'), ['/admin/order/index'], ['aria-current' => $current('admin/order')]) ?></li>
                                        <li><?= Html::a(Yii::t('app', 'Job opportunity'), ['/admin/opportunity/index'], ['aria-current' => $current('admin/opportunity')]) ?></li>
                                    </ul>
                                </details>
                            </li>
                        <?php endif; ?>
                        <?php if (Yii::$app->user->can('manageSettings') || Yii::$app->user->can('manageSystem')): ?>
                            <li>
                                <details class="admin-nav-group" data-admin-nav-group <?= $groupCurrent(['admin/setting']) ? 'open' : '' ?>>
                                    <summary><?= Icon::show('settings') ?><span><?= Yii::t('app', 'Site settings') ?></span><?= Icon::show('chevron-down', ['class' => 'icon admin-nav-chevron']) ?></summary>
                                    <ul class="admin-nav-submenu">
                                        <?php if (Yii::$app->user->can('manageSettings')): ?>
                                            <li><?= Html::a(Yii::t('app', 'General settings'), ['/admin/setting/index'], ['aria-current' => $route === 'admin/setting/index' ? 'page' : null]) ?></li>
                                            <li><?= Html::a(Yii::t('app', 'About'), ['/admin/setting/about'], ['aria-current' => $route === 'admin/setting/about' ? 'page' : null]) ?></li>
                                            <li><?= Html::a(Yii::t('app', 'Social Network'), ['/admin/setting/social'], ['aria-current' => $route === 'admin/setting/social' ? 'page' : null]) ?></li>
                                        <?php endif; ?>
                                        <?php if (Yii::$app->user->can('manageSystem')): ?>
                                            <li><?= Html::a(Yii::t('app', 'System'), ['/admin/setting/system'], ['aria-current' => $route === 'admin/setting/system' ? 'page' : null]) ?></li>
                                            <li><?= Html::a(Yii::t('app', 'Email settings'), ['/admin/setting/email'], ['aria-current' => $route === 'admin/setting/email' ? 'page' : null]) ?></li>
                                        <?php endif; ?>
                                    </ul>
                                </details>
                            </li>
                        <?php endif; ?>
                        <?php if (Yii::$app->user->can('viewAudit') || Yii::$app->user->can('exportData') || Yii::$app->user->can('manageBackup') || Yii::$app->user->can('manageUsers')): ?>
                            <li>
                                <details class="admin-nav-group" data-admin-nav-group <?= $groupCurrent(['admin/audit', 'admin/export', 'admin/backup', 'admin/user']) ? 'open' : '' ?>>
                                    <summary><?= Icon::show('users') ?><span><?= Yii::t('app', 'Administration') ?></span><?= Icon::show('chevron-down', ['class' => 'icon admin-nav-chevron']) ?></summary>
                                    <ul class="admin-nav-submenu">
                                        <?php if (Yii::$app->user->can('viewAudit')): ?><li><?= Html::a(Yii::t('app', 'Admin activity'), ['/admin/audit/index'], ['aria-current' => $current('admin/audit')]) ?></li><?php endif; ?>
                                        <?php if (Yii::$app->user->can('exportData')): ?><li><?= Html::a(Yii::t('app', 'Data export'), ['/admin/export/index'], ['aria-current' => $current('admin/export')]) ?></li><?php endif; ?>
                                        <?php if (Yii::$app->user->can('manageBackup')): ?><li><?= Html::a(Yii::t('app', 'Backup and restore'), ['/admin/backup/index'], ['aria-current' => $current('admin/backup')]) ?></li><?php endif; ?>
                                        <?php if (Yii::$app->user->can('manageUsers')): ?><li><?= Html::a(Yii::t('app', 'User Management'), ['/admin/user/index'], ['aria-current' => $current('admin/user')]) ?></li><?php endif; ?>
                                    </ul>
                                </details>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <p class="admin-nav-empty" data-admin-nav-empty hidden><?= Yii::t('app', 'No matching admin sections') ?></p>
                    <?= $this->render('_appearance', ['inSidebar' => true]) ?>
                </aside>
                <section class="admin-content">
            <?php else: ?>
                <section>
            <?php endif; ?>
                    <?= Breadcrumbs::widget([
                        'links' => $this->params['breadcrumbs'] ?? [],
                        'options' => ['class' => 'breadcrumbs', 'aria-label' => Yii::t('app', 'Breadcrumb')],
                        'homeLink' => ['label' => Yii::t('app', 'Home'), 'url' => ['/site/index']],
                    ]) ?>
                    <?= Alert::widget() ?>
                    <?= $content ?>
                </section>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <section>
                    <h2><?= Html::encode($siteName) ?></h2>
                    <p><?= Html::encode($settingValue('Address')) ?></p>
                    <p class="ltr"><?= Html::encode($settingValue('Email')) ?><br><?= Html::encode($settingValue('PhoneNumber')) ?></p>
                    <?php if ($socialLinks): ?><ul class="social-links" aria-label="<?= Yii::t('app', 'Social Network') ?>"><?php foreach ($socialLinks as $network => $url): ?><li><?= Html::a(Icon::show(strtolower($network)) . Html::tag('span', Html::encode(Yii::t('app', $network)), ['class' => 'sr-only']), $url, ['class' => 'social-link', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'aria-label' => Yii::t('app', $network)]) ?></li><?php endforeach; ?></ul><?php endif; ?>
                </section>
                <nav aria-label="<?= Yii::t('app', 'Useful links') ?>">
                    <h2><?= Yii::t('app', 'Useful links') ?></h2>
                    <ul class="footer-links">
                        <?php if ($footerMenu): ?>
                            <?php foreach ($footerMenu as $menuItem): ?>
                                <li><?= Html::a(Html::encode($menuItem->getLocalized('label')), $menuItem->getPublicUrl(), ['target' => $menuItem->target, 'rel' => $menuItem->target === '_blank' ? 'noopener noreferrer' : null]) ?></li>
                            <?php endforeach; ?>
                            <?php if (!array_filter($footerMenu, static function ($item) {
                                return trim((string) $item->url, '/') === 'opportunity';
                            })): ?>
                                <li><?= Html::a(Yii::t('app', 'Job opportunity'), ['/site/opportunity']) ?></li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li><?= Html::a(Yii::t('app', 'About'), ['/site/about']) ?></li>
                            <li><?= Html::a(Yii::t('app', 'Contact'), ['/site/contact']) ?></li>
                            <li><?= Html::a(Yii::t('app', 'Blog'), ['/blog/index']) ?></li>
                            <li><?= Html::a(Yii::t('app', 'FAQS'), ['/site/faqs']) ?></li>
                            <li><?= Html::a(Yii::t('app', 'Service request'), ['/site/order']) ?></li>
                            <li><?= Html::a(Yii::t('app', 'Job opportunity'), ['/site/opportunity']) ?></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <div class="footer-bottom"><span>&copy; <?= Yii::$app->formatter->asYear(time()) ?> <?= Html::encode($siteName) ?></span><?php if (Yii::$app->user->isGuest): ?><?= Html::a(Yii::t('app', 'Admin login'), ['/site/login']) ?><?php endif; ?></div>
        </div>
    </footer>
</div>
<button id="scroll-to-top" class="d-btn d-btn-circle d-btn-primary" type="button" aria-label="<?= Yii::t('app', 'Scroll to top') ?>"><?= Icon::show('arrow-up') ?></button>
<?php if ($isAdmin): ?>
<dialog id="confirmation-dialog" class="d-modal admin-confirmation" data-confirmation-dialog aria-labelledby="confirmation-title">
    <div class="d-modal-box">
        <form method="dialog"><button class="d-btn d-btn-sm d-btn-circle d-btn-ghost confirmation-close" value="cancel" aria-label="<?= Yii::t('app', 'Close') ?>"><?= Icon::show('close', ['width' => 18, 'height' => 18]) ?></button></form>
        <h2 id="confirmation-title" class="confirmation-title"><?= Yii::t('app', 'Confirm action') ?></h2>
        <p data-confirmation-message><?= Yii::t('app', 'This action cannot be undone.') ?></p>
        <div class="d-modal-action"><form method="dialog"><button class="d-btn d-btn-ghost" value="cancel"><?= Yii::t('app', 'Cancel') ?></button><button class="d-btn d-btn-error" value="confirm"><?= Yii::t('app', 'Confirm') ?></button></form></div>
    </div>
    <form method="dialog" class="d-modal-backdrop"><button value="cancel"><?= Yii::t('app', 'Close') ?></button></form>
</dialog>
<dialog id="image-preview-dialog" class="d-modal image-preview-dialog" data-image-preview-dialog aria-label="<?= Yii::t('app', 'Image preview') ?>"><div class="d-modal-box"><form method="dialog"><button class="d-btn d-btn-sm d-btn-circle d-btn-ghost confirmation-close" value="cancel" aria-label="<?= Yii::t('app', 'Close') ?>"><?= Icon::show('close') ?></button></form><img data-image-preview-target src="" alt=""></div><form method="dialog" class="d-modal-backdrop"><button value="cancel"><?= Yii::t('app', 'Close') ?></button></form></dialog>
<dialog id="remote-detail-dialog" class="d-modal remote-detail-dialog" data-remote-detail-dialog aria-label="<?= Yii::t('app', 'View details') ?>"><div class="d-modal-box"><form method="dialog"><button class="d-btn d-btn-sm d-btn-circle d-btn-ghost confirmation-close" value="cancel" aria-label="<?= Yii::t('app', 'Close') ?>"><?= Icon::show('close') ?></button></form><div data-remote-detail-content></div></div><form method="dialog" class="d-modal-backdrop"><button value="cancel"><?= Yii::t('app', 'Close') ?></button></form></dialog>
<?php endif; ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
