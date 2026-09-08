<?php

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $model \frontend\models\ContactForm */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\components\TextCaptcha;

$this->title = Yii::t('app', 'Service request');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-order public-form-card">
    <header class="public-form-header"><p class="text-overline"><?= Yii::t('app', 'Tell us what you need') ?></p><h1><?= Html::encode($this->title) ?></h1><p><?= Yii::t('app', 'Describe the service you need, your current situation and any constraints. You do not need a complete brief.') ?></p></header>

            <?= $this->render('_submission_expectation') ?>

            <?php $form = ActiveForm::begin(['id' => 'order-form']); ?>

                <?= $form->field($model, 'name')->textInput(['autofocus' => true, 'autocomplete' => 'name']) ?>

                <?= $form->field($model, 'phoneNumber')->input('tel', ['autocomplete' => 'tel', 'inputmode' => 'tel', 'dir' => 'ltr', 'placeholder' => '+98 912 000 0000']) ?>

                <?= $form->field($model, 'company')->textInput(['autocomplete' => 'organization']) ?>

                <?= $form->field($model, 'website')->input('url', ['autocomplete' => 'url', 'dir' => 'ltr', 'placeholder' => 'https://example.com']) ?>

                <?= $form->field($model, 'email')->input('email', ['autocomplete' => 'email', 'dir' => 'ltr']) ?>

                <?= $form->field($model, 'description')->textArea(['rows' => 6, 'placeholder' => Yii::t('app', 'What outcome do you need, and what is getting in the way today?')]) ?>

                <div class="captcha-panel">
                    <p id="captcha-question" class="captcha-question"><?= Html::encode(TextCaptcha::question()) ?></p>
                    <?= $form->field($model, 'verifyCode')->textInput([
                        'inputmode' => 'numeric',
                        'autocomplete' => 'off',
                        'aria-describedby' => 'captcha-question',
                    ]) ?>
                </div>

                <div class="form-group">
                    <?= Html::submitButton(Yii::t('app', 'Submit service request'), ['class' => 'd-btn d-btn-primary', 'name' => 'order-button']) ?>
                </div>

            <?php ActiveForm::end(); ?>
</div>
