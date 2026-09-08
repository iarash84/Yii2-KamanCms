<?php

namespace tests\integration;

use frontend\models\Contact;
use frontend\models\ContactForm;
use frontend\models\Opportunity;
use frontend\models\OpportunityForm;
use frontend\models\Order;
use frontend\models\OrderForm;
use tests\Support\DatabaseTestCase;

class PublicFormsTest extends DatabaseTestCase
{
    public function testContactFormIsValidatedAndSaved(): void
    {

        $form = new ContactForm([
            'name' => 'کاربر تست', 'email' => 'contact@example.test',
            'phoneNumber' => '۰۹۱۲ ۰۰۰ ۰۰۰۰', 'subject' => 'موضوع', 'body' => 'پیام',
            'verifyCode' => 'testme',
        ]);
        self::assertTrue($form->validate(), json_encode($form->errors));
        self::assertSame('09120000000', $form->phoneNumber);
        self::assertTrue($form->saveContact());
        self::assertSame(1, Contact::find()->where(['email' => 'contact@example.test'])->count());
    }

    public function testOrderFormIsValidatedAndSaved(): void
    {
        $form = new OrderForm([
            'name' => 'مشتری تست', 'email' => 'order@example.test',
            'phoneNumber' => '09120000001', 'company' => 'شرکت تست',
            'website' => 'example.test', 'description' => 'شرح سفارش',
            'verifyCode' => 'testme',
        ]);
        self::assertTrue($form->validate(), json_encode($form->errors));
        self::assertSame('https://example.test', $form->website);
        self::assertTrue($form->saveOrder());
        self::assertSame(1, Order::find()->where(['email' => 'order@example.test'])->count());
    }

    public function testOpportunityFormIsValidatedAndSaved(): void
    {
        $form = new OpportunityForm([
            'name' => 'متقاضی تست', 'email' => 'job@example.test',
            'phoneNumber' => '09120000002', 'verifyCode' => 'testme',
        ]);
        self::assertTrue($form->validate(), json_encode($form->errors));
        self::assertTrue($form->saveOpportunity());
        self::assertSame(1, Opportunity::find()->where(['email' => 'job@example.test'])->count());
    }

    public function testInvalidPublicFormsAreRejected(): void
    {
        $contact = new ContactForm(['email' => 'invalid', 'verifyCode' => 'wrong']);
        $order = new OrderForm(['email' => 'invalid', 'verifyCode' => 'wrong']);
        $opportunity = new OpportunityForm(['email' => 'invalid', 'verifyCode' => 'wrong']);
        self::assertFalse($contact->validate());
        self::assertFalse($order->validate());
        self::assertFalse($opportunity->validate());
    }

    public function testMalformedPhoneNumberIsRejected(): void
    {

        $form = new ContactForm([
            'name' => 'Test user',
            'email' => 'valid@example.test',
            'phoneNumber' => '123-abc',
            'subject' => 'Subject',
            'body' => 'Message',
            'verifyCode' => 'testme',
        ]);
        self::assertFalse($form->validate());
        self::assertArrayHasKey('phoneNumber', $form->errors);
    }
}
