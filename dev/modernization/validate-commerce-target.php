#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateCommerceSpecs($errors);

if ($final) {
    validateFinalCommerceImplementation($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Commerce target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateCommerceSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Preserve all existing storefront and admin screens, flows, URLs, API contracts, cron behavior, order/payment/tax/cart behavior, and visual look and feel unless an explicit retirement or difference is approved.',
            'Every complex feature, especially cart calculation, pricing, promotions, tax, shipping, payment, order lifecycle, indexing, reports, cron, permissions, and EAV writes, must be reverse-engineered and specified before Laravel replacement starts.',
            'Prove happy path, edge-case, failure-path, rollback, operations, security, performance, accessibility, documentation, and support readiness.',
        ],
        'specs/modernization/magento-feature-catalog.md' => [
            'Complex Commerce Features',
            'Quote lifecycle',
            'Price resolution',
            'Cart price rules',
            'Tax calculation',
            'Shipping rates',
            'Payment lifecycle',
            'Order state machine',
        ],
        'specs/modernization/complex-feature-reverse-engineering.md' => [
            'CB-001 through CB-006',
            'Quote lifecycle, product type rows, price source order, cart rule application, tax order, totals collector sequence, DB snapshots.',
            'CB-007 through CB-010',
            'Shipping rates, payment lifecycle, inventory/stock behavior, order state machine, invoices, shipments, credit memos, refunds.',
            'CB-011 through CB-014',
            'EAV scope semantics, indexing, cache/session behavior, email queue.',
            'Cart/totals, checkout, payment, tax, shipping, order lifecycle, EAV writes, permissions, and cron jobs all have dual-runtime parity tests.',
        ],
        'specs/modernization/test-plan.md' => [
            'Every complex commerce behavior listed in `specs/modernization/complex-feature-reverse-engineering.md` has a reverse-engineered spec before Laravel replacement starts.',
            'No feature ID may be marked complete by visual approval alone, and no cart/checkout/sales feature may be marked complete without DB side-effect comparison.',
            'Data integrity',
            'Database side effects are correct, transactional, idempotent where required, and reversible through documented restore/rollback paths.',
            'Complex behavior spec',
        ],
        'specs/modernization/roadmap.md' => [
            'Phase 9: Domain Services',
            'Implement cart services for quotes, items, totals, coupons, shipping rates, and payment selection.',
            'Implement checkout services.',
            'Implement sales services for orders, invoices, shipments, credit memos, refunds, emails, and comments.',
            'Implement tax, shipping, payment, inventory, and promotion services.',
        ],
        'specs/modernization/backlog.md' => [
            'Add commerce behavior parity tests',
            'Cart, checkout, pricing, tax, shipping, payment, order, EAV, index, cache, and email behavior is locked.',
            'PHPUnit 12 green and characterization evidence reviewed.',
        ],
        'specs/modernization/risk-register.md' => [
            'Checkout regression',
            'Sales/order lifecycle corruption',
            'Transaction policy, fixture replay, payment/refund tests.',
        ],
        'specs/modernization/visual-tolerances.md' => [
            'Critical commerce regions',
            'price, totals, cart, checkout, order, invoice, shipment, refund, tax, and payment regions.',
            'Visual approval cannot override functional, security, accessibility, performance, pricing, tax, payment, order, or data side-effect failures.',
        ],
        'docusaurus/docs/developer/testing-and-verification.md' => [
            'Database side effects for cart, checkout, order, payment, tax, shipping, refund, reports, indexes, and cron.',
        ],
        'docusaurus/docs/user/storefront-guide.md' => [
            'All critical edge cases: out-of-stock products, invalid quantities, expired quotes, unavailable shipping rates, failed payments, coupon rejection, validation errors, session expiration, empty results, and no-route pages.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing commerce planning phrase '{$phrase}'";
            }
        }
    }

    validateCommerceCatalog($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateCommerceCatalog(array &$errors): void
{
    $path = 'specs/modernization/magento-feature-catalog.md';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (commerceFeatureIds() as $featureId) {
        if (! str_contains($content, "| {$featureId} |")) {
            $errors[] = "{$path}: missing commerce feature row for {$featureId}";
        }
    }

    foreach (['Quote lifecycle', 'Product type behavior', 'Price resolution', 'Cart price rules', 'Catalog price rules', 'Tax calculation', 'Shipping rates', 'Payment lifecycle', 'Inventory and stock', 'Order state machine', 'Indexing', 'Cache and session behavior', 'Email queue'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing commerce catalog phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalCommerceImplementation(array &$errors): void
{
    validateCommerceArtifacts($errors);
    validateCommerceTests($errors);
    validateCommerceEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateCommerceArtifacts(array &$errors): void
{
    $appFiles = phpFilesUnder('laravel/app');

    if ($appFiles === []) {
        $errors[] = 'Final commerce implementation requires Laravel app files';

        return;
    }

    $requiredContexts = [
        'Quote',
        'Cart',
        'Totals',
        'ProductType',
        'Pricing',
        'Promotion',
        'Tax',
        'Shipping',
        'Payment',
        'Inventory',
        'Order',
        'Invoice',
        'Shipment',
        'CreditMemo',
        'Refund',
        'Index',
        'Cache',
        'EmailQueue',
    ];

    foreach ($requiredContexts as $context) {
        if (! pathOrContentMatches($appFiles, "/{$context}|".preg_quote(kebabToWords($context), '/').'/i')) {
            $errors[] = "Final commerce implementation requires {$context} service or domain coverage";
        }
    }

    $requirements = [
        'service contracts or interfaces' => '/\/Contracts\/|Interface\.php$|contract/i',
        'DTOs or value objects' => '/\/Data\/|\/DTO\/|Dto\.php$|Data\.php$|ValueObject/i',
        'transaction policy' => '/DB::transaction|database transaction|transactional/i',
        'locking or idempotency controls' => '/lockForUpdate|Cache::lock|idempot|duplicate/i',
        'external integration retry controls' => '/Http::retry|->retry\(|timeout\(|connectTimeout\(|throw\(/',
        'domain events or jobs' => '/\/Events\/|\/Jobs\/|ShouldQueue|Dispatchable/',
    ];

    foreach ($requirements as $label => $pattern) {
        if (! pathOrContentMatches($appFiles, $pattern)) {
            $errors[] = "Final commerce implementation requires {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateCommerceTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $aggregateContent = '';

    foreach ($testFiles as $testFile) {
        $content = file_get_contents($testFile);
        if ($content !== false) {
            $aggregateContent .= "\n".$testFile."\n".$content;
        }
    }

    if ($aggregateContent === '') {
        $errors[] = 'Final commerce implementation requires Laravel PHPUnit tests';

        return;
    }

    $coverage = [
        'all commerce feature IDs' => allCommerceFeatureIdsPresent($aggregateContent),
        'dual-runtime legacy comparison' => preg_match('/dual-runtime|Magento|legacy result|legacy response|legacy comparison/i', $aggregateContent) === 1,
        'DB side-effect snapshots' => preg_match('/DB delta|database delta|DB snapshot|database snapshot|assertDatabase|side effect/i', $aggregateContent) === 1,
        'quote cart and totals behavior' => preg_match('/quote|cart|totals|collector/i', $aggregateContent) === 1,
        'price rule tax behavior' => preg_match('/price|pricing|catalog rule|cart rule|coupon|tax/i', $aggregateContent) === 1,
        'shipping payment failure and retry behavior' => preg_match('/shipping|payment|failure|retry|timeout|mock|sandbox/i', $aggregateContent) === 1,
        'order lifecycle behavior' => preg_match('/order state|invoice|shipment|credit memo|creditmemo|refund|PDF|email/i', $aggregateContent) === 1,
        'inventory index cache session email behavior' => preg_match('/inventory|stock|index|cache|session|email queue|stale/i', $aggregateContent) === 1,
        'edge failure concurrency recovery behavior' => preg_match('/edge|invalid|permission|concurrency|duplicate|recovery|rollback|stale/i', $aggregateContent) === 1,
    ];

    foreach ($coverage as $label => $covered) {
        if (! $covered) {
            $errors[] = "Final commerce implementation requires PHPUnit coverage for {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateCommerceEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/commerce-parity-evidence.md',
        'docs/content/modernization/commerce-parity-evidence.md',
        'docusaurus/docs/developer/commerce-parity.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final commerce implementation requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (commerceFeatureIds() as $featureId) {
        if (! str_contains($content, $featureId)) {
            $errors[] = "{$path}: missing commerce evidence row for {$featureId}";
        }
    }

    foreach (['Legacy Characterization', 'Laravel Test', 'DB Delta', 'Fixture', 'Edge Case', 'Failure Path', 'Concurrency', 'Rollback', 'Approved Difference', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing commerce evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required|To inventory)\b/', $content) === 1) {
        $errors[] = "{$path}: final commerce evidence still contains placeholders";
    }
}

/**
 * @return list<string>
 */
function commerceFeatureIds(): array
{
    return array_map(
        static fn (int $id): string => sprintf('CB-%03d', $id),
        range(1, 14)
    );
}

function allCommerceFeatureIdsPresent(string $content): bool
{
    foreach (commerceFeatureIds() as $featureId) {
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

function kebabToWords(string $value): string
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
