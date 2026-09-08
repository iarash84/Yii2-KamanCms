<?php

namespace console\controllers;

use common\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class SeedController extends Controller
{
    public function actionIndex($demo = true)
    {
        $password = (string) getenv('ADMIN_PASSWORD');

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user = $this->seedAdmin($password);
            $this->seedSettings($user->id);
            if (filter_var($demo, FILTER_VALIDATE_BOOLEAN)) {
                $this->seedDemoContent($user->id);
                $this->seedCompleteDemoData($user->id);
            }
            $transaction->commit();
            $this->stdout("Seed data installed successfully.\n");
            return ExitCode::OK;
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            $this->stderr($exception->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    private function seedAdmin($password)
    {
        $username = getenv('ADMIN_USERNAME') ?: 'admin';
        $email = getenv('ADMIN_EMAIL') ?: 'admin@example.com';
        $user = User::findOne(['username' => $username]);
        $isNewUser = $user === null;
        if ($isNewUser) {
            $user = new User();
        }
        if (
            ($isNewUser || $password !== '')
            && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{12,72}$/', $password)
        ) {
            throw new \RuntimeException('ADMIN_PASSWORD must satisfy the project password policy.');
        }
        $user->username = $username;
        $user->email = $email;
        $user->status = User::STATUS_ACTIVE;
        if ($isNewUser || $password !== '') {
            $user->setPassword($password);
            $user->generateAuthKey();
        }
        if (!$user->save()) {
            throw new \RuntimeException(implode(' ', $user->getFirstErrors()));
        }
        $role = Yii::$app->authManager->getRole('superAdmin');
        if ($role === null) {
            throw new \RuntimeException('RBAC migration has not been applied.');
        }
        Yii::$app->authManager->revokeAll($user->id);
        Yii::$app->authManager->assign($role, $user->id);
        return $user;
    }

    private function seedSettings($userId)
    {
        $settings = [
            'CompanyName' => 'وب‌سایت من',
            'Address' => 'نشانی شرکت',
            'Email' => getenv('ADMIN_EMAIL') ?: 'admin@example.com',
            'PhoneNumber' => '',
            'FaxNumber' => '',
            'PostalCode' => '',
            'WorkingHours' => 'شنبه تا چهارشنبه، ۸ تا ۱۷',
            'About' => '<p>متن معرفی مجموعه را از پنل مدیریت ویرایش کنید.</p>',
            'Opportunity' => '<p>برای همکاری با ما فرم زیر را تکمیل کنید.</p>',
        ];
        foreach ($settings as $type => $content) {
            $exists = Yii::$app->db->createCommand(
                'SELECT id FROM {{%site_setting}} WHERE type=:type LIMIT 1',
                [':type' => $type]
            )->queryScalar();
            $command = Yii::$app->db->createCommand();
            if ($exists) {
                $command->update('{{%site_setting}}', ['user_id' => $userId, 'content' => $content], ['id' => $exists])->execute();
            } else {
                $command->insert('{{%site_setting}}', ['user_id' => $userId, 'type' => $type, 'content' => $content])->execute();
            }
        }
    }

    private function seedDemoContent($userId)
    {
        $categoryId = Yii::$app->db->createCommand(
            'SELECT id FROM {{%blog_category}} WHERE title=:title LIMIT 1',
            [':title' => 'اخبار']
        )->queryScalar();
        if (!$categoryId) {
            Yii::$app->db->createCommand()->insert('{{%blog_category}}', [
                'user_id' => $userId,
                'title' => 'اخبار',
            ])->execute();
            $categoryId = Yii::$app->db->getLastInsertID();
        }
        if (
            $categoryId && !Yii::$app->db->createCommand(
                'SELECT 1 FROM {{%blog_post}} WHERE title=:title LIMIT 1',
                [':title' => 'شروع به کار وب‌سایت']
            )->queryScalar()
        ) {
            Yii::$app->db->createCommand()->insert('{{%blog_post}}', [
                'user_id' => $userId,
                'category_id' => $categoryId,
                'title' => 'شروع به کار وب‌سایت',
                'description' => '<p>این نوشته نمونه است و می‌توانید آن را حذف یا ویرایش کنید.</p>',
                'content' => '<p>نصب وب‌سایت با موفقیت انجام شد.</p>',
                'keywords' => 'نمونه,نصب',
            ])->execute();
        }

        $portfolioItems = [
            [
                'title' => 'پلتفرم تحلیل داده',
                'content' => '<p>داشبورد مدیریتی برای پایش شاخص‌های کلیدی و گزارش‌های عملیاتی.</p>',
                'image' => 'img/portfolio/analytics-platform.webp',
                'link_label' => 'مشاهده جزئیات پروژه',
            ],
            [
                'title' => 'همراه‌بانک سازمانی',
                'content' => '<p>تجربه امن و ساده بانکداری همراه برای مشتریان حقیقی و سازمانی.</p>',
                'image' => 'img/portfolio/mobile-banking.webp',
                'link_label' => 'مشاهده جزئیات پروژه',
            ],
            [
                'title' => 'فروشگاه آنلاین',
                'content' => '<p>تجربه خرید یکپارچه و سریع برای دسکتاپ، تبلت و موبایل.</p>',
                'image' => 'img/portfolio/commerce-experience.webp',
                'link_label' => 'مشاهده جزئیات پروژه',
            ],
        ];
        foreach ($portfolioItems as $item) {
            $sampleId = Yii::$app->db->createCommand(
                'SELECT id FROM {{%portfolio_item}} WHERE title=:title LIMIT 1',
                [':title' => $item['title']]
            )->queryScalar();

            $sample = array_merge($item, [
                'user_id' => $userId,
                'link_url' => '#',
            ]);
            if ($sampleId) {
                Yii::$app->db->createCommand()->update('{{%portfolio_item}}', $sample, ['id' => $sampleId])->execute();
            } else {
                Yii::$app->db->createCommand()->insert('{{%portfolio_item}}', $sample)->execute();
            }
        }

        $carouselTitle = 'راهکارهای دیجیتال قابل اعتماد';
        $carouselId = Yii::$app->db->createCommand(
            'SELECT id FROM {{%carousel}} WHERE title=:title OR image=:image ORDER BY id LIMIT 1',
            [
                ':title' => $carouselTitle,
                ':image' => 'img/portfolio/hero-studio.webp',
            ]
        )->queryScalar();

        $carousel = [
            'user_id' => $userId,
            'image' => 'img/portfolio/hero-studio.webp',
            'link' => '',
            'primary_button_label' => '',
            'secondary_link' => '',
            'secondary_button_label' => '',
            'title' => $carouselTitle,
            'text' => '<p>طراحی و توسعه محصولاتی که برای رشد کسب‌وکار ساخته شده‌اند.</p>',
            'show_content' => 1,
            'eyebrow' => '',
            'sort_order' => 1,
            'status' => 1,
        ];
        if ($carouselId) {
            Yii::$app->db->createCommand()->update('{{%carousel}}', $carousel, ['id' => $carouselId])->execute();
        } else {
            Yii::$app->db->createCommand()->insert('{{%carousel}}', $carousel)->execute();
        }
    }

    private function seedCompleteDemoData($userId)
    {
        $db = Yii::$app->db;
        $now = time();

        $homeSections = [
            ['features', 'خدماتی برای رشد پایدار', 'از ایده تا محصول قابل اعتماد', '<p>راهکارهای ما بر تجربه کاربر، امنیت و نگهداری‌پذیری تمرکز دارند.</p>', 10],
            ['stats', 'اعتماد در یک نگاه', 'دستاوردهای مجموعه', '<p>آمار این بخش به‌صورت خودکار از محتوای سایت محاسبه می‌شود.</p>', 20],
            ['portfolio', 'منتخب نمونه‌کارها', 'پروژه‌های اخیر', '<p>نگاهی کوتاه به تازه‌ترین تجربه‌های طراحی و توسعه ما.</p>', 30],
            ['posts', 'از وبلاگ', 'تازه‌ترین نوشته‌ها', '<p>دانش و تجربه‌های کاربردی تیم را دنبال کنید.</p>', 40],
            ['faqs', 'پاسخ‌های کوتاه و روشن', 'سؤالات متداول', '', 50],
            ['cta', 'برای شروع آماده‌اید؟', 'همکاری با ما', '<p>درباره هدف و نیاز پروژه صحبت کنیم و مسیر مناسب را پیدا کنیم.</p>', 60],
        ];
        foreach ($homeSections as [$type, $title, $subtitle, $content, $order]) {
            if (!(new \yii\db\Query())->from('{{%home_section}}')->where(['type' => $type])->exists()) {
                $db->createCommand()->insert('{{%home_section}}', [
                    'type' => $type,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'content' => $content,
                    'sort_order' => $order,
                    'status' => 1,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
            }
        }

        $mediaPaths = [
            'img/portfolio/analytics-platform.webp' => ['analytics-platform.webp', 'داشبورد تحلیل داده'],
            'img/portfolio/mobile-banking.webp' => ['mobile-banking.webp', 'همراه بانک سازمانی'],
            'img/portfolio/commerce-experience.webp' => ['commerce-experience.webp', 'فروشگاه آنلاین'],
            'img/portfolio/hero-studio.webp' => ['hero-studio.webp', 'استودیوی طراحی دیجیتال'],
        ];
        foreach ($mediaPaths as $path => [$name, $alt]) {
            if (!(new \yii\db\Query())->from('{{%media}}')->where(['path' => $path])->exists()) {
                $file = Yii::getAlias('@frontend/web/' . $path);
                $db->createCommand()->insert('{{%media}}', [
                    'path' => $path, 'original_name' => $name, 'mime_type' => 'image/webp',
                    'extension' => 'webp', 'size' => is_file($file) ? filesize($file) : 0,
                    'alt_text' => $alt, 'created_by' => $userId, 'created_at' => $now, 'updated_at' => $now,
                ])->execute();
            }
        }

        if (!(new \yii\db\Query())->from('{{%page}}')->where(['slug' => 'services'])->exists()) {
            $db->createCommand()->insert('{{%page}}', [
                'title' => 'خدمات ما', 'slug' => 'services',
                'summary' => 'راهکارهای طراحی، توسعه و بهینه‌سازی محصولات دیجیتال.',
                'content' => '<h2>راهکار متناسب با کسب‌وکار شما</h2><p>از تحلیل نیاز تا طراحی و توسعه، در کنار تیم شما هستیم.</p>',
                'status' => 'published', 'seo_title' => 'خدمات طراحی و توسعه وب',
                'seo_description' => 'معرفی خدمات طراحی تجربه کاربری و توسعه نرم‌افزار.',
                'seo_keywords' => 'طراحی وب,توسعه نرم‌افزار', 'robots' => 'index,follow',
                'created_by' => $userId, 'updated_by' => $userId, 'created_at' => $now, 'updated_at' => $now,
            ])->execute();
        }

        $menus = [
            ['خانه', '/', 'main', 10], ['وبلاگ', '/blog', 'main', 20],
            ['نمونه‌کارها', '/sample', 'main', 30], ['خدمات', '/services', 'main', 40],
            ['درباره ما', '/about', 'footer', 10], ['تماس با ما', '/contact', 'footer', 20],
            ['سؤالات متداول', '/faqs', 'footer', 30],
        ];
        foreach ($menus as [$label, $url, $location, $order]) {
            if (!(new \yii\db\Query())->from('{{%menu_item}}')->where(['label' => $label, 'location' => $location])->exists()) {
                $db->createCommand()->insert('{{%menu_item}}', [
                    'label' => $label, 'url' => $url, 'location' => $location, 'target' => '_self',
                    'sort_order' => $order, 'status' => 1, 'created_by' => $userId,
                    'created_at' => $now, 'updated_at' => $now,
                ])->execute();
            }
        }

        $faqs = [
            ['چطور می‌توانم درخواست خدمات ثبت کنم؟', 'از صفحه درخواست خدمات، مشخصات و جزئیات نیاز خود را ارسال کنید تا کارشناسان با شما تماس بگیرند.'],
            ['زمان پاسخ‌گویی چقدر است؟', 'در روزهای کاری معمولاً کمتر از یک روز کاری پاسخ می‌دهیم.'],
            ['آیا خدمات پشتیبانی ارائه می‌شود؟', 'بله، شرایط پشتیبانی متناسب با نوع پروژه در قرارداد مشخص می‌شود.'],
        ];
        foreach ($faqs as $index => [$question, $answer]) {
            if (!(new \yii\db\Query())->from('{{%faq}}')->where(['question' => $question])->exists()) {
                $db->createCommand()->insert('{{%faq}}', [
                    'user_id' => $userId, 'question' => $question, 'answer' => $answer,
                    'status' => 1, 'sort_order' => ($index + 1) * 10,
                ])->execute();
            }
        }

        $postId = (new \yii\db\Query())->select('id')->from('{{%blog_post}}')->orderBy(['id' => SORT_ASC])->scalar();
        foreach (['فناوری' => 'technology', 'طراحی' => 'design'] as $name => $slug) {
            $tagId = (new \yii\db\Query())->select('id')->from('{{%blog_tag}}')->where(['slug' => $slug])->scalar();
            if (!$tagId) {
                $db->createCommand()->insert('{{%blog_tag}}', ['name' => $name, 'slug' => $slug])->execute();
                $tagId = $db->getLastInsertID();
            }
            if ($postId && !(new \yii\db\Query())->from('{{%blog_post_tag}}')->where(['post_id' => $postId, 'tag_id' => $tagId])->exists()) {
                $db->createCommand()->insert('{{%blog_post_tag}}', ['post_id' => $postId, 'tag_id' => $tagId])->execute();
            }
        }

        $submissions = [
            ['{{%contact_submission}}', ['name' => 'کاربر نمونه', 'phone_number' => '02100000000', 'email' => 'contact@example.com', 'subject' => 'درخواست مشاوره', 'body' => 'برای بازطراحی وب‌سایت به مشاوره نیاز دارم.']],
            ['{{%order_submission}}', ['name' => 'شرکت نمونه', 'company' => 'راهکار نو', 'phone_number' => '02100000001', 'website' => 'https://example.com', 'email' => 'order@example.com', 'description' => 'طراحی و توسعه وب‌سایت شرکتی.']],
            ['{{%opportunity_submission}}', ['name' => 'متقاضی نمونه', 'phone_number' => '09120000000', 'email' => 'career@example.com']],
        ];
        foreach ($submissions as [$table, $data]) {
            if (!(new \yii\db\Query())->from($table)->where(['email' => $data['email']])->exists()) {
                $db->createCommand()->insert($table, $data)->execute();
            }
        }

        $socialSettings = ['Instagram' => 'https://instagram.com/example', 'Linkedin' => 'https://linkedin.com/company/example', 'Telegram' => 'https://t.me/example'];
        foreach ($socialSettings as $type => $content) {
            if (!(new \yii\db\Query())->from('{{%site_setting}}')->where(['type' => $type])->exists()) {
                $db->createCommand()->insert('{{%site_setting}}', ['user_id' => $userId, 'type' => $type, 'content' => $content])->execute();
            }
        }

        $this->seedVisitorAnalytics();
    }

    private function seedVisitorAnalytics()
    {
        $db = Yii::$app->db;
        $countries = ['IR' => 58, 'US' => 15, 'DE' => 10, 'CA' => 7, 'TR' => 6, 'AE' => 4];
        $pages = ['/fa' => 34, '/fa/blog' => 24, '/fa/sample' => 18, '/fa/services' => 12, '/fa/contact' => 8, '/en' => 4];
        for ($offset = 29; $offset >= 0; $offset--) {
            $date = gmdate('Y-m-d', strtotime('-' . $offset . ' days'));
            $factor = 0.72 + ((29 - $offset) / 100) + (($offset % 5) / 25);
            $views = (int) round(110 * $factor);
            $visitors = (int) round($views * 0.62);
            $this->upsertAggregate('{{%visitor_daily}}', ['visit_date' => $date], $views, $visitors);
            foreach ($countries as $code => $share) {
                $countryViews = max(1, (int) round($views * $share / 100));
                $this->upsertAggregate('{{%visitor_country_daily}}', ['visit_date' => $date, 'country_code' => $code], $countryViews, (int) round($countryViews * 0.65));
            }
            foreach ($pages as $path => $share) {
                $pageViews = max(1, (int) round($views * $share / 100));
                $this->upsertAggregate('{{%visitor_page_daily}}', ['visit_date' => $date, 'path' => $path], $pageViews, (int) round($pageViews * 0.7));
            }
        }
    }

    private function upsertAggregate($table, array $key, $views, $visitors)
    {
        $values = array_merge($key, ['page_views' => $views, 'visitors' => $visitors]);
        $exists = (new \yii\db\Query())->from($table)->where($key)->exists();
        $command = Yii::$app->db->createCommand();
        $exists ? $command->update($table, $values, $key)->execute() : $command->insert($table, $values)->execute();
    }
}
