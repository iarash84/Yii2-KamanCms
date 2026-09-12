<?php

namespace frontend\models;


use common\models\User;
use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class ChangePasswordForm extends Model
{
    public $oldPassword;
    public $newPassword;
    public $repeatPassword;


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['oldPassword', 'newPassword', 'repeatPassword'], 'required'],
            //[['oldPassword, newPassword, newPassword'], 'required'],
            [['oldPassword'], 'findPasswords', 'skipOnEmpty' => false, 'skipOnError' => false],
            [['newPassword'], 'checkPasswords', 'skipOnEmpty' => false, 'skipOnError' => false],
            ['newPassword', \common\validators\PasswordValidator::class],
            ['repeatPassword', 'compare', 'compareAttribute'=>'newPassword']
        ];
    }

    //matching the old password with your existing password.
    public function checkPasswords($attribute, $params)
    {
        if($this->oldPassword == $this->newPassword) {
            $this->addError($attribute, Yii::t('app','Old password and new password is same, please chose another password.'));
        }
    }

    //matching the old password with your existing password.
    public function findPasswords($attribute, $params)
    {
        $user = User::find()->where(['id' => Yii::$app->user->identity->getId()])->one();
        if(!Yii::$app->security->validatePassword($this->oldPassword, $user->password_hash)) {
            $this->addError($attribute, Yii::t('app','Old password is wrong. please try again'));
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'oldPassword' => Yii::t('app', 'Old Password'),
            'newPassword' => Yii::t('app', 'New Password'),
            'repeatPassword' => Yii::t('app', 'Repeat Password'),

        ];
    }

}
