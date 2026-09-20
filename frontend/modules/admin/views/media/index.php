<?php
use frontend\widgets\Icon;
use frontend\widgets\AdminActionColumn;
use frontend\widgets\AdminButton;
use yii\grid\CheckboxColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
$this->title = Yii::t('app', 'Media library'); $this->params['breadcrumbs'][] = $this->title;
?>
<div class="media-index">
    <div class="page-header"><p class="text-overline"><?= Yii::t('app', 'Files and images') ?></p><h1><?= Html::encode($this->title) ?></h1></div>
    <div class="card media-upload">
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
        <?= $form->errorSummary($model) ?>
        <div class="form-grid"><div class="form-group"><label for="media-file"><?= Yii::t('app', 'Select file') ?></label><?= Html::fileInput('mediaFile', null, ['id' => 'media-file', 'accept' => 'image/png,image/jpeg,image/gif,image/webp,application/pdf', 'required' => true]) ?></div><div class="form-group"><label for="media-folder"><?= Yii::t('app', 'Folder') ?></label><?= Html::textInput('folder', $folder ?? '', ['id' => 'media-folder', 'class' => 'form-control', 'placeholder' => Yii::t('app', 'For example: banners')]) ?></div><div class="form-group"><label for="media-alt"><?= Yii::t('app', 'Alternative text') ?></label><?= Html::textInput('altText', null, ['id' => 'media-alt', 'class' => 'form-control']) ?></div></div>
        <?= AdminButton::submit(Icon::show('upload', ['width' => 18, 'height' => 18]) . Yii::t('app', 'Upload')) ?>
        <?php ActiveForm::end(); ?>
    </div>
    <?php $folderRoutes = [Url::to(['index']) => Yii::t('app', 'All folders'), Url::to(['index', 'folder' => '__root__']) => Yii::t('app', 'Root')]; foreach ($folders as $folderName) { $folderRoutes[Url::to(['index', 'folder' => $folderName])] = $folderName; } $selectedFolderRoute = $folder === null ? Url::to(['index']) : Url::to(['index', 'folder' => $folder === '' ? '__root__' : $folder]); ?>
    <div class="media-library-toolbar"><div><strong><?= Yii::t('app', 'Media files') ?></strong><span class="text-muted"><?= Yii::t('app', 'Edit alternative text directly in the list.') ?></span></div><?= Html::dropDownList('folderRoute', $selectedFolderRoute, $folderRoutes, ['class' => 'form-control media-folder-filter', 'onchange' => 'window.location.href = this.value']) ?></div>
    <?= Html::beginForm(['index'], 'post', ['class' => 'media-bulk-form']) ?>
    <?= Html::hiddenInput('folder', $folder === null ? '' : ($folder === '' ? '__root__' : $folder)) ?>
    <?= GridView::widget(['dataProvider' => $dataProvider, 'tableOptions' => ['class' => 'striped responsive-table media-table'], 'columns' => [
        ['class' => CheckboxColumn::class, 'name' => 'mediaIds[]', 'headerOptions' => ['class' => 'media-select-column'], 'contentOptions' => ['class' => 'media-select-column'], 'checkboxOptions' => static fn ($item) => ['value' => $item->id]],
        ['label' => Yii::t('app', 'Preview'), 'format' => 'raw', 'contentOptions' => ['data-label' => Yii::t('app', 'Preview')], 'value' => static function ($item) {
            if (!$item->getIsImage()) {
                return Icon::show('posts');
            }
            $alt = $item->alt_text ?: $item->original_name;
            return Html::button(Html::img($item->getUrl(), ['class' => 'carousel-thumbnail-image', 'alt' => Html::encode($alt)]), [
                'type' => 'button',
                'class' => 'carousel-thumbnail media-preview-button',
                'data-image-preview' => $item->getUrl(),
                'data-image-alt' => $alt,
                'title' => Yii::t('app', 'View full-size image'),
                'aria-label' => Yii::t('app', 'View full-size image'),
            ]);
        }],
        ['attribute' => 'original_name', 'label' => Yii::t('app', 'File name'), 'contentOptions' => ['data-label' => Yii::t('app', 'File name')]], ['attribute' => 'folder', 'label' => Yii::t('app', 'Folder'), 'contentOptions' => ['data-label' => Yii::t('app', 'Folder')], 'value' => static fn ($item) => $item->folder ?: Yii::t('app', 'Root')],
        ['attribute' => 'alt_text', 'label' => Yii::t('app', 'Alternative text'), 'format' => 'raw', 'contentOptions' => ['data-label' => Yii::t('app', 'Alternative text')], 'value' => static fn ($item) => Html::textInput('alt_text[' . $item->id . ']', $item->alt_text, ['class' => 'form-control media-alt-input', 'aria-label' => Yii::t('app', 'Alternative text')])],
        ['attribute' => 'mime_type', 'contentOptions' => ['data-label' => Yii::t('app', 'File type')]], ['attribute' => 'size', 'contentOptions' => ['data-label' => Yii::t('app', 'File size')], 'value' => static fn ($item) => Yii::$app->formatter->asShortSize($item->size)],
        ['label' => Yii::t('app', 'Public URL'), 'format' => 'raw', 'contentOptions' => ['data-label' => Yii::t('app', 'Public URL')], 'value' => static fn ($item) => Html::textInput('', $item->getUrl(), ['class' => 'form-control ltr', 'readonly' => true])],
        ['class' => AdminActionColumn::class, 'template' => '{delete}', 'contentOptions' => ['class' => 'admin-table-actions-column', 'data-label' => Yii::t('app', 'Actions')], 'buttons' => ['delete' => static fn ($url, $item) => Html::a(Icon::show('trash'), $url, ['class' => AdminButton::classes('compact', 'admin-action-delete'), 'title' => Yii::t('app', 'Delete'), 'aria-label' => Yii::t('app', 'Delete'), 'data-confirm' => Yii::t('app', 'Are you sure you want to delete this item?'), 'data-method' => 'post'])]],
    ]]) ?>
    <div class="media-bulk-actions"><?= Html::submitButton(Icon::show('save') . Yii::t('app', 'Save alternative text'), ['class' => 'btn btn-secondary', 'name' => 'saveAlt', 'value' => '1']) ?><?= Html::submitButton(Icon::show('trash') . Yii::t('app', 'Delete selected'), ['class' => 'btn btn-danger', 'formaction' => Url::to(['delete-selected']), 'data-confirm' => Yii::t('app', 'Are you sure you want to delete the selected files?')]) ?></div>
    <?= Html::endForm() ?>
</div>
