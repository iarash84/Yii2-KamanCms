<?php

namespace frontend\widgets;

use Yii;
use yii\grid\ActionColumn;
use yii\helpers\Html;

class AdminActionColumn extends ActionColumn
{
    public $contentOptions = ['class' => 'admin-table-actions-column'];
    public $headerOptions = ['class' => 'admin-table-actions-column'];

    protected function renderDataCellContent($model, $key, $index)
    {
        return Html::tag(
            'div',
            parent::renderDataCellContent($model, $key, $index),
            ['class' => 'admin-table-actions']
        );
    }

    protected function initDefaultButtons()
    {
        $this->registerButton('view', 'eye', Yii::t('app', 'View'));
        $this->registerButton('update', 'edit', Yii::t('app', 'Update'));
        $this->registerButton('delete', 'trash', Yii::t('app', 'Delete'), [
            'data-confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
            'data-method' => 'post',
            'class' => AdminButton::classes('compact', 'admin-action-delete'),
        ]);
    }

    private function registerButton(string $name, string $icon, string $label, array $defaults = []): void
    {
        if (isset($this->buttons[$name])) {
            return;
        }
        $this->buttons[$name] = static function ($url, $model, $key) use ($icon, $label, $defaults) {
            $options = array_merge([
                'class' => AdminButton::classes('compact'),
                'title' => $label,
                'aria-label' => $label,
                'data-pjax' => '0',
            ], $defaults);
            return Html::a(Icon::show($icon, ['width' => 18, 'height' => 18]), $url, $options);
        };
    }
}
