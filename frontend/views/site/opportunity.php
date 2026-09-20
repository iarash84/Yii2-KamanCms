<?php

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $model \frontend\models\ContactForm */

use frontend\components\TextCaptcha;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\widgets\ActiveForm;

$this->title = Yii::t('app', 'Job opportunity');
$this->params['breadcrumbs'][] = $this->title;
?>


<div class="site-opportunity public-form-card">
    <header class="public-form-header">
        <p class="text-overline"><?= Yii::t('app', 'Join the team') ?></p>
        <h1><?= Html::encode($this->title) ?></h1>
        <p><?= Yii::t('app', 'Tell us about yourself and attach your resume so the team can get back to you.') ?></p>
    </header>
    <div class="prose public-form-intro">
        <?php
            $setting = new \frontend\models\Setting();
            echo HtmlPurifier::process($setting->opportunity);
        ?>
    </div>

    <?= $this->render('_submission_expectation') ?>

    <?php $form = ActiveForm::begin(['id' => 'opportunity-form', 'options' => ['class' => 'opportunity-form', 'enctype' => 'multipart/form-data']]); ?>

    <fieldset class="opportunity-form-fieldset">
        <legend><?= Yii::t('app', 'Applicant information') ?></legend>
        <div class="form-grid">
            <?= $form->field($model, 'name')->textInput(['autofocus' => true, 'autocomplete' => 'name']) ?>
            <?= $form->field($model, 'phoneNumber')->input('tel', ['autocomplete' => 'tel', 'inputmode' => 'tel', 'dir' => 'ltr', 'placeholder' => '+98 912 000 0000']) ?>
        </div>
        <?= $form->field($model, 'email')->input('email', ['autocomplete' => 'email', 'dir' => 'ltr']) ?>
    </fieldset>

    <fieldset class="opportunity-form-fieldset">
        <legend><?= Yii::t('app', 'Resume') ?></legend>
        <div class="resume-upload-panel">
            <span class="resume-upload-icon" aria-hidden="true"><?= frontend\widgets\Icon::show('upload') ?></span>
            <div class="resume-upload-text">
                <strong><?= Yii::t('app', 'Attach your resume (PDF)') ?></strong>
                <p><?= Yii::t('app', 'Drag the file here or choose it from your device. Maximum allowed size applies.') ?></p>
            </div>
            <?= $form->field($model, 'resume', ['options' => ['class' => 'resume-upload-field']])->fileInput([
                'accept' => 'application/pdf',
                'aria-describedby' => 'resume-help',
                'data-resume-input' => true,
            ])->label(Yii::t('app', 'Choose file'), ['class' => 'resume-upload-button', 'data-replace-label' => Yii::t('app', 'Replace file')]) ?>
            <p class="resume-upload-filename" data-resume-filename hidden></p>
            <p id="resume-help" class="resume-upload-help"><?= Yii::t('app', 'PDF format, up to 5 MB.') ?></p>
        </div>
    </fieldset>


    <div class="captcha-panel">
        <p id="captcha-question" class="captcha-question"><?= Html::encode(TextCaptcha::question()) ?></p>
        <?= $form->field($model, 'verifyCode')->textInput([
            'inputmode' => 'numeric',
            'autocomplete' => 'off',
            'aria-describedby' => 'captcha-question',
        ]) ?>
    </div>

    <div class="form-actions">
        <?= Html::submitButton(Yii::t('app', 'Submit application'), ['class' => 'd-btn d-btn-primary', 'name' => 'opportunity-button']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
