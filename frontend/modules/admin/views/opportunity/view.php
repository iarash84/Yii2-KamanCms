<?php

use frontend\widgets\Icon;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model frontend\models\Opportunity */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Opportunities'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$hasResume = trim((string) $model->resume) !== '';
?>
<div class="opportunity-view">
    <div class="page-header page-header-actions">
        <div>
            <p class="text-overline"><?= Yii::t('app', 'Job opportunity') ?></p>
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="opportunity-view-actions">
            <?php if ($hasResume): ?>
                <?= Html::a(
                    Icon::show('download') . Yii::t('app', 'Download resume'),
                    ['download', 'id' => $model->id],
                    ['class' => 'btn', 'download' => 'resume-' . $model->id]
                ) ?>
            <?php endif; ?>
            <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                    'method' => 'post',
                ],
            ]) ?>
        </div>
    </div>

    <section class="card opportunity-detail-card">
        <?= DetailView::widget([
            'model' => $model,
            'template' => '<tr><th>{label}</th><td>{value}</td></tr>',
            'attributes' => [
                'phone_number',
                'email:email',
                [
                    'attribute' => 'resume',
                    'format' => 'raw',
                    'label' => Yii::t('app', 'Resume'),
                    'value' => $hasResume
                        ? Html::a(Icon::show('resume') . Yii::t('app', 'View resume file'), ['download', 'id' => $model->id], [
                            'class' => 'opportunity-resume-link',
                            'download' => 'resume-' . $model->id,
                        ])
                        : Html::tag('span', Yii::t('app', 'No resume attached'), ['class' => 'text-muted']),
                ],
                ['attribute' => 'created_at', 'format' => 'datetime'],
            ],
        ]) ?>
    </section>
</div>
