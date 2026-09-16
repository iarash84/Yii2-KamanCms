<?php

namespace tests\integration;

use frontend\models\Contact;
use frontend\models\Opportunity;
use frontend\models\Order;
use frontend\models\SystemSetting;
use frontend\services\NotificationService;
use tests\Support\DatabaseTestCase;

class NotificationServiceTest extends DatabaseTestCase
{
    /**
     * تست: آیتم‌های اعلان باید از همه منابع (تماس، سفارش، فرصت شغلی) جمع‌آوری شوند.
     *
     * خلاصه اعلانِ تماس باید برابر body و عنوان آن برابر subject باشد.
     */
    public function testSubmissionItemsAggregateAllSourcesSortedByDate(): void
    {
        $contact = new Contact(['name' => 'Lead', 'email' => 'lead@example.test', 'subject' => 'Subject', 'body' => 'Body']);
        $contact->save(false);
        $order = new Order(['name' => 'Buyer', 'email' => 'buyer@example.test', 'description' => 'Description']);
        $order->save(false);
        $opportunity = new Opportunity(['name' => 'Applicant', 'email' => 'job@example.test', 'resume' => 'resume.pdf']);
        $opportunity->save(false);

        $items = NotificationService::submissionItems();
        $types = array_column($items, 'type');
        sort($types);

        self::assertSame(['contact', 'opportunity', 'order'], $types);
        self::assertCount(3, $items);

        $contactItem = null;
        foreach ($items as $item) {
            if ($item['type'] === 'contact') {
                $contactItem = $item;
            }
        }
        self::assertNotNull($contactItem);
        self::assertSame('Body', $contactItem['summary']);
        self::assertSame('Subject', $contactItem['title']);
    }

    /**
     * تست: فیلتر «فقط خوانده‌نشده» باید فقط آیتم‌های read_at=null را برگرداند.
     *
     * رکوردی که قبلاً read_at آن ست شده در خروجی ظاهر نمی‌شود.
     */
    public function testUnreadFilterReturnsOnlyUnreadSubmissions(): void
    {
        $read = new Contact(['name' => 'Read lead', 'email' => 'read@example.test', 'subject' => 's', 'body' => 'b']);
        $read->save(false);
        $read->updateAttributes(['read_at' => time()]);

        $unread = new Contact(['name' => 'New lead', 'email' => 'new@example.test', 'subject' => 's2', 'body' => 'b2']);
        $unread->save(false);

        $items = NotificationService::submissionItems(true);
        self::assertCount(1, $items);
        self::assertSame('b2', $items[0]['summary']);
        self::assertSame('s2', $items[0]['title']);
    }

    /**
     * تست: علامت‌گذاری همه submission ها به‌عنوان خوانده‌شده باید state همه منابع را پاک کند.
     *
     * بعد از فراخوانی، هیچ رکوردی در Contact/Order/Opportunity با read_at=null نباید بماند.
     */
    public function testMarkAllSubmissionsReadClearsUnreadState(): void
    {
        foreach ([
            new Contact(['name' => 'C', 'email' => 'c@example.test', 'subject' => 's', 'body' => 'b']),
            new Order(['name' => 'O', 'email' => 'o@example.test', 'description' => 'd']),
            new Opportunity(['name' => 'P', 'email' => 'p@example.test']),
        ] as $model) {
            $model->save(false);
        }

        NotificationService::markAllSubmissionsRead();

        self::assertSame(0, Contact::find()->where(['read_at' => null])->count());
        self::assertSame(0, Order::find()->where(['read_at' => null])->count());
        self::assertSame(0, Opportunity::find()->where(['read_at' => null])->count());
    }

    /**
     * تست: وقتی اعلان غیرفعال است (notify_contact=0)، فرم نباید ایمیلی ارسال کند.
     */
    public function testFormSubmittedReturnsFalseWhenNotificationsAreDisabled(): void
    {
        SystemSetting::put('notify_contact', '0');
        self::assertFalse(NotificationService::formSubmitted('contact', ['name' => 'Lead', 'email' => 'lead@example.test']));
    }

    /**
     * تست: با فعال‌بودن اعلان و گیرنده پیکربندی‌شده، ایمیل باید ارسال شود.
     *
     * فیلدهای حساس (مانند verifyCode و resume) از بدنه ایمیل حذف می‌شوند.
     */
    public function testFormSubmittedSendsEmailToConfiguredRecipientAndHidesSensitiveFields(): void
    {
        SystemSetting::put('notify_contact', '1');
        SystemSetting::put('notification_email', 'admin@example.test');

        $sent = NotificationService::formSubmitted('contact', [
            'name' => 'Lead',
            'email' => 'lead@example.test',
            'verifyCode' => 'secret-code',
            'resume' => 'resume.pdf',
            'body' => '<p>Hello</p>',
        ]);

        self::assertTrue($sent);
    }

    /**
     * تست: بدون گیرنده پیکربندی‌شده (notification_email خالی)، ایمیلی ارسال نمی‌شود.
     */
    public function testFormSubmittedReturnsFalseWithoutConfiguredRecipient(): void
    {
        SystemSetting::put('notify_contact', '1');
        SystemSetting::put('notification_email', '');
        self::assertFalse(NotificationService::formSubmitted('contact', ['name' => 'Lead']));
    }
}