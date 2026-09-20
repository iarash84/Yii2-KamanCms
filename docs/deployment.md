# استقرار در محیط عملیاتی

برای ساخت خودکار GitHub Release و image کانتینر بر اساس تگ نسخه، [راهنمای انتشار](release.md) را ببینید.

## آماده‌سازی انتشار

```powershell
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php init --env=Production --overwrite=All
php yii migrate --interactive=0
```

فایل `.env` محیط مقصد را جداگانه ایجاد کنید. اجرای `init` با `--overwrite=All` ممکن است فایل‌های محیطی را بازنویسی کند؛ پیش از اجرا نسخه پشتیبان داشته باشید.

## وب‌سرور

- Document Root فقط `frontend/web` باشد.
- HTTPS و تغییر مسیر دائمی HTTP به HTTPS فعال باشد.
- فایل‌های مخفی، `.env`، `vendor`، `console` و ریشه مخزن از وب قابل دریافت نباشند.
- اندازه درخواست با محدودیت upload برنامه هماهنگ باشد.
- هدر کشور فقط از reverse proxy مورد اعتماد پذیرفته شود.

## پس از انتشار

- `YII_ENV=prod` و `YII_DEBUG=0` را کنترل کنید.
- `.install.lock` باید موجود باشد.
- صفحه اصلی، ورود، فرم‌ها و دانلود محافظت‌شده را smoke test کنید.
- cron یا scheduler لازم برای وظایف زمان‌بندی‌شده را در سطح سرور تنظیم کنید.
- logها، فضای دیسک، خطاهای 5xx و زمان پاسخ را پایش کنید.

## پردازش Analytics

ثبت Analytics در وب فقط یک event ناشناس را در صف فایل قرار می‌دهد. Worker را از ریشه پروژه اجرا کنید:

```powershell
php yii analytics/process
```

برای اجرای یک بار در cron یا scheduler مقدار positional `1` را بدهید. Worker باید با همان کاربر دسترسی خواندن/نوشتن مسیر `APP_ANALYTICS_QUEUE_PATH` و دسترسی دیتابیس اجرا شود. پاک‌سازی markerهای قدیمی را جداگانه زمان‌بندی کنید:

```powershell
php yii analytics/process 1
php yii analytics/cleanup 90
```

در production مسیر صف را روی دیسک محلی پایدار و خارج از Document Root تنظیم کنید. در چند سرور وب، این صف فایل محلی مشترک نیست؛ در آن معماری باید یک صف مشترک مانند Redis یا broker دیگری به‌صورت جداگانه طراحی و نصب شود.

نسخه پشتیبان دیتابیس و uploadها باید خارج از Document Root و ترجیحاً روی فضای جداگانه نگهداری شود.
