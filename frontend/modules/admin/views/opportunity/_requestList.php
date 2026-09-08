<?php

use yii\grid\GridView;
use frontend\widgets\AdminActionColumn;
use yii\helpers\Html;


?>
<div class="opportunity-requestList">

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
                'attribute' => 'name',
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
            //'resume',
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
                'template' => '{view} {delete}'
            ],
        ],
    ]); ?>
    <br /><br />
</div>
