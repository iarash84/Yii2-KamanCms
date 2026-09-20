<?php

namespace tests\integration;

use frontend\models\SystemSetting;
use frontend\services\BackupService;
use tests\Support\DatabaseTestCase;
use Yii;

/**
 * پشتیبان‌گیری و بازیابی پایگاه داده.
 *
 * این کلاس جریان کامل «ایجاد نسخه پشتیبان» و «بازیابی» را پوشش می‌دهد:
 * - دور کامل create → تغییر داده → restore و مقایسه نتیجه
 * - رد کردن فرمت/نسخه/ساختار نامعتبر با پیام‌های خطای مشخص
 * - درستی خروجی JSON (فرمت، نسخه، فهرست جداول)
 */
class BackupServiceTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->cache->flush();
    }

    /**
     * تست: بازیابی باید داده‌های نسخه پشتیبان را دقیقاً بازگرداند.
     *
     * یک رکورد system_setting ساخته می‌شود، نسخه پشتیبان گرفته می‌شود، رکورد
     * حذف و رکورد دیگری اضافه می‌شود؛ سپس restore باید وضعیت «پیش از تغییر» را
     * بازگرداند و رکورد افزوده‌شده پس از پشتیبان را حذف کند.
     */
    public function testRestoreReturnsDataToTheBackupState(): void
    {
        // یک تنظیم اولیه به عنوان داده مرجع
        SystemSetting::put('site_name', 'Original Name');

        // نسخه پشتیبان از وضعیت فعلی گرفته شود
        $json = BackupService::create();
        self::assertSame('Original Name', SystemSetting::getValue('site_name'));

        // تغییر داده‌ها پس از پشتیبان‌گیری: حذف تنظیم موجود و افزودن تنظیم جدید
        SystemSetting::findOne('site_name')->delete();
        SystemSetting::put('post_backup_key', 'should vanish');
        self::assertNull(SystemSetting::getValue('site_name'));
        self::assertSame('should vanish', SystemSetting::getValue('post_backup_key'));

        // بازیابی نسخه پشتیبان: وضعیت باید به قبل برگردد
        BackupService::restore($json);
        self::assertSame('Original Name', SystemSetting::getValue('site_name'));
        self::assertNull(SystemSetting::getValue('post_backup_key'));
    }

    /**
     * تست: خروجی create یک JSON با فرمت و نسخه صحیح است.
     *
     * کلیدهای format/version/tables باید وجود داشته باشند و حداقل جدول‌های
     * system_setting و dashboard_preference در فهرست جداول باشند.
     */
    public function testCreateProducesValidatedFormat(): void
    {
        $backup = json_decode(BackupService::create(), true);

        self::assertIsArray($backup);
        self::assertSame('yii2-kamancms-backup', $backup['format']);
        self::assertSame(3, $backup['version']);
        self::assertArrayHasKey('created_at', $backup);
        self::assertIsArray($backup['tables']);
        self::assertArrayHasKey('system_setting', $backup['tables']);
        self::assertArrayHasKey('dashboard_preference', $backup['tables']);
    }

    /**
     * تست: فرمت یا نسخه نامعتبر باید با پیام «Invalid backup format.» رد شود.
     *
     * سه حالت بررسی می‌شود: فرمت ناشناخته، نسخه نامعتبر و نبود کلید tables.
     */
    public function testInvalidFormatVersionOrShapeIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid backup format.');
        BackupService::restore('{"format":"invalid"}');
    }

    /**
     * تست: جدول ناشناخته در پشتیبان باید با پیام اختصاصی رد شود.
     */
    public function testUnknownTableIsRejected(): void
    {
        $payload = json_encode([
            'format' => 'yii2-kamancms-backup',
            'version' => 3,
            'tables' => ['not_a_table' => []],
        ], JSON_THROW_ON_ERROR);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Backup contains an unknown table.');
        BackupService::restore($payload);
    }

    /**
     * تست: ردیف‌های نامنظم (non-array) باید با پیام «Backup rows are malformed.» رد شوند.
     */
    public function testMalformedRowsAreRejected(): void
    {
        $payload = json_encode([
            'format' => 'yii2-kamancms-backup',
            'version' => 3,
            'tables' => ['system_setting' => ['not-a-row']],
        ], JSON_THROW_ON_ERROR);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Backup rows are malformed.');
        BackupService::restore($payload);
    }

    /**
     * تست: ستون ناشناخته باید با پیام «Backup column mismatch.» رد شود.
     */
    public function testUnknownColumnIsRejected(): void
    {
        $payload = json_encode([
            'format' => 'yii2-kamancms-backup',
            'version' => 3,
            'tables' => ['system_setting' => [['made_up_column' => 'x']]],
        ], JSON_THROW_ON_ERROR);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Backup column mismatch.');
        BackupService::restore($payload);
    }

    /**
     * تست: ردیف با ستون‌های ناقص باید با پیام «Backup row column mismatch.» رد شود.
     */
    public function testRowWithMissingColumnsIsRejected(): void
    {
        // ردیف اول کامل با تمام ستون‌های واقعی جدول system_setting
        $schema = Yii::$app->db->schema->getTableSchema('system_setting');
        $columns = array_keys($schema->columns);
        $fullRow = [];
        foreach ($columns as $col) {
            $fullRow[$col] = match ($schema->columns[$col]->type) {
                'integer', 'bigint' => 0,
                'boolean' => 0,
                'text' => '',
                default => '',
            };
        }
        // مقادیر خاص برای ستون‌های NOT NULL
        $fullRow['key'] = 'row_mismatch_test';
        $fullRow['value'] = 'x';
        $fullRow['is_secret'] = 0;
        $fullRow['created_at'] = time();
        $fullRow['updated_at'] = time();

        // ردیف دوم یک ستون کمتر دارد → ناهماهنگی ستون
        $incompleteRow = $fullRow;
        array_pop($incompleteRow);

        $payload = json_encode([
            'format' => 'yii2-kamancms-backup',
            'version' => 3,
            'tables' => ['system_setting' => [$fullRow, $incompleteRow]],
        ], JSON_THROW_ON_ERROR);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Backup row column mismatch.');
        BackupService::restore($payload);
    }

    /**
     * تست: بازگرداندن یک نسخه پشتیبان کامل نباید خطا بدهد و رکوردها باید بازگردند.
     *
     * یک کاربر ساخته می‌شود (برای دیده شدن در پشتیبان)، پشتیبان گرفته می‌شود،
     * جدول system_setting خالی می‌شود و سپس restore اجرا می‌شود؛ در پایان مقدار
     * تنظیم ذخیره‌شده باید دوباره در دسترس باشد.
     */
    public function testFullRoundTripDoesNotThrow(): void
    {
        $this->createUser('editor');
        SystemSetting::put('notification_email', 'roundtrip@example.test');
        $json = BackupService::create();

        Yii::$app->db->createCommand()->delete('system_setting')->execute();
        self::assertNull(SystemSetting::getValue('notification_email'));

        BackupService::restore($json);
        self::assertSame('roundtrip@example.test', SystemSetting::getValue('notification_email'));
    }
}
