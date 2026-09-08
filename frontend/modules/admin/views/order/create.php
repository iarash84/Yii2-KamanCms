<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model frontend\models\Order */

$this->title = Yii::t('app', 'Create service request');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Service requests'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="order-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
