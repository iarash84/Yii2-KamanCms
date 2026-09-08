<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\User */

$this->title = Yii::t('app', 'Update') . ' ' . ($model->full_name ?: $model->username);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'User Management'), 'url' => ['index']];

$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="user-update">

    <h5><?= Html::encode($this->title) ?></h5>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
