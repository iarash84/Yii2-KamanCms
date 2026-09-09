<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);
$languageCodes = array_keys($params['languages']);
$languagePattern = implode('|', array_map(static function ($code) {
    return preg_quote($code, '#');
}, $languageCodes));
$defaultLanguage = $params['languages'][$params['defaultLanguage']]['yii'] ?? 'en_US';

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'languageBootstrap', 'frontend\components\SecurityHeaders', 'frontend\components\MaintenanceMode', 'visitorAnalytics'],
    'language' => $defaultLanguage,
    'sourceLanguage' => 'en_US',
    'controllerNamespace' => 'frontend\controllers',
    'modules' => [
        'admin' => [
            'class' => 'frontend\modules\admin\Module',
        ],
    ],
    'components' => [
        'assetManager' => [
            'appendTimestamp' => true,
        ],
        'mutex' => [
            'class' => yii\mutex\FileMutex::class,
            'mutexPath' => '@runtime/mutex',
        ],
        'languageManager' => [
            'class' => 'frontend\components\LanguageManager',
            'languages' => $params['languages'],
            'defaultLanguage' => $params['defaultLanguage'],
            'adminLanguage' => $params['adminLanguage'],
        ],
        'languageBootstrap' => [
            'class' => 'frontend\components\LanguageBootstrap',
        ],
        'visitorAnalytics' => [
            'class' => 'frontend\components\VisitorAnalytics',
        ],
        'formatter' => [
            'class' => 'frontend\components\LocalizedFormatter',
            'dateFormat' => 'php:Y/m/d',
            'datetimeFormat' => 'php:Y/m/d H:i',
            'timeFormat' => 'php:H:i',
        ],
        'request' => [
            'enableCsrfValidation' => true,
        ],
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'en_US',
                ],
            ],
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => [
                'name' => '_identity',
                'httpOnly' => true,
                'sameSite' => 'Lax',
                'secure' => YII_ENV_PROD,
            ],
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'defaultRoles' => ['guest'],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'class' => 'frontend\components\LocalizedUrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'admin' => 'admin/dashboard/index',
                'admin/blog' => 'admin/blog/index',
                'admin/users' => 'admin/user/index',
                'admin/settings' => 'admin/setting/index',
                'admin/<controller:[\w-]+>/<action:[\w-]+>' => 'admin/<controller>/<action>',
                'admin/<controller:[\w-]+>' => 'admin/<controller>/index',
                "<language:{$languagePattern}>" => 'site/index',
                "<language:{$languagePattern}>/blog" => 'blog/index',
                "<language:{$languagePattern}>/blog/category/<id:\\d+>" => 'blog/category',
                "<language:{$languagePattern}>/blog/<id:\\d+>/<subject>" => 'blog/view',
                "<language:{$languagePattern}>/blog/<id:\\d+>" => 'blog/view',
                "<language:{$languagePattern}>/search" => 'search/index',
                "<language:{$languagePattern}>/<alias:index|contact|about|login|logout|faqs|order|sample|opportunity>" => 'site/<alias>',
                "<language:{$languagePattern}>/<slug:[a-z0-9][a-z0-9-]*>" => 'page/view',
                'blog' => 'blog/index',
                'changepass' => 'admin/user/change',
                '<alias:index|contact|about|login|logout|faqs|order|sample|opportunity>' => 'site/<alias>',
                '<controller:\w+>s' => '<controller>/index',
                '<controller:\w+>/<id:\d+>/<action:(update|delete)>' => '<controller>/<action>',
                '<controller:\w+>/<id:\d+>/<subject>/' => '<controller>/view',
                '<controller:\w+>/<id:\d+>' => '<controller>/view'
            ],
        ],

    ],
    'params' => $params,
];

