<?php

return [
    'implementation' => 'typed config without XML',

    'env_overrides' => [
        'web/secure/base_url' => env('MAGENTO_SECURE_BASE_URL_OVERRIDE'),
    ],

    'secret_paths' => [
        'payment/gateway/token',
    ],

    'source_models' => [
        'catalog/frontend/list_mode' => ['grid', 'list'],
        'general/locale/code' => ['en_US', 'de_DE'],
        'currency/options/base' => ['USD', 'EUR'],
    ],
];
