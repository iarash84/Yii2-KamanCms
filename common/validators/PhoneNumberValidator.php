<?php

namespace common\validators;

use Yii;
use yii\validators\Validator;

/**
 * Accepts international phone numbers and normalizes Persian/Arabic digits.
 */
class PhoneNumberValidator extends Validator
{
    public $message;

    public function init()
    {
        parent::init();
        $this->message = $this->message ?: Yii::t('app', 'Enter a valid phone number.');
    }

    public function validateAttribute($model, $attribute)
    {
        $normalized = self::normalize($model->$attribute);
        $model->$attribute = $normalized;

        if ($normalized !== '' && !preg_match('/^\+?[0-9]{8,15}$/', $normalized)) {
            $this->addError($model, $attribute, $this->message);
        }
    }

    protected function validateValue($value)
    {
        $normalized = self::normalize($value);
        if ($normalized === '' || preg_match('/^\+?[0-9]{8,15}$/', $normalized)) {
            return null;
        }

        return [$this->message, []];
    }

    private static function normalize($value): string
    {
        $value = strtr(trim((string) $value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return (string) preg_replace('/[\s().-]+/u', '', $value);
    }
}
