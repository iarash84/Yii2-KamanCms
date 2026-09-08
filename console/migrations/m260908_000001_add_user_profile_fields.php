<?php

use yii\db\Migration;

class m260908_000001_add_user_profile_fields extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'full_name', $this->string(160)->null()->after('username'));
        $this->addColumn('{{%user}}', 'avatar', $this->string()->null()->after('email'));
        $this->addColumn('{{%user}}', 'job_title', $this->string(120)->null()->after('avatar'));
        $this->addColumn('{{%user}}', 'phone', $this->string(32)->null()->after('job_title'));
        $this->addColumn('{{%user}}', 'location', $this->string(160)->null()->after('phone'));
        $this->addColumn('{{%user}}', 'website', $this->string()->null()->after('location'));
        $this->addColumn('{{%user}}', 'bio', $this->text()->null()->after('website'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'bio');
        $this->dropColumn('{{%user}}', 'website');
        $this->dropColumn('{{%user}}', 'location');
        $this->dropColumn('{{%user}}', 'phone');
        $this->dropColumn('{{%user}}', 'job_title');
        $this->dropColumn('{{%user}}', 'avatar');
        $this->dropColumn('{{%user}}', 'full_name');
    }
}
