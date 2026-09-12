<?php

use yii\db\Migration;

class m260912_000002_optimize_public_content_indexes extends Migration
{
    public function safeUp()
    {
        $this->createIndex('idx-portfolio-created-id', '{{%portfolio_item}}', ['created_at', 'id']);
        $this->createIndex('idx-blog-post-created-id', '{{%blog_post}}', ['created_at', 'id']);
        $this->createIndex('idx-blog-post-category-created-id', '{{%blog_post}}', ['category_id', 'created_at', 'id']);

        foreach ([
            ['idx-carousel-status-order', '{{%carousel}}', ['status', 'sort_order', 'id']],
            ['idx-faq-status-order', '{{%faq}}', ['status', 'sort_order', 'id']],
            ['idx-home-section-status-order', '{{%home_section}}', ['status', 'sort_order', 'id']],
            ['idx-menu-item-location-status-order', '{{%menu_item}}', ['location', 'status', 'sort_order', 'id']],
        ] as [$name, $table, $columns]) {
            $this->dropIndex($name, $table);
            $this->createIndex($name, $table, $columns);
        }
    }

    public function safeDown()
    {
        $this->dropIndex('idx-blog-post-category-created-id', '{{%blog_post}}');
        $this->dropIndex('idx-blog-post-created-id', '{{%blog_post}}');
        $this->dropIndex('idx-portfolio-created-id', '{{%portfolio_item}}');

        foreach ([
            ['idx-menu-item-location-status-order', '{{%menu_item}}', ['location', 'status', 'sort_order']],
            ['idx-home-section-status-order', '{{%home_section}}', ['status', 'sort_order']],
            ['idx-faq-status-order', '{{%faq}}', ['status', 'sort_order']],
            ['idx-carousel-status-order', '{{%carousel}}', ['status', 'sort_order']],
        ] as [$name, $table, $columns]) {
            $this->dropIndex($name, $table);
            $this->createIndex($name, $table, $columns);
        }
    }
}