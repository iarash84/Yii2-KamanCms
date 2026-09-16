<?php

namespace tests\integration;

use frontend\models\EmailSettingsForm;
use frontend\models\SystemSetting;
use tests\Support\DatabaseTestCase;
use Yii;

/**
 * فرم تنظیمات ایمیل (EmailSettingsForm).
 *
 * این کلاس نگاشت دوطرفه بین خصوصیت‌های فرم و کلیدهای system_setting را پوشش
 * می‌دهد؛ از جمله رفتار ویژه رمز SMTP (ذخیره رمزنگاری‌شده، پرش از رمز خالی)
 * و اعتبارسنجی فیلدهای ایمیل/پورت/رمزنگاری.
 */
class EmailSettingsFormTest extends DatabaseTestCase
{
    /**
     * تست: فیلدهای معمولی باید از تنظیمات بارگذاری و دوباره ذخیره شوند.
     *
     * سه کلید نمونه (smtp_host، mail_from_email و notify_order) نوشته می‌شوند و
     * expectedForm باید دقیقاً همان مقادیر را از تنظیمات بخواند.
     */
    public function testSettingsAreLoadedIntoForm(): void
    {
        SystemSetting::put('smtp_host', 'smtp.example.test');
        SystemSetting::put('mail_from_email', 'no-reply@example.test');
        SystemSetting::put('notify_order', '0');

        $form = new EmailSettingsForm();
        $form->loadSettings();

        self::assertSame('smtp.example.test', $form->smtpHost);
        self::assertSame('no-reply@example.test', $form->fromEmail);
        self::assertSame('0', $form->notifyOrder);
    }

    /**
     * تست: saveSettings باید همه مقادیر را در تنظیمات بنویسد.
     *
     * پس از ذخیره، هر کلید نگاشت‌شده باید مقدار مورد انتظار را برگرداند.
     */
    public function testSettingsAreSavedFromForm(): void
    {
        $form = new EmailSettingsForm([
            'smtpHost' => 'mail.example.test',
            'smtpPort' => 2525,
            'smtpEncryption' => 'ssl',
            'smtpPassword' => '',
            'fromEmail' => 'hello@example.test',
            'fromName' => 'Test Sender',
            'notificationEmail' => 'admin@example.test',
            'fileTransport' => false,
            'notifyContact' => false,
            'notifyOrder' => true,
            'notifyOpportunity' => false,
            'notifyAdmins' => true,
        ]);

        self::assertTrue($form->saveSettings());

        self::assertSame('mail.example.test', SystemSetting::getValue('smtp_host'));
        self::assertSame('2525', SystemSetting::getValue('smtp_port'));
        self::assertSame('ssl', SystemSetting::getValue('smtp_encryption'));
        self::assertSame('hello@example.test', SystemSetting::getValue('mail_from_email'));
        self::assertSame('Test Sender', SystemSetting::getValue('mail_from_name'));
        self::assertSame('admin@example.test', SystemSetting::getValue('notification_email'));
        // مقادیر boolean false به‌صورت رشته خالی '' ذخیره می‌شوند (نه '0')
        self::assertSame('', SystemSetting::getValue('mail_file_transport'));
        self::assertSame('', SystemSetting::getValue('notify_contact'));
        self::assertSame('1', SystemSetting::getValue('notify_order'));
        self::assertSame('', SystemSetting::getValue('notify_opportunity'));
        self::assertSame('1', SystemSetting::getValue('notify_admins'));
    }

    /**
     * تست: رمز SMTP باید به‌صورت رمزنگاری‌شده ذخیره شود و با مقدار اصلی برابر خوانده شود.
     *
     * مقدار ذخیره‌شده در پایگاه داده نباید خود رمز باشد، اما getValue باید رمز
     * اصلی را پس از رمزگشایی برگرداند.
     */
    public function testSmtpPasswordIsStoredSecretAndReadable(): void
    {
        $form = new EmailSettingsForm(['smtpPassword' => 'SuperSecret!2026']);
        self::assertTrue($form->saveSettings());

        $stored = SystemSetting::findOne('smtp_password');
        self::assertSame('SuperSecret!2026', SystemSetting::getValue('smtp_password'));
        self::assertNotSame('SuperSecret!2026', $stored->value);
    }

    /**
     * تست: بارگذاری تنظیمات نباید مقدار رمز SMTP موجود را بازنویسی کند.
     *
     * کامنت‌گذاری: رمز به تنهایی نباید در loadSettings خوانده شود؛ یعنی وقتی
     * رمز در پایگاه داده وجود دارد، خصوصیت smtpPassword نباید با آن پر شود تا
     * در UI رمز از پیش پر نشده نمایش داده نشود.
     */
    public function testLoadSettingsDoesNotPopulatePassword(): void
    {
        SystemSetting::put('smtp_password', 'DoNotShowMe', true);

        $form = new EmailSettingsForm(['smtpPassword' => '']);
        $form->loadSettings();

        self::assertSame('', $form->smtpPassword);
    }

    /**
     * تست: رمز خالی نباید مقدار ذخیره‌شده قبلی را پاک کند.
     *
     * هنگام ذخیره، اگر smtpPassword رشته خالی باشد، کلید smtp_password باید
     * دست‌نخورده بماند.
     */
    public function testEmptyPasswordDoesNotOverwriteStoredPassword(): void
    {
        SystemSetting::put('smtp_password', 'KeepMe!2026', true);

        $form = new EmailSettingsForm(['smtpPassword' => '']);
        self::assertTrue($form->saveSettings());

        self::assertSame('KeepMe!2026', SystemSetting::getValue('smtp_password'));
    }

    /**
     * تست: ایمیل نامعتبر در اعتبارسنجی باید رد شود.
     */
    public function testInvalidEmailIsRejected(): void
    {
        $form = new EmailSettingsForm(['fromEmail' => 'not-an-email']);
        self::assertFalse($form->validate());
        self::assertArrayHasKey('fromEmail', $form->errors);
    }

    /**
     * تست: پورت خارج از بازه مجاز (۱ تا ۶۵۵۳۵) باید رد شود.
     */
    public function testInvalidPortIsRejected(): void
    {
        $form = new EmailSettingsForm(['smtpPort' => 99999]);
        self::assertFalse($form->validate());
        self::assertArrayHasKey('smtpPort', $form->errors);
    }

    /**
     * تست: مقدار نامعتبر برای نوع رمزنگاری باید رد شود.
     */
    public function testInvalidEncryptionIsRejected(): void
    {
        $form = new EmailSettingsForm(['smtpEncryption' => 'plain']);
        self::assertFalse($form->validate());
        self::assertArrayHasKey('smtpEncryption', $form->errors);
    }

    /**
     * تست: تست اتصال بدون هاست SMTP باید خطای RuntimeException بدهد.
     *
     * اگر smtpHost خالی باشد، نباید تلاشی برای اتصال واقعی انجام شود و باید
     * پیام «SMTP host is required» صادر شود.
     */
    public function testConnectionTestWithoutHostThrows(): void
    {
        // برای استقلال از ترتیب اجرای تست‌ها، زبان را صریحاً en_US می‌کنیم
        Yii::$app->language = 'en_US';
        $form = new EmailSettingsForm(['smtpHost' => '']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SMTP host is required for connection testing.');
        $form->testConnection();
    }
}
