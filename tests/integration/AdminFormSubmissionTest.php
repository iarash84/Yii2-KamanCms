<?php

namespace tests\integration;

use common\widgets\Alert;
use frontend\models\Faqs;
use frontend\models\SignupForm;
use frontend\models\SystemSetting;
use tests\Support\DatabaseTestCase;
use Yii;

class AdminFormSubmissionTest extends DatabaseTestCase
{
    public function testFaqCanBeCreatedWhenSortOrderIsLeftBlank(): void
    {
        $faq = new Faqs();
        self::assertTrue($faq->load(['Faqs' => [
            'question' => 'How does it work?', 'answer' => 'It works safely.',
            'sort_order' => '', 'status' => '1',
        ]]));
        $faq->user_id = $this->createUser()->id;
        self::assertTrue($faq->save(), json_encode($faq->errors));
        self::assertSame(0, (int) $faq->sort_order);
        self::assertTrue($faq->saveTranslations(['en' => [
            'question' => 'How does it work?', 'answer' => 'It works safely.',
        ]]));
    }

    public function testDateDisplaySettingAndSuccessAlertRenderWithoutError(): void
    {
        $admin = $this->createUser('admin', 'system-form-admin');
        self::assertTrue(Yii::$app->user->login($admin));
        self::assertTrue(SystemSetting::put('date_calendar', 'jalali'));
        Yii::$app->session->setFlash('success', Yii::t('app', 'Date display settings saved.'));
        $html = Alert::widget();
        self::assertStringContainsString(Yii::t('app', 'Date display settings saved.'), $html);
        self::assertSame('jalali', SystemSetting::getValue('date_calendar'));
    }

    public function testDatabaseUsesConsistentSnakeCaseNames(): void
    {
        $legacy = ['tbl_blog_category', 'tbl_blog_post', 'tbl_carousel', 'tbl_contact_us', 'tbl_faqs', 'tbl_log', 'tbl_opportunity', 'tbl_order', 'tbl_sample', 'tbl_setting'];
        foreach ($legacy as $table) {
            self::assertNull(Yii::$app->db->schema->getTableSchema($table, true));
        }
        $tables = ['blog_category', 'blog_post', 'carousel', 'contact_submission', 'faq', 'login_attempt', 'opportunity_submission', 'order_submission', 'portfolio_item', 'site_setting'];
        foreach ($tables as $table) {
            $schema = Yii::$app->db->schema->getTableSchema($table, true);
            self::assertNotNull($schema, "Missing standardized table: {$table}");
            foreach ($schema->columnNames as $column) {
                self::assertMatchesRegularExpression('/^[a-z][a-z0-9_]*$/', $column, "Non-standard column: {$table}.{$column}");
            }
        }
        $siteSetting = Yii::$app->db->schema->getTableSchema('site_setting', true);
        $uniqueTypeIndexes = array_filter(Yii::$app->db->schema->findUniqueIndexes($siteSetting), static function ($columns) {

                return $columns === ['type'];
        });
        self::assertNotEmpty($uniqueTypeIndexes);
    }

    public function testUserProfileFieldsCanBeMassAssignedAndValidated(): void
    {

        $user = $this->createUser('editor', 'old-username');
        self::assertTrue($user->load(['User' => [
            'username' => 'new-username',
            'full_name' => 'Example Editor',
            'email' => 'new@example.test',
            'job_title' => 'Content editor',
            'phone' => '+98 912 000 0000',
            'location' => 'Tehran',
            'website' => 'https://example.test',
            'bio' => 'Creates and reviews editorial content.',
            'role' => 'editor',
        ]]));
        self::assertTrue($user->validate(), json_encode($user->errors));
        self::assertSame('new-username', $user->username);
        self::assertSame('Example Editor', $user->full_name);
        self::assertSame('new@example.test', $user->email);
        self::assertSame('Content editor', $user->job_title);
        self::assertSame('+989120000000', $user->phone);
        self::assertSame('Tehran', $user->location);
        self::assertSame('https://example.test', $user->website);
        self::assertSame('Creates and reviews editorial content.', $user->bio);
    }

    public function testCompleteProfileIsPersistedWhenAnAdministratorCreatesAUser(): void
    {
        $form = new SignupForm([
            'username' => 'new-profile-user',
            'full_name' => 'New Profile User',
            'email' => 'new-profile-user@example.test',
            'password' => 'ValidPassword!2026',
            'role' => 'editor',
            'job_title' => 'Writer',
            'phone' => '+1 202 555 0147',
            'location' => 'Remote',
            'website' => 'https://example.test/profile',
            'bio' => 'Writes product documentation.',
        ]);

        $user = $form->signup();
        self::assertNotNull($user, json_encode($form->errors));
        self::assertSame('New Profile User', $user->full_name);
        self::assertSame('Writer', $user->job_title);
        self::assertSame('+12025550147', $user->phone);
        self::assertSame('Remote', $user->location);
        self::assertSame('https://example.test/profile', $user->website);
        self::assertSame('Writes product documentation.', $user->bio);
        self::assertArrayHasKey('editor', Yii::$app->authManager->getRolesByUser($user->id));
    }

    public function testProfileScenarioCannotChangeRoleOrUsername(): void
    {
        $user = $this->createUser('editor', 'profile-scope-user');
        $user->scenario = 'profile';

        self::assertTrue($user->load(['User' => [
            'username' => 'unauthorized-username',
            'role' => 'superAdmin',
            'full_name' => 'Profile Owner',
            'email' => 'profile-owner@example.test',
        ]]));
        self::assertTrue($user->validate(), json_encode($user->errors));
        self::assertSame('profile-scope-user', $user->username);
        self::assertNull($user->role);
        self::assertSame('Profile Owner', $user->full_name);
        self::assertSame('profile-owner@example.test', $user->email);
    }
}
