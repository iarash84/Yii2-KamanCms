<?php

namespace frontend\models;



use common\validators\PhoneNumberValidator;
use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class OrderForm extends Model
{
    public $name;
    public $email;
    public $company;
    public $phoneNumber;
    public $website;
    public $description;
    public $verifyCode;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // name, email, subject and body are required
            [['name', 'email', 'description','phoneNumber'], 'required'],
            [['name', 'email', 'company', 'phoneNumber', 'website'], 'filter', 'filter' => 'trim'],
            [['name', 'company'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 5000],
            [['website'], 'filter', 'filter' => static function ($value) {
                $value = trim((string) $value);
                return $value !== '' && !preg_match('~^https?://~i', $value) ? 'https://' . $value : $value;
            }],
            [['website'], 'url', 'validSchemes' => ['http', 'https'], 'defaultScheme' => 'https', 'skipOnEmpty' => true],
            [['website', 'email'], 'string', 'max' => 255],
            // email has to be a valid email address
            ['email', 'email'],
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
            'company' => Yii::t('app', 'Company'),
            'email' => Yii::t('app', 'Email'),
            'phoneNumber' => Yii::t('app', 'Phone Number'),
            'website' => Yii::t('app', 'Website'),
            'description' => Yii::t('app', 'Request details'),
            'verifyCode' => Yii::t('app', 'Verify Code'),
        ];
    }

    /**
     * Sends an email to the specified email address using the information collected by this model.
     *
     * @param  string  $email the target email address
     * @return boolean whether the email was sent
     */
    public function saveOrder()
    {
        $orderModel = new Order();
        $orderModel->name = $this->name;
        $orderModel->email = $this->email;
        $orderModel->company = $this->company;
        $orderModel->phone_number = $this->phoneNumber;
        $orderModel->website = $this->website;
        $orderModel->description = $this->description;
        if($orderModel->save())
            return true;
        return false;
    }
}
