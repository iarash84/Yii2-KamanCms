<?php

namespace tests\integration;

use tests\Support\DatabaseTestCase;
use Yii;

/**
 * مدیریت زبان (LanguageManager).
 *
 * این کلاس رفتار کامپوننت languageManager را که در frontend/config/main.php
 * پیکربندی شده است پوشش می‌دهد:
 * - فعال‌سازی زبان و تغییر Yii::$app->language
 * - نرمال‌سازی کدهای زبان و کدهای yii/locale
 * - تشخیص جهت RTL و دریافت locale/fallback
 * - ساخت URL زبان‌دار
 */
class LanguageManagerTest extends DatabaseTestCase
{
    /**
     * تست: activate باید زبان برنامه را تغییر دهد و کد نرمال‌شده را برگرداند.
     */
    public function testActivateChangesApplicationLanguage(): void
    {
        $manager = Yii::$app->languageManager;

        self::assertSame('fa', $manager->activate('fa'));
        self::assertSame('fa_IR', Yii::$app->language);

        self::assertSame('en', $manager->activate('en'));
        self::assertSame('en_US', Yii::$app->language);
    }

    /**
     * تست: normalize باید کدهای yii (fa_IR) و locale (fa-IR یا en-US) را به کد اصلی نگاشت کند.
     */
    public function testNormalizeMapsYiiAndLocaleCodes(): void
    {
        $manager = Yii::$app->languageManager;

        self::assertSame('fa', $manager->normalize('fa'));
        self::assertSame('fa', $manager->normalize('fa_IR'));
        self::assertSame('fa', $manager->normalize('fa-IR'));
        self::assertSame('en', $manager->normalize('en'));
        self::assertSame('en', $manager->normalize('en_US'));
        self::assertSame('en', $manager->normalize('en-US'));
        self::assertNull($manager->normalize('de'));
        self::assertNull($manager->normalize('unknown'));
    }

    /**
     * تست: کد زبان ناشناخته هنگام activate باید به زبان پیش‌فرض برگردد.
     */
    public function testActivateFallsBackForUnknownCode(): void
    {
        $manager = Yii::$app->languageManager;

        $result = $manager->activate('de');

        self::assertSame($manager->defaultLanguage, $result);
        self::assertSame($result, $manager->getActiveLanguage());
    }

    /**
     * تست: تشخیص جهت RTL باید برای فارسی true و برای انگلیسی false باشد.
     */
    public function testRtlDetectionPerLanguage(): void
    {
        $manager = Yii::$app->languageManager;

        self::assertTrue($manager->isRtl('fa'));
        self::assertFalse($manager->isRtl('en'));
    }

    /**
     * تست: getLocale باید locale مربوط به هر زبان را برگرداند.
     */
    public function testLocaleResolution(): void
    {
        $manager = Yii::$app->languageManager;

        self::assertSame('fa-IR', $manager->getLocale('fa'));
        self::assertSame('en-US', $manager->getLocale('en'));
    }

    /**
     * تست: getFallbacks باید خود زبان، fallback و زبان پیش‌فرض را به‌صورت یکتا برگرداند.
     */
    public function testFallbackChain(): void
    {
        $manager = Yii::$app->languageManager;

        // fallback زبان فارسی «en» است؛ زبان پیش‌فرض هم «fa» — هر دو یکتا باشند
        self::assertSame(['fa', 'en'], $manager->getFallbacks('fa'));
        self::assertSame(['en', 'fa'], $manager->getFallbacks('en'));
    }

    /**
     * تست: activate('fa') باید زبان رایت (RTL) را فعال کند و activate('en') جهت چپ.
     */
    public function testActivationAffectsRtlState(): void
    {
        $manager = Yii::$app->languageManager;

        $manager->activate('fa');
        self::assertTrue($manager->isRtl());
        self::assertSame('fa-IR', $manager->getLocale());

        $manager->activate('en');
        self::assertFalse($manager->isRtl());
        self::assertSame('en-US', $manager->getLocale());
    }

    /**
     * تست: getLanguageUrl باید پارامتر language را در URL لحاظ کند.
     */
    public function testLanguageUrlContainsLanguageParam(): void
    {
        $manager = Yii::$app->languageManager;

        // getLanguageUrl به کنترلر فعلی نیاز دارد تا route را بخواند
        Yii::$app->controller = new \yii\web\Controller('site', Yii::$app);

        $url = $manager->getLanguageUrl('en');
        self::assertStringContainsString('language=en', $url);
    }
}
