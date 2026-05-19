#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateComplexReverseEngineeringPlanning($errors);
validateComplexFeatureMapping($errors);

if ($final) {
    validateFinalComplexReverseEngineeringEvidence($errors);
    validateFinalDualRuntimeParityTests($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Complex reverse-engineering readiness ';
echo $final ? 'final evidence check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateComplexReverseEngineeringPlanning(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Every complex feature, especially cart calculation, pricing, promotions, tax, shipping, payment, order lifecycle, indexing, reports, cron, permissions, and EAV writes, must be reverse-engineered and specified before Laravel replacement starts.',
            'Write detailed specs for complex behavior before implementation.',
            'All complex features in specs/modernization/complex-feature-reverse-engineering.md have approved reverse-engineering specs and dual-runtime parity tests.',
        ],
        'specs/modernization/complex-feature-reverse-engineering.md' => [
            'Magento behavior that affects money, inventory, permissions, integrations, or background processing must be reverse-engineered before replacement.',
            'Quote lifecycle, product type rows, price source order, cart rule application, tax order, totals collector sequence, DB snapshots.',
            'Shipping rates, payment lifecycle, inventory/stock behavior, order state machine, invoices, shipments, credit memos, refunds.',
            'EAV scope semantics, indexing, cache/session behavior, email queue.',
            'SOAP, XML-RPC, REST/API2 auth, payloads, errors, filters, pagination, permissions.',
            'Payment, shipping, external services, feeds, analytics, project ERP/PIM/CRM/email integrations.',
            'Schedule, input rows/config, locks, output rows/files/emails, logs, failure and idempotency behavior.',
            'No complex feature is implemented in Laravel before its Magento behavior has an approved reverse-engineering spec.',
            'Cart/totals, checkout, payment, tax, shipping, order lifecycle, EAV writes, permissions, and cron jobs all have dual-runtime parity tests.',
        ],
        'specs/modernization/test-plan.md' => [
            'Every complex commerce behavior listed in `specs/modernization/complex-feature-reverse-engineering.md` has a reverse-engineered spec before Laravel replacement starts.',
            'Complex behavior spec',
            'Reverse-engineered algorithm, fixture matrix, side effects, comparison command, and numeric approved tolerances where comparison is not exact.',
            'Cart calculation, checkout, pricing, tax, shipping, payment, indexing, EAV writes, reports, and cron jobs must follow the deeper reverse-engineering workflow in `specs/modernization/complex-feature-reverse-engineering.md`.',
        ],
        'specs/modernization/magento-feature-catalog.md' => [
            'Every complex behavior maps to `specs/modernization/complex-feature-reverse-engineering.md`.',
            'Every cron job maps to a Laravel scheduler entry or an approved retirement decision.',
            'Complex Commerce Features',
            'API And Integration Features',
            'Cron And Scheduled Features',
        ],
        'specs/modernization/backlog.md' => [
            'Add commerce behavior parity tests',
            'Add API contract tests',
            'Add cron/job parity tests',
            'Cart, checkout, pricing, tax, shipping, payment, order, EAV, index, cache, and email behavior is locked.',
        ],
        'specs/modernization/roadmap.md' => [
            'Write the cron/scheduler and command spec.',
            'Risk register covers checkout, sales, EAV, admin permissions, pricing, tax, inventory, payments, and extension compatibility.',
            'Implement cart services for quotes, items, totals, coupons, shipping rates, and payment selection.',
            'Implement tax, shipping, payment, inventory, and promotion services.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing complex reverse-engineering planning phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateComplexFeatureMapping(array &$errors): void
{
    $path = 'specs/modernization/complex-feature-reverse-engineering.md';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    $requiredRanges = [
        'CB-001 through CB-006',
        'CB-007 through CB-010',
        'CB-011 through CB-014',
        'API-001 through API-003',
        'API-004 through API-006',
        'CJ-001 through CJ-025',
        'AD-001 through AD-018',
        'SF-001 through SF-016',
    ];

    foreach ($requiredRanges as $range) {
        if (! str_contains($content, $range)) {
            $errors[] = "{$path}: missing complex feature mapping range '{$range}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalComplexReverseEngineeringEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/complex-reverse-engineering-evidence.md',
        'specs/modernization/complex-feature-approvals.md',
        'docs/content/modernization/complex-reverse-engineering-evidence.md',
        'docusaurus/docs/developer/complex-reverse-engineering.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final complex reverse-engineering readiness requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    $requiredPhrases = [
        'Feature ID',
        'Domain',
        'Magento Entry Points',
        'Fixture ID',
        'Legacy Evidence',
        'DB Snapshot',
        'Payload Snapshot',
        'Side Effects',
        'Parity Test',
        'Approved By',
        'Approved At',
        'Laravel Replacement Start',
        'Status',
        'Cart Totals',
        'Pricing',
        'Promotions',
        'Tax',
        'Shipping',
        'Payment',
        'Order Lifecycle',
        'Indexing',
        'Reports',
        'Cron',
        'Permissions',
        'EAV Writes',
    ];

    foreach ($requiredPhrases as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing complex reverse-engineering evidence phrase '{$phrase}'";
        }
    }

    foreach (complexFeatureIds() as $featureId) {
        if (! str_contains($content, $featureId)) {
            $errors[] = "{$path}: missing approved reverse-engineering evidence for feature ID {$featureId}";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Missing|Gap|To inventory|Required|Required where applicable|Blocked|Unapproved)\b/', $content) === 1) {
        $errors[] = "{$path}: final complex reverse-engineering evidence still contains placeholders or blockers";
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalDualRuntimeParityTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $aggregateContent = aggregateFileContent($testFiles);

    if ($aggregateContent === '') {
        $errors[] = 'Final complex reverse-engineering readiness requires Laravel PHPUnit parity tests';

        return;
    }

    $coverage = [
        'approved reverse-engineering linkage' => '/reverse-engineer|reverse engineering|complex-feature-reverse-engineering|approved spec/i',
        'dual-runtime legacy comparison' => '/dual-runtime|Magento|legacy result|legacy response|legacy comparison|baseline comparison/i',
        'fixture and feature ID traceability' => '/Fixture ID|fixture_id|fixtureId|feature id|Feature ID/i',
        'DB snapshots and side effects' => '/DB snapshot|database snapshot|DB delta|database delta|side effect|assertDatabase/i',
        'cart totals pricing promotion tax behavior' => '/cart|quote|totals|collector|pricing|promotion|coupon|tax/i',
        'shipping payment order lifecycle behavior' => '/shipping|payment|order lifecycle|order state|invoice|shipment|credit memo|creditmemo|refund/i',
        'indexing reports cron behavior' => '/index|stale index|report|aggregate|cron|scheduler|idempot/i',
        'permissions EAV API integration behavior' => '/permission|ACL|EAV|attribute|scope|API|SOAP|REST|XML-RPC|OAuth|integration/i',
        'failure retry rollback behavior' => '/failure|invalid|timeout|retry|rollback|recovery|concurrency|duplicate/i',
    ];

    foreach ($coverage as $label => $pattern) {
        if (preg_match($pattern, $aggregateContent) !== 1) {
            $errors[] = "Final complex reverse-engineering readiness requires PHPUnit coverage for {$label}";
        }
    }

    foreach (complexFeatureIds() as $featureId) {
        if (! str_contains($aggregateContent, $featureId)) {
            $errors[] = "Laravel PHPUnit parity tests are missing complex feature ID {$featureId}";
        }
    }
}

/**
 * @return list<string>
 */
function complexFeatureIds(): array
{
    $path = 'specs/modernization/magento-feature-catalog.md';
    if (! is_file($path)) {
        return [];
    }

    $content = file_get_contents($path);
    if ($content === false) {
        return [];
    }

    preg_match_all('/\b(?:SF|AD|CB|API|CJ)-\d{3}\b/', $content, $matches);
    $ids = array_values(array_unique($matches[0] ?? []));
    sort($ids);

    return $ids;
}

/**
 * @return list<string>
 */
function phpFilesUnder(string $directory): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    $files = [];

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }

        if ($file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

/**
 * @param  list<string>  $paths
 */
function aggregateFileContent(array $paths): string
{
    $content = '';

    foreach ($paths as $path) {
        $fileContent = file_get_contents($path);
        if ($fileContent !== false) {
            $content .= "\n{$path}\n{$fileContent}";
        }
    }

    return $content;
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
