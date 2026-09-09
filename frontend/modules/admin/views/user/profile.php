<?php

use frontend\widgets\Icon;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = Yii::t('app', 'Edit profile');
$this->params['breadcrumbs'][] = $this->title;
$roles = array_intersect(
    array_keys(Yii::$app->authManager->getRolesByUser($model->id)),
    ['editor', 'admin', 'superAdmin']
);
$role = reset($roles) ?: 'editor';
$roleLabels = [
    'editor' => Yii::t('app', 'Editor'),
    'admin' => Yii::t('app', 'Admin'),
    'superAdmin' => Yii::t('app', 'Super Admin'),
];
?>
<div class="page-header page-header-actions">
    <div>
        <p class="text-overline"><?= Yii::t('app', 'Personal details') ?></p>
        <h1><?= Html::encode($this->title) ?></h1>
        <p><?= Yii::t('app', 'Update your profile photo and contact information.') ?></p>
    </div>
    <?= Html::a(Icon::show('settings') . Yii::t('app', 'Change Password'), ['change'], [
        'class' => 'btn btn-secondary',
    ]) ?>
</div>

<?php $form = ActiveForm::begin([
    'id' => 'form-profile',
    'options' => ['enctype' => 'multipart/form-data', 'class' => 'user-profile-form'],
]); ?>
<section class="card user-form-card">
    <?= $form->errorSummary($model) ?>

    <div class="profile-account-context">
        <div>
            <strong><?= Yii::t('app', 'Your account') ?></strong>
            <span class="ltr"><?= Html::encode('@' . $model->username) ?></span>
        </div>
        <div>
            <span><?= Yii::t('app', 'Role') ?></span>
            <strong class="status-pill"><?= Html::encode($roleLabels[$role] ?? $role) ?></strong>
        </div>
        <p><?= Yii::t('app', 'Account credentials and roles can only be changed by an authorized administrator.') ?></p>
    </div>

    <?= $this->render('_profile_fields', [
        'form' => $form,
        'model' => $model,
        'includeEmail' => true,
    ]) ?>

    <div class="form-actions">
        <?= Html::submitButton(Icon::show('save') . Yii::t('app', 'Save changes'), ['class' => 'btn']) ?>
    </div>
</section>
<?php ActiveForm::end(); ?>
