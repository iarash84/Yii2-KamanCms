<?php

return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'user.passwordResetTokenExpire' => 3600,
    'systemInfo' => [
        'name' => 'Kaman CMS',
        'version' => getenv('APP_VERSION') ?: '0.1.0',
        'status' => 'Stable',
        'releaseDate' => getenv('APP_RELEASE_DATE') ?: '2026-09-12',
        'links' => [
            'github' => 'https://github.com/iarash84/Yii2-KamanCms',
            'documentation' => 'https://github.com/iarash84/Yii2-KamanCms/tree/main/docs',
            'changelog' => 'https://github.com/iarash84/Yii2-KamanCms/blob/main/CHANGELOG.md',
            'issues' => 'https://github.com/iarash84/Yii2-KamanCms/issues',
        ],
    ],
];
