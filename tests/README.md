# تست‌ها

مجموعه تست فعال پروژه با PHPUnit 10 در پوشه‌های `tests/unit` و
`tests/integration` قرار دارد. تست‌ها همیشه از دیتابیس مستقل
`yii2_kamancms_test` استفاده می‌کنند و اطلاعات محیط توسعه را تغییر نمی‌دهند.

## پیش‌نیاز: MySQL

تست‌های یکپارچه به MySQL نیاز دارند. اگر MySQL اجرا نباشد، خطای
`SQLSTATE[HY000] [2002] Connection refused` دریافت می‌شود. در محیط ویندوز
می‌توان MySQL را از طریق Laragon (دکمه Start All) بالا آورد.

## راه‌اندازی و اجرا

```powershell
php init --env=Development --overwrite=All
composer test:prepare
composer test
composer test:install
```

- `test:prepare` دیتابیس تست را از صفر با migrationها می‌سازد (فقط بار اول).
- `test` تست‌های واحد و یکپارچه را اجرا می‌کند.
- `test:install` نصب روی دیتابیس خالی را آزمایش و سپس دیتابیس موقت را حذف می‌کند.

اجرای مستقیم بدون composer:

```powershell
php vendor/bin/phpunit --configuration phpunit.xml.dist
```

اجرای یک فایل یا یک متد خاص:

```powershell
php vendor/bin/phpunit --configuration phpunit.xml.dist tests/integration/LoginRateLimitTest.php
php vendor/bin/phpunit --configuration phpunit.xml.dist --filter testLoginIsBlockedAfterFiveFailedAttempts tests/integration/LoginRateLimitTest.php
```

اتصال دیتابیس تست (پیش‌فرض در `tests/bootstrap.php`) را می‌توان با متغیرهای
محیطی `TEST_DB_HOST`، `TEST_DB_PORT`، `TEST_DB_NAME`، `TEST_DB_USER` و
`TEST_DB_PASSWORD` تغییر داد.

## پوشش تست‌ها

شمار تست‌ها: **۱۲۵ تست، ۷۸۳ assertion** (۵ unit + ۱۲۰ integration).

### امنیت و یکپارچگی (اولویت ۱)

| فایل تست | پوشش |
| --- | --- |
| `tests/integration/PublicRateLimiterTest.php` | محدودیت نرخ فرم‌های عمومی — عبور درون حد، throw پس از عبور، ایزوله بودن scopeها |
| `tests/integration/ExportControllerTest.php` | محافظت CSV Injection — نقل‌قول `=`/`+`/`-`/`@`/تب/CR و رد نوع ناشناخته |
| `tests/integration/UserPasswordResetAndGuardTest.php` | توکن بازنشانی رمز (معتبر/منقضی/خالی)، نگهبانی آخرین سوپرادمین |
| `tests/integration/ResetPasswordFlowTest.php` | جریان کامل بازنشانی رمز — درخواست، تولید توکن، بازنشانی موفق، رد توکن باطل |
| `tests/integration/MaintenanceModeTest.php` | حالت تعمیرات — 503 + Retry-After، معافیت admin/login/error و کاربر احرازشده |
| `tests/integration/SystemSettingSecurityTest.php` | رمزنگاری مقادیر سری، خطا هنگام نبود کلید رمزنگاری |
| `tests/integration/LoginRateLimitTest.php` | قفل بعد از ۵ تلاش ناموفق، ثبت لاگ تلاش‌ها، پاک‌شدن شمارنده پس از ورود موفق |
| `tests/integration/NotificationServiceTest.php` | تجمیع submissionها، فیلتر خوانده‌نشده، علامت‌گذاری، ارسال اعلان |

### سایر تست‌های یکپارچه

- **ورود و دسترسی**: `AuthenticationTest`, `AdminAccessTest`, `RbacTest`
- **محتوا و مدیریت**: `ContentManagementTest`, `ContentPlatformTest`, `AdminFormSubmissionTest`, `BlogAndPresentationTest`, `MenuManagementTest`
- **فرم‌های عمومی**: `PublicFormsTest`
- **UI و ترجمه**: `UiCompletenessTest`, `LocalizationTest`, `DashboardPreferenceTest`
- **عملیات**: `OperationsManagementTest`, `VisitorAnalyticsTest`, `SmokeTest`, `UploadSecurityTest`

### تست‌های واحد

- `SmtpTransportFactoryTest` — ساخت transport با STARTTLS و TLS ضمنی
- `MediaUrlTest` — تصویر موجود و fallback تصویر گمشده
- `InstallerServiceTest` — نبود رمز ادمین در فایل‌ها، سیاست رمز، DSN و پورت
- `PasswordValidatorTest` — سیاست رمز عبور
- `SecureUploadTest` — اعتبارسنجی فایل‌های آپلودی

## رفتار تراکنشی

تست‌های یکپارچه از `tests/Support/DatabaseTestCase` ارث می‌برند که هر تست را
داخل یک transaction اجرا و پس از پایان rollback می‌کند؛ بنابراین تغییرات
دیتابیس بین تست‌ها باقی نمی‌ماند و سوییِت قابل اجرای مکرر است.

## کنترل‌های کیفیت دیگر

```powershell
composer lint
composer style
composer analyse
composer security:audit
```

مجموعه قدیمی Codeception به‌دلیل اجرا نشدن در CI و پوشش همان سناریوها توسط PHPUnit حذف شده است. تست `LocalizationTest` مسیرهای زبان‌دار، RTL/LTR و fallback
ترجمه‌های دیتابیسی را پوشش می‌دهد.
