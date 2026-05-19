<?php

return [
    'default_owner' => 'legacy',

    'legacy_base_url' => env('MAGENTO_LEGACY_BASE_URL', 'http://127.0.0.1:8080'),

    'admin_frontname' => env('MAGENTO_ADMIN_FRONTNAME', 'admin'),

    'store_codes' => [
        'default',
        'de',
        'en',
    ],

    'fallback' => [
        'enabled' => env('MODERNIZATION_LEGACY_FALLBACK_ENABLED', false),
        'rollback' => 'legacy',
    ],

    'session_boundary' => [
        'customer' => 'explicit_non_sharing',
        'admin' => 'explicit_non_sharing',
    ],

    'routes' => [
        [
            'pattern' => '_modernization/*',
            'methods' => ['GET'],
            'owner' => 'Laravel',
            'feature' => 'ARCH',
            'feature_flag' => 'route.modernization.laravel',
            'rollback' => 'legacy_disabled',
        ],
        [
            'pattern' => 'admin/*',
            'methods' => ['GET', 'POST'],
            'owner' => 'bridge',
            'feature' => 'ADMIN_ROUTE_BOUNDARY',
            'feature_flag' => 'route.admin.bridge',
            'rollback' => 'legacy_admin_frontname',
        ],
        [
            'pattern' => '*/catalog/*',
            'methods' => ['GET'],
            'owner' => 'legacy',
            'feature' => 'CATALOG_URL_REWRITE',
            'feature_flag' => 'route.catalog.legacy',
            'rollback' => 'legacy_catalog',
        ],
        [
            'pattern' => 'checkout/*',
            'methods' => ['GET', 'POST'],
            'owner' => 'bridge',
            'feature' => 'CHECKOUT_FORM_KEY_BOUNDARY',
            'feature_flag' => 'route.checkout.bridge',
            'rollback' => 'legacy_checkout',
        ],
    ],
];
