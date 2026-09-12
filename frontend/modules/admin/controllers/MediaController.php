<?php

namespace frontend\modules\admin\controllers;

use common\components\SecureUpload;
use frontend\models\Media;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class MediaController extends Controller
{
    public function behaviors()
    {
        return ['verbs' => ['class' => VerbFilter::class, 'actions' => ['delete' => ['post'], 'delete-selected' => ['post'], 'update-alt' => ['post']]]];
    }

    public function actionIndex()
    {
        $model = new Media();
        $folderParameter = Yii::$app->request->get('folder');
        $folder = $folderParameter === null ? null : ($folderParameter === '__root__' ? '' : SecureUpload::normalizeMediaFolder($folderParameter));
        if (Yii::$app->request->isPost) {
            if (Yii::$app->request->post('saveAlt') !== null) {
                foreach ((array) Yii::$app->request->post('alt_text', []) as $id => $altText) {
                    $altModel = Media::findOne((int) $id);
                    if ($altModel !== null) {
                        $altModel->updateAttributes(['alt_text' => trim((string) $altText)]);
                    }
                }
                Yii::$app->session->setFlash('success', Yii::t('app', 'Alternative text updated.'));
                return $this->redirect($this->mediaIndexRoute(Yii::$app->request->post('folder')));
            }
            if (($altId = Yii::$app->request->post('updateAlt')) !== null) {
                $altModel = Media::findOne((int) $altId);
                if ($altModel !== null) {
                    $altTexts = (array) Yii::$app->request->post('alt_text', []);
                    $altModel->alt_text = trim((string) ($altTexts[$altId] ?? ''));
                    if ($altModel->save(true, ['alt_text'])) {
                        Yii::$app->session->setFlash('success', Yii::t('app', 'Alternative text updated.'));
                    }
                }
                return $this->redirect($this->mediaIndexRoute(Yii::$app->request->post('folder')));
            }
            $file = UploadedFile::getInstanceByName('mediaFile');
            if ($file === null) {
                $model->addError('path', Yii::t('app', 'Select a file to upload.'));
            } else {
                $folder = SecureUpload::normalizeMediaFolder(Yii::$app->request->post('folder', ''));
                $path = SecureUpload::storeMedia($file, $folder);
                $absolutePath = Yii::getAlias('@webroot/' . $path);
                $model->setAttributes([
                    'path' => $path, 'folder' => $folder, 'original_name' => basename(FileHelper::normalizePath($file->name)),
                    'mime_type' => FileHelper::getMimeType($absolutePath), 'extension' => pathinfo($path, PATHINFO_EXTENSION),
                    'size' => $file->size, 'alt_text' => Yii::$app->request->post('altText'),
                    'created_by' => Yii::$app->user->id,
                ], false);
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', Yii::t('app', 'Media uploaded.'));
                    return $this->redirect($this->mediaIndexRoute($folder));
                }
                @unlink(Yii::getAlias('@webroot/' . $path));
            }
        }
        return $this->render('index', [
            'model' => $model,
            'folder' => $folder,
            'folders' => Media::find()->select('folder')->distinct()->andWhere(['not', ['folder' => '']])->orderBy(['folder' => SORT_ASC])->column(),
            'dataProvider' => new ActiveDataProvider(['query' => Media::find()->andFilterWhere($folder === null ? [] : ['folder' => $folder])->orderBy(['created_at' => SORT_DESC])]),
        ]);
    }

    public function actionDeleteSelected()
    {
        $ids = array_filter(array_map('intval', (array) Yii::$app->request->post('mediaIds', [])));
        foreach (Media::find()->where(['id' => $ids])->all() as $model) {
            $this->deleteMedia($model);
        }
        Yii::$app->session->setFlash('success', Yii::t('app', '{count} media files deleted.', ['count' => count($ids)]));
        return $this->redirect($this->mediaIndexRoute(Yii::$app->request->post('folder')));
    }

    public function actionDelete($id)
    {
        $model = Media::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
        $this->deleteMedia($model);
        Yii::$app->session->setFlash('success', Yii::t('app', 'Media deleted.'));
        return $this->redirect(['index']);
    }

    private function deleteMedia(Media $model)
    {
        if ($model->delete()) {
            $path = Yii::getAlias('@webroot/' . ltrim($model->path, '/'));
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function mediaIndexRoute($folder)
    {
        if ($folder === null || $folder === '') {
            return ['index'];
        }
        if ($folder === '__root__') {
            return ['index', 'folder' => '__root__'];
        }
        return ['index', 'folder' => SecureUpload::normalizeMediaFolder($folder)];
    }
}
