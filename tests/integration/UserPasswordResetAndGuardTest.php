<?php

namespace tests\integration;

use common\models\User;
use frontend\modules\admin\controllers\UserController;
use tests\Support\DatabaseTestCase;
use Yii;
use yii\web\BadRequestHttpException;

class UserPasswordResetAndGuardTest extends DatabaseTestCase
{
    /**
     * تست: توکن بازنشانی رمزِ معتبر و تازه باید پذیرفته شود.
     *
     * تولید توکن، معتبر بودن آن با isPasswordResetTokenValid و پیدا کردن کاربر
     * مرتبط از طریق findByPasswordResetToken بررسی می‌شود.
     */
    public function testValidResetTokenIsAccepted(): void
    {
        $user = $this->createUser('editor', 'reset-token-user');
        $user->generatePasswordResetToken();
        self::assertTrue($user->save());

        self::assertTrue(User::isPasswordResetTokenValid($user->password_reset_token));
        $found = User::findByPasswordResetToken($user->password_reset_token);
        self::assertNotNull($found);
        self::assertSame($user->id, $found->id);
    }

    /**
     * تست: توکن بازنشانی رمزِ منقضی‌شده باید رد شود.
     *
     * توکنی که بیش از ۳۶۰۰ ثانیه (مدت اعتبار تنظیم‌شده در پارامترها) عمر داشته
     * باشد، نامعتبر شناخته می‌شود و کاربر مرتبط پیدا نمی‌شود.
     */
    public function testExpiredResetTokenIsRejected(): void
    {
        $user = $this->createUser('editor', 'expired-token-user');
        $expired = 'expired-token_' . (time() - 7200); // older than the 3600s expiry
        $user->password_reset_token = $expired;
        self::assertTrue($user->save(false));

        self::assertFalse(User::isPasswordResetTokenValid($expired));
        self::assertNull(User::findByPasswordResetToken($expired));
    }

    /**
     * تست: توکن خالی یا بدساختار (بدون بخش زمان) باید رد شود.
     *
     * این کار از حملات با توکن‌های دستکاری‌شده جلوگیری می‌کند.
     */
    public function testEmptyAndMalformedTokensAreRejected(): void
    {
        self::assertFalse(User::isPasswordResetTokenValid(''));
        self::assertFalse(User::isPasswordResetTokenValid('token-without-timestamp'));
        self::assertNull(User::findByPasswordResetToken('not-a-real-token'));
    }

    /**
     * تست: توکن بازنشانی باید قابل تولید و حذف باشد.
     *
     * تولید توکن شامل بخش «_» (جداساز زمان) است و حذف آن مقدار را به null
     * برمی‌گرداند.
     */
    public function testResetTokenCanBeGeneratedAndRemoved(): void
    {
        $user = $this->createUser('editor', 'generate-remove-token');
        self::assertEmpty($user->password_reset_token);

        $user->generatePasswordResetToken();
        self::assertNotEmpty($user->password_reset_token);
        self::assertStringContainsString('_', (string) $user->password_reset_token);

        $user->removePasswordResetToken();
        self::assertNull($user->password_reset_token);
    }

    /**
     * تست: آخرین سوپرادمین نباید قابل تنزل نقش باشد.
     *
     * نگهبان guardLastSuperAdmin باید هنگام تلاش برای تنزل حیاتی‌ترین کاربر سیستم
     * استثنای BadRequestHttpException پرتاب کند.
     */
    public function testLastSuperAdminCannotBeDemoted(): void
    {
        $super = $this->createUser('superAdmin', 'last-super-admin');
        $controller = new UserController('user', Yii::$app->getModule('admin'));
        $method = new \ReflectionMethod(UserController::class, 'guardLastSuperAdmin');
        $method->setAccessible(true);

        $this->expectException(BadRequestHttpException::class);
        $method->invoke($controller, $super->id, 'editor');
    }

    /**
     * تست: سوپرادمین فقط وقتی قابل تنزل است که سوپرادمین دیگری وجود داشته باشد.
     *
     * با وجود دو سوپرادمین، تنزل نقش یکی از آن‌ها نباید خطا ایجاد کند.
     */
    public function testSuperAdminCanBeDemotedWhenAnotherExists(): void
    {
        $first = $this->createUser('superAdmin', 'first-super-admin');
        $second = $this->createUser('superAdmin', 'second-super-admin');
        $controller = new UserController('user', Yii::$app->getModule('admin'));
        $method = new \ReflectionMethod(UserController::class, 'guardLastSuperAdmin');
        $method->setAccessible(true);

        // Must not throw because another superAdmin still exists
        $method->invoke($controller, $first->id, 'editor');
        self::assertTrue(true);
    }

    /**
     * تست: کاربران غیرسوپرادمین بدون محدودیت قابل تنزل/حذف هستند.
     *
     * نگهبان فقط درباره آخرین سوپرادمین اعمال می‌شود و روی نقش‌های دیگر اثری ندارد.
     */
    public function testNonSuperAdminCanBeDemotedOrDeleted(): void
    {
        $editor = $this->createUser('editor', 'plain-editor');
        $controller = new UserController('user', Yii::$app->getModule('admin'));
        $method = new \ReflectionMethod(UserController::class, 'guardLastSuperAdmin');
        $method->setAccessible(true);

        // Must not throw for non-superAdmin
        $method->invoke($controller, $editor->id, 'editor');
        self::assertTrue(true);
    }
}