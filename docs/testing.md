# راهنمای تست و کنترل کیفیت

## پیش‌نیازها

برای تست‌های کامل PHP 8.2+، Composer، MySQL/MariaDB و افزونه `pdo_mysql` لازم است. تست‌های integration به یک دیتابیس جداگانه نیاز دارند.

## تست‌های PHP

ابتدا وابستگی‌ها را نصب کنید:

```powershell
composer install
```

برای ساخت دیتابیس تست، migration و بررسی نصب تازه:

```powershell
composer run test:prepare
```

سپس تست‌ها را اجرا کنید:

```powershell
composer test
```

نام پیش‌فرض دیتابیس `yii2_kamancms_test` است. در صورت نیاز این Environment Variableها را تنظیم کنید: `TEST_DB_HOST`، `TEST_DB_PORT`، `TEST_DB_NAME`، `TEST_DB_USER` و `TEST_DB_PASSWORD`.

`tests/Support/DatabaseTestCase.php` قبل از هر تست transaction باز می‌کند و بعد از تست rollback می‌کند. بنابراین تست‌ها نباید به ترتیب اجرای تست دیگری وابسته باشند.

## کنترل‌های استاتیک

```powershell
composer lint
composer style
composer analyse
composer security:audit
```

- `lint`: بررسی syntax فایل‌های PHP
- `style`: اجرای PHP_CodeSniffer طبق `phpcs.xml`
- `analyse`: اجرای PHPStan طبق `phpstan.neon`
- `security:audit`: بررسی advisoryهای Composer

## تست frontend و CSS

```powershell
npm install
npm run build
npm run check:css
```

برای توسعه پیوسته CSS:

```powershell
npm run dev
```

خروجی CSS تولیدی را دستی ویرایش نکنید؛ منبع تغییر `frontend/web/css/src/app.css` یا `frontend/web/css/design-system.css` است.

## تست کامل CI

فرمان اصلی پروژه برای کنترل کامل:

```powershell
composer ci
npm audit --audit-level=moderate
npm run build
```

تست نصب کامل، دیتابیس خالی و migrationها را نیز بررسی می‌کند:

```powershell
composer test:install
```

## تست دستی Feature

برای هر Feature علاوه بر تست خودکار، این موارد را بررسی کنید:

- کاربر مهمان و کاربر بدون permission مناسب
- درخواست با method نادرست و CSRF نامعتبر
- داده خالی، طول بیش از حد و ورودی مخرب
- خطای validation و خطای دیتابیس
- موفقیت و redirect صحیح
- RTL، LTR، موبایل و keyboard navigation برای UI
- log، audit، notification، cache و فایل‌های تولیدشده در صورت مرتبط‌بودن

## Debug کردن خطاها

1. ابتدا `YII_ENV` و `YII_DEBUG` را فقط در محیط توسعه بررسی کنید.
2. لاگ‌های runtime را در `frontend/runtime/logs` و `console/runtime/logs` بررسی کنید.
3. route و پارامترهای Request را در Controller بررسی کنید.
4. validation errors مدل را قبل از redirect یا پیام موفقیت log/نمایش دهید.
5. query و migration را با دیتابیس تست جداگانه بازتولید کنید.
6. برای خطای frontend، Console مرورگر، Network و خروجی `npm run build` را بررسی کنید.
7. برای رفتار permission، نقش، permission و route مورد استفاده را در RBAC و `frontend/modules/admin/Module.php` دنبال کنید.

Debug Toolbar و Gii فقط در محیط توسعه فعال باشند و نباید در Production در دسترس عمومی قرار بگیرند.
