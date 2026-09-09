<?php

namespace frontend\modules\admin;

use Yii;
use yii\web\ForbiddenHttpException;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'frontend\modules\admin\controllers';
    public $defaultRoute = 'dashboard/index';

    public function beforeAction($action)
    {
        if (Yii::$app->user->isGuest) {
            Yii::$app->user->setReturnUrl(Yii::$app->request->url);
            Yii::$app->response->redirect(['/site/login']);
            return false;
        }

        $permission = $this->requiredPermission($action->controller->id, $action->id);
        if (!Yii::$app->user->can('accessAdmin') || !Yii::$app->user->can($permission)) {
            throw new ForbiddenHttpException(Yii::t('app', 'Forbidden Http Exception'));
        }

        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        try {
            $request = Yii::$app->request;
            $audit = new \frontend\models\AdminAudit([
                'user_id' => Yii::$app->user->id, 'route' => $action->uniqueId,
                'action' => $action->id, 'method' => $request->method,
                'details' => json_encode(['query' => $request->getQueryParams()], JSON_UNESCAPED_UNICODE),
                'ip' => $request->userIP, 'user_agent' => mb_substr((string) $request->userAgent, 0, 500), 'created_at' => time(),
            ]);
            $audit->save(false);
        } catch (\Throwable $e) {
            Yii::warning('Admin audit could not be recorded: ' . $e->getMessage(), __METHOD__);
        }
        return parent::afterAction($action, $result);
    }

    private function requiredPermission($controllerId, $actionId)
    {
        if ($controllerId === 'dashboard') {
            return 'accessAdmin';
        }
        if ($controllerId === 'analytics') {
            return 'viewAnalytics';
        }
        if ($controllerId === 'user' && in_array($actionId, ['change', 'profile'], true)) {
            return 'accessAdmin';
        }

        if ($controllerId === 'user') {
            return 'manageUsers';
        }

        if ($controllerId === 'menu') {
            return 'manageMenus';
        }
        if ($controllerId === 'page') {
            return 'managePages';
        }
        if ($controllerId === 'media') {
            return 'manageMedia';
        }
        if ($controllerId === 'audit') { return 'viewAudit'; }
        if ($controllerId === 'export') { return 'exportData'; }
        if ($controllerId === 'backup') { return 'manageBackup'; }
        if ($controllerId === 'setting' && in_array($actionId, ['system', 'flush', 'clear', 'maintenance', 'email'], true)) { return 'manageSystem'; }

        if (in_array($controllerId, ['blog', 'category', 'carousel', 'sample', 'faqs', 'home-section'], true)) {
            return 'manageContent';
        }

        if (in_array($controllerId, ['contact', 'order', 'opportunity'], true)) {
            return 'viewSubmissions';
        }

        return 'manageSettings';
    }
}
