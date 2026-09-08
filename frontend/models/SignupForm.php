<?php
namespace frontend\models;

use common\components\SecureUpload;
use common\models\User;
use yii\base\Model;
use Yii;

/**
 * Signup form
 */
class SignupForm extends Model
{
    public $username;
    public $full_name;
    public $email;
    public $password;
    public $role = 'editor';
    public $avatarFile;
    public $job_title;
    public $phone;
    public $location;
    public $website;
    public $bio;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['username', 'filter', 'filter' => 'trim'],
            ['username', 'required'],
            ['username', 'unique', 'targetClass' => '\common\models\User', 'message' => 'This username has already been taken.'],
            ['username', 'string', 'min' => 2, 'max' => 255],

            [['full_name', 'job_title', 'phone', 'location', 'website'], 'filter', 'filter' => 'trim'],
            ['full_name', 'required'],
            ['full_name', 'string', 'max' => 160],
            ['job_title', 'string', 'max' => 120],
            ['phone', 'string', 'max' => 32],
            ['phone', \common\validators\PhoneNumberValidator::class, 'skipOnEmpty' => true],
            ['location', 'string', 'max' => 160],
            ['website', 'string', 'max' => 255],
            ['website', 'url', 'defaultScheme' => 'https'],
            ['bio', 'string', 'max' => 1000],
            [
                'avatarFile',
                'file',
                'skipOnEmpty' => true,
                'extensions' => ['png', 'jpg', 'jpeg', 'webp'],
                'mimeTypes' => ['image/png', 'image/jpeg', 'image/webp'],
                'maxSize' => 2 * 1024 * 1024,
            ],

            ['role', 'required'],
            ['role', 'in', 'range' => ['editor', 'admin', 'superAdmin']],

            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\common\models\User', 'message' => 'This email address has already been taken.'],

            ['password', 'required'],
            ['password', \common\validators\PasswordValidator::class],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'username' => Yii::t('app', 'User Name'),
            'full_name' => Yii::t('app', 'Full name'),
            'email' => Yii::t('app', 'Email'),
            'password' => Yii::t('app', 'Password'),
            'role' => Yii::t('app', 'Role'),
            'avatarFile' => Yii::t('app', 'Profile photo'),
            'job_title' => Yii::t('app', 'Job title'),
            'phone' => Yii::t('app', 'Phone number'),
            'location' => Yii::t('app', 'Location'),
            'website' => Yii::t('app', 'Website'),
            'bio' => Yii::t('app', 'Biography'),
        ];
    }

    /**
     * Signs user up.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function signup()
    {
        if (!$this->validate()) {
            return null;
        }

        $avatar = $this->avatarFile === null ? null : SecureUpload::storeAvatar($this->avatarFile);
        $user = new User();
        $user->username = $this->username;
        $user->full_name = $this->full_name;
        $user->email = $this->email;
        $user->job_title = $this->job_title;
        $user->phone = $this->phone;
        $user->location = $this->location;
        $user->website = $this->website;
        $user->bio = $this->bio;
        $user->avatar = $avatar;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        if (!$user->save()) {
            SecureUpload::deleteAvatar($avatar);
            return null;
        }

        $role = Yii::$app->authManager->getRole($this->role);
        if ($role === null) {
            $user->delete();
            SecureUpload::deleteAvatar($avatar);
            $this->addError('role', Yii::t('app', 'Invalid role.'));
            return null;
        }
        Yii::$app->authManager->assign($role, $user->id);

        return $user;
    }
}
