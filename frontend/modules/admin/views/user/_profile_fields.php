<?php

use common\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

$hasAvatar = $model instanceof User && trim((string) $model->avatar) !== '';
$displayName = trim((string) $model->full_name) ?: trim((string) $model->username);
$initial = $displayName === '' ? '?' : mb_strtoupper(mb_substr($displayName, 0, 1));
?>
<div class="user-profile-editor">
    <aside class="user-avatar-panel">
        <div class="user-avatar-preview" data-avatar-preview>
            <?php if ($hasAvatar): ?>
                <?= Html::img(Url::to('@web/' . ltrim($model->avatar, '/')), [
                    'alt' => $displayName,
                    'data-avatar-preview-image' => true,
                ]) ?>
            <?php else: ?>
                <span data-avatar-preview-fallback><?= Html::encode($initial) ?></span>
            <?php endif; ?>
        </div>
        <div>
            <strong><?= Yii::t('app', 'Profile photo') ?></strong>
            <p class="text-muted"><?= Yii::t('app', 'PNG, JPG or WebP up to 2 MB.') ?></p>
        </div>
        <?= $form->field($model, 'avatarFile')->fileInput([
            'accept' => 'image/png,image/jpeg,image/webp',
            'data-avatar-input' => true,
        ])->label(false) ?>
        <?php if ($hasAvatar): ?>
            <?= $form->field($model, 'removeAvatar')->checkbox() ?>
        <?php endif; ?>
    </aside>

    <div class="user-profile-fields">
        <fieldset>
            <legend><?= Yii::t('app', 'Profile information') ?></legend>
            <div class="form-grid">
                <?= $form->field($model, 'full_name')->textInput(['maxlength' => true]) ?>
                <?= $form->field($model, 'job_title')->textInput(['maxlength' => true]) ?>
            </div>
            <?= $form->field($model, 'bio')->textarea([
                'rows' => 4,
                'maxlength' => 1000,
                'placeholder' => Yii::t('app', 'A short introduction about this user'),
            ]) ?>
        </fieldset>

        <fieldset>
            <legend><?= Yii::t('app', 'Contact information') ?></legend>
            <div class="form-grid">
                <?= $form->field($model, 'phone')->textInput(['maxlength' => true, 'dir' => 'ltr']) ?>
                <?= $form->field($model, 'location')->textInput(['maxlength' => true]) ?>
                <?= $form->field($model, 'website')->textInput(['maxlength' => true, 'dir' => 'ltr']) ?>
            </div>
        </fieldset>
    </div>
</div>
