<?php

namespace frontend\models;

use common\models\User;
use Yii;

/**
 * This is the model class for table "tbl_carousel".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $image
 * @property string $link
 * @property string $title
 * @property string $text
 * @property integer $show_content
 * @property string $eyebrow
 * @property string $primary_button_label
 * @property string $secondary_link
 * @property string $secondary_button_label
 * @property integer $sort_order
 * @property integer $status
 *
 * @property User $user
 */
class Carousel extends \yii\db\ActiveRecord
{
    use traits\TranslatableContent;

    public function translatedAttributes()
    {
        return ['title', 'text', 'eyebrow', 'primary_button_label', 'secondary_button_label'];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%carousel}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'sort_order', 'status', 'show_content'], 'integer'],
            [['sort_order'], 'default', 'value' => 0],
            [['status'], 'default', 'value' => 1],
            [['show_content'], 'default', 'value' => 0],
            [['text'], 'string'],
            [['image'], 'string', 'max' => 255],
            [['title', 'eyebrow', 'primary_button_label', 'secondary_button_label'], 'string', 'max' => 128],
            [['link', 'secondary_link'], 'string', 'max' => 255],
            [['link', 'secondary_link'], 'match', 'pattern' => '~^(?:https?://|/|#|mailto:).+~i', 'skipOnEmpty' => true, 'message' => Yii::t('app', 'Enter a valid public link.')],
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
            'image' => Yii::t('app', 'Image'),
            'link' => Yii::t('app', 'Link'),
            'title' => Yii::t('app', 'Title'),
            'text' => Yii::t('app', 'Text'),
            'show_content' => Yii::t('app', 'Show content on this slide'),
            'eyebrow' => Yii::t('app', 'Eyebrow text'),
            'primary_button_label' => Yii::t('app', 'Primary button label'),
            'secondary_link' => Yii::t('app', 'Secondary link'),
            'secondary_button_label' => Yii::t('app', 'Secondary button label'),
            'sort_order' => Yii::t('app', 'Display order'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ((int) $this->show_content === 1) {
            static::updateAll(['show_content' => 0], ['and', ['<>', 'id', $this->id], ['show_content' => 1]]);
        }
    }
}
