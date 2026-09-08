<?php

use frontend\helpers\MediaUrl;
use frontend\widgets\AdminButton;
use frontend\widgets\Icon;
use frontend\widgets\StatusBadge;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Carousel');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="carousel-index">
    <div class="page-header page-header-actions">
        <div>
            <p class="text-overline"><?= Yii::t('app', 'Homepage') ?></p>
            <h1><?= Html::encode($this->title) ?></h1>
            <p class="text-muted"><?= Yii::t('app', 'Drag slides to change their display order.') ?> <span data-carousel-save-state aria-live="polite"></span></p>
        </div>
        <?= AdminButton::link(Icon::show('plus') . Yii::t('app', 'Create carousel'), ['create'], 'primary') ?>
    </div>

    <?php if ($dataProvider->count > 0): ?>
        <div class="table-responsive">
            <table class="table carousel-admin-table">
                <thead><tr>
                    <th class="drag-column"><span class="sr-only"><?= Yii::t('app', 'Display order') ?></span></th>
                    <th><?= Yii::t('app', 'Image') ?></th>
                    <th><?= Yii::t('app', 'Title') ?></th>
                    <th><?= Yii::t('app', 'Text') ?></th>
                    <th><?= Yii::t('app', 'Links') ?></th>
                    <th><?= Yii::t('app', 'Status') ?></th>
                    <th class="admin-table-actions-column"><span class="sr-only"><?= Yii::t('app', 'Actions') ?></span></th>
                </tr></thead>
                <tbody data-carousel-sorter data-save-url="<?= Url::to(['reorder']) ?>" data-csrf-param="<?= Html::encode(Yii::$app->request->csrfParam) ?>" data-csrf-token="<?= Html::encode(Yii::$app->request->csrfToken) ?>">
                <?php foreach ($dataProvider->models as $item): ?>
                    <?php $imageUrl = MediaUrl::image($item->image, 'img/portfolio/hero-studio.webp'); ?>
                    <tr class="carousel-sort-row" draggable="true" data-carousel-id="<?= (int) $item->id ?>">
                        <td><span class="drag-handle" title="<?= Yii::t('app', 'Drag to reorder') ?>" aria-hidden="true">⋮⋮</span></td>
                        <td><button type="button" class="carousel-thumbnail" data-image-preview="<?= Html::encode($imageUrl) ?>" data-image-alt="<?= Html::encode($item->title) ?>" aria-label="<?= Yii::t('app', 'View full-size image') ?>"><?= Html::img($imageUrl, ['alt' => Html::encode($item->title), 'class' => 'carousel-thumbnail-image']) ?></button></td>
                        <td>
                            <strong><?= Html::encode($item->title ?: '—') ?></strong>
                            <?php if ($item->eyebrow): ?><small><?= Html::encode($item->eyebrow) ?></small><?php endif; ?>
                            <?php if ($item->show_content): ?><span class="d-badge d-badge-info"><?= Yii::t('app', 'Content slide') ?></span><?php endif; ?>
                        </td>
                        <td><?= Html::encode(StringHelper::truncate(trim(strip_tags((string) $item->text)), 90)) ?: '—' ?></td>
                        <td class="carousel-links-cell">
                            <?php if ($item->link): ?><a href="<?= Html::encode($item->link) ?>" dir="ltr"><?= Html::encode($item->primary_button_label ?: $item->link) ?></a><?php endif; ?>
                            <?php if ($item->secondary_link): ?><a href="<?= Html::encode($item->secondary_link) ?>" dir="ltr"><?= Html::encode($item->secondary_button_label ?: $item->secondary_link) ?></a><?php endif; ?>
                            <?php if (!$item->link && !$item->secondary_link): ?>—<?php endif; ?>
                        </td>
                        <td><?= StatusBadge::boolean($item->status) ?></td>
                        <td class="admin-table-actions-column"><div class="admin-table-actions">
                            <?= AdminButton::link(Icon::show('edit'), ['update', 'id' => $item->id], 'compact', ['aria-label' => Yii::t('app', 'Update')]) ?>
                            <?= AdminButton::link(Icon::show('trash'), ['delete', 'id' => $item->id], 'compact', ['class' => 'admin-action-delete', 'data-method' => 'post', 'data-confirm' => Yii::t('app', 'Are you sure you want to delete this item?'), 'aria-label' => Yii::t('app', 'Delete')]) ?>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="card empty-state"><p><?= Yii::t('app', 'No records found') ?></p></div>
    <?php endif; ?>
</div>
