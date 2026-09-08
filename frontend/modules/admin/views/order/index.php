<?php

use yii\helpers\Html;
use yii\grid\GridView;
use frontend\widgets\AdminActionColumn;
use frontend\widgets\Icon;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\OrderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Service requests');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="order-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'options' => ['class' => 'grid-view service-request-grid'],
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'striped service-request-table'],
        'rowOptions' => static function ($model) {
            return ['class' => $model->read_at === null ? 'submission-row-unread' : null];
        },
        'columns' => [
            [
                'label' => Yii::t('app', 'Customer'),
                'format' => 'raw',
                'headerOptions' => ['class' => 'request-customer-column'],
                'contentOptions' => ['class' => 'request-customer-cell', 'data-label' => Yii::t('app', 'Customer')],
                'value' => static function ($model) {
                    $company = trim((string) $model->company);
                    return Html::tag('strong', Html::encode($model->name))
                        . ($company === '' ? '' : Html::tag('small', Html::encode($company)));
                },
            ],
            [
                'label' => Yii::t('app', 'Contact details'),
                'format' => 'raw',
                'headerOptions' => ['class' => 'request-contact-column'],
                'contentOptions' => ['class' => 'request-contact-cell', 'data-label' => Yii::t('app', 'Contact details')],
                'value' => static function ($model) {
                    $items = [];
                    if ($model->phone_number) {
                        $items[] = Html::a(Html::encode($model->phone_number), 'tel:' . rawurlencode($model->phone_number), ['dir' => 'ltr']);
                    }
                    if ($model->email) {
                        $items[] = Html::mailto(Html::encode($model->email), $model->email, ['dir' => 'ltr']);
                    }
                    return implode('', array_map(static fn ($item) => Html::tag('span', $item), $items));
                },
            ],
            [
                'class' => 'yii\grid\DataColumn',
                'headerOptions' => ['class' => 'request-date-column'],
                'contentOptions' => ['class' => 'request-date-cell', 'data-label' => Yii::t('app', 'Create Date Time')],
                'attribute' => 'created_at',
                'format' => 'datetime',
            ],
            [
                'label' => Yii::t('app', 'Status'),
                'format' => 'raw',
                'headerOptions' => ['class' => 'request-status-column'],
                'contentOptions' => ['class' => 'request-status-cell', 'data-label' => Yii::t('app', 'Status')],
                'value' => static function ($model) {
                    $unread = $model->read_at === null;
                    $label = $unread ? Yii::t('app', 'Unread') : Yii::t('app', 'Read');
                    return Html::tag('span', $label, [
                        'class' => 'submission-status ' . ($unread ? 'is-unread' : 'is-read'),
                    ]);
                },
            ],
            [
                'class' => AdminActionColumn::class,
                'headerOptions' => ['class' => 'admin-table-actions-column'],
                'contentOptions' => ['class' => 'admin-table-actions-column', 'data-label' => Yii::t('app', 'Actions')],
                'template' => '{detail} {delete}',
                'buttons' => ['detail' => static fn ($url, $model) => Html::button(Icon::show('eye'), ['class' => 'd-btn d-btn-sm d-btn-square d-btn-ghost', 'data-remote-dialog-url' => Url::to(['detail', 'id' => $model->id]), 'data-error-message' => Yii::t('app', 'Unable to load details.'), 'aria-label' => Yii::t('app', 'View')])],
            ],
        ],
    ]); ?>
    <br /><br />
</div>
