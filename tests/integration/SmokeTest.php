<?php

namespace tests\integration;

use frontend\models\Setting;
use frontend\models\HomeSection;
use frontend\models\Sample;
use frontend\models\Carousel;
use tests\Support\DatabaseTestCase;
use Yii;

class SmokeTest extends DatabaseTestCase
{
    public function testHomepageRendersManagedSlidesAndPortfolioImages(): void
    {
        $user = $this->createUser('editor', 'homepage-editor');
        self::assertTrue((new Carousel([
            'user_id' => $user->id,
            'image' => 'img/portfolio/hero-studio.webp',
            'title' => 'Managed slide',
            'text' => '<p>Managed hero copy</p>',
            'eyebrow' => 'Selected content',
            'link' => '/site/order',
            'primary_button_label' => 'Request product',
            'secondary_link' => '/site/contact',
            'secondary_button_label' => 'Talk to us',
            'show_content' => 1,
            'sort_order' => 10,
            'status' => 1,
        ]))->save());
        self::assertTrue((new Carousel(['user_id' => $user->id, 'image' => 'img/portfolio/mobile-banking.webp', 'title' => 'Image only slide', 'text' => 'Must stay hidden', 'sort_order' => 20, 'status' => 1]))->save());
        self::assertTrue((new HomeSection(['type' => 'portfolio', 'title' => 'Portfolio', 'status' => 1]))->save());
        self::assertTrue((new Sample(['title' => 'Image item', 'content' => '<p>Content</p>', 'image' => 'img/portfolio/commerce-experience.webp']))->save());

        $output = Yii::$app->runAction('site/index');
        self::assertStringContainsString('data-hero-slider', $output);
        self::assertStringContainsString('Managed slide', $output);
        self::assertStringContainsString('Request product', $output);
        self::assertStringContainsString('Talk to us', $output);
        self::assertSame(1, substr_count($output, 'class="hero-content"'));
        self::assertStringNotContainsString('Must stay hidden', $output);
        self::assertStringContainsString('home-portfolio-image', $output);
        self::assertStringContainsString('commerce-experience.webp', $output);
    }

    public function testDemoImageAssetsExist(): void
    {
        $webroot = Yii::getAlias('@webroot');
        foreach (['analytics-platform.webp', 'mobile-banking.webp', 'commerce-experience.webp', 'hero-studio.webp'] as $file) {
            $path = $webroot . '/img/portfolio/' . $file;
            self::assertFileExists($path);
            self::assertGreaterThan(10_000, filesize($path));
        }
    }

    public function testMainPagesRenderWithoutServerErrors(): void
    {
        self::assertTrue((new HomeSection(['type' => 'portfolio', 'title' => 'Portfolio', 'status' => 1]))->save());
        self::assertTrue((new Sample(['title' => 'Sample', 'content' => '<p>Sample content</p>']))->save());
        $settings = [
            'CompanyName' => 'سایت آزمایشی',
            'About' => '<p>درباره ما</p>',
            'Opportunity' => '<p>همکاری</p>',
            'Home' => '<p>خانه</p>',
        ];
        foreach ($settings as $type => $content) {
            self::assertTrue((new Setting(['type' => $type, 'content' => $content]))->save());
        }

        foreach (['site/index', 'site/about', 'site/contact', 'site/order', 'site/opportunity', 'site/sample', 'site/faqs', 'blog/index'] as $route) {
            Yii::$app->response->clear();
            $output = Yii::$app->runAction($route);
            self::assertIsString($output, "Route did not render: {$route}");
            self::assertNotSame('', trim($output), "Route returned empty output: {$route}");
            self::assertLessThan(500, Yii::$app->response->statusCode, "Route failed: {$route}");
        }
    }

