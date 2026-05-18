#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateIntegrationSpecs($errors);

if ($final) {
    validateFinalIntegrationImplementation($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Integration target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateIntegrationSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Identify all modules, routes, controllers, templates, admin screens, APIs, cron jobs, shell commands, observers, setup scripts, config paths, and integrations.',
            'Verification must cover happy paths, edge cases, failure paths, invalid input, permission denial, concurrency, stale cache/index states, integration outages, recovery, rollback, and production-readiness criteria.',
            'Prove happy path, edge-case, failure-path, rollback, operations, security, performance, accessibility, documentation, and support readiness.',
        ],
        'specs/modernization/magento-feature-catalog.md' => [
            'External payment redirects',
            'Integration admin',
            'Payment integrations',
            'Shipping integrations',
            'External services',
            'Currency rate imports, Google Analytics, Google Base, email provider, project ERP/PIM/CRM/feed integrations.',
        ],
        'specs/modernization/backlog.md' => [
            'Inventory integrations',
            'Every integration has sandbox, fake, mock, outage, retry, and rollback plan.',
            'Integration matrix reviewed.',
        ],
        'specs/modernization/complex-feature-reverse-engineering.md' => [
            'Reverse-engineer required SOAP, REST, API2, OAuth, payment, shipping, tax, feed, analytics, and external sync behavior.',
            'For each integration capture:',
            'Request and response payloads.',
            'Retry behavior.',
            'Sandbox or mock strategy.',
            'Secrets/config paths.',
            'API-004 through API-006',
            'Sandbox or mock payload tests, timeout/retry/failure behavior, secret/config tests.',
        ],
        'specs/modernization/data-fixtures.md' => [
            'Integration data | Covers API users, sandbox providers, external IDs.',
            'Shipping | Free shipping, flat rate, table rate, UPS/USPS/FedEx/DHL sandbox or mocked rates, unavailable shipping address, split-address multishipping case.',
            'Payment | Check/money order, bank transfer, cash on delivery, purchase order, free payment, sandbox or mocked PayPal, Authorize.Net/Paygate if enabled, failed payment, payment review.',
            'Failure and resilience | Invalid coupons, invalid addresses, expired sessions, denied admin roles, failed payments, unavailable shipping methods, missing media, stale indexes, stale cache, failed email queue, failed cron lock, duplicate order submission attempt, and integration timeout mocks.',
        ],
        'specs/modernization/test-plan.md' => [
            'integration timeout',
            'external service outages are tested.',
            'Integration fixture | Proves API and external behavior.',
            'Sanitized production-like database, media, config, and integrations in sandbox mode.',
            'Config | Environment config, secret handling, cache invalidation.',
        ],
        'specs/modernization/inventory.md' => [
            'Integrations | Payment, shipping, tax, ERP, PIM, CRM, email, analytics, search, feeds, webhooks.',
            'Every integration has a sandbox/testing strategy.',
        ],
        'specs/modernization/feature-inventory.md' => [
            'Payments | Providers, auth/capture/refund/void, webhooks, sandbox.',
            'ERP/PIM/CRM | Sync direction, schedule, data ownership, failure handling.',
            'Each integration has sandbox credentials or a mock strategy.',
        ],
        'specs/modernization/ui-screen-inventory.md' => [
            'Integrations | Payment configuration, shipping methods, tax settings, API users/roles, OAuth consumers/tokens, Google integrations if enabled.',
        ],
        'docusaurus/docs/developer/testing-and-verification.md' => [
            'integration-outage',
            'rollback scenarios.',
        ],
        'docusaurus/docs/user/admin-guide.md' => [
            'Operational recovery paths for failed imports, failed emails, stale indexes, cache invalidation, scheduler failures, and integration outages.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing integration planning phrase '{$phrase}'";
            }
        }
    }

    validateIntegrationCatalog($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateIntegrationCatalog(array &$errors): void
{
    $path = 'specs/modernization/magento-feature-catalog.md';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (integrationFeatureIds() as $featureId) {
        if (! str_contains($content, "| {$featureId} |")) {
            $errors[] = "{$path}: missing integration feature row for {$featureId}";
        }
    }

    foreach (['PayPal', 'Authorize.Net', 'Paygate', 'UPS', 'USPS', 'FedEx', 'DHL', 'Currency rate imports', 'Google Analytics', 'Google Base', 'ERP', 'PIM', 'CRM', 'OAuth'] as $phrase) {
        if (! containsCaseInsensitive($content, $phrase)) {
            $errors[] = "{$path}: missing integration catalog phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalIntegrationImplementation(array &$errors): void
{
    validateIntegrationMatrix($errors);
    validateIntegrationArtifacts($errors);
    validateIntegrationTests($errors);
    validateIntegrationEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateIntegrationMatrix(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/integration-matrix.md',
        'docs/content/modernization/integration-matrix.md',
        'docusaurus/docs/developer/integration-matrix.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final integration implementation requires an integration matrix at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (['Integration', 'Owner', 'Feature IDs', 'Sandbox', 'Fake', 'Mock', 'Outage', 'Retry', 'Rollback', 'Secret', 'Config Path', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing integration matrix phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateIntegrationArtifacts(array &$errors): void
{
    $appFiles = phpFilesUnder('laravel/app');
    $configFiles = phpFilesUnder('laravel/config');
    $files = array_merge($appFiles, $configFiles);

    if ($appFiles === []) {
        $errors[] = 'Final integration implementation requires Laravel app files';

        return;
    }

    $requiredContexts = [
        'PaymentGateway',
        'ShippingCarrier',
        'CurrencyRate',
        'GoogleAnalytics',
        'GoogleBase',
        'EmailProvider',
        'Erp',
        'Pim',
        'Crm',
        'Feed',
        'Webhook',
        'OAuth',
        'Sandbox',
        'IntegrationConfig',
    ];

    foreach ($requiredContexts as $context) {
        if (! pathOrContentMatches($files, "/{$context}|".preg_quote(splitWords($context), '/').'/i')) {
            $errors[] = "Final integration implementation requires {$context} adapter or domain coverage";
        }
    }

    $requirements = [
        'HTTP timeout and retry controls' => '/Http::.*(?:timeout|connectTimeout|retry)|->(?:timeout|connectTimeout|retry)\(/s',
        'HTTP status handling' => '/throw\(|successful\(|failed\(|status\(|clientError\(|serverError\(/',
        'sandbox or fake provider controls' => '/sandbox|fake|mock|test mode|test_mode/i',
        'secret and config path handling' => '/config\(|env\(|secret|credential|token|api_key|password/i',
        'queued retry or failure handling' => '/ShouldQueue|retryUntil|backoff|tries|failed\s*\(|RateLimited|Queue::/i',
        'webhook or callback handling' => '/Webhook|callback|IPN|return URL|cancel URL|signature/i',
        'logging or observability context' => '/Log::|logger\(|context\(|metric|alert/i',
        'rollback or recovery control' => '/rollback|recover|compensat|idempot|replay/i',
    ];

    foreach ($requirements as $label => $pattern) {
        if (! pathOrContentMatches($files, $pattern)) {
            $errors[] = "Final integration implementation requires {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateIntegrationTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $aggregateContent = aggregateFiles($testFiles);

    if ($aggregateContent === '') {
        $errors[] = 'Final integration implementation requires Laravel PHPUnit tests';

        return;
    }

    $coverage = [
        'all integration feature IDs' => allIntegrationFeatureIdsPresent($aggregateContent),
        'payment redirect webhook and failure behavior' => preg_match('/payment|PayPal|Authorize\.?Net|Paygate|webhook|IPN|callback|failed payment/i', $aggregateContent) === 1,
        'shipping carrier sandbox and unavailable-rate behavior' => preg_match('/shipping|UPS|USPS|FedEx|DHL|unavailable|rate/i', $aggregateContent) === 1,
        'currency analytics feed ERP PIM CRM and email integrations' => preg_match('/currency|Google Analytics|Google Base|ERP|PIM|CRM|feed|email provider/i', $aggregateContent) === 1,
        'HTTP fake failed connection and retry behavior' => preg_match('/Http::fake|failedConnection|failedRequest|retry|timeout|connectTimeout/i', $aggregateContent) === 1,
        'secret config and sandbox strategy' => preg_match('/config\(|secret|credential|token|sandbox|mock|fake/i', $aggregateContent) === 1,
        'queue notification and failure handling' => preg_match('/Queue::fake|Notification::fake|Mail::fake|assertPushed|assertSent|failed|backoff|retry/i', $aggregateContent) === 1,
        'dual-runtime payload comparison' => preg_match('/dual-runtime|legacy payload|legacy comparison|Magento|baseline response|payload snapshot/i', $aggregateContent) === 1,
        'rollback recovery and observability' => preg_match('/rollback|recover|Log::|metric|alert|outage|integration timeout/i', $aggregateContent) === 1,
    ];

    foreach ($coverage as $label => $covered) {
        if (! $covered) {
            $errors[] = "Final integration implementation requires PHPUnit coverage for {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateIntegrationEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/integration-parity-evidence.md',
        'docs/content/modernization/integration-parity-evidence.md',
        'docusaurus/docs/developer/integration-parity.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final integration implementation requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (integrationFeatureIds() as $featureId) {
        if (! str_contains($content, $featureId)) {
            $errors[] = "{$path}: missing integration evidence row for {$featureId}";
        }
    }

    foreach (['Integration', 'Legacy Payload', 'Laravel Payload', 'Sandbox', 'Mock', 'Outage', 'Retry', 'Rollback', 'Secret Review', 'Config Path', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing integration evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required|To inventory)\b/', $content) === 1) {
        $errors[] = "{$path}: final integration evidence still contains placeholders";
    }
}

/**
 * @return list<string>
 */
function integrationFeatureIds(): array
{
    return [
        'SF-015',
        'SF-016',
        'AD-018',
        'API-004',
        'API-005',
        'API-006',
        'CJ-002',
        'CJ-004',
        'CJ-017',
    ];
}

function allIntegrationFeatureIdsPresent(string $content): bool
{
    foreach (integrationFeatureIds() as $featureId) {
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
function aggregateFiles(array $files): string
{
    $content = '';

    foreach ($files as $file) {
        $fileContent = file_get_contents($file);
        if ($fileContent !== false) {
            $content .= "\n".$file."\n".$fileContent;
        }
    }

    return $content;
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

function splitWords(string $value): string
{
    return strtolower((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $value));
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
