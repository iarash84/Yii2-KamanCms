<?php

namespace common\components;

use Yii;
use yii\base\DynamicModel;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\UploadedFile;

class SecureUpload
{
    public static function storeImage(UploadedFile $file)
    {
        self::validate($file, ['png', 'jpg', 'jpeg', 'gif', 'webp'], [
            'image/png', 'image/jpeg', 'image/gif', 'image/webp',
        ], 5 * 1024 * 1024);
        $relative = 'upload/image/' . Yii::$app->security->generateRandomString(32) . '.' . strtolower($file->extension);
        self::save($file, Yii::getAlias('@webroot/' . $relative));
        return $relative;
    }

    public static function storeAvatar(UploadedFile $file)
    {
        self::validate($file, ['png', 'jpg', 'jpeg', 'webp'], [
            'image/png', 'image/jpeg', 'image/webp',
        ], 2 * 1024 * 1024);
        $relative = 'upload/avatar/' . Yii::$app->security->generateRandomString(32)
            . '.' . strtolower($file->extension);
        self::save($file, Yii::getAlias('@webroot/' . $relative));
        return $relative;
    }

    public static function deleteAvatar($relativePath)
    {
        $relativePath = str_replace('\\', '/', (string) $relativePath);
        if (!preg_match('#^upload/avatar/[A-Za-z0-9_-]{32}\.(?:png|jpe?g|webp)$#i', $relativePath)) {
            return;
        }

        $path = Yii::getAlias('@webroot/' . $relativePath);
        if (is_file($path)) {
            unlink($path);
        }
    }

    public static function storeResume(UploadedFile $file)
    {
        self::validate($file, ['pdf'], ['application/pdf'], 5 * 1024 * 1024);
        $name = Yii::$app->security->generateRandomString(40) . '.pdf';
        self::save($file, Yii::getAlias('@storage/resumes/' . $name));
        return $name;
    }

    public static function storeMedia(UploadedFile $file)
    {
        self::validate($file, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'pdf'], [
            'image/png', 'image/jpeg', 'image/gif', 'image/webp', 'application/pdf',
        ], 10 * 1024 * 1024);
        $extension = strtolower($file->extension);
        $relative = 'upload/media/' . Yii::$app->security->generateRandomString(40) . '.' . $extension;
        self::save($file, Yii::getAlias('@webroot/' . $relative));
        return $relative;
    }

    private static function validate($file, array $extensions, array $mimeTypes, $maxSize)
    {
        $model = DynamicModel::validateData(['file' => $file], [[
            'file', 'file', 'extensions' => $extensions, 'mimeTypes' => $mimeTypes,
            'maxSize' => $maxSize, 'checkExtensionByMimeType' => true,
        ]]);
        if ($model->hasErrors()) {
            throw new BadRequestHttpException(implode(' ', $model->getFirstErrors()));
        }
    }

    private static function save(UploadedFile $file, $path)
    {
        FileHelper::createDirectory(dirname($path), 0750, true);
        if (!$file->saveAs($path, !YII_ENV_TEST)) {
            throw new BadRequestHttpException(Yii::t('app', 'File upload failed.'));
        }
    }
}
