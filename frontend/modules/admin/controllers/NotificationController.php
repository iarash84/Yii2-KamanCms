<?php

namespace frontend\modules\admin\controllers;

use frontend\services\NotificationService;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;

class NotificationController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['mark-all-read' => ['post']],
            ],
        ];
    }

    public function actionIndex($filter = 'all')
    {
        $unreadOnly = $filter === 'unread';
        return $this->render('index', [
            'items' => NotificationService::submissionItems($unreadOnly),
            'filter' => $unreadOnly ? 'unread' : 'all',
            'unreadCount' => count(NotificationService::submissionItems(true)),
        ]);
    }

    public function actionMarkAllRead()
    {
        NotificationService::markAllSubmissionsRead();
        Yii::$app->session->setFlash('success', Yii::t('app', 'All notifications marked as read.'));
        return $this->redirect(['index']);
    }
}