<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use frontend\widgets\AdminActionColumn;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'User Management');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

    <?php  echo $this->render('signup', ['model' => $model]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'striped responsive-table' ],
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'headerOptions' => ['style'=>'text-align:center;'],
                'contentOptions' => ['style'=>'text-align:center;'],
            ],
            [
                'class' => 'yii\grid\DataColumn',
                'label' => Yii::t('app', 'User'),
                'format' => 'raw',
                'headerOptions' => ['style'=>'text-align:center;'],
                'contentOptions' => ['style'=>'text-align:center;'],
                'value' => static function ($data) {
                    $name = trim((string) $data->full_name) ?: $data->username;
                    $avatar = trim((string) $data->avatar) !== ''
                        ? Html::img(Url::to('@web/' . ltrim($data->avatar, '/')), ['alt' => ''])
                        : Html::tag('span', Html::encode(mb_strtoupper(mb_substr($name, 0, 1))));
                    return Html::tag(
                        'div',
                        Html::tag('span', $avatar, ['class' => 'user-list-avatar'])
                        . Html::tag('span', Html::tag('strong', Html::encode($name))
                            . Html::tag('small', Html::encode('@' . $data->username))),
                        ['class' => 'user-list-identity']
                    );
                },
            ],
            [
                'class' => 'yii\grid\DataColumn',
                'headerOptions' => ['style'=>'text-align:center;'],
                'contentOptions' => ['style'=>'text-align:center;'],
                'attribute' => 'email'
            ],
            [
                'attribute' => 'job_title',
                'value' => static fn ($data) => $data->job_title ?: Yii::t('app', 'Not provided'),
            ],
            [
                'class' => 'yii\grid\DataColumn',
                'value' => function ($data) {
                    return Yii::$app->formatter->asDatetime($data->created_at);
                },
                'headerOptions' => ['style'=>'text-align:center;'],
                'contentOptions' => ['style'=>'text-align:center;'],
                'attribute' => 'created_at',
            ],
            [
                'label' => Yii::t('app', 'Role'),
                'format' => 'raw',
                'value' => static function ($data) {
                    $labels = ['superAdmin' => Yii::t('app', 'Super Admin'), 'admin' => Yii::t('app', 'Admin'), 'editor' => Yii::t('app', 'Editor')];
                    $roles = array_keys(Yii::$app->authManager->getRolesByUser($data->id));
                    return implode(' ', array_map(static fn ($role) => Html::tag('span', Html::encode($labels[$role] ?? $role), ['class' => 'status-pill']), $roles));
                },
            ],
            [
                'class' => AdminActionColumn::class,
                'template' => '{update} {delete}'
            ]
        ],
    ]); ?>
    <br /><br />
</div>
