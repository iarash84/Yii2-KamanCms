<?php

use frontend\widgets\AdminActionColumn;
use frontend\widgets\Icon;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;


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
                'attribute' => 'email',
            ],
            [
                'label' => Yii::t('app', 'Resume'),
                'format' => 'raw',
                'headerOptions' => ['style' => 'text-align:center;'],
                'contentOptions' => ['style' => 'text-align:center;'],
                'value' => static function ($model) {
                    if (trim((string) $model->resume) === '') {
                        return Html::tag('span', Yii::t('app', 'No resume attached'), ['class' => 'text-muted']);
                    }
                    return Html::a(
                        Icon::show('download', ['width' => 16, 'height' => 16]) . Yii::t('app', 'Download resume'),
                        Url::to(['download', 'id' => $model->id]),
                        [
                            'class' => 'opportunity-resume-chip',
                            'title' => Yii::t('app', 'Download resume'),
                            'aria-label' => Yii::t('app', 'Download resume'),
                            'download' => 'resume-' . $model->id,
                            'data-pjax' => '0',
                        ]
                    );
                },
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
                'template' => '{detail} {delete}',
                'buttons' => [
                    'detail' => static fn ($url, $model) => Html::button(Icon::show('eye'), [
                        'class' => 'd-btn d-btn-sm d-btn-square d-btn-ghost',
                        'data-remote-dialog-url' => Url::to(['detail', 'id' => $model->id]),
                        'data-error-message' => Yii::t('app', 'Unable to load details.'),
                        'aria-label' => Yii::t('app', 'View'),
                        'title' => Yii::t('app', 'View'),
                    ]),
                ],
            ],
        ],
    ]); ?>
</div>
