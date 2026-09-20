# راهنمای افزودن Feature جدید

این راهنما مسیر پیشنهادی افزودن یک قابلیت جدید به KamanCMS را بر اساس لایه‌های موجود پروژه توضیح می‌دهد.

## 1. از تعریف مسئله شروع کنید

قبل از کدنویسی مشخص کنید:

- Feature برای سایت عمومی است یا پنل admin یا هر دو؟
- چه role و permissionهایی نیاز دارد؟
- چه داده‌ای ذخیره یا تغییر می‌کند؟
- آیا فایل، اعلان، cache، ترجمه یا audit درگیر است؟
- رفتار موفقیت، خطا و دسترسی غیرمجاز چیست؟

سپس routeها، Modelها، Viewهای مشابه و migrationهای مرتبط موجود را پیدا کنید. از ایجاد الگوی جدید وقتی نمونه موجود قابل استفاده است خودداری کنید.

## 2. محل تغییر در هر لایه

| نیاز | محل معمول |
| --- | --- |
| جدول، ستون، index یا foreign key | `console/migrations` |
| ActiveRecord، relation و rule | `frontend/models` یا `common/models` |
| فرم و validation ورودی | Form Model نزدیک به feature |
| فیلتر و query لیست | Search Model نزدیک به feature |
| عملیات چندمرحله‌ای | `frontend/services` یا component مشترک مناسب |
| صفحه عمومی | `frontend/controllers` و `frontend/views` |
| پنل مدیریت | `frontend/modules/admin/controllers` و `views` |
| permission و کنترل admin | RBAC و `frontend/modules/admin/Module.php` |
| متن رابط | `frontend/messages/<locale>` |
| CSS و component UI | `frontend/web/css/src/app.css` و `design-system.css` |
| رفتار تعاملی browser | `frontend/web/js/app.js` |
| تست | `tests/unit` یا `tests/integration` |
| راهنمای استفاده/نگهداری | `docs` و در صورت نیاز `README.md` |

## 3. ترتیب پیشنهادی پیاده‌سازی

### داده و migration

Migration برگشت‌پذیر ایجاد کنید، نام‌ها را `snake_case` بگذارید و constraintها و indexها را صریح تعریف کنید. migration را روی دیتابیس خالی و دیتابیس دارای داده آزمایش کنید.

```powershell
php yii migrate/create add_feature_table
php yii migrate --interactive=0
```

### Model و validation

ActiveRecord را با relationها و validationهای لازم اضافه یا تکمیل کنید. ورودی فرم را به `load()` بسپارید و قبل از save، سناریو و permission را مشخص کنید. برای عملیات حساس نتیجه `save()` و `saveTranslations()` را بررسی کنید و در خطا پیام موفقیت ندهید.

### Service

اگر عملیات بیش از یک Model، transaction، فایل، cache یا اعلان را درگیر می‌کند، آن را در Service قرار دهید. Service نباید به Request یا View وابسته باشد و باید خطای قابل تشخیص ایجاد کند.

### Controller و authorization

Controller را نازک نگه دارید. در admin، permission عمومی `accessAdmin` و permission اختصاصی feature را در مسیر کنترل دسترسی ثبت و بررسی کنید. برای delete، reorder، restore و سایر mutationها `VerbFilter` با POST اضافه کنید. CSRF را غیرفعال نکنید.

### View و asset

Viewهای admin را در module و Viewهای عمومی را در `frontend/views` قرار دهید. خروجی داده را encode کنید. متن‌ها را با `Yii::t()` بنویسید. CSS را با tokenها و componentهای موجود توسعه دهید و JavaScript را در فایل موجود یا محل مشخص فعلی اضافه کنید.

### ترجمه

پیام‌های جدید را در فایل message زبان‌های پشتیبانی‌شده اضافه کنید. برای محتوای قابل ویرایش چندزبانه از الگوی `content_translation` و helperهای مدل موجود استفاده کنید؛ ساختار ذخیره‌سازی جدید ایجاد نکنید مگر نیاز مستند داشته باشد.

## 4. تست Feature

حداقل این تست‌ها را اضافه کنید:

- validation داده معتبر و نامعتبر
- دسترسی role مجاز و غیرمجاز
- ایجاد، ویرایش، حذف یا تغییر وضعیت
- رفتار translation و fallback زبان
- رفتار upload یا notification در صورت وجود
- خطای transaction و rollback برای عملیات چندمرحله‌ای

سپس اجرا کنید:

```powershell
composer lint
composer style
composer analyse
composer test:prepare
composer test
npm run build
npm run check:css
```

## 5. چک‌لیست Pull Request

- [ ] migration جدید و در صورت امکان `safeDown()` دارد.
- [ ] Model/Form Model و validation اضافه یا به‌روزرسانی شده است.
- [ ] permission و محدودیت method برای admin وجود دارد.
- [ ] متن‌ها ترجمه شده‌اند.
- [ ] View خروجی را encode می‌کند.
- [ ] تست unit/integration و تست دستی انجام شده است.
- [ ] UI در RTL، LTR، موبایل و keyboard بررسی شده است.
- [ ] secret، upload واقعی و asset runtime commit نشده‌اند.
- [ ] README یا سند مرتبط به‌روزرسانی شده است.
- [ ] روش rollback و اثر روی داده موجود در توضیح PR نوشته شده است.
