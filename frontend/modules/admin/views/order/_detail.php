<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

?>
<div class="submission-detail">
    <p class="text-overline"><?= Yii::t('app', 'Service request') ?></p>
    <h2><?= Html::encode($model->name) ?></h2>
    <?= DetailView::widget(['model' => $model, 'attributes' => ['name', 'company', 'phone_number', 'website', 'email:email', 'description:ntext', ['attribute' => 'created_at', 'format' => 'datetime']]]) ?>
</div>
