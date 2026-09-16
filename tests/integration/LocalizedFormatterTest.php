<?php

namespace tests\integration;

use frontend\components\LocalizedFormatter;
use frontend\models\SystemSetting;
use tests\Support\DatabaseTestCase;

/**
 * قالب‌دهنده تاریخ محلی (LocalizedFormatter).
 *
 * این کلاس رفتار فرمت‌دهی تاریخ را در هر دو تقویم میلادی و جلالی پوشش می‌دهد:
 * - فرمت میلادی پیش‌فرض (Y/m/d برای تاریخ و datetime)
 * - خروجی جلالی با فعال‌سازی تنظیم date_calendar
 * - تبدیل‌های شناخته‌شده میلادی → جلالی (آغاز سال، روزهای کبیسه)
 * - تابع asYear در هر دو تقویم
 * - خروجی nullDisplay برای مقادیر نامعتبر
 */
class LocalizedFormatterTest extends DatabaseTestCase
{
    /**
     * تست: با تنظیم پیش‌فرض (gregorian) تاریخ باید به شکل میلادی فرمت شود.
     */
    public function testGregorianDateFormatIsDefault(): void
    {
        // تنظیم صریح locale و فرمت برای اطمینان از خروجی ASCII
        $formatter = new LocalizedFormatter([
            'locale' => 'en_US',
            'dateFormat' => 'php:Y/m/d',
            'datetimeFormat' => 'php:Y/m/d H:i',
        ]);
        self::assertSame('2024/03/20', $formatter->asDate('2024-03-20'));
        self::assertSame('2024/03/20 12:30', $formatter->asDatetime('2024-03-20 12:30:00'));
    }

    /**
     * تست: با فعال‌سازی تنظیم date_calendar=jalali تاریخ باید جلالی شود.
     *
     * ۲۰ مارس ۲۰۲۴ میلادی معادل ۱ فروردین ۱۴۰۳ شمسی است؛ متد asDatetime نیز
     * باید با همین پیشوند شروع شود.
     */
    public function testJalaliDateFormatIsApplied(): void
    {
        self::assertTrue(SystemSetting::put('date_calendar', 'jalali'));
        $formatter = new LocalizedFormatter();

        self::assertSame('1403/01/01', $formatter->asDate('2024-03-20'));
        self::assertStringStartsWith('1403/01/01 ', $formatter->asDatetime('2024-03-20 12:30:00'));
    }

    /**
     * تست: تابع gregorianToJalali باید تبدیل‌های شناخته‌شده را درست محاسبه کند.
     *
     * چهار نقطه مرجع معروف: آغاز سال ۱۴۰۳، اواسط سال، آغاز سال ۱۴۰۴ و روز
     * شناخته‌شده دیگری (۱ دی ۱۴۰۲).
     */
    public function testGregorianToJalaliKnownConversions(): void
    {
        // ۲۰ مارس ۲۰۲۴ = ۱ فروردین ۱۴۰۳
        self::assertSame([1403, 1, 1], LocalizedFormatter::gregorianToJalali(2024, 3, 20));
        // ۲۱ سپتامبر ۲۰۲۴ = ۳۱ شهریور ۱۴۰۳ (۲۲ سپتامبر روز اول مهر است)
        self::assertSame([1403, 6, 31], LocalizedFormatter::gregorianToJalali(2024, 9, 21));
        // ۲۲ دسامبر ۲۰۲۴ = ۲ دی ۱۴۰۳
        self::assertSame([1403, 10, 2], LocalizedFormatter::gregorianToJalali(2024, 12, 22));
        // ۲۱ مارس ۲۰۲۵ = ۱ فروردین ۱۴۰۴ (آغاز سال بعدی)
        self::assertSame([1404, 1, 1], LocalizedFormatter::gregorianToJalali(2025, 3, 21));
    }

    /**
     * تست: تابع asYear باید سال میلادی یا جلالی را جدا کند.
     */
    public function testYearIsExtractedInBothCalendars(): void
    {
        $gregorian = new LocalizedFormatter();
        self::assertSame('2024', $gregorian->asYear('2024-03-20'));

        self::assertTrue(SystemSetting::put('date_calendar', 'jalali'));
        $jalali = new LocalizedFormatter();
        self::assertSame('1403', $jalali->asYear('2024-03-20'));
    }

    /**
     * تست: مقدار خالی یا نامعتبر باید nullDisplay (پیش‌فرض «') نمایش داده شود.
     */
    public function testInvalidValueUsesNullDisplay(): void
    {
        $formatter = new LocalizedFormatter(['nullDisplay' => 'N/A']);
        // مقدار null باید nullDisplay برگرداند
        self::assertSame('N/A', $formatter->asDate(null));
        self::assertSame('N/A', $formatter->asDatetime(null));
    }
}