    public function testAdminPagesRenderWithDesignSystemDependencies(): void
    {
        $admin = $this->createUser('superAdmin', 'ui-smoke-admin');
        self::assertTrue(Yii::$app->user->login($admin));

        $adminRoutes = [
            'admin/dashboard/index',
            'admin/setting/index',
            'admin/setting/about',
            'admin/setting/home',
            'admin/setting/social',
            'admin/setting/system',
            'admin/blog/index',
            'admin/category/index',
            'admin/carousel/index',
            'admin/sample/index',
            'admin/page/index',
            'admin/media/index',
            'admin/menu/index',
            'admin/faqs/index',
            'admin/setting/email',
            'admin/audit/index',
            'admin/export/index',
            'admin/backup/index',
            'admin/contact/index',
            'admin/order/index',
            'admin/opportunity/index',
            'admin/user/index',
        ];
        foreach ($adminRoutes as $route) {
            Yii::$app->response->clear();
            $output = Yii::$app->runAction($route);
            self::assertIsString($output, "Admin route did not render: {$route}");
            self::assertNotSame('', trim($output), "Admin route returned empty output: {$route}");
            self::assertLessThan(500, Yii::$app->response->statusCode, "Admin route failed: {$route}");
        }
    }

    public function testCarouselAdminShowsManagedFieldsAndDragSorter(): void
    {

        $admin = $this->createUser('superAdmin', 'carousel-admin');
        self::assertTrue(Yii::$app->user->login($admin));
        self::assertTrue((new Carousel([
            'user_id' => $admin->id,
            'image' => 'img/portfolio/hero-studio.webp',
            'title' => 'Visible admin title',
            'text' => 'Visible admin text',
            'link' => '/site/order',
            'primary_button_label' => 'Visible link label',
            'show_content' => 1,
            'sort_order' => 10,
            'status' => 1,
        ]))->save());
        $output = Yii::$app->runAction('admin/carousel/index');
        self::assertStringContainsString('data-carousel-sorter', $output);
        self::assertStringContainsString('draggable="true"', $output);
        self::assertStringContainsString('Visible admin title', $output);
        self::assertStringContainsString('Visible admin text', $output);
        self::assertStringContainsString('Visible link label', $output);
        self::assertStringNotContainsString('href="/admin/carousel/up?id=', $output);
        self::assertStringNotContainsString('href="/admin/carousel/down?id=', $output);
    }

    public function testOnlyOneCarouselCanBeSelectedAsContentSlide(): void
    {

        $user = $this->createUser('editor', 'carousel-content-editor');
        $first = new Carousel(['user_id' => $user->id, 'image' => 'first.webp', 'show_content' => 1, 'status' => 1]);
        $second = new Carousel(['user_id' => $user->id, 'image' => 'second.webp', 'show_content' => 1, 'status' => 1]);
        self::assertTrue($first->save());
        self::assertTrue($second->save());
        self::assertTrue($first->refresh());
        self::assertTrue($second->refresh());
        self::assertSame(0, (int) $first->show_content);
        self::assertSame(1, (int) $second->show_content);
        self::assertSame(1, (int) Carousel::find()->where(['show_content' => 1])->count());
    }

    public function testCarouselContentCanBeLocalized(): void
    {
        $user = $this->createUser('editor', 'carousel-translation-editor');
        $slide = new Carousel([
            'user_id' => $user->id,
            'image' => 'img/portfolio/hero-studio.webp',
            'title' => 'عنوان فارسی',
            'text' => '<p>متن فارسی</p>',
            'show_content' => 1,
            'status' => 1,
        ]);
        self::assertTrue($slide->save());
        self::assertTrue($slide->saveTranslations(['en' => [
            'title' => 'English hero title',
            'text' => '<p>English hero text</p>',
            'primary_button_label' => 'Start now',
        ]]));

        Yii::$app->languageManager->activate('en');
        $output = Yii::$app->runAction('site/index');
        self::assertStringContainsString('English hero title', $output);
        self::assertStringContainsString('English hero text', $output);
    }
}
