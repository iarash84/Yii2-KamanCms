<?php

use frontend\widgets\Icon;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model frontend\models\ChangePasswordForm */

$this->title = Yii::t('app', 'Change Password');
$this->params['breadcrumbs'][] = $this->title;

$identity = Yii::$app->user->identity;
$passwordUpdatedAt = $identity !== null && !empty($identity->updated_at)
    ? Yii::$app->formatter->asRelativeTime((int) $identity->updated_at)
    : Yii::t('app', 'unknown');

$requirements = [
    Yii::t('app', 'At least 12 characters'),
    Yii::t('app', 'Uppercase and lowercase letters'),
    Yii::t('app', 'At least one number'),
    Yii::t('app', 'At least one symbol (e.g. !@#)'),
];
?>
<div class="change-password-page">
    <div class="page-header page-header-actions">
        <div>
            <p class="text-overline"><?= Yii::t('app', 'Account security') ?></p>
            <h1><?= Html::encode($this->title) ?></h1>
            <p><?= Yii::t('app', 'Choose a strong password to keep your account safe.') ?></p>
        </div>
        <?= Html::a(Icon::show('user') . Yii::t('app', 'Back to profile'), ['/admin/user/profile'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <div class="change-password-layout">
        <?php $form = ActiveForm::begin([
            'id' => 'form-change-password',
            'options' => ['class' => 'change-password-form'],
        ]); ?>
        <section class="card change-password-card">
            <div class="change-password-card-heading">
                <span class="change-password-shield" aria-hidden="true"><?= Icon::show('lock') ?></span>
                <div>
                    <h2><?= Yii::t('app', 'Update your password') ?></h2>
                    <p><?= Yii::t('app', 'Your other sessions will be signed out after the change.') ?></p>
                </div>
            </div>

            <?= $form->errorSummary($model, ['class' => 'error-summary']) ?>

            <?= $form->field($model, 'oldPassword')->passwordInput([
                'autocomplete' => 'current-password',
                'dir' => 'ltr',
                'data-password-input' => true,
                'data-password-show-label' => Yii::t('app', 'Show password'),
                'data-password-hide-label' => Yii::t('app', 'Hide password'),
            ])->label(Yii::t('app', 'Current password')) ?>

            <?= $form->field($model, 'newPassword')->passwordInput([
                'autocomplete' => 'new-password',
                'dir' => 'ltr',
                'data-password-input' => true,
                'data-password-strength' => true,
                'data-password-show-label' => Yii::t('app', 'Show password'),
                'data-password-hide-label' => Yii::t('app', 'Hide password'),
            ]) ?>

            <div class="password-strength" data-password-strength-meter hidden data-password-strength-labels="<?= Html::encode(Json::encode([
                Yii::t('app', 'Very weak'),
                Yii::t('app', 'Weak'),
                Yii::t('app', 'Good'),
                Yii::t('app', 'Strong'),
                Yii::t('app', 'Very strong'),
            ])) ?>">
                <span class="password-strength-track" aria-hidden="true"><span class="password-strength-bar"></span></span>
                <span class="password-strength-label" data-password-strength-label aria-live="polite"></span>
            </div>

            <?= $form->field($model, 'repeatPassword')->passwordInput([
                'autocomplete' => 'new-password',
                'dir' => 'ltr',
                'data-password-input' => true,
                'data-password-show-label' => Yii::t('app', 'Show password'),
                'data-password-hide-label' => Yii::t('app', 'Hide password'),
            ])->label(Yii::t('app', 'Repeat new password')) ?>

            <div class="form-actions">
                <?= Html::submitButton(Icon::show('save') . Yii::t('app', 'Save changes'), ['class' => 'btn']) ?>
            </div>
        </section>
        <?php ActiveForm::end(); ?>

        <aside class="change-password-aside">
            <section class="card change-password-requirements">
                <h2><?= Icon::show('shield') . Yii::t('app', 'Password requirements') ?></h2>
                <ul class="password-requirements-list">
                    <?php foreach ($requirements as $requirement): ?>
                        <li data-requirement><?= Icon::show('check') ?><span><?= Html::encode($requirement) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="card change-password-tips">
                <h2><?= Icon::show('info') . Yii::t('app', 'Security tips') ?></h2>
                <ul>
                    <li><?= Icon::show('check') . Yii::t('app', 'Use a long phrase instead of a single word.') ?></li>
                    <li><?= Icon::show('check') . Yii::t('app', 'Avoid names, birthdays and reused passwords.') ?></li>
                    <li><?= Icon::show('check') . Yii::t('app', 'Never share your password with anyone.') ?></li>
                </ul>
            </section>

            <p class="change-password-footnote">
                <?= Icon::show('info') ?>
                <span><?= Yii::t('app', 'Your password is stored hashed and never visible to administrators.') ?></span>
                <small><?= Yii::t('app', 'Last updated') ?>: <span class="ltr"><?= Html::encode($passwordUpdatedAt) ?></span></small>
            </p>
        </aside>
    </div>
</div>
