<?php

namespace frontend\models;

use common\models\User;
use Yii;

/**
 * This is the model class for table "tbl_setting".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $type
 * @property string $content
 * @property string $updateDateTime
 *
 * @property User $user
 */
class Setting extends \yii\db\ActiveRecord
{
    use \frontend\models\traits\TranslatableContent;

    public function translatedAttributes()
    {
        return ['content'];
    }

    public function getLocalizedContent($language = null)
    {
        return $this->getLocalized('content', $language);
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%site_setting}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'content'], 'string'],
            [['updated_at'], 'safe']
        ];
    }



    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'type' => Yii::t('app', 'Type'),
            'content' => Yii::t('app', 'Content'),
            'updated_at' => Yii::t('app', 'Update Date Time'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @return mixed|null
     */
    public function getAbout()
    {
        $model = $this::find()->where(['type' => 'About'])->one();

        if($model == null)
            return null;
        return $model->getLocalizedContent();
    }

    /**
     * @return mixed|null
     */
    public function getOpportunity()
    {
        $model = $this::find()->where(['type' => 'Opportunity'])->one();

        if($model == null)
            return null;
        return $model->getLocalizedContent();
    }

    /**
     * @return mixed|null
     */
    public function getWorkingHours()
    {
        $model = $this::find()->where(['type' => 'WorkingHours'])->one();

        if($model == null)
            return null;
        return $model->getLocalizedContent();
    }

    public function setWorkingHours($value)
    {
        return $this->saveSettingValue('WorkingHours', $value);
    }

    /**
     * @return mixed|null
     */
    public function getPostalCode()
    {
        $model = $this::find()->where(['type' => 'PostalCode'])->one();

        if($model == null)
            return null;
        return $model->getLocalizedContent();
    }

    /**
     * @param $value
     * @return bool
     */
    public function setPostalCode($value)
    {
        $setting = new Setting();
        $setting::deleteAll(['type'=> 'PostalCode']);
        $setting->user_id = Yii::$app->user->identity->getId();
        $setting->type = 'PostalCode';
        $setting->content = $value;
        return $setting->save();
    }

    /**
     * @return mixed|null
     */
    public function getFaxNumber()
    {
        $model = $this::find()->where(['type' => 'FaxNumber'])->one();

        if($model == null)
            return null;
        return $model->getLocalizedContent();
    }

    /**
     * @param $value
     * @return bool
     */
    public function setFaxNumber($value)
    {
        $setting = new Setting();
        $setting::deleteAll(['type'=> 'FaxNumber']);
        $setting->user_id = Yii::$app->user->identity->getId();
        $setting->type = 'FaxNumber';
        $setting->content = $value;
        return $setting->save();
    }

    /**
     * @return mixed|null
     */
    public function getPhoneNumber()
    {
        $model = $this::find()->where(['type' => 'PhoneNumber'])->one();

        if($model == null)
            return null;
        return $model->getLocalizedContent();
    }

    /**
     * @param $value
     * @return bool
     */
    public function setPhoneNumber($value)
    {
        $setting = new Setting();
        $setting::deleteAll(['type'=> 'PhoneNumber']);
        $setting->user_id = Yii::$app->user->identity->getId();
        $setting->type = 'PhoneNumber';
        $setting->content = $value;
        return $setting->save();
    }


    /**
     * @return mixed|null
     */
    public function getEmail()
    {
        $model = $this::find()->where(['type' => 'Email'])->one();

        if($model == null)
            return null;
        return $model->getLocalizedContent();
    }

    /**
     * @param $value
     * @return bool
     */
    public function setEmail($value)
    {
        $setting = new Setting();
        $setting::deleteAll(['type'=> 'Email']);
        $setting->user_id = Yii::$app->user->identity->getId();
        $setting->type = 'Email';
        $setting->content = $value;
        return $setting->save();

    }

    /**
     * @return mixed|null
     */
    public function getAddress()
    {
        $model = $this::find()->where(['type' => 'Address'])->one();

        if($model == null)
            return null;
        return $model->getLocalizedContent();
    }

    /**
     * @param $value
     */
    public function setAddress($value)
    {
        return $this->saveSettingValue('Address', $value);
    }


    /**
     * @return mixed|null
     */
    public function getCompanyName()
    {
        $model = $this::find()->where(['type' => 'CompanyName'])->one();

        if($model == null)
            return null;
        return $model->getLocalizedContent();
    }

    /**
     * @param $value
     */
    public function setCompanyName($value)
    {
        return $this->saveSettingValue('CompanyName', $value);
    }

    private function saveSettingValue($type, $value)
    {
        $setting = static::findOne(['type' => $type]) ?: new self(['type' => $type]);
        $setting->user_id = Yii::$app->user->id ?: null;
        $setting->content = $value;
        return $setting->save();
    }

    public function getSocialLinks()
    {
        $labels = ['Facebook', 'Twitter', 'Linkedin', 'Instagram', 'Youtube', 'Telegram', 'Aparat'];
        $rows = static::find()->select(['type', 'content'])->where(['type' => $labels])->indexBy('type')->all();
        $links = [];
        foreach ($labels as $type) {
            $url = isset($rows[$type]) ? trim((string) $rows[$type]->content) : '';
            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                $links[$type] = $url;
            }
        }
        return $links;
    }


    /**
     * @return mixed|null
     */
    public function getTwitter()
    {
        $model = $this::find()->where(['type' => 'Twitter'])->one();

        if($model == null)
            return null;
        return $model->content;
    }


    /**
     * @param $value
     */
    public function setTwitter($value)
    {
        $setting = new Setting();
        $setting::deleteAll(['type'=> 'Twitter']);
        $setting->user_id = Yii::$app->user->identity->getId();
        $setting->type = 'Twitter';
        $setting->content = $value;
        return $setting->save();
    }
    /**
     * @return mixed|null
     */
    public function getLinkedin()
    {
        $model = $this::find()->where(['type' => 'Linkedin'])->one();

        if($model == null)
            return null;
        return $model->content;
    }

    /**
     * @param $value
     */
    public function setLinkedin($value)
    {
        $setting = new Setting();
        $setting::deleteAll(['type'=> 'Linkedin']);
        $setting->user_id = Yii::$app->user->identity->getId();
        $setting->type = 'Linkedin';
        $setting->content = $value;
        return $setting->save();
    }
    /**
     * @return mixed|null
     */
    public function getAparat()
    {
        $model = $this::find()->where(['type' => 'Aparat'])->one();

        if($model == null)
            return null;
        return $model->content;
    }

    /**
     * @param $value
     */
    public function setAparat($value)
    {
        $setting = new Setting();
        $setting::deleteAll(['type'=> 'Aparat']);
        $setting->user_id = Yii::$app->user->identity->getId();
        $setting->type = 'Aparat';
        $setting->content = $value;
        return $setting->save();
    }
    /**
     * @return mixed|null
     */
    public function getTelegram()
    {
        $model = $this::find()->where(['type' => 'Telegram'])->one();

        if($model == null)
            return null;
        return $model->content;
    }

    /**
     * @param $value
     */
    public function setTelegram($value)
    {
        $setting = new Setting();
        $setting::deleteAll(['type'=> 'Telegram']);
        $setting->user_id = Yii::$app->user->identity->getId();
        $setting->type = 'Telegram';
        $setting->content = $value;
        return $setting->save();
    }
    /**
     * @return mixed|null
     */
    public function getYoutube()
    {
        $model = $this::find()->where(['type' => 'Youtube'])->one();

        if($model == null)
            return null;
        return $model->content;
    }

    /**
     * @param $value
     */
    public function setYoutube($value)
    {
        $setting = new Setting();
        $setting::deleteAll(['type'=> 'Youtube']);
        $setting->user_id = Yii::$app->user->identity->getId();
        $setting->type = 'Youtube';
        $setting->content = $value;
        return $setting->save();
    }
    /**
     * @return mixed|null
     */
    public function getInstagram()
    {
        $model = $this::find()->where(['type' => 'Instagram'])->one();

        if($model == null)
            return null;
        return $model->content;
    }


    /**
     * @param $value
     */
    public function setInstagram($value)
    {
        $setting = new Setting();
        $setting::deleteAll(['type'=> 'Instagram']);
        $setting->user_id = Yii::$app->user->identity->getId();
        $setting->type = 'Instagram';
        $setting->content = $value;
        return $setting->save();
    }

    /**
     * @return mixed|null
     */
    public function getFacebook()
    {
        $model = $this::find()->where(['type' => 'Facebook'])->one();

        if($model == null)
            return null;
        return $model->content;
    }

    /**
     * @return mixed|null
     */
    /**
     * @param $value
     */
    public function setFacebook($value)
    {
        $setting = new Setting();
        $setting::deleteAll(['type'=> 'Facebook']);
        $setting->user_id = Yii::$app->user->identity->getId();
        $setting->type = 'Facebook';
        $setting->content = $value;
        return $setting->save();
    }

}
