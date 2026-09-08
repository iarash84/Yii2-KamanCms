<?php

use yii\helpers\Html;
use yii\grid\GridView;
use frontend\widgets\AdminActionColumn;
use frontend\widgets\Icon;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\ContactSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Contact');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="contact-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php  //echo $this->render('_search', ['model' => $searchModel]); ?>


    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'striped responsive-table' ],
        'rowOptions' => static function ($model) {
            return ['class' => $model->read_at === null ? 'submission-row-unread' : null];
        },
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'headerOptions' => ['style'=>'text-align:center;'],
                'contentOptions' => ['style'=>'text-align:center;'],
            ],
            [
                'class' => 'yii\grid\DataColumn',
                'headerOptions' => ['style'=>'text-align:center;'],
                'contentOptions' => ['style'=>'text-align:center;'],
                'attribute' => 'name'
            ],
            [
                'class' => 'yii\grid\DataColumn',
                'headerOptions' => ['style'=>'text-align:center;'],
                'contentOptions' => ['style'=>'text-align:center;'],
                'attribute' => 'phone_number'
            ],
            [
                'class' => 'yii\grid\DataColumn',
                'headerOptions' => ['style'=>'text-align:center;'],
                'contentOptions' => ['style'=>'text-align:center;'],
                'attribute' => 'email'
            ],
            [
                'class' => 'yii\grid\DataColumn',
                'headerOptions' => ['style'=>'text-align:center;'],
                'contentOptions' => ['style'=>'text-align:center;'],
                'attribute' => 'subject'
            ],
            [
                'class' => 'yii\grid\DataColumn',
                'headerOptions' => ['style'=>'text-align:center;'],
                'contentOptions' => ['style'=>'text-align:center;'],
                'attribute' => 'created_at',
                'format' => 'datetime',
            ],
            [
                'label' => Yii::t('app', 'Status'),
                'format' => 'raw',
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
                'contentOptions' => ['class' => 'admin-table-actions-column'],
                'template' => '{detail} {delete}',
                'buttons' => ['detail' => static fn ($url, $model) => Html::button(Icon::show('eye'), ['class' => 'd-btn d-btn-sm d-btn-square d-btn-ghost', 'data-remote-dialog-url' => Url::to(['detail', 'id' => $model->id]), 'data-error-message' => Yii::t('app', 'Unable to load details.'), 'aria-label' => Yii::t('app', 'View')])],
            ],
        ],
    ]); ?>

    <br /><br />

</div>
