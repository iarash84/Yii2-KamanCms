<?php

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $model \frontend\models\SignupForm */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>
<div class="site-signup">

    <?php $form = ActiveForm::begin([
        'id' => 'form-signup',
        'options' => ['enctype' => 'multipart/form-data', 'class' => 'user-profile-form'],
    ]); ?>
    <section class="card user-form-card">
        <header class="user-form-heading">
            <div>
                <p class="text-overline"><?= Yii::t('app', 'New user') ?></p>
                <h2><?= Yii::t('app', 'Create a complete user profile') ?></h2>
                <p class="text-muted"><?= Yii::t('app', 'Add account, role, contact and profile details in one place.') ?></p>
            </div>
        </header>

        <?= $form->errorSummary($model) ?>
        <?= $this->render('_profile_fields', ['form' => $form, 'model' => $model]) ?>

        <fieldset>
            <legend><?= Yii::t('app', 'Account access') ?></legend>
            <div class="form-grid">
                <?= $form->field($model, 'username')->textInput(['autofocus' => true, 'maxlength' => true]) ?>
                <?= $form->field($model, 'email')->input('email', ['maxlength' => true, 'dir' => 'ltr']) ?>
                <?= $form->field($model, 'password')->passwordInput(['autocomplete' => 'new-password']) ?>
                <?= $form->field($model, 'role')->dropDownList([
                    'editor' => Yii::t('app', 'Editor'),
                    'admin' => Yii::t('app', 'Admin'),
                    'superAdmin' => Yii::t('app', 'Super Admin'),
                ]) ?>
            </div>
        </fieldset>

        <div class="form-actions">
            <?= Html::submitButton(Yii::t('app', 'Signup'), ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
        </div>
    </section>

    <?php ActiveForm::end(); ?>

</div>
