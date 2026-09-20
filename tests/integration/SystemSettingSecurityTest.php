<?php

namespace tests\integration;

use frontend\models\SystemSetting;
use PHPUnit\Framework\TestCase;

class SystemSettingSecurityTest extends TestCase
{
    private ?string $originalKey = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalKey = getenv('APP_DATA_ENCRYPTION_KEY');
    }

    protected function tearDown(): void
    {
        if ($this->originalKey !== false) {
            putenv('APP_DATA_ENCRYPTION_KEY=' . $this->originalKey);
        } else {
            putenv('APP_DATA_ENCRYPTION_KEY');
        }
        parent::tearDown();
    }

    /**
     * تست: مقادیر سری باید در دیتابیس به‌صورت رمزشده ذخیره شوند.
     *
     * با وجود کلید رمزنگاری: مقدار ذخیره‌شده نباید برابر متن اصلی باشد، پرچم
     * is_secret باید 1 باشد و بازیابی مقدار باید متن اصلی را برگرداند.
     */
    public function testSecretValueIsEncryptedAtRestWithConfigKey(): void
    {
        putenv('APP_DATA_ENCRYPTION_KEY=test-data-encryption-key-32-bytes');

        self::assertTrue(SystemSetting::put('smtp_password', 'VerySecret!2026', true));
        $stored = SystemSetting::findOne('smtp_password');

        self::assertSame(1, (int) $stored->is_secret);
        self::assertNotSame('VerySecret!2026', $stored->value);
        self::assertSame('VerySecret!2026', SystemSetting::getValue('smtp_password'));
    }

    /**
     * تست: ذخیره مقدار سری بدون کلید رمزنگاری باید RuntimeException پرتاب کند.
     *
     * وقتی APP_DATA_ENCRYPTION_KEY خالی است، نباید اجازه ذخیره مقدار سری داده شود.
     */
    public function testStoringSecretWithoutEncryptionKeyThrows(): void
    {
        putenv('APP_DATA_ENCRYPTION_KEY=');
        self::assertSame('', trim((string) getenv('APP_DATA_ENCRYPTION_KEY')));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('APP_DATA_ENCRYPTION_KEY is not configured.');
        SystemSetting::put('smtp_password', 'VerySecret!2026', true);
    }

    /**
     * تست: مقادیر غیرسری (ساده) بدون نیاز به کلید رمزنگاری قابل ذخیره و بازیابی هستند.
     */
    public function testPlainValueIsStoredReadableWithoutEncryptionKey(): void
    {
        putenv('APP_DATA_ENCRYPTION_KEY=');
        self::assertTrue(SystemSetting::put('maintenance_enabled', '1', false));
        self::assertSame('1', SystemSetting::getValue('maintenance_enabled'));
    }
}
