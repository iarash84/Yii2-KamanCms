<?php

$requiredDatabaseVariables = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
foreach ($requiredDatabaseVariables as $variable) {
    if (getenv($variable) === false || trim((string) getenv($variable)) === '') {
        throw new RuntimeException($variable . ' must be configured in production.');
    }
}

return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => sprintf(
                'mysql:host=%s;port=%s;dbname=%s',
                getenv('DB_HOST'),
                getenv('DB_PORT'),
                getenv('DB_NAME')
            ),
            'username' => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'charset' => 'utf8mb4',
        ],
        'mailer' => [
            'class' => 'yii\symfonymailer\Mailer',
            'viewPath' => '@common/mail',
            'useFileTransport' => filter_var(getenv('MAIL_USE_FILE_TRANSPORT') ?: '0', FILTER_VALIDATE_BOOLEAN),
        ],
    ],
];
