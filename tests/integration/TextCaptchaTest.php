<?php

namespace tests\integration;

use frontend\components\TextCaptcha;
use tests\Support\DatabaseTestCase;
use Yii;

/**
 * کپچای متنی (TextCaptcha).
 *
 * در محیط test (YII_ENV_TEST) رفتار کپچا به یک کد ثابت «testme» ساده می‌شود؛
 * این کلاس همان رفتاری را که فرم‌های عمومی در تست‌ها تجربه می‌کنند اعتبارسنجی
 * می‌کند: سؤال ثابت تست، پذیرش کد صحیح، رد کد اشتباه، عدم حساسیت به فاصله و
 * نادیده گرفتن حروف بزرگ/کوچک.
 */
class TextCaptchaTest extends DatabaseTestCase
{
    /**
     * تست: در محیط تست، سؤال کپچا باید پیام راهنمای «test verification code» باشد.
     */
    public function testQuestionReturnsTestPrompt(): void
    {
        // برای استقلال از ترتیب اجرای تست‌ها، زبان را صریحاً en_US می‌کنیم
        Yii::$app->language = 'en_US';
        self::assertSame('Enter the test verification code.', TextCaptcha::question());
    }

    /**
     * تست: مقدار صحیح «testme» باید پذیرفته شود.
     *
     * این همان کدی است که همه فرم‌ها در تست‌ها ارسال می‌کنند.
     */
    public function testValidAnswerIsAccepted(): void
    {
        self::assertTrue(TextCaptcha::validate('testme'));
    }

    /**
     * تست: پاسخ اشتباه باید رد شود.
     */
    public function testWrongAnswerIsRejected(): void
    {
        self::assertFalse(TextCaptcha::validate('wrong'));
    }

    /**
     * تست: فاصله‌های اضافی دور پاسخ نباید مشکلی ایجاد کند.
     *
     * مقایسه با trim انجام می‌شود پس «testme » یا « testme» نیز معتبر است.
     */
    public function testSurroundingWhitespaceIsTrimmed(): void
    {
        self::assertTrue(TextCaptcha::validate('  testme  '));
    }

    /**
     * تست: پاسخ باید حساس به بزرگی/کوچکی حروف باشد (hash_equals).
     *
     * مقدار «TESTME» با «testme» برابر نیست، بنابراین باید رد شود.
     */
    public function testAnswerIsCaseSensitive(): void
    {
        self::assertFalse(TextCaptcha::validate('TESTME'));
    }
}
