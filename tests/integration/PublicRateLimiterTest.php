<?php

namespace tests\integration;

use frontend\components\PublicRateLimiter;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\web\TooManyRequestsHttpException;

class PublicRateLimiterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->cache->flush();
    }

    /**
     * تست: درخواست‌های درونِ حد مجاز نباید هیچ استثنایی ایجاد کنند.
     *
     * سه بار فراخوانی enforce با حد مجاز ۳، همگی باید بدون خطا عبور کنند.
     */
    public function testAllowsRequestsWithinLimit(): void
    {
        $scope = 'test-scope-' . bin2hex(random_bytes(4));

        for ($i = 0; $i < 3; $i++) {
            PublicRateLimiter::enforce($scope, 3, 60);
        }

        self::assertTrue(true); // Reaching this point means no exception was thrown
    }

    /**
     * تست: عبور از حد مجاز باید TooManyRequestsHttpException (HTTP 429) پرتاب کند.
     *
     * پنج بار فراخوانی (حد مجاز ۵) مجاز است؛ فراخوانی ششم باید مسدود شود.
     */
    public function testThrowsWhenLimitIsExceeded(): void
    {
        Yii::$app->request->setPathInfo('');
        Yii::$app->request->setUrl('/');
        $scope = 'test-scope-' . bin2hex(random_bytes(4));

        for ($i = 0; $i < 5; $i++) {
            PublicRateLimiter::enforce($scope, 5, 60);
        }

        $this->expectException(TooManyRequestsHttpException::class);
        PublicRateLimiter::enforce($scope, 5, 60);
    }

    /**
     * تست: محدودیت نرخ باید بین scope های مختلف مستقل (ایزوله) باشد.
     *
     * پر کردن کامل حد مجازِ یک scope نباید روی scope دیگر اثری بگذارد
     * و scope دوم همچنان باید بتواند درخواست ارسال کند.
     */
    public function testDifferentScopesAreIsolated(): void
    {
        $first = 'test-isolated-' . bin2hex(random_bytes(4));
        $second = 'test-isolated-' . bin2hex(random_bytes(4));

        for ($i = 0; $i < 5; $i++) {
            PublicRateLimiter::enforce($first, 5, 60);
        }

        // A different scope must still be allowed
        PublicRateLimiter::enforce($second, 5, 60);

        self::assertTrue(true);
    }
}