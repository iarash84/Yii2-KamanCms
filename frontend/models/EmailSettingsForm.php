<?php

namespace frontend\models;

use common\components\SmtpTransportFactory;
use Yii;
use yii\base\Model;

class EmailSettingsForm extends Model
{
    public $smtpHost;
    public $smtpPort = 587;
    public $smtpUsername;
    public $smtpPassword;
    public $smtpEncryption = 'tls';
    public $fromEmail;
    public $fromName;
    public $notificationEmail;
    public $fileTransport = true;
    public $notifyContact = true;
    public $notifyOrder = true;
    public $notifyOpportunity = true;
    public $notifyAdmins = false;
    public function rules()
    {
        return [[['smtpHost', 'smtpUsername', 'smtpPassword', 'fromName'], 'string', 'max' => 255], [['smtpPort'], 'integer', 'min' => 1, 'max' => 65535], [['smtpEncryption'], 'in', 'range' => ['', 'tls', 'ssl']], [['fromEmail', 'notificationEmail'], 'email'], [['fileTransport', 'notifyContact', 'notifyOrder', 'notifyOpportunity', 'notifyAdmins'], 'boolean']];
    }
    public function attributeLabels()
    {
        return ['smtpHost' => Yii::t('app', 'SMTP host'), 'smtpPort' => Yii::t('app', 'SMTP port'), 'smtpUsername' => Yii::t('app', 'SMTP username'), 'smtpPassword' => Yii::t('app', 'SMTP password'), 'smtpEncryption' => Yii::t('app', 'Encryption'), 'fromEmail' => Yii::t('app', 'Sender email'), 'fromName' => Yii::t('app', 'Sender name'), 'notificationEmail' => Yii::t('app', 'Notification recipient'), 'fileTransport' => Yii::t('app', 'Write emails to files'), 'notifyContact' => Yii::t('app', 'Contact notifications'), 'notifyOrder' => Yii::t('app', 'Service request notifications'), 'notifyOpportunity' => Yii::t('app', 'Opportunity notifications'), 'notifyAdmins' => Yii::t('app', 'Email active admins for each new notification')];
    }
    public function loadSettings()
    {
        $map = $this->map();
        foreach ($map as $property => $key) {
            if ($property !== 'smtpPassword') {
                $this->$property = SystemSetting::getValue($key, $this->$property);
            }
        }
    }
    public function saveSettings()
    {
        foreach ($this->map() as $property => $key) {
            if ($property === 'smtpPassword' && $this->$property === '') {
                continue;
            } SystemSetting::put($key, $this->$property, $property === 'smtpPassword');
        } return true;
    }
    public function testConnection()
    {
        if (!$this->smtpHost) {
            throw new \RuntimeException(Yii::t('app', 'SMTP host is required for connection testing.'));
        }
        $password = $this->smtpPassword !== ''
            ? $this->smtpPassword
            : (string) SystemSetting::getValue('smtp_password', '');
        $transport = SmtpTransportFactory::create(
            $this->smtpHost,
            (int) $this->smtpPort,
            (string) $this->smtpUsername,
            $password,
            (string) $this->smtpEncryption
        );
        $transport->getStream()->setTimeout(10);
        $transport->start();
        $transport->stop();
        return true;
    }
    private function map()
    {
        return ['smtpHost' => 'smtp_host','smtpPort' => 'smtp_port','smtpUsername' => 'smtp_username','smtpPassword' => 'smtp_password','smtpEncryption' => 'smtp_encryption','fromEmail' => 'mail_from_email','fromName' => 'mail_from_name','notificationEmail' => 'notification_email','fileTransport' => 'mail_file_transport','notifyContact' => 'notify_contact','notifyOrder' => 'notify_order','notifyOpportunity' => 'notify_opportunity','notifyAdmins' => 'notify_admins'];
    }
}
