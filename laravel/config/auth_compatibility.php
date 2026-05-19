<?php

return [
    'customer' => [
        'guard' => 'customer',
        'session_key' => 'customer_id',
        'boundary' => 'explicit_non_sharing',
        'persistent_cart' => 'same-database ownership, no cookie sharing before cutover',
    ],
    'admin' => [
        'guard' => 'admin',
        'session_key' => 'admin_user_id',
        'boundary' => 'explicit_non_sharing',
        'timeout_minutes' => 120,
    ],
    'cookie' => [
        'name' => env('SESSION_COOKIE', 'laravel_session'),
        'domain' => env('SESSION_DOMAIN'),
        'path' => '/',
        'secure' => env('SESSION_SECURE_COOKIE', false),
        'same_site' => env('SESSION_SAME_SITE', 'lax'),
        'logout_invalidation' => true,
    ],
    'form_key' => [
        'session_key' => '_form_key',
        'request_key' => 'form_key',
        'csrf' => 'Laravel CSRF remains authoritative; Magento form key is accepted only for characterized legacy flows.',
    ],
    'password_hash' => [
        'label' => 'password hash compatibility',
        'modern' => 'Laravel Hash::check with needsRehash upgrade planning',
        'legacy' => 'Magento salted md5 hash verification for imported users before password rotation',
    ],
    'session' => [
        'customer' => 'explicit non-sharing until route ownership is Laravel',
        'admin' => 'explicit non-sharing until admin route ownership is Laravel',
    ],
    'rollback' => [
        'strategy' => 'legacy_runtime_fallback',
        'invalidates_sessions' => true,
    ],
];
