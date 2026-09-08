<?php

namespace tests\integration;

use frontend\models\VisitorReport;
use frontend\models\Contact;
use frontend\models\Order;
use tests\Support\DatabaseTestCase;
use Yii;

class VisitorAnalyticsTest extends DatabaseTestCase
{
    public function testAnalyticsPermissionBelongsToAdministratorsButNotEditors(): void
    {
        $editor = $this->createUser('editor', 'analytics-editor');
        self::assertFalse(Yii::$app->authManager->checkAccess($editor->id, 'viewAnalytics'));

        $admin = $this->createUser('admin', 'analytics-admin');
        self::assertTrue(Yii::$app->authManager->checkAccess($admin->id, 'viewAnalytics'));

        $superAdmin = $this->createUser('superAdmin', 'analytics-super-admin');
        self::assertTrue(Yii::$app->authManager->checkAccess($superAdmin->id, 'viewAnalytics'));
    }

    public function testDashboardReportAggregatesVisitsWithoutPersonalData(): void
    {
        $today = gmdate('Y-m-d');
        Yii::$app->db->createCommand()->insert('{{%visitor_daily}}', [
            'visit_date' => $today, 'page_views' => 12, 'visitors' => 7,
        ])->execute();
        Yii::$app->db->createCommand()->insert('{{%visitor_country_daily}}', [
            'visit_date' => $today, 'country_code' => 'IR', 'page_views' => 8, 'visitors' => 5,
        ])->execute();
        Yii::$app->db->createCommand()->insert('{{%visitor_page_daily}}', [
            'visit_date' => $today, 'path' => '/fa/blog', 'page_views' => 6, 'visitors' => 4,
        ])->execute();
        self::assertTrue((new Contact(['name' => 'Lead', 'email' => 'lead@example.test']))->save());
        self::assertTrue((new Order(['name' => 'Buyer', 'email' => 'buyer@example.test']))->save());
        $report = VisitorReport::dashboard(30);
        self::assertSame(12, $report['totals']['page_views']);
        self::assertSame(7, $report['totals']['visitors']);
        self::assertSame(2, $report['totals']['inquiries']);
        self::assertSame(28.6, $report['totals']['inquiry_rate']);
        self::assertSame('IR', $report['countries'][0]['country_code']);
        self::assertSame('/fa/blog', $report['pages'][0]['path']);

        $columns = Yii::$app->db->schema->getTableSchema('{{%visitor_daily}}')->columnNames;
        self::assertNotContains('ip', $columns);
        self::assertNotContains('user_agent', $columns);
    }
}
