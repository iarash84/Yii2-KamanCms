<?php

namespace tests\integration;

use common\models\LoginForm;
use common\models\Log;
use tests\Support\DatabaseTestCase;
use Yii;

class LoginRateLimitTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->cache->flush();
    }

    /**
     * تست: هر تلاش ناموفق ورود باید در جدول لاگ (login_attempt) ثبت شود.
     *
     * یک تلاش با رمز اشتباه باید رکوردی با success=0 برای همان نام کاربری ایجاد کند.
     */
    public function testFailedAttemptsAreLogged(): void
    {
        $user = $this->createUser('editor', 'failed-log-user');
        $form = new LoginForm([
            'username' => $user->username,
            'password' => 'WrongPassword!2026',
            'rememberMe' => false,
        ]);

        self::assertFalse($form->login());
        self::assertSame(
            1,
            Log::find()->where(['username' => $user->username, 'success' => 0])->count()
        );
    }

    /**
     * تست: ورود موفق باید لاگ شود و شمارنده محدودیت نرخ را صفر کند.
     *
     * پس از ورود موفق، شمارنده پاک می‌شود؛ برای قفل‌شدن دوباره باید پنج تلاش ناموفق
     * جدید انجام شود.
     */
    public function testSuccessfulLoginIsLoggedAndClearsRateLimit(): void
    {
        $user = $this->createUser('editor', 'success-log-user');

        // Two failed attempts, then a successful login
        $failed = new LoginForm(['username' => $user->username, 'password' => 'WrongPassword!2026', 'rememberMe' => false]);
        self::assertFalse($failed->login());
        $failed = new LoginForm(['username' => $user->username, 'password' => 'WrongPassword!2026', 'rememberMe' => false]);
        self::assertFalse($failed->login());

        $success = new LoginForm(['username' => $user->username, 'password' => 'ValidPassword!2026', 'rememberMe' => false]);
        self::assertTrue($success->login(), json_encode($success->errors));

        // Login success is logged
        self::assertSame(
            1,
            Log::find()->where(['username' => $user->username, 'success' => 1])->count()
        );

        // Because the counter was cleared, five further failures are needed to lock again
        for ($i = 0; $i < 5; $i++) {
            $form = new LoginForm(['username' => $user->username, 'password' => 'WrongPassword!2026', 'rememberMe' => false]);
            self::assertFalse($form->login());
        }
        $blocked = new LoginForm(['username' => $user->username, 'password' => 'ValidPassword!2026', 'rememberMe' => false]);
        self::assertFalse($blocked->login());
        self::assertArrayHasKey('password', $blocked->errors);
    }

    /**
     * تست: پس از پنج تلاش ناموفق، ششمین تلاش (حتی با رمز صحیح) باید مسدود شود.
     *
     * پیام خطا باید «تلاش‌های ورود بیش از حد مجاز است» باشد و کاربر لاگین نشود.
     */
    public function testLoginIsBlockedAfterFiveFailedAttempts(): void
    {
        $user = $this->createUser('editor', 'blocked-login-user');

        for ($i = 0; $i < 5; $i++) {
            $form = new LoginForm(['username' => $user->username, 'password' => 'WrongPassword!2026', 'rememberMe' => false]);
            self::assertFalse($form->login());
        }

        // The sixth attempt, even with a correct password, must be rate-limited
        $blocked = new LoginForm(['username' => $user->username, 'password' => 'ValidPassword!2026', 'rememberMe' => false]);
        self::assertFalse($blocked->login());
        self::assertArrayHasKey('password', $blocked->errors);
        self::assertSame(
            Yii::t('app', 'Too many login attempts. Please try again later.'),
            $blocked->getFirstError('password')
        );
        self::assertTrue(Yii::$app->user->isGuest);
    }

    /**
     * تست: محدودیت نرخ باید به‌ازای هر کاربر مستقل باشد.
     *
     * قفل‌شدن کاربر اول نباید مانع ورود کاربر دیگر (با نام کاربری و IP متفاوت) شود.
     */
    public function testRateLimitIsPerUserWithDifferentIpIsolation(): void
    {
        $first = $this->createUser('editor', 'first-ip-user');
        $second = $this->createUser('editor', 'second-ip-user');

        // Lock out the first user by failing 5 times
        for ($i = 0; $i < 5; $i++) {
            $form = new LoginForm(['username' => $first->username, 'password' => 'WrongPassword!2026', 'rememberMe' => false]);
            $form->login();
        }

        // A different user must still be able to log in
        $ok = new LoginForm(['username' => $second->username, 'password' => 'ValidPassword!2026', 'rememberMe' => false]);
        self::assertTrue($ok->login());
    }
}
