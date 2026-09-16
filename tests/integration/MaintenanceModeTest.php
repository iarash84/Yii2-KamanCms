<?php

namespace tests\integration;

use frontend\models\SystemSetting;
use tests\Support\DatabaseTestCase;
use Yii;
use yii\web\HttpException;

class MaintenanceModeTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->cache->flush();
    }

    protected function tearDown(): void
    {
        SystemSetting::put('maintenance_enabled', '0');
        Yii::$app->cache->flush();
        parent::tearDown();
    }

    private function triggerBeforeRequest(string $path): void
    {
        $request = Yii::$app->request;
        $request->setUrl('/' . ltrim($path, '/'));
        $request->setPathInfo(ltrim($path, '/'));
        Yii::$app->trigger(\yii\base\Application::EVENT_BEFORE_REQUEST);
    }

    /**
     * تست: در حالت تعمیرات، مسیرهای عمومی باید با خطای 503 مسدود شوند.
     *
     * هدر Retry-After نیز باید برابر 3600 باشد (بازگشت خودکار پس از یک ساعت).
     */
    public function testMaintenanceEnabledThrows503ForPublicPath(): void
    {
        SystemSetting::put('maintenance_enabled', '1');
        Yii::$app->cache->flush();

        try {
            $this->triggerBeforeRequest('fa/home');
            self::fail('Expected HttpException 503 was not thrown.');
        } catch (HttpException $e) {
            self::assertSame(503, $e->statusCode);
            self::assertSame('3600', Yii::$app->response->headers->get('Retry-After'));
        }
    }

    /**
     * تست: مسیرهای ادمین از حالت تعمیرات مستثنی هستند.
     *
     * مدیران باید بتوانند در حین نگهداری همچنان وارد پنل مدیریت شوند.
     */
    public function testAdminPathIsExemptFromMaintenance(): void
    {
        SystemSetting::put('maintenance_enabled', '1');
        Yii::$app->cache->flush();

        // Must not throw: admin routes are always accessible
        $this->triggerBeforeRequest('admin/dashboard/index');
        self::assertTrue(true);
    }

    /**
     * تست: مسیرهای ورود (login) و صفحه خطا (error) از حالت تعمیرات مستثنی هستند.
     *
     * کاربر باید بتواند وارد شود و صفحات خطا هم باید قابل نمایش باشند.
     */
    public function testLoginAndErrorPathsAreExemptFromMaintenance(): void
    {
        SystemSetting::put('maintenance_enabled', '1');
        Yii::$app->cache->flush();

        $this->triggerBeforeRequest('login');
        $this->triggerBeforeRequest('error');
        self::assertTrue(true);
    }

    /**
     * تست: وقتی حالت تعمیرات غیرفعال است هیچ درخواستی مسدود نمی‌شود.
     */
    public function testDisabledMaintenanceDoesNotInterruptRequests(): void
    {
        SystemSetting::put('maintenance_enabled', '0');
        Yii::$app->cache->flush();

        $this->triggerBeforeRequest('fa/home');
        self::assertTrue(true);
    }

    /**
     * تست: کاربر احرازشده (واردشده) از حالت تعمیرات مستثنی است.
     *
     * حتی در حالت تعمیرات فعال، کاربرِ لاگین‌شده باید به سایت دسترسی داشته باشد.
     */
    public function testAuthenticatedUserIsExemptFromMaintenance(): void
    {
        $admin = $this->createUser('admin', 'maintenance-admin');
        Yii::$app->user->login($admin);
        SystemSetting::put('maintenance_enabled', '1');
        Yii::$app->cache->flush();

        $this->triggerBeforeRequest('fa/home');
        self::assertFalse(Yii::$app->user->isGuest);
    }
}
