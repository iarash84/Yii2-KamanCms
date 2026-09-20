<?php

namespace frontend\modules\admin\controllers;

use frontend\services\BackupService;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\UploadedFile;

class BackupController extends Controller
{
    public function behaviors()
    {
        return ['verbs' => ['class' => VerbFilter::class,'actions' => ['create' => ['post'],'restore' => ['post']]]];
    }
    public function actionIndex()
    {
        return $this->render('index');
    }
    public function actionCreate()
    {
        return Yii::$app->response->sendContentAsFile(BackupService::create(), 'database-backup-' . date('Ymd-His') . '.json', ['mimeType' => 'application/json']);
    }
    public function actionRestore()
    {
        $file = UploadedFile::getInstanceByName('backupFile');
        $isJson = $file !== null && strtolower((string) $file->extension) === 'json';
        $isWithinLimit = $file !== null && (int) $file->size <= 50 * 1024 * 1024;

        if (!$file || !$isWithinLimit || !$isJson || $file->error !== UPLOAD_ERR_OK) {
            throw new \yii\web\BadRequestHttpException(Yii::t('app', 'Select a valid backup file.'));
        }

        $contents = file_get_contents($file->tempName);
        if ($contents === false || trim($contents) === '') {
            throw new \yii\web\BadRequestHttpException(Yii::t('app', 'The backup file could not be read.'));
        }

        try {
            BackupService::restore($contents);
        } catch (\JsonException | \RuntimeException $exception) {
            Yii::warning('Backup restore rejected: ' . $exception->getMessage(), __METHOD__);
            throw new \yii\web\BadRequestHttpException(Yii::t('app', 'The backup file is invalid.'));
        }

        Yii::$app->session->setFlash('success', Yii::t('app', 'Backup restored successfully.'));
        return $this->redirect(['index']);
    }
}
