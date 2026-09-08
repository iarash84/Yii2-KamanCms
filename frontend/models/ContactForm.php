<?php

namespace frontend\models;


use common\validators\PhoneNumberValidator;
use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class ContactForm extends Model
{
    public $name;
    public $email;
    public $phoneNumber;
    public $subject;
    public $body;
    public $verifyCode;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // name, email, subject and body are required
            [['name', 'email', 'subject', 'body'], 'required'],
            [['name', 'email', 'phoneNumber', 'subject'], 'filter', 'filter' => 'trim'],
            [['name', 'subject'], 'string', 'max' => 255],
            [['body'], 'string', 'max' => 5000],
            // email has to be a valid email address
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            // verifyCode needs to be entered correctly
            ['verifyCode', \common\validators\TextCaptchaValidator::class],
            ['phoneNumber', PhoneNumberValidator::class],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => Yii::t('app', 'Name and family'),
            'email' => Yii::t('app', 'Email'),
            'phoneNumber' => Yii::t('app', 'Phone Number'),
            'subject' => Yii::t('app', 'Subject'),
            'body' => Yii::t('app', 'Body'),
            'verifyCode' => Yii::t('app', 'Verify Code'),
        ];
    }

    /**
     * Sends an email to the specified email address using the information collected by this model.
     *
     * @param  string  $email the target email address
     * @return boolean whether the email was sent
     */
    public function saveContact()
    {
        $contactUsModel = new Contact();
        $contactUsModel->name = $this->name;
        $contactUsModel->email = $this->email;
        $contactUsModel->phone_number = $this->phoneNumber;
        $contactUsModel->subject = $this->subject;
        $contactUsModel->body = $this->body;
        if($contactUsModel->save())
            return true;
        return false;
    }
}
