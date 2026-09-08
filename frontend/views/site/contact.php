<?php

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $model \frontend\models\ContactForm */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\components\TextCaptcha;

$this->title = Yii::t('app','Contact');;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-contact public-form-card">
    <header class="public-form-header"><p class="text-overline"><?= Yii::t('app', 'Talk to the team') ?></p><h1><?= Html::encode($this->title) ?></h1><p><?= Yii::t('app', 'Send a question or share the context you would like us to understand.') ?></p></header>

            <?= $this->render('_submission_expectation') ?>

            <?php $form = ActiveForm::begin(['id' => 'contact-form']); ?>

                <?= $form->field($model, 'name')->textInput(['autofocus' => true, 'autocomplete' => 'name']) ?>

                <?= $form->field($model, 'phoneNumber')->input('tel', ['autocomplete' => 'tel', 'inputmode' => 'tel', 'dir' => 'ltr', 'placeholder' => '+98 912 000 0000']) ?>

                <?= $form->field($model, 'email')->input('email', ['autocomplete' => 'email', 'dir' => 'ltr']) ?>

                <?= $form->field($model, 'subject') ?>

                <?= $form->field($model, 'body')->textArea(['rows' => 6]) ?>

                <div class="captcha-panel">
                    <p id="captcha-question" class="captcha-question"><?= Html::encode(TextCaptcha::question()) ?></p>
                    <?= $form->field($model, 'verifyCode')->textInput([
                        'inputmode' => 'numeric',
                        'autocomplete' => 'off',
                        'aria-describedby' => 'captcha-question',
                    ]) ?>
                </div>

                <div class="form-group">
                    <?= Html::submitButton(Yii::t('app', 'Send message'), ['class' => 'd-btn d-btn-primary', 'name' => 'contact-button']) ?>
                </div>

            <?php ActiveForm::end(); ?>
</div>
