<?php

namespace tests\integration;

use frontend\models\Setting;
use frontend\models\Contact;
use frontend\models\HomeSection;
use frontend\models\Order;
use frontend\models\Opportunity;
use frontend\widgets\AdminActionColumn;
use tests\Support\DatabaseTestCase;
use Yii;

class UiCompletenessTest extends DatabaseTestCase
{
    public function testFaqManagementIsVisibleToEditor(): void
    {
        $editor = $this->createUser('editor', 'faq-editor');
        self::assertTrue(Yii::$app->user->login($editor));
        $output = Yii::$app->runAction('admin/dashboard/index');
        self::assertStringContainsString('/admin/faqs/index', $output);
        self::assertStringContainsString(Yii::t('app', 'FAQ management'), $output);
    }

    public function testConfiguredSocialLinksAreRenderedInFooter(): void
    {
        foreach (['Instagram' => 'https://instagram.com/example', 'Telegram' => 'https://t.me/example'] as $type => $url) {
            self::assertTrue((new Setting(['type' => $type, 'content' => $url]))->save());
        }
        Yii::$app->user->logout(false);
        $output = Yii::$app->runAction('site/index');
        self::assertStringContainsString('https://instagram.com/example', $output);
        self::assertStringContainsString('https://t.me/example', $output);
    }

