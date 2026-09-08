<?php

use frontend\helpers\MediaUrl;
use frontend\widgets\Icon;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$form = ActiveForm::begin([
    'enableClientValidation' => true,
    'options' => ['enctype' => 'multipart/form-data', 'class' => 'model-form'],
]);
?>
<div class="card">
    <?= $form->errorSummary($model) ?>

    <?php if (!$model->isNewRecord): ?>
        <div class="carousel-form-preview">
            <?= Html::img(MediaUrl::image($model->image, 'img/portfolio/hero-studio.webp'), ['alt' => Html::encode($model->title)]) ?>
        </div>
    <?php endif; ?>

    <?= $form->field($model, 'image')->fileInput(['accept' => 'image/jpeg,image/png,image/webp'])
        ->hint($model->isNewRecord ? Yii::t('app', 'Image is required.') : Yii::t('app', 'Leave empty to keep the current image.')) ?>

    <div class="form-grid">
        <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'eyebrow')->textInput(['maxlength' => true]) ?>
    </div>
    <?= $form->field($model, 'text')->textarea(['rows' => 4]) ?>

    <fieldset>
        <legend><?= Yii::t('app', 'Primary action') ?></legend>
        <div class="form-grid">
            <?= $form->field($model, 'primary_button_label')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'link')->textInput(['maxlength' => true, 'dir' => 'ltr']) ?>
        </div>
    </fieldset>

    <fieldset>
        <legend><?= Yii::t('app', 'Secondary action') ?></legend>
        <div class="form-grid">
            <?= $form->field($model, 'secondary_button_label')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'secondary_link')->textInput(['maxlength' => true, 'dir' => 'ltr']) ?>
        </div>
    </fieldset>

    <?= $form->field($model, 'show_content')->checkbox()
        ->hint(Yii::t('app', 'Only one slide can display content. Selecting this option clears it from the previous slide.')) ?>
    <?= $form->field($model, 'status')->checkbox() ?>

    <?= $this->render('@app/modules/admin/views/_translation_fields', ['model' => $model]) ?>

    <div class="form-actions">
        <?= Html::submitButton(Icon::show('save') . Yii::t('app', 'Save'), ['class' => 'btn']) ?>
    </div>
</div>
<?php ActiveForm::end(); ?>
