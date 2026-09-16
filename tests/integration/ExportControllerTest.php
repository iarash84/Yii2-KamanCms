<?php

namespace tests\integration;

use frontend\modules\admin\controllers\ExportController;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\web\BadRequestHttpException;

class ExportControllerTest extends TestCase
{
    private function invokeSafeCsvValue(ExportController $controller, $value): string
    {
        $method = new \ReflectionMethod(ExportController::class, 'safeCsvValue');
        $method->setAccessible(true);

        return (string) $method->invoke($controller, $value);
    }

    /**
     * تست: مقدار CSV آغازشده با «=» (فرمول اکسل) باید با پیشوند «'» نقل‌قول شود.
     *
     * جلوگیری از حملات CSV Injection / Formula Injection در فایل‌های خروجی اکسل.
     */
    public function testLeadingEqualsSignIsQuoted(): void
    {
        $controller = new ExportController('export', Yii::$app->getModule('admin'));
        self::assertSame("'=1+1", $this->invokeSafeCsvValue($controller, '=1+1'));
    }

    /**
     * تست: مقادیر آغازشده با «+»، «-» یا «@» باید نقل‌قول شوند.
     *
     * این کاراکترها همگی می‌توانند به فرمول اکسل تبدیل شوند؛ برای امنیت باید
     * با پیشوند «'» بی‌ضرر شوند.
     */
    public function testLeadingPlusMinusAndAtAreQuoted(): void
    {
        $controller = new ExportController('export', Yii::$app->getModule('admin'));
        self::assertSame("'+SUM(A1:A2)", $this->invokeSafeCsvValue($controller, '+SUM(A1:A2)'));
        self::assertSame("'-10", $this->invokeSafeCsvValue($controller, '-10'));
        self::assertSame("'@cmd", $this->invokeSafeCsvValue($controller, '@cmd'));
    }

    /**
     * تست: کاراکترهای تب (Tab) و بازگشت به ابتدای سطر (CR) باید نقل‌قول شوند.
     *
     * این نویسه‌ها ممکن است برای خارج شدن مقدار از سلول و ایجاد ستون/سطر جعلی
     * در فایل CSV مورد سوءاستفاده قرار گیرند.
     */
    public function testTabAndCarriageReturnAreQuoted(): void
    {
        $controller = new ExportController('export', Yii::$app->getModule('admin'));
        self::assertSame("'\tvalue", $this->invokeSafeCsvValue($controller, "\tvalue"));
        self::assertSame("'\rvalue", $this->invokeSafeCsvValue($controller, "\rvalue"));
    }

    /**
     * تست: مقادیر امن (بدون پیشوند خطرناک) باید بدون تغییر باقی بمانند.
     *
     * متن ساده، عدد، رشته خالی و متن فارسی نباید دستکاری شوند.
     */
    public function testSafeValuesAreLeftUnchanged(): void
    {
        $controller = new ExportController('export', Yii::$app->getModule('admin'));
        self::assertSame('plain text', $this->invokeSafeCsvValue($controller, 'plain text'));
        self::assertSame('123', $this->invokeSafeCsvValue($controller, '123'));
        self::assertSame('', $this->invokeSafeCsvValue($controller, ''));
        self::assertSame('ُEnglish', $this->invokeSafeCsvValue($controller, 'ُEnglish'));
    }

    /**
     * تست: نوع خروجی ناشناخته در اکشن دانلود باید رد شود.
     *
     * درخواست دانلود با نوع نامعتبر باید با BadRequestHttpException (HTTP 400)
     * شکست بخورد.
     */
    public function testUnknownTypeIsRejected(): void
    {
        $controller = new ExportController('export', Yii::$app->getModule('admin'));
        $this->expectException(BadRequestHttpException::class);
        $controller->actionDownload('unknown-type');
    }
}
