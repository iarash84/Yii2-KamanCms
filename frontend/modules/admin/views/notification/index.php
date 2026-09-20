<?php

use frontend\widgets\Icon;
use yii\helpers\Html;

$this->title = Yii::t('app', 'Notifications');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notification-page">
    <header class="page-header page-header-actions">
        <div><p class="text-overline"><?= Yii::t('app', 'Admin panel') ?></p><h1><?= Html::encode($this->title) ?></h1><p class="text-muted"><?= Yii::t('app', 'Keep track of your latest form submissions.') ?></p></div>
        <?php if ($unreadCount > 0): ?>
            <?= Html::beginForm(['mark-all-read'], 'post', ['class' => 'notification-mark-all-form']) ?>
                <?= Html::submitButton(Icon::show('check') . Yii::t('app', 'Mark all as read'), ['class' => 'btn btn-secondary']) ?>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </header>
    <div class="notification-toolbar">
        <div class="d-tabs d-tabs-box" role="tablist">
            <?= Html::a(Yii::t('app', 'All'), ['index', 'filter' => 'all'], ['class' => 'd-tab ' . ($filter === 'all' ? 'd-tab-active' : ''), 'aria-selected' => $filter === 'all' ? 'true' : 'false']) ?>
            <?= Html::a(Yii::t('app', 'Unread') . ($unreadCount ? ' (' . $unreadCount . ')' : ''), ['index', 'filter' => 'unread'], ['class' => 'd-tab ' . ($filter === 'unread' ? 'd-tab-active' : ''), 'aria-selected' => $filter === 'unread' ? 'true' : 'false']) ?>
        </div>
    </div>
    <?php if ($items): ?>
        <div class="notification-list">
            <?php foreach ($items as $item): ?>
                <article class="notification-item <?= $item['read_at'] === null ? 'is-unread' : '' ?>">
                    <div class="notification-item-icon" aria-hidden="true"><?= Icon::show($item['type'] === 'contact' ? 'inbox' : ($item['type'] === 'order' ? 'posts' : 'users')) ?></div>
                    <div class="notification-item-content">
                        <div class="notification-item-meta"><span><?= Html::encode($item['label']) ?></span><time datetime="<?= Html::encode(date(DATE_ATOM, (int) $item['created_at'])) ?>"><?= Yii::$app->formatter->asRelativeTime($item['created_at']) ?></time></div>
                        <h2><?= Html::encode($item['title'] ?: Yii::t('app', 'New notification')) ?></h2>
                        <?php if ($item['summary'] !== ''): ?><p><?= Html::encode(mb_strimwidth($item['summary'], 0, 180, '...')) ?></p><?php endif; ?>
                    </div>
                    <div class="notification-item-action"><?= Html::a(Icon::show('eye'), $item['url'], ['class' => 'd-btn d-btn-sm d-btn-square d-btn-ghost', 'title' => Yii::t('app', 'View'), 'aria-label' => Yii::t('app', 'View')]) ?></div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card empty-state"><h2><?= Yii::t('app', 'No notifications found.') ?></h2><p><?= Yii::t('app', 'You are all caught up.') ?></p></div>
    <?php endif; ?>
</div>