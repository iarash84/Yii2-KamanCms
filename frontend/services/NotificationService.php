<?php

namespace frontend\services;

use common\components\SmtpTransportFactory;
use common\models\User;
use frontend\models\Contact;
use frontend\models\Opportunity;
use frontend\models\Order;
use frontend\models\SystemSetting;
use Yii;

class NotificationService
{
    public static function submissionItems($unreadOnly = false, $limit = 50)
    {
        $sources = [
            ['model' => Contact::class, 'type' => 'contact', 'label' => Yii::t('app', 'Contact'), 'url' => ['/admin/contact/index']],
            ['model' => Order::class, 'type' => 'order', 'label' => Yii::t('app', 'Service requests'), 'url' => ['/admin/order/index']],
            ['model' => Opportunity::class, 'type' => 'opportunity', 'label' => Yii::t('app', 'Job opportunity'), 'url' => ['/admin/opportunity/index']],
        ];
        $items = [];
        foreach ($sources as $source) {
            $query = $source['model']::find()->orderBy(['created_at' => SORT_DESC]);
            if ($unreadOnly) {
                $query->andWhere(['read_at' => null]);
            }
            foreach ($query->limit($limit)->all() as $model) {
                $createdAt = is_numeric($model->created_at) ? (int) $model->created_at : (strtotime((string) $model->created_at) ?: 0);
                $items[] = [
                    'type' => $source['type'],
                    'label' => $source['label'],
                    'title' => self::submissionTitle($source['type'], $model),
                    'summary' => self::submissionSummary($source['type'], $model),
                    'url' => array_merge($source['url'], ['id' => $model->id]),
                    'created_at' => $createdAt,
                    'read_at' => $model->read_at,
                ];
            }
        }
        usort($items, static fn ($left, $right) => (int) $right['created_at'] <=> (int) $left['created_at']);
        return array_slice($items, 0, $limit);
    }

    public static function markAllSubmissionsRead()
    {
        $now = time();
        foreach ([Contact::class, Order::class, Opportunity::class] as $modelClass) {
            $modelClass::updateAll(['read_at' => $now], ['read_at' => null]);
        }
    }

    public static function formSubmitted($type, array $data)
    {
        if (!filter_var(SystemSetting::getValue('notify_' . $type, '1'), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }
        $recipients = self::recipients();
        if (!$recipients) {
            return false;
        }
        self::configureMailer();
        $submissionLabels = [
            'contact' => 'contact',
            'order' => 'service request',
            'opportunity' => 'job application',
        ];
        $submissionLabel = $submissionLabels[$type] ?? (string) $type;
        $body = "New {$submissionLabel} submission\n\n";
        foreach ($data as $key => $value) {
            if (is_scalar($value) && !in_array($key, ['verifyCode', 'resume'], true)) {
                $body .= $key . ': ' . strip_tags((string) $value) . "\n";
            }
        }
        try {
            $sent = false;
            foreach ($recipients as $recipient) {
                $sent = Yii::$app->mailer->compose()->setTo($recipient)->setFrom([SystemSetting::getValue('mail_from_email', Yii::$app->params['supportEmail']) => SystemSetting::getValue('mail_from_name', Yii::$app->name)])->setSubject("New {$submissionLabel} submission")->setTextBody($body)->send() || $sent;
            }
            return $sent;
        } catch (\Throwable $e) {
            Yii::warning('Form notification failed: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }
    public static function configureMailer()
    {
        $host = SystemSetting::getValue('smtp_host');
        if (!$host) {
            return;
        }
        Yii::$app->mailer->useFileTransport = filter_var(SystemSetting::getValue('mail_file_transport', '1'), FILTER_VALIDATE_BOOLEAN);
        Yii::$app->mailer->setTransport(SmtpTransportFactory::create(
            $host,
            (int) SystemSetting::getValue('smtp_port', 587),
            SystemSetting::getValue('smtp_username'),
            SystemSetting::getValue('smtp_password'),
            SystemSetting::getValue('smtp_encryption', 'tls')
        ));
    }

    private static function recipients()
    {
        $recipients = [];
        $configuredRecipient = SystemSetting::getValue('notification_email', Yii::$app->params['adminEmail']);
        if (filter_var($configuredRecipient, FILTER_VALIDATE_EMAIL)) {
            $recipients[] = $configuredRecipient;
        }
        if (filter_var(SystemSetting::getValue('notify_admins', '0'), FILTER_VALIDATE_BOOLEAN)) {
            $userIds = array_merge(
                Yii::$app->authManager->getUserIdsByRole('admin'),
                Yii::$app->authManager->getUserIdsByRole('superAdmin')
            );
            foreach (User::find()->where(['status' => User::STATUS_ACTIVE])->andWhere(['id' => array_unique($userIds)])->all() as $user) {
                if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = $user->email;
                }
            }
        }
        return array_values(array_unique($recipients));
    }

    private static function submissionTitle($type, $model)
    {
        if ($type === 'contact') {
            return trim((string) $model->subject) ?: trim((string) $model->name);
        }
        return trim((string) $model->name);
    }

    private static function submissionSummary($type, $model)
    {
        if ($type === 'contact') {
            return trim((string) $model->body);
        }
        return $type === 'order' ? trim((string) $model->description) : trim((string) $model->email);
    }
}
