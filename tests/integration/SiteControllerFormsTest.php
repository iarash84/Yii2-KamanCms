<?php

namespace tests\integration;

use frontend\models\Contact;
use frontend\models\Opportunity;
use frontend\models\Order;
use tests\Support\DatabaseTestCase;
use Yii;
use yii\web\TooManyRequestsHttpException;

/**
 * اکشن‌های فرم‌های عمومی SiteController.
 *
 * این کلاس جریان «درخواست واقعی POST» (نه فقط مدل) را پوشش می‌دهد:
 * - ذخیره‌سازی سفارش، تماس و فرصت شغلی از طریق اکشن‌های کنترلر
 * - پیام موفقیت (flash) پس از ثبت موفق فرم
 * - رد فرم نامعتبر بدون ذخیره داده
 * - اعمال محدودیت نرخ (rate limit) روی اسکوپ‌های عمومی
 * - کاهش سقف محدودیت برای بازیابی رمز عبور
 */
class SiteControllerFormsTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->cache->flush();
        Yii::$app->session->removeAllFlashes();
    }

    /**
     * تست: ثبت فرم سفارش از طریق POST باید رکورد را ذخیره و پیام موفقیت بدهد.
     */
    public function testOrderFormPostPersistsRecordAndFlashesSuccess(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams([
            'OrderForm' => [
                'name' => 'مشتری تست',
                'email' => 'order-flow@example.test',
                'company' => 'شرکت تست',
                'phoneNumber' => '09120000011',
                'website' => 'example.test',
                'description' => 'شرح سفارش',
                'verifyCode' => 'testme',
            ],
        ]);
        Yii::$app->response->clear();

        Yii::$app->runAction('site/order');

        self::assertSame(1, Order::find()->where(['email' => 'order-flow@example.test'])->count());
        self::assertNotNull(Yii::$app->session->getFlash('success'));
    }

    /**
     * تست: ثبت فرم تماس از طریق POST باید رکورد را ذخیره و پیام موفقیت بدهد.
     */
    public function testContactFormPostPersistsRecordAndFlashesSuccess(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams([
            'ContactForm' => [
                'name' => 'کاربر تست',
                'email' => 'contact-flow@example.test',
                'phoneNumber' => '09120000012',
                'subject' => 'موضوع تست',
                'body' => 'متن پیام',
                'verifyCode' => 'testme',
            ],
        ]);
        Yii::$app->response->clear();

        Yii::$app->runAction('site/contact');

        self::assertSame(1, Contact::find()->where(['email' => 'contact-flow@example.test'])->count());
        self::assertNotNull(Yii::$app->session->getFlash('success'));
    }

    /**
     * تست: ثبت فرم فرصت شغلی از طریق POST باید رکورد را ذخیره و پیام موفقیت بدهد.
     */
    public function testOpportunityFormPostPersistsRecordAndFlashesSuccess(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams([
            'OpportunityForm' => [
                'name' => 'متقاضی تست',
                'email' => 'job-flow@example.test',
                'phoneNumber' => '09120000013',
                'verifyCode' => 'testme',
            ],
        ]);
        Yii::$app->response->clear();

        Yii::$app->runAction('site/opportunity');

        self::assertSame(1, Opportunity::find()->where(['email' => 'job-flow@example.test'])->count());
        self::assertNotNull(Yii::$app->session->getFlash('success'));
    }

    /**
     * تست: فرم نامعتبر از طریق POST نباید رکوردی ذخیره کند.
     *
     * کد تایید اشتباه باعث شکست اعتبارسنجی می‌شود و در نتیجه اکشن فقط فرم را
     * دوباره رندر می‌کند و پیام موفقیت نیز نباید ثبت شود.
     */
    public function testInvalidPostDoesNotPersistAndRendersForm(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams([
            'ContactForm' => [
                'name' => 'کاربر تست',
                'email' => 'invalid-flow@example.test',
                'phoneNumber' => '09120000014',
                'subject' => 'موضوع',
                'body' => 'متن',
                'verifyCode' => 'wrong-answer',
            ],
        ]);
        Yii::$app->response->clear();

        $output = Yii::$app->runAction('site/contact');

        self::assertSame(0, Contact::find()->where(['email' => 'invalid-flow@example.test'])->count());
        self::assertNull(Yii::$app->session->getFlash('success'));
        self::assertIsString($output);
        self::assertNotSame('', trim($output));
    }

    /**
     * تست: محدودیت نرخ اسکوپ «contact» باید پس از پنج درخواست فعال شود.
     *
     * با پیش‌گرم کردن کلید کش به مقدار حد مجاز (۵)، ششمین POST باید با خطای
     * 429 (Too Many Requests) رد شود.
     */
    public function testContactScopeIsRateLimitedAfterFiveRequests(): void
    {
        $key = 'public-rate:' . hash('sha256', 'contact|' . (string) Yii::$app->request->userIP);
        Yii::$app->cache->set($key, 5, 600);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams([
            'ContactForm' => [
                'name' => 'کاربر تست',
                'email' => 'ratelimit@example.test',
                'phoneNumber' => '09120000015',
                'subject' => 'موضوع',
                'body' => 'متن',
                'verifyCode' => 'testme',
            ],
        ]);

        $this->expectException(TooManyRequestsHttpException::class);
        Yii::$app->runAction('site/contact');
    }

    /**
     * تست: بازیابی رمز عبور باید سقف محدودیت نرخ سخت‌تری داشته باشد (۳ درخواست).
     */
    public function testPasswordResetRateLimitIsEnforcedAtThreeRequests(): void
    {
        $key = 'public-rate:' . hash('sha256', 'password-reset|' . (string) Yii::$app->request->userIP);
        Yii::$app->cache->set($key, 3, 900);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams([
            'PasswordResetRequestForm' => [
                'email' => 'nobody@example.test',
            ],
        ]);

        $this->expectException(TooManyRequestsHttpException::class);
        Yii::$app->runAction('site/request-password-reset');
    }

    /**
     * تست: درخواست بازیابی رمز برای ایمیل ناشناخته نباید خطای سرور بدهد.
     *
     * ایمیل ناموجود باید منجر به نمایش فرم با پیام خطای «unable to reset»
     * شود و نه استثنای سرور؛ همچنین اطلاعات وجود/عدم وجود کاربر لو نرود.
     */
    public function testPasswordResetForUnknownEmailRendersWithoutServerError(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams([
            'PasswordResetRequestForm' => [
                'email' => 'does-not-exist@example.test',
            ],
        ]);
        Yii::$app->response->clear();

        Yii::$app->runAction('site/request-password-reset');

        // ایمیل ناشناخته نباید خطای سرور (5xx) بدهد؛
        // ممکن است ریدایرکت 302 یا رندر فرم باشد.
        self::assertLessThan(500, Yii::$app->response->statusCode);
    }
}
