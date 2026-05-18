#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateReportSpecs($errors);

if ($final) {
    validateFinalReportImplementation($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Report target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateReportSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Every complex feature, especially cart calculation, pricing, promotions, tax, shipping, payment, order lifecycle, indexing, reports, cron, permissions, and EAV writes, must be reverse-engineered and specified before Laravel replacement starts.',
            'Demo data must be predefined and reproducible. It must cover all Magento product types, discounts, coupons, tax/shipping/payment paths, customer groups, admin roles, API users, reports, cron jobs, multistore scope, media, and edge cases.',
            'Prove happy path, edge-case, failure-path, rollback, operations, security, performance, accessibility, documentation, and support readiness.',
        ],
        'specs/modernization/magento-feature-catalog.md' => [
            'Reports',
            'Sales, tax, shipping, invoiced, refunded, coupons, products, customers, reviews, tags, carts, search terms, refresh statistics.',
            'aggregate sales orders',
            'aggregate coupon reports',
            'aggregate tax reports',
            'Report table parity.',
        ],
        'specs/modernization/complex-feature-reverse-engineering.md' => [
            'Cron, Email, And Reports',
            'Reports must be tested both before and after aggregation jobs run.',
            'Laravel scheduler/job parity tests and report table snapshots.',
            'reports, indexing, cache/session behavior, admin workflows, and storefront UI states have dual-runtime parity tests where applicable.',
        ],
        'specs/modernization/test-plan.md' => [
            'Complex behavior spec',
            'Cart, checkout, pricing, tax, shipping, payment, EAV, indexing, reports, cron',
            'Reports | Existing reports that remain supported.',
            'report grids, integrations, and cron/index/cache screens.',
            'The final test report must include this matrix populated for every ID in `specs/modernization/magento-feature-catalog.md`.',
        ],
        'specs/modernization/data-fixtures.md' => [
            'Reports | Sales, tax, shipping, invoiced, refunded, coupon, product, customer, search, carts, reviews, tags, wishlist and bestsellers data.',
            'Cron and reports',
            'Report aggregates are populated; cron schedule rows are missing.',
            'The demo fixture covers all product types, promotion types, tax/shipping/payment paths, admin roles, API users, cron jobs, and report aggregates listed above.',
        ],
        'specs/modernization/ui-screen-inventory.md' => [
            'Reports | Sales, tax, shipping, refunds, coupons, products, customers, reviews, tags, search terms, bestsellers, low stock.',
            'Sales | Orders grid/view/create, invoices, shipments, credit memos, transactions, recurring profiles, billing agreements, tax reports.',
            'Promotions | Catalog price rules, shopping cart price rules, coupons, rule conditions/actions, rule reports.',
        ],
        'specs/modernization/roadmap.md' => [
            'Implement CMS, newsletter, reports, sitemap, and search services.',
            'Add domain-level contract tests.',
        ],
        'docusaurus/docs/user/index.md' => [
            'Admin catalog, sales, customer, promotion, CMS, reports, system configuration, cache, index, user, role, import/export, tax, currency, and operations workflows.',
        ],
        'docusaurus/docs/developer/testing-and-verification.md' => [
            'Database side effects for cart, checkout, order, payment, tax, shipping, refund, reports, indexes, and cron.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing report planning phrase '{$phrase}'";
            }
        }
    }

    validateReportCatalog($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateReportCatalog(array &$errors): void
{
    $path = 'specs/modernization/magento-feature-catalog.md';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (reportFeatureIds() as $featureId) {
        if (! str_contains($content, "| {$featureId} |")) {
            $errors[] = "{$path}: missing report feature row for {$featureId}";
        }
    }

    foreach (['Sales', 'tax', 'shipping', 'invoiced', 'refunded', 'coupons', 'products', 'customers', 'reviews', 'tags', 'carts', 'search terms', 'bestsellers'] as $phrase) {
        if (! containsCaseInsensitive($content, $phrase)) {
            $errors[] = "{$path}: missing report catalog phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalReportImplementation(array &$errors): void
{
    validateReportArtifacts($errors);
    validateReportTests($errors);
    validateReportEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateReportArtifacts(array &$errors): void
{
    $appFiles = phpFilesUnder('laravel/app');

    if ($appFiles === []) {
        $errors[] = 'Final report implementation requires Laravel app files';

        return;
    }

    $requiredContexts = [
        'SalesReport',
        'TaxReport',
        'ShippingReport',
        'InvoicedReport',
        'RefundedReport',
        'CouponReport',
        'ProductReport',
        'CustomerReport',
        'SearchReport',
        'CartReport',
        'ReviewReport',
        'TagReport',
        'BestsellerReport',
        'LowStockReport',
    ];

    foreach ($requiredContexts as $context) {
        if (! pathOrContentMatches($appFiles, "/{$context}|".preg_quote(splitWords($context), '/').'/i')) {
            $errors[] = "Final report implementation requires {$context} service or domain coverage";
        }
    }

    $requirements = [
        'report query or aggregate logic' => '/DB::table|selectRaw|groupBy|sum\(|count\(|avg\(|aggregate|aggregation/i',
        'report DTOs or value objects' => '/\/Data\/|\/DTO\/|Dto\.php$|Data\.php$|ValueObject/i',
        'report controller or Livewire admin surface' => '/\/Http\/Controllers\/.*Report.*Controller\.php$|\/Livewire\/.*Report|Report.*Component/i',
        'report export or grid behavior' => '/export|csv|grid|pagination|filter|sort/i',
        'report date store and currency filters' => '/date range|from date|to date|store scope|store id|website|currency/i',
        'report aggregation job or command' => '/Aggregate.*Report|Report.*Aggregate|report aggregation|\/Jobs\/|\/Console\/Commands\//i',
        'report permission or policy artifact' => '/\/Policies\/|Policy\.php$|permission|Gate::|authorize\(/i',
    ];

    foreach ($requirements as $label => $pattern) {
        if (! pathOrContentMatches($appFiles, $pattern)) {
            $errors[] = "Final report implementation requires {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateReportTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $aggregateContent = aggregateFiles($testFiles);

    if ($aggregateContent === '') {
        $errors[] = 'Final report implementation requires Laravel PHPUnit tests';

        return;
    }

    $coverage = [
        'all report feature IDs' => allReportFeatureIdsPresent($aggregateContent),
        'sales tax shipping invoiced refunded and coupon reports' => preg_match('/sales report|tax report|shipping report|invoiced|refunded|coupon report/i', $aggregateContent) === 1,
        'product customer search cart review tag and bestseller reports' => preg_match('/product report|customer report|search terms|cart report|review report|tag report|bestseller|low stock/i', $aggregateContent) === 1,
        'before and after aggregation job behavior' => preg_match('/before aggregation|after aggregation|aggregate.*job|report aggregation|schedule|artisan/i', $aggregateContent) === 1,
        'report DB snapshots and table parity' => preg_match('/report table|DB snapshot|database snapshot|DB delta|database delta|assertDatabase|table parity/i', $aggregateContent) === 1,
        'dual-runtime legacy report comparison' => preg_match('/dual-runtime|legacy report|legacy comparison|Magento|baseline report/i', $aggregateContent) === 1,
        'date store currency and permission filters' => preg_match('/date range|store scope|store id|currency|permission|authorized|forbidden/i', $aggregateContent) === 1,
        'empty export and performance states' => preg_match('/empty state|export|csv|query count|performance|timeout|large catalog/i', $aggregateContent) === 1,
    ];

    foreach ($coverage as $label => $covered) {
        if (! $covered) {
            $errors[] = "Final report implementation requires PHPUnit coverage for {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateReportEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/report-parity-evidence.md',
        'docs/content/modernization/report-parity-evidence.md',
        'docusaurus/docs/developer/report-parity.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final report implementation requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (reportFeatureIds() as $featureId) {
        if (! str_contains($content, $featureId)) {
            $errors[] = "{$path}: missing report evidence row for {$featureId}";
        }
    }

    foreach (['Report Name', 'Legacy Snapshot', 'Laravel Snapshot', 'DB Delta', 'Aggregation Job', 'Fixture', 'Filter Coverage', 'Permission', 'Export', 'Performance', 'Approved Difference', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing report evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required|To inventory)\b/', $content) === 1) {
        $errors[] = "{$path}: final report evidence still contains placeholders";
    }
}

/**
 * @return list<string>
 */
function reportFeatureIds(): array
{
    return [
        'AD-014',
        'AD-008',
        'AD-005',
        'AD-006',
        'CJ-004',
        'CJ-007',
        'CJ-008',
        'CJ-009',
        'CJ-010',
        'CJ-011',
        'CJ-015',
        'CJ-020',
    ];
}

function allReportFeatureIdsPresent(string $content): bool
{
    foreach (reportFeatureIds() as $featureId) {
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
