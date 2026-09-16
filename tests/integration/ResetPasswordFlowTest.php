<?php

namespace tests\integration;

use common\models\User;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use tests\Support\DatabaseTestCase;
use Yii;
use yii\base\InvalidParamException;

class ResetPasswordFlowTest extends DatabaseTestCase
{
    /**
     * تست: فرم درخواست بازنشانی، ایمیلِ نامعتبر را رد می‌کند.
     *
     * مقدار غیرایمیل باید خطای اعتبارسنجی فیلد email تولید کند.
     */
    public function testRequestFormValidatesEmail(): void
    {
        $form = new PasswordResetRequestForm(['email' => 'not-an-email']);
        self::assertFalse($form->validate());
        self::assertArrayHasKey('email', $form->errors);
    }

    /**
     * تست: درخواست بازنشانی برای ایمیلِ ناشناخته نباید خطا بدهد (مقاوم در برابر شمارش کاربران).
     *
     * برای جلوگیری از افشای وجود کاربران، برای ایمیلِ ناموجود همانند موفقیت true
     * برگردانده می‌شود بدون اینکه ایمیلی ارسال شود.
     */
    public function testRequestForUnknownEmailDoesNotError(): void
    {
        $form = new PasswordResetRequestForm(['email' => 'nobody@example.test']);
        self::assertTrue($form->validate());
        self::assertTrue($form->sendEmail()); // returns true without sending
    }

    /**
     * تست: برای کاربر فعال، ایمیلِ درخواست بازنشانی باید ارسال و توکن تولید شود.
     *
     * پس از ارسال، فیلد password_reset_token کاربر باید پر و معتبر باشد.
     */
    public function testRequestGeneratesTokenForActiveUser(): void
    {
        $user = $this->createUser('editor', 'reset-request-user');
        $user->email = 'reset-request@example.test';
        self::assertTrue($user->save(false));

        $form = new PasswordResetRequestForm(['email' => 'reset-request@example.test']);
        self::assertTrue($form->validate());

        $sent = Yii::$app->mailer->useFileTransport;
        Yii::$app->mailer->useFileTransport = true;
        try {
            self::assertTrue($form->sendEmail());
        } finally {
            Yii::$app->mailer->useFileTransport = $sent;
        }

        $reloaded = User::findOne($user->id);
        self::assertNotEmpty($reloaded->password_reset_token);
        self::assertTrue(User::isPasswordResetTokenValid($reloaded->password_reset_token));
    }

    /**
     * تست: فرم بازنشانی با توکن خالی باید InvalidParamException پرتاب کند.
     */
    public function testResetFormRejectsBlankToken(): void
    {
        $this->expectException(InvalidParamException::class);
        new ResetPasswordForm('');
    }

    /**
     * تست: فرم بازنشانی با توکن جعلی/نامعتبر باید InvalidParamException پرتاب کند.
     */
    public function testResetFormRejectsInvalidToken(): void
    {
        $this->expectException(InvalidParamException::class);
        new ResetPasswordForm('invalid_token_' . (time() - 999999));
    }

    /**
     * تست: فرم بازنشانی با توکن منقضی‌شده باید InvalidParamException پرتاب کند.
     */
    public function testResetFormRejectsExpiredToken(): void
    {
        $user = $this->createUser('editor', 'expired-reset-user');
        $user->password_reset_token = 'expired_' . (time() - 7200);
        self::assertTrue($user->save(false));

        $this->expectException(InvalidParamException::class);
        new ResetPasswordForm($user->password_reset_token);
    }

    /**
     * تست: جریان کامل بازنشانی موفقِ رمز عبور.
     *
     * پس از بازنشانی: توکن حذف می‌شود، رمز جدید معتبر است و رمز قبلی دیگر قابل
     * استفاده نیست.
     */
    public function testPasswordCanBeResetSuccessfully(): void
    {
        $user = $this->createUser('editor', 'reset-success-user');
        $user->generatePasswordResetToken();
        self::assertTrue($user->save());

        $form = new ResetPasswordForm($user->password_reset_token, [
            'password' => 'NewStrongPassword!2026',
        ]);
        self::assertTrue($form->validate(), json_encode($form->errors));
        self::assertTrue($form->resetPassword());

        $reloaded = User::findOne($user->id);
        self::assertNull($reloaded->password_reset_token);
        self::assertTrue($reloaded->validatePassword('NewStrongPassword!2026'));
        self::assertFalse($reloaded->validatePassword('ValidPassword!2026'));
    }
}
