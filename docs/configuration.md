# پیکربندی

تنظیمات مخصوص هر محیط در `.env` قرار می‌گیرند. فایل `.env.example` الگوی قابل انتشار است؛ خود `.env` شامل اطلاعات حساس است و نباید وارد Git شود.

## تنظیمات اصلی

```dotenv
YII_ENV=prod
YII_DEBUG=0
APP_URL=https://example.com
APP_LANGUAGE=fa
ADMIN_LANGUAGE=fa
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=yii2_kamancms
DB_USER=app_user
DB_PASSWORD=
MAIL_USE_FILE_TRANSPORT=0
APP_ANALYTICS_KEY=
APP_ANALYTICS_QUEUE_PATH=storage/analytics-queue
```

برای مقدارهای دارای فاصله از کوتیشن استفاده کنید. دسترسی کاربر دیتابیس را به همان دیتابیس محدود کنید و از حساب root در production استفاده نکنید.

## تنظیمات قابل مدیریت از پنل

- مشخصات عمومی سایت: `/admin/setting`
- زبان، تاریخ، Cache و Maintenance: `/admin/setting/system`
- SMTP و اعلان فرم‌ها: `/admin/setting/email`
- شبکه‌های اجتماعی: `/admin/setting/social`

تنظیمات حساس مانند رمز SMTP به‌شکل رمزنگاری‌شده نگهداری می‌شوند. کلیدهای رمزنگاری و رمز دیتابیس باید بیرون از مخزن و در اختیار مدیر سرور باشند.

ارسال ایمیل با افزونه رسمی `yiisoft/yii2-symfonymailer` انجام می‌شود. حالت‌های بدون رمزنگاری، STARTTLS و SSL از پنل قابل انتخاب‌اند؛ در production از STARTTLS یا SSL و گواهی معتبر سرویس‌دهنده استفاده کنید.

## مسیرهای قابل‌نوشتن

به کاربر وب‌سرور فقط برای `runtime`، assetهای تولیدشده، پوشه‌های upload و مسیر صف Analytics مجوز نوشتن بدهید. ریشه پروژه، پوشه config و فایل `.env` نباید قابل‌نوشتن عمومی باشند.
