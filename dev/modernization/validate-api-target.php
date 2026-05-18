#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateApiSpecs($errors);

if ($final) {
    validateFinalApiImplementation($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'API target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateApiSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Preserve all existing storefront and admin screens, flows, URLs, API contracts, cron behavior, order/payment/tax/cart behavior, and visual look and feel unless an explicit retirement or difference is approved.',
            'Build Laravel bootstrap, config, module registry, no-XML manifest system, route strangler/fallback, EAV access layer, auth/session/security foundation, scheduler, queue, events, API contracts, and operations hooks.',
            'API, cron, queue, auth, security, performance, accessibility, deployment, rollback, backup/restore, docs, and operations gates pass.',
        ],
        'specs/modernization/architecture-specs.md' => [
            'APIs',
            'How legacy APIs are preserved and modern APIs are added.',
            'Auth, payloads, versioning, resources, error formats, docs.',
            'Required legacy endpoints are contract-tested before replacement.',
            'Modern APIs require versioning and OpenAPI documentation.',
        ],
        'specs/modernization/compatibility-policy.md' => [
            'Existing critical API behavior remains compatible.',
            'Existing API clients',
            'Preserve for approved endpoints',
            'API contract tests.',
            'API endpoint compatibility.',
        ],
        'specs/modernization/roadmap.md' => [
            'Phase 11: APIs',
            'preserve existing API behavior while adding Laravel-native API implementation.',
            'Inventory REST, XML-RPC, SOAP, and API2 endpoints in use.',
            'Implement request validation and response resources.',
            'OpenAPI docs build successfully.',
        ],
        'specs/modernization/test-plan.md' => [
            'API Test Plan',
            'REST',
            'XML-RPC',
            'SOAP',
            'API2',
            'OpenAPI docs, versioning, auth, resource serialization.',
            'Contract tests cover all supported endpoints.',
        ],
        'specs/modernization/complex-feature-reverse-engineering.md' => [
            'API-001 through API-003',
            'SOAP, XML-RPC, REST/API2 auth, payloads, errors, filters, pagination, permissions.',
            'Contract tests with legacy response comparison and error mapping.',
            'API-004 through API-006',
            'Sandbox or mock payload tests, timeout/retry/failure behavior, secret/config tests.',
        ],
        'specs/modernization/backlog.md' => [
            'Add API contract tests',
            'Required SOAP, XML-RPC, REST/API2, payment, shipping, and service endpoints are covered.',
            'PHPUnit 12 API tests green.',
        ],
        'specs/modernization/risk-register.md' => [
            'API client breakage',
            'Contract tests and endpoint inventory.',
        ],
        'docs/content/modernization/compatibility.md' => [
            'APIs',
            'Preserve required clients through contract tests.',
        ],
        'docs/content/modernization/testing.md' => [
            'API contract tests pass for preserved endpoints.',
        ],
        'docusaurus/docs/developer/testing-and-verification.md' => [
            'API contract',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing API planning phrase '{$phrase}'";
            }
        }
    }

    validateApiCatalog($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateApiCatalog(array &$errors): void
{
    $path = 'specs/modernization/magento-feature-catalog.md';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (apiFeatureIds() as $featureId) {
        if (! str_contains($content, "| {$featureId} |")) {
            $errors[] = "{$path}: missing API feature row for {$featureId}";
        }
    }

    foreach (['SOAP API v1/v2', 'XML-RPC API', 'REST/API2', 'Payment integrations', 'Shipping integrations', 'External services'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing API catalog phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalApiImplementation(array &$errors): void
{
    validateApiRoutes($errors);
    validateApiArtifacts($errors);
    validateApiTests($errors);
    validateApiDocumentation($errors);
    validateApiEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateApiRoutes(array &$errors): void
{
    $apiRoutePath = 'laravel/routes/api.php';
    $content = readTextFile($apiRoutePath, $errors);
    if ($content === null) {
        return;
    }

    foreach (['apiResource', 'middleware', 'version', 'fallback', 'legacy'] as $phrase) {
        if (! containsCaseInsensitive($content, $phrase)) {
            $errors[] = "{$apiRoutePath}: missing API route phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateApiArtifacts(array &$errors): void
{
    $appFiles = phpFilesUnder('laravel/app');

    $requirements = [
        'API controller' => '/\/Http\/Controllers\/.*Api.*Controller\.php$|\/Http\/Controllers\/Api\//',
        'API resource' => '/\/Http\/Resources\/|Resource\.php$/',
        'API form request' => '/\/Http\/Requests\/|Request\.php$/',
        'API auth or policy artifact' => '/\/Policies\/|Policy\.php$|Api.*Auth|Token|OAuth|Sanctum/',
        'legacy API compatibility artifact' => '/Legacy.*Api|Api.*Legacy|Soap|XmlRpc|XMLRPC|Api2|Contract/i',
    ];

    foreach ($requirements as $label => $pattern) {
        if (! pathOrContentMatches($appFiles, $pattern)) {
            $errors[] = "Final API implementation requires a Laravel {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateApiTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $coverage = [
        'JSON API requests' => false,
        'SOAP contract' => false,
        'XML-RPC contract' => false,
        'REST/API2 OAuth and roles' => false,
        'error formats and status codes' => false,
        'payment/shipping/external integration mocks' => false,
        'all API feature IDs' => false,
    ];

    foreach ($testFiles as $testFile) {
        $content = file_get_contents($testFile);
        if ($content === false || preg_match('/api|soap|xml-rpc|xmlrpc|api2|oauth|openapi|API-\d{3}/i', $content.$testFile) !== 1) {
            continue;
        }

        $coverage['JSON API requests'] = $coverage['JSON API requests'] || preg_match('/getJson|postJson|putJson|patchJson|deleteJson|assertJson|AssertableJson/i', $content) === 1;
        $coverage['SOAP contract'] = $coverage['SOAP contract'] || preg_match('/SOAP|WSDL|soap/i', $content) === 1;
        $coverage['XML-RPC contract'] = $coverage['XML-RPC contract'] || preg_match('/XML-RPC|XMLRPC|xml-rpc|xmlrpc/i', $content) === 1;
        $coverage['REST/API2 OAuth and roles'] = $coverage['REST/API2 OAuth and roles'] || preg_match('/REST|API2|OAuth|admin role|customer role|guest role|token/i', $content) === 1;
        $coverage['error formats and status codes'] = $coverage['error formats and status codes'] || preg_match('/assertStatus|assertUnauthorized|assertForbidden|assertNotFound|error payload|error format/i', $content) === 1;
        $coverage['payment/shipping/external integration mocks'] = $coverage['payment/shipping/external integration mocks'] || preg_match('/payment|shipping|external|integration|Http::fake|timeout|retry|mock/i', $content) === 1;
        $coverage['all API feature IDs'] = $coverage['all API feature IDs'] || allApiFeatureIdsPresent($content);
    }

    foreach ($coverage as $label => $covered) {
        if (! $covered) {
            $errors[] = "Final API implementation requires PHPUnit coverage for {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateApiDocumentation(array &$errors): void
{
    $candidatePaths = [
        'laravel/openapi.yaml',
        'laravel/openapi.yml',
        'laravel/docs/openapi.yaml',
        'docs/content/modernization/openapi.yaml',
        'docusaurus/docs/developer/openapi.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final API implementation requires OpenAPI documentation at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (['openapi', 'paths', 'security', 'responses', 'version'] as $phrase) {
        if (! containsCaseInsensitive($content, $phrase)) {
            $errors[] = "{$path}: missing OpenAPI documentation phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateApiEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/api-contract-evidence.md',
        'docs/content/modernization/api-contract-evidence.md',
        'docusaurus/docs/developer/api-contracts.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final API implementation requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (apiFeatureIds() as $featureId) {
        if (! str_contains($content, $featureId)) {
            $errors[] = "{$path}: missing API evidence row for {$featureId}";
        }
    }

    foreach (['SOAP', 'XML-RPC', 'REST/API2', 'OAuth', 'OpenAPI', 'Legacy Response', 'Error Format', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing API evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required)\b/', $content) === 1) {
        $errors[] = "{$path}: final API evidence still contains placeholders";
    }
}

/**
 * @return list<string>
 */
function apiFeatureIds(): array
{
    return array_map(
        static fn (int $id): string => sprintf('API-%03d', $id),
        range(1, 6)
    );
}

function allApiFeatureIdsPresent(string $content): bool
{
    foreach (apiFeatureIds() as $featureId) {
        if (! str_contains($content, $featureId)) {
            return false;
        }
    }

    return true;
}

/**
 * @return list<string>
 */
function phpFilesUnder(string $directory): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        if (str_ends_with($path, '.php')) {
            $files[] = $path;
        }
    }

    sort($files);

    return $files;
}

/**
 * @param  list<string>  $files
 */
function pathOrContentMatches(array $files, string $pattern): bool
{
    foreach ($files as $file) {
        $content = file_get_contents($file);
        if ($content !== false && preg_match($pattern, normalizePath($file)."\n".$content) === 1) {
            return true;
        }
    }

    return false;
}

function containsCaseInsensitive(string $content, string $needle): bool
{
    return stripos($content, $needle) !== false;
}

function normalizePath(string $path): string
{
    return str_replace(DIRECTORY_SEPARATOR, '/', $path);
}

/**
 * @param  list<string>  $errors
 */
function readTextFile(string $path, array &$errors): ?string
{
    if (! is_file($path)) {
        $errors[] = "{$path}: missing file";

        return null;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = "{$path}: unable to read file";

        return null;
    }

    return $content;
}

/**
 * @param  list<string>  $paths
 */
function firstExistingPath(array $paths): ?string
{
    foreach ($paths as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}
