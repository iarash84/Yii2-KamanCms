<?php

use yii\db\Migration;

class m260912_000001_add_media_folders extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%media}}', 'folder', $this->string(120)->notNull()->defaultValue(''));
        $this->createIndex('idx-media-folder-created', '{{%media}}', ['folder', 'created_at']);
    }

    public function safeDown()
    {
        $this->dropIndex('idx-media-folder-created', '{{%media}}');
        $this->dropColumn('{{%media}}', 'folder');
    }
}