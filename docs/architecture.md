# معماری KamanCMS

این سند نمای معماری فعلی پروژه را توضیح می‌دهد. هدف آن معرفی ساختار موجود است، نه پیشنهاد یک لایه یا الگوی جدید.

## نمای کلی

KamanCMS یک برنامه Yii2 با ساختار پیشرفته است که در این پروژه بخش عمومی و پنل مدیریت هر دو در `frontend` قرار دارند. کدهای مشترک در `common`، فرمان‌های CLI و migrationها در `console` و تست‌ها در `tests` قرار گرفته‌اند.

```text
Browser / API client
        |
        v
frontend/web (Document Root, entry scripts)
        |
        v
Yii application bootstrap + config
        |
        +--> frontend/controllers       صفحات عمومی
        |
        +--> frontend/modules/admin     پنل مدیریت و RBAC
        |
        +--> frontend/models             ActiveRecord و Form Modelها
        |
        +--> frontend/services           عملیات چندمرحله‌ای و زیرساختی
        |
        +--> common/components           اجزای مشترک مانند upload و analytics
        |
        v
Yii ActiveRecord / Query Builder
        |
        v
MySQL / MariaDB
```

## مسئولیت مسیرهای اصلی

| مسیر | مسئولیت |
| --- | --- |
| `common/config` | تنظیمات مشترک، bootstrap و بارگذاری Environment |
| `common/components` | اجزای قابل استفاده مجدد مانند `SecureUpload` و پردازش Analytics |
| `common/models` | مدل‌های مشترک مانند User، LoginForm و Log |
| `common/validators` | اعتبارسنجی‌های سفارشی فرم‌ها |
| `common/installer` | منطق سرویس و Workflow نصب‌کننده |
| `console/controllers` | فرمان‌های `yii` مانند install و seed |
| `console/migrations` | ساخت و تغییر schema و RBAC دیتابیس |
| `frontend/controllers` | Controllerهای سایت عمومی |
| `frontend/modules/admin` | Module، Controller و Viewهای پنل مدیریت |
| `frontend/models` | مدل‌های دامنه، Search Modelها و Form Modelهای frontend |
| `frontend/services` | عملیات سطح application مانند Backup و Notification |
| `frontend/views` | قالب و Viewهای عمومی |
| `frontend/web` | Document Root، assetهای CSS/JS، upload عمومی و entry script |
| `tests` | تست‌های unit، integration و ابزار نصب تست |

ریشه مخزن نباید Document Root وب‌سرور باشد؛ Document Root باید `frontend/web` باشد.

## چرخه پردازش Request

1. وب‌سرور درخواست را به `frontend/web/index.php` یا Router مربوط به آن می‌رساند.
2. Yii با bootstrap و configurationهای مشترک و frontend ساخته می‌شود.
3. URL Manager مسیر را به route، controller و action تبدیل می‌کند.
4. Filterهای controller اجرا می‌شوند؛ برای نمونه VerbFilter، AccessControl و کنترل‌های احراز هویت.
5. در مسیرهای admin، `frontend/modules/admin/Module.php` ابتدا guest بودن و permission لازم را بررسی می‌کند.
6. Controller ورودی را از Request می‌گیرد، Form Model یا ActiveRecord را load و validate می‌کند.
7. برای عملیات چندمرحله‌ای، Controller سرویس موجود در `frontend/services` یا component مشترک را صدا می‌زند.
8. Model با ActiveRecord یا Query Builder به دیتابیس متصل می‌شود.
9. Controller View را render می‌کند یا Response، redirect، فایل یا JSON برمی‌گرداند.
10. Layout و View خروجی HTML را با helperهای Yii encode و render می‌کنند و assetهای frontend بارگذاری می‌شوند.

## Controller، Service و Model

### Controller

Controller مرز HTTP است و باید کارهای زیر را انجام دهد:

- دریافت و اعتبارسنجی نوع درخواست و پارامترهای route
- load کردن داده در Model/Form Model
- اجرای authorization و محدودیت method
- هماهنگ‌کردن Model و Service
- انتخاب Response مناسب

منطق سنگین backup، اعلان، upload یا پردازش چند مدل نباید در Controller تکرار شود. قبل از ایجاد Service جدید، `frontend/services` و `common/components` را بررسی کنید.

### Service و Component

Service برای یک عملیات application-level که چند مرحله، چند مدل، transaction، فایل یا cache را درگیر می‌کند مناسب است؛ مانند `BackupService` و `NotificationService`. Component مشترک باید مستقل از یک feature خاص و قابل استفاده در چند application باشد؛ مانند `SecureUpload`.

Service نباید مسئول route یا render کردن View باشد. خطاهای قابل نمایش را به شکل exception کنترل‌شده یا نتیجه مشخص به Controller برگرداند.

### Model و Database

- ActiveRecordهای `frontend/models` و `common/models` نماینده جدول‌ها و روابط هستند.
- Form Modelها ورودی و validation سناریوهای فرم را نگهداری می‌کنند.
- Search Modelها query و فیلترهای Grid را نگهداری می‌کنند.
- queryهای ساده می‌توانند در Model یا query object موجود باشند؛ queryهای چندمرحله‌ای و تکرارشونده باید محل مشخص و قابل تست داشته باشند.
- تغییر schema فقط از طریق migration انجام می‌شود.
- نام جدول و ستون‌ها از `snake_case` پیروی می‌کند.

## Configuration و Environment

تنظیمات مشترک در `common/config` و تنظیمات application در configهای مربوط به frontend/console قرار دارند. `common/config/env.php` مقدارهای فایل `.env` را فقط برای نام‌های معتبر Environment می‌خواند و مقدار Environment موجود را override نمی‌کند.

Secretها، رمزها و کلیدها نباید commit شوند. برای توسعه از `.env.example` کپی بگیرید. در Production از Environment Variable یا Secret Manager استفاده کنید. بعد از تغییر configuration، cache و رفتار محیط (`YII_ENV` و `YII_DEBUG`) را بررسی کنید.

## Migration

Migrationها در `console/migrations` به ترتیب timestamp اجرا می‌شوند. هر migration باید:

- فقط schema یا داده‌ای را تغییر دهد که به feature مربوط است.
- `safeUp()` و در صورت امکان `safeDown()` داشته باشد.
- index و foreign key لازم را صریح تعریف کند.
- برای داده‌های موجود و نصب تازه قابل اجرا باشد.
- همراه تست یا به‌روزرسانی تست‌های integration ارائه شود.

فرمان‌های اصلی:

```powershell
php yii migrate --interactive=0
php yii migrate/down 1 --interactive=0
```

قبل از migration روی محیط واقعی از دیتابیس backup بگیرید.

## Frontend و Assetها

- Viewهای عمومی در `frontend/views` و Viewهای admin در `frontend/modules/admin/views` هستند.
- CSS ورودی Tailwind در `frontend/web/css/src/app.css` است.
- `frontend/web/css/app.css` خروجی build است و دستی ویرایش نمی‌شود.
- tokenها و قواعد اختصاصی در `frontend/web/css/design-system.css` قرار دارند.
- JavaScript بدون framework در `frontend/web/js/app.js` نگهداری می‌شود.
- فونت‌ها و تصاویر در `frontend/web/fonts` و `frontend/web/img` قرار دارند.

پس از تغییر کلاس‌های Tailwind یا View، `npm run build` اجرا و تغییر خروجی بررسی شود.

## نقاط امنیتی معماری

مسیرهای admin به authentication و RBAC نیاز دارند. عملیات تغییردهنده باید POST و CSRF داشته باشند. فایل‌ها باید از مسیر `SecureUpload` عبور کنند. داده خروجی در View باید encode شود مگر اینکه منبع و پاک‌سازی HTML مشخصاً امن باشد. فایل‌های upload و secret نباید خارج از سیاست پروژه در Git یا Document Root قرار گیرند.
