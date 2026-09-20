<?php

use yii\db\Migration;

class m260920_000001_add_resume_download_permission extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $permission = $auth->getPermission('downloadResumes');

        if ($permission === null) {
            $permission = $auth->createPermission('downloadResumes');
            $permission->description = 'دانلود رزومه درخواست‌های استخدامی';
            $auth->add($permission);
        }

        $admin = $auth->getRole('admin');
        if ($admin !== null && !$auth->hasChild($admin, $permission)) {
            $auth->addChild($admin, $permission);
        }

        $auth->invalidateCache();
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $permission = $auth->getPermission('downloadResumes');

        if ($permission !== null) {
            $auth->remove($permission);
        }

        $auth->invalidateCache();
    }
}
