<?php

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $model \common\models\User */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>
<div class="user-update">

    <?php $form = ActiveForm::begin([
        'id' => 'form-signup',
        'options' => ['enctype' => 'multipart/form-data', 'class' => 'user-profile-form'],
    ]); ?>
    <section class="card user-form-card">
        <?= $form->errorSummary($model) ?>
        <?= $this->render('_profile_fields', ['form' => $form, 'model' => $model]) ?>

        <fieldset>
            <legend><?= Yii::t('app', 'Account access') ?></legend>
            <div class="form-grid">
                <?= $form->field($model, 'username')->textInput(['autofocus' => true, 'maxlength' => true]) ?>
                <?= $form->field($model, 'email')->input('email', ['maxlength' => true, 'dir' => 'ltr']) ?>
                <?= $form->field($model, 'password')->passwordInput(['autocomplete' => 'new-password'])
                    ->hint(Yii::t('app', 'Leave empty to keep the current password.')) ?>
                <?= $form->field($model, 'role')->dropDownList([
                    'editor' => Yii::t('app', 'Editor'),
                    'admin' => Yii::t('app', 'Admin'),
                    'superAdmin' => Yii::t('app', 'Super Admin'),
                ]) ?>
            </div>
        </fieldset>

        <div class="form-actions">
            <?= Html::submitButton(Yii::t('app', 'Update'), ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
        </div>
    </section>

    <?php ActiveForm::end(); ?>

</div>
