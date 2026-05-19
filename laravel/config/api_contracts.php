<?php

return [
    'version' => 'v1',

    'contracts' => [
        [
            'feature_id' => 'API-001',
            'name' => 'SOAP API v1/v2',
            'protocol' => 'SOAP',
            'auth' => 'admin_api_user',
            'roles' => ['admin role', 'integration role'],
            'legacy_endpoint' => '/api/soap',
            'status' => 'contract-test-required',
        ],
        [
            'feature_id' => 'API-002',
            'name' => 'XML-RPC API',
            'protocol' => 'XML-RPC',
            'auth' => 'admin_api_user',
            'roles' => ['admin role', 'integration role'],
            'legacy_endpoint' => '/api/xmlrpc',
            'status' => 'contract-test-required',
        ],
        [
            'feature_id' => 'API-003',
            'name' => 'REST/API2 OAuth',
            'protocol' => 'REST/API2',
            'auth' => 'OAuth token',
            'roles' => ['admin role', 'customer role', 'guest role'],
            'legacy_endpoint' => '/api/rest',
            'status' => 'contract-test-required',
        ],
        [
            'feature_id' => 'API-004',
            'name' => 'Payment integrations',
            'protocol' => 'REST/API2',
            'auth' => 'secret_config',
            'roles' => ['integration role'],
            'legacy_endpoint' => 'payment gateway callbacks',
            'status' => 'mock-required',
        ],
        [
            'feature_id' => 'API-005',
            'name' => 'Shipping integrations',
            'protocol' => 'REST/API2',
            'auth' => 'secret_config',
            'roles' => ['integration role'],
            'legacy_endpoint' => 'carrier rate endpoints',
            'status' => 'mock-required',
        ],
        [
            'feature_id' => 'API-006',
            'name' => 'External services',
            'protocol' => 'REST/API2',
            'auth' => 'secret_config',
            'roles' => ['integration role'],
            'legacy_endpoint' => 'third-party service endpoints',
            'status' => 'mock-required',
        ],
    ],
];
