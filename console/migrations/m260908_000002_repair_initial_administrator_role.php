<?php

use common\models\User;
use yii\db\Migration;

class m260908_000002_repair_initial_administrator_role extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $activeUsers = User::find()->where(['status' => User::STATUS_ACTIVE])->orderBy(['id' => SORT_ASC])->all();

        if (count($activeUsers) !== 1) {
            return;
        }

        $administrator = $activeUsers[0];
        $roles = $auth->getRolesByUser($administrator->id);
        if (isset($roles['superAdmin'])) {
            return;
        }

        $superAdmin = $auth->getRole('superAdmin');
        if ($superAdmin === null) {
            throw new \RuntimeException('The superAdmin RBAC role is missing.');
        }

        $auth->revokeAll($administrator->id);
        $auth->assign($superAdmin, $administrator->id);
    }

    public function safeDown()
    {
        echo "m260908_000002_repair_initial_administrator_role cannot be safely reverted.\n";
        return false;
    }
}