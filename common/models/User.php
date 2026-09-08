<?php
namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string|null $full_name
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string|null $avatar
 * @property string|null $job_title
 * @property string|null $phone
 * @property string|null $location
 * @property string|null $website
 * @property string|null $bio
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;
    public $password;
    public $isSuperAdmin;
    public $role;
    public $avatarFile;
    public $removeAvatar = false;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'email', 'full_name', 'job_title', 'phone', 'location', 'website'], 'filter', 'filter' => 'trim'],
            [['username', 'email'], 'required'],
            [['username', 'email'], 'string', 'max' => 255],
            ['full_name', 'string', 'max' => 160],
            ['job_title', 'string', 'max' => 120],
            ['phone', 'string', 'max' => 32],
            ['phone', \common\validators\PhoneNumberValidator::class, 'skipOnEmpty' => true],
            ['location', 'string', 'max' => 160],
            ['website', 'string', 'max' => 255],
            ['website', 'url', 'defaultScheme' => 'https'],
            ['bio', 'string', 'max' => 1000],
            ['email', 'email'],
            [['username', 'email'], 'unique'],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
            ['isSuperAdmin', 'boolean'],
            ['removeAvatar', 'boolean'],
            ['role', 'in', 'range' => ['editor', 'admin', 'superAdmin']],
            ['password', \common\validators\PasswordValidator::class, 'skipOnEmpty' => true],
            [
                'avatarFile',
                'file',
                'skipOnEmpty' => true,
                'extensions' => ['png', 'jpg', 'jpeg', 'webp'],
                'mimeTypes' => ['image/png', 'image/jpeg', 'image/webp'],
                'maxSize' => 2 * 1024 * 1024,
            ],
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
            'avatarFile' => Yii::t('app', 'Profile photo'),
            'removeAvatar' => Yii::t('app', 'Remove current photo'),
            'job_title' => Yii::t('app', 'Job title'),
            'phone' => Yii::t('app', 'Phone number'),
            'location' => Yii::t('app', 'Location'),
            'website' => Yii::t('app', 'Website'),
            'bio' => Yii::t('app', 'Biography'),
            'password' => Yii::t('app', 'Password'),
            'created_at' => Yii::t('app', 'Create Date Time'),
            'isSuperAdmin' => Yii::t('app', 'User Management Access'),
            'role' => Yii::t('app', 'Role'),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }
}
