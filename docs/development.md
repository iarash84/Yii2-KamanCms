# راهنمای توسعه

## آماده‌سازی

```powershell
composer install
npm install
php init --env=Development --overwrite=All
Copy-Item .env.example .env
php yii migrate --interactive=0
php yii seed
npm run build
```

برای build پیوسته CSS از `npm run dev` استفاده کنید.

## قواعد تغییر

- تغییر دیتابیس فقط با migration و بدون ویرایش migration اجراشده انجام شود.
- قابلیت مدیریتی permission و AccessControl داشته باشد.
- رشته رابط با Yii i18n ترجمه شود.
- رابط جدید در RTL، LTR، موبایل و صفحه‌کلید آزمایش شود.
- قابلیت جدید همراه تست و مستندات باشد.
- فایل تولیدشده، secret، upload واقعی یا asset runtime وارد Git نشود.

## قرارداد رابط کاربری

- زبان بصری رابط از پنل‌های SaaS مینیمال الهام می‌گیرد، اما Preline یا کتابخانه UI دیگری به dependencyهای پروژه اضافه نمی‌شود.
- Tailwind CSS و daisyUI با پیشوند `d-` لایه component باقی می‌مانند و tokenها و overrideهای مشترک در `frontend/web/css/design-system.css` نگهداری می‌شوند.
- برای رنگ، فاصله، radius، border و shadow ابتدا از tokenهای semantic موجود استفاده کنید؛ style پراکنده و مقدارهای hard-coded در Viewها مجاز نیست مگر برای داده پویا.
- چیدمان با propertyهای منطقی مانند `margin-inline` و `inset-inline` نوشته شود و در عرض‌های ۳۷۵، ۷۶۸، ۱۰۲۴ و ۱۴۴۰ پیکسل، در هر دو جهت RTL و LTR بررسی شود.
- کارت فقط برای یک سطح محتوایی مستقل استفاده شود. جداسازی ساده بخش‌ها با border و spacing بر card تو‌در‌تو ترجیح دارد.
- دکمه اصلی فقط برای اقدام اصلی، دکمه ghost برای عملیات کم‌اهمیت و رنگ danger فقط برای عملیات مخرب استفاده شود.
- پس از تغییر View یا کلاس‌های Tailwind، `npm run build` اجرا و فایل `frontend/web/css/app.css` نیز ثبت شود.

## پروفایل کاربران

- اطلاعات تکمیلی کاربر در جدول `user` نگهداری می‌شود؛ پس از دریافت نسخه جدید، migrationها را با `php yii migrate --interactive=0` اجرا کنید.
- تصویر پروفایل فقط با فرمت PNG، JPG/JPEG یا WebP و حداکثر حجم ۲ مگابایت پذیرفته و با نام تصادفی در `frontend/web/upload/avatar` ذخیره می‌شود.
- حذف یا جایگزینی تصویر از مسیر امن `SecureUpload::deleteAvatar()` انجام می‌شود تا فایل خارج از پوشه اختصاصی آواتار حذف نشود.
- سناریوی `profile` فقط ویرایش نام کامل، ایمیل، تصویر و اطلاعات فردی کاربر جاری را مجاز می‌کند؛ تغییر نام کاربری و نقش همچنان به مجوز `manageUsers` محدود است و تغییر کلمه عبور جریان مستقل خود را دارد.
- گروه‌های منوی مدیریت از `details/summary` استفاده می‌کنند؛ مسیر فعال باید هنگام رندر، ویژگی `open` و لینک فعال باید `aria-current="page"` داشته باشد.

## کنترل کیفیت

```powershell
composer ci
npm audit --audit-level=moderate
npm run build
git diff --exit-code -- frontend/web/css/app.css
```

جزئیات تست‌ها در [tests/README.md](../tests/README.md) قرار دارد. برای یک تغییر محدود ابتدا تست مرتبط و پیش از Pull Request مجموعه کامل را اجرا کنید.
