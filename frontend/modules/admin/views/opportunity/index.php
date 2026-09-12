<?php

use frontend\models\Setting;
use frontend\widgets\Icon;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

$this->title = Yii::t('app', 'Job opportunity');
$this->params['breadcrumbs'][] = $this->title;
$setting = new Setting();
?>
<div class="page-header page-header-actions">
    <div>
        <p class="text-overline"><?= Yii::t('app', 'Requests') ?></p>
        <h1><?= Html::encode($this->title) ?></h1>
        <p><?= Yii::t('app', 'Review job applications and download the attached resumes.') ?></p>
    </div>
</div>

<section class="card opportunity-submissions-card">
    <div class="opportunity-submissions-heading">
        <span class="opportunity-submissions-icon" aria-hidden="true"><?= Icon::show('inbox') ?></span>
        <div>
            <h2><?= Yii::t('app', 'Request List') ?></h2>
            <p><?= Yii::t('app', 'Unread applications are highlighted so you can answer candidates faster.') ?></p>
        </div>
    </div>
    <?= $this->render('_requestList', ['dataProvider' => $dataProvider]) ?>
</section>

<div class="admin-tabs" data-admin-tabs>
    <div class="d-tabs d-tabs-box" role="tablist"><button type="button" class="d-tab d-tab-active" role="tab" aria-selected="true" data-tab-target="opportunity-preview"><?= Yii::t('app', 'View') ?></button><button type="button" class="d-tab" role="tab" aria-selected="false" data-tab-target="opportunity-edit"><?= Yii::t('app', 'Update') ?></button></div>
    <section id="opportunity-preview" class="card admin-tab-panel" role="tabpanel" data-tab-panel>
        <div class="opportunity-submissions-heading">
            <span class="opportunity-submissions-icon" aria-hidden="true"><?= Icon::show('pages') ?></span>
            <div>
                <h2><?= Yii::t('app', 'View') ?></h2>
                <p><?= Yii::t('app', 'This content is shown on the public job opportunity page.') ?></p>
            </div>
        </div>
        <div class="opportunity-page-preview prose"><?= HtmlPurifier::process($setting->opportunity) ?></div>
    </section>
    <section id="opportunity-edit" class="card admin-tab-panel" role="tabpanel" data-tab-panel hidden>
        <div class="opportunity-submissions-heading">
            <span class="opportunity-submissions-icon" aria-hidden="true"><?= Icon::show('edit') ?></span>
            <div>
                <h2><?= Yii::t('app', 'Update') ?></h2>
                <p><?= Yii::t('app', 'Edit the page introduction text for both languages.') ?></p>
            </div>
        </div>
        <?= $this->render('_update', ['model' => $model]) ?>
    </section>
</div>