    public function testEveryLiteralApplicationMessageHasPersianTranslation(): void
    {
        $catalog = require Yii::getAlias('@app/messages/fa_IR/app.php');
        $missing = [];
        foreach (['common', 'frontend', 'console'] as $directory) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/' . $directory));
            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                preg_match_all('/Yii::t\(\s*[\'\"]app[\'\"]\s*,\s*([\'\"])(.*?)\1/s', file_get_contents($file->getPathname()), $matches);
                foreach ($matches[2] as $message) {
                    if (!array_key_exists($message, $catalog)) {
                        $missing[$message] = true;
                    }
                }
            }
        }
        self::assertSame([], array_keys($missing), 'Missing fa_IR translations: ' . implode(', ', array_keys($missing)));
    }

    public function testPersianCatalogDoesNotContainUnusedMessages(): void
    {
        $catalog = require Yii::getAlias('@app/messages/fa_IR/app.php');
        $used = [];
        foreach (['common', 'frontend', 'console'] as $directory) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/' . $directory));
            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                preg_match_all('/Yii::t\(\s*[\'\"]app[\'\"]\s*,\s*([\'\"])(.*?)\1/s', file_get_contents($file->getPathname()), $matches);
                foreach ($matches[2] as $message) {
                    $used[$message] = true;
                }
            }
        }
        $unused = array_keys(array_diff_key($catalog, $used));
        self::assertSame([], $unused, 'Unused fa_IR translations: ' . implode(', ', $unused));
    }

    public function testThemeSelectorProvidesCuratedAccessibleOptions(): void
    {
        Yii::$app->user->logout(false);
        $output = Yii::$app->runAction('site/index');
        self::assertStringContainsString('<body class="public-interface">', $output);
        self::assertStringContainsString('data-theme-option', $output);
        foreach (['system', 'site-light', 'site-dark'] as $theme) {
            self::assertStringContainsString('data-theme-option="' . $theme . '"', $output);
        }
        self::assertStringNotContainsString('data-theme-option="light"', $output);
        self::assertStringNotContainsString('data-theme-option="dark"', $output);
    }

    public function testPublicJourneyHasAVisiblePrimaryActionAndClearFormExpectations(): void
    {

        Yii::$app->user->logout(false);
        $homepage = Yii::$app->runAction('site/index');
        self::assertStringContainsString(Yii::t('app', 'Request a service'), $homepage);
        self::assertStringContainsString('home-trust-strip', $homepage);
        Yii::$app->response->clear();
        $order = Yii::$app->runAction('site/order');
        self::assertStringContainsString('form-assurance', $order);
        self::assertStringContainsString(Yii::t('app', 'What happens next'), $order);
        self::assertStringContainsString('type="tel"', $order);
    }

    public function testAdminShellProvidesResponsiveNavigationAndConfirmationDialog(): void
    {
        $admin = $this->createUser('superAdmin', 'admin-ui-shell');
        self::assertTrue(Yii::$app->user->login($admin));
        $output = Yii::$app->runAction('admin/dashboard/index');
        self::assertStringContainsString('<body class="admin-interface">', $output);
        self::assertStringContainsString('admin-sidebar-header', $output);
        self::assertStringContainsString('data-admin-sidebar-toggle', $output);
        self::assertStringContainsString('data-admin-sidebar', $output);
        self::assertStringContainsString('data-admin-nav-group', $output);
        self::assertStringContainsString('admin-nav-submenu', $output);
        self::assertStringContainsString('data-confirmation-dialog', $output);
        self::assertStringContainsString('d-modal', $output);
    }

    public function testGridActionsUseCentralizedAdminColumn(): void
    {
        self::assertInstanceOf(AdminActionColumn::class, Yii::$container->get(\yii\grid\ActionColumn::class));

        $admin = $this->createUser('superAdmin', 'consistent-grid-actions');
        self::assertTrue(Yii::$app->user->login($admin));
        self::assertTrue((new Opportunity([
            'name' => 'Example applicant',
            'phone_number' => '+98 912 000 0000',
            'email' => 'applicant@example.test',
        ]))->save());

        $output = Yii::$app->runAction('admin/opportunity/index');
        self::assertSame(1, preg_match('/<thead>\s*<tr>(.*?)<\/tr>\s*<\/thead>/s', $output, $matches));
        $statusPosition = strpos($matches[1], Yii::t('app', 'Status'));
        $actionsPosition = strpos($matches[1], 'admin-table-actions-column');
        self::assertNotFalse($statusPosition);
        self::assertNotFalse($actionsPosition);
        self::assertLessThan($actionsPosition, $statusPosition);
        self::assertStringContainsString('<div class="admin-table-actions">', $output);
    }

    public function testFrontendEnhancementsPreserveActiveFormAndDialogCancellation(): void
    {
        $script = file_get_contents(Yii::getAlias('@webroot/js/app.js'));

        self::assertStringContainsString(".on('beforeSubmit', 'form'", $script);
        self::assertStringContainsString("data('yiiActiveForm')", $script);
        self::assertStringContainsString("confirmationDialog.returnValue = 'cancel'", $script);
        self::assertStringContainsString("confirmationDialog.returnValue === 'confirm'", $script);
        self::assertStringContainsString("trigger.removeAttribute('data-confirm')", $script);
        self::assertStringContainsString("form.method === 'dialog'", $script);
    }

    public function testAdminNotificationTracksUnreadContactSubmission(): void
    {
        $contact = new Contact(['name' => 'New contact', 'email' => 'contact@example.test', 'subject' => 'Hello', 'body' => 'Message']);
        self::assertTrue($contact->save());
        $admin = $this->createUser('superAdmin', 'notification-admin');
        self::assertTrue(Yii::$app->user->login($admin));

        $homepage = Yii::$app->runAction('site/index');
        self::assertStringContainsString('notification-badge', $homepage);
        $opportunityUrl = '/' . Yii::$app->languageManager->activeLanguage . '/opportunity';
        self::assertStringContainsString($opportunityUrl, $homepage);

        $dashboard = Yii::$app->runAction('admin/dashboard/index');
        self::assertStringContainsString('notification-badge', $dashboard);
        $contactIndex = Yii::$app->runAction('admin/contact/index');
        self::assertStringContainsString('submission-row-unread', $contactIndex);
        self::assertStringContainsString('submission-status is-unread', $contactIndex);
        Yii::$app->runAction('admin/contact/detail', ['id' => $contact->id]);
        $contact->refresh();
        self::assertNotNull($contact->read_at);
    }

    public function testNewAdminInteractionsAreRendered(): void
    {
        $admin = $this->createUser('superAdmin', 'admin-interactions');
        self::assertTrue(Yii::$app->user->login($admin));

        $dashboard = Yii::$app->runAction('admin/dashboard/index');
        self::assertStringContainsString('data-quick-link="media"', $dashboard);
        self::assertStringContainsString("action.classList.toggle('is-hidden', !input.checked)", file_get_contents(Yii::getAlias('@webroot/js/app.js')));

        $about = Yii::$app->runAction('admin/setting/about');
        self::assertStringContainsString('data-tab-target="about-preview"', $about);
        self::assertStringContainsString('data-tab-target="about-edit"', $about);

        $email = Yii::$app->runAction('admin/setting/email');
        self::assertStringContainsString('name="test-email"', $email);

        $media = Yii::$app->runAction('admin/media/index');
        self::assertStringContainsString('data-image-preview', $media);
    }

    public function testDashboardControlsAndCompleteUserProfileEditorAreRendered(): void
    {
        $admin = $this->createUser('superAdmin', 'profile-ui-admin');
        self::assertTrue(Yii::$app->user->login($admin));

        $dashboard = Yii::$app->runAction('admin/dashboard/index');
        self::assertStringContainsString('data-collapse-label', $dashboard);
        $javascript = file_get_contents(Yii::getAlias('@webroot/js/app.js'));
        self::assertStringContainsString("toolbar.className = 'dashboard-widget-toolbar'", $javascript);
        self::assertStringContainsString("collapse.setAttribute('aria-expanded'", $javascript);
        self::assertStringContainsString('widgets.every(function (widget)', $javascript);
        $styles = file_get_contents(Yii::getAlias('@webroot/css/design-system.css'));
        self::assertStringNotContainsString(
            '.dashboard-widget.is-collapsed > :not(.dashboard-widget-toggle)',
            $styles
        );
        self::assertStringContainsString(
            '.dashboard-widget.is-collapsed > :not(.dashboard-widget-toolbar)',
            $styles
        );

        Yii::$app->response->clear();
        $users = Yii::$app->runAction('admin/user/index');
        foreach (['full_name', 'avatarFile', 'job_title', 'phone', 'location', 'website', 'bio'] as $attribute) {
            self::assertStringContainsString($attribute, $users);
        }
        self::assertStringContainsString('multipart/form-data', $users);
        self::assertStringContainsString('user-profile-editor', $users);
    }

    public function testEveryAdminRoleCanEditOnlyItsOwnProfileDetails(): void
    {
        $editor = $this->createUser('editor', 'self-profile-editor');
        self::assertTrue(Yii::$app->user->login($editor));

        $profile = Yii::$app->runAction('admin/user/profile');
        self::assertStringContainsString('id="form-profile"', $profile);
        self::assertStringContainsString('name="User[full_name]"', $profile);
        self::assertStringContainsString('name="User[email]"', $profile);
        self::assertStringContainsString('name="User[avatarFile]"', $profile);
        self::assertStringNotContainsString('name="User[username]"', $profile);
        self::assertStringNotContainsString('name="User[role]"', $profile);
        self::assertStringContainsString('/admin/user/profile', $profile);

        $javascript = file_get_contents(Yii::getAlias('@webroot/js/app.js'));
        self::assertStringContainsString("event.target.closest('[data-avatar-input]')", $javascript);
        self::assertStringContainsString('reader.readAsDataURL(file)', $javascript);
    }

    public function testServiceRequestsFitWithoutAHorizontalTableAndHomepageActionsAreIconOnly(): void
    {
        $admin = $this->createUser('superAdmin', 'service-request-admin');
        self::assertTrue(Yii::$app->user->login($admin));
        self::assertTrue((new Order([
            'name' => 'Example customer',
            'company' => 'Example organization',
            'phone_number' => '+98 912 000 0000',
            'email' => 'request@example.test',
            'website' => 'https://not-shown-in-the-list.example.test',
            'description' => 'A general service request.',
        ]))->save());

        $requests = Yii::$app->runAction('admin/order/index');
        self::assertStringContainsString('service-request-grid', $requests);
        self::assertStringContainsString('data-label="' . Yii::t('app', 'Contact details') . '"', $requests);
        self::assertStringNotContainsString('not-shown-in-the-list.example.test', $requests);

        self::assertTrue((new HomeSection([
            'type' => 'content',
            'title' => 'Icon action section',
            'status' => 1,
        ]))->save());
        $sections = Yii::$app->runAction('admin/home-section/index');
        foreach (['update', 'delete'] as $action) {
            self::assertMatchesRegularExpression(
                '/<a[^>]+href="[^"]*home-section\\/' . $action . '[^"]*"[^>]+aria-label="[^"]+"[^>]*>\\s*<svg/s',
                $sections
            );
        }
    }
}
