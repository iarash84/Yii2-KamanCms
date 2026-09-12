<?php

use frontend\widgets\Icon;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model frontend\models\Opportunity */

$hasResume = trim((string) $model->resume) !== '';
?>
<div class="submission-detail opportunity-detail">
    <p class="text-overline"><?= Yii::t('app', 'Job opportunity') ?></p>
    <h2><?= Html::encode($model->name) ?></h2>

    <?php if ($hasResume): ?>
        <div class="opportunity-resume-download">
            <?= Html::a(
                Icon::show('download') . Yii::t('app', 'Download resume') . Html::tag('span', '.PDF', ['class' => 'opportunity-resume-type']),
                ['download', 'id' => $model->id],
                ['class' => 'd-btn d-btn-sm opportunity-resume-button']
            ) ?>
        </div>
    <?php endif; ?>

    <?= DetailView::widget([
        'model' => $model,
        'template' => '<tr><th>{label}</th><td>{value}</td></tr>',
        'attributes' => [
            'phone_number',
            'email:email',
            [
                'attribute' => 'resume',
                'format' => 'raw',
                'value' => $hasResume
                    ? Html::a(Icon::show('resume') . Yii::t('app', 'View resume file'), Url::to(['download', 'id' => $model->id]), [
                        'class' => 'opportunity-resume-link',
                        'download' => 'resume-' . $model->id,
                    ])
                    : Html::tag('span', Yii::t('app', 'No resume attached'), ['class' => 'text-muted']),
                'visible' => true,
                'label' => Yii::t('app', 'Resume'),
            ],
            ['attribute' => 'created_at', 'format' => 'datetime'],
        ],
    ]) ?>
</div>
