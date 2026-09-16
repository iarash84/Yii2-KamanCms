<?php

namespace tests\integration;

use frontend\models\DashboardPreference;
use frontend\modules\admin\controllers\DashboardController;
use tests\Support\DatabaseTestCase;
use Yii;
use yii\web\BadRequestHttpException;

/**
 * ذخیره چیدمان داشبورد (DashboardController::actionLayout).
 *
 * این کلاس اکشن POST-only را پوشش می‌دهد:
 * - ذخیره چیدمان معتبر و نرمال‌سازی ویجت‌ها در جدول dashboard_preference
 * - پاسخ JSON موفقیت‌آمیز با چیدمان نرمال‌شده
 - رد چیدمان نامعتبر (JSON ناخوانا) با BadRequestHttpException
 * - به‌روزرسانی چیدمان موجود به‌جای ایجاد رکورد تکراری
 * - رد درخواست GET توسط VerbFilter
 */
class DashboardLayoutTest extends DatabaseTestCase
{
    /**
     * تست: ذخیره چیدمان معتبر باید رکورد dashboard_preference را برای همان کاربر بسازد.
     *
     * یک کاربر لاگین می‌شود و با POST چیدمانی شامل order و hidden ارسال می‌شود؛
     * چیدمان نرمال‌شده باید پاسخ JSON و رکورد پایگاه داده را مطابقت دهد.
     */
    public function testValidLayoutIsPersistedAndReturnedAsJson(): void
    {
        $user = $this->createUser('admin');
        self::assertTrue(Yii::$app->user->login($user));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams([
            'layout' => json_encode([
                'order' => ['analytics', 'unknown', 'metrics'],
                'hidden' => ['unknown'],
                'collapsed' => ['analytics'],
                'quick_links' => ['media', 'invalid', 'settings'],
            ], JSON_THROW_ON_ERROR),
        ]);
        Yii::$app->response->clear();

        $controller = new DashboardController('dashboard', Yii::$app->getModule('admin'));
        /** @var \yii\web\Response $response */
        $response = $controller->runAction('layout');

        self::assertNotNull($response);
        self::assertNotNull($response->data);
        $payload = $response->data;
        self::assertTrue($payload['success']);
        self::assertSame(['analytics', 'metrics', 'quick_actions', 'recent_activity', 'system_status'], $payload['layout']['order']);
        self::assertSame([], $payload['layout']['hidden']);
        self::assertSame(['analytics'], $payload['layout']['collapsed']);
        self::assertSame(['media', 'settings'], $payload['layout']['quick_links']);

        $saved = DashboardPreference::findOne($user->id);
        self::assertNotNull($saved);
        self::assertSame($payload['layout'], json_decode($saved->layout_json, true));
    }

    /**
     * تست: چیدمان نامعتبر (JSON ناخوانا) باید با BadRequestHttpException رد شود.
     */
    public function testInvalidJsonLayoutIsRejected(): void
    {
        $user = $this->createUser('admin');
        self::assertTrue(Yii::$app->user->login($user));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams(['layout' => '{not-valid-json']);

        $controller = new DashboardController('dashboard', Yii::$app->getModule('admin'));

        $this->expectException(BadRequestHttpException::class);
        $controller->runAction('layout');
    }

    /**
     * تست: ذخیره مجدد چیدمان باید رکورد موجود را به‌روزرسانی کند نه اینکه رکورد تکراری بسازد.
     */
    public function testSavingAgainUpdatesExistingPreference(): void
    {
        $user = $this->createUser('admin');
        self::assertTrue(Yii::$app->user->login($user));

        $first = new DashboardPreference([
            'user_id' => $user->id,
            'layout_json' => json_encode(['order' => [], 'hidden' => []], JSON_THROW_ON_ERROR),
            'updated_at' => time() - 1000,
        ]);
        self::assertTrue($first->save());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams([
            'layout' => json_encode(['order' => ['system_status']], JSON_THROW_ON_ERROR),
        ]);
        Yii::$app->response->clear();

        $controller = new DashboardController('dashboard', Yii::$app->getModule('admin'));
        $response = $controller->runAction('layout');

        self::assertCount(1, DashboardPreference::find()->where(['user_id' => $user->id])->all());
        self::assertNotNull($response);
        self::assertNotNull($response->data);
        self::assertSame('system_status', $response->data['layout']['order'][0]);
    }

    /**
     * تست: اکشن layout فقط POST می‌پذیرد و درخواست GET باید با خطای MethodNotAllowed رد شود.
     */
    public function testGetRequestToLayoutIsRejected(): void
    {
        $user = $this->createUser('admin');
        self::assertTrue(Yii::$app->user->login($user));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        Yii::$app->response->clear();

        $controller = new DashboardController('dashboard', Yii::$app->getModule('admin'));

        $this->expectException(\yii\web\MethodNotAllowedHttpException::class);
        $controller->runAction('layout');
    }
}
