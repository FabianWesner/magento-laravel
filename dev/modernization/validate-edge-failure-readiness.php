#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateEdgeFailurePlanning($errors);
validateScenarioTaxonomy($errors);

if ($final) {
    validateFinalEdgeFailureEvidence($errors);
    validateFinalEdgeFailureTests($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Edge/failure/resilience readiness ';
echo $final ? 'final evidence check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateEdgeFailurePlanning(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Verification must cover happy paths, edge cases, failure paths, invalid input, permission denial, concurrency, stale cache/index states, integration outages, recovery, rollback, and production-readiness criteria.',
            'Prove happy path, edge-case, failure-path, rollback, operations, security, performance, accessibility, documentation, and support readiness.',
            'Edge-case, failure-path, resilience, recovery, observability, and production-readiness gates pass; happy-path smoke tests alone never satisfy completion.',
        ],
        'specs/modernization/test-plan.md' => [
            'Prove edge cases, failure paths, resilience, recovery, observability, and production readiness, not only happy-path workflows.',
            'Happy-path smoke tests are never enough for completion.',
            'Every critical feature has normal, edge, failure, invalid input, permission-denied, stale-cache/index, retry, concurrency, and recovery coverage where applicable.',
            'Invalid input, expired session, denied permission, payment failure, unavailable shipping, integration timeout, missing media, stale cache/index, and failed cron/queue job produce safe outcomes.',
            'Retries, locks, duplicate submission protection, concurrent requests, scheduler overlap, and external service outages are tested.',
            'Cache flush, reindex, queue retry, fixture restore, backup restore, deploy rollback, and failed release recovery are rehearsed.',
        ],
        'specs/modernization/data-fixtures.md' => [
            'Failure and resilience',
            'Invalid coupons, invalid addresses, expired sessions, denied admin roles, failed payments, unavailable shipping methods, missing media, stale indexes, stale cache, failed email queue, failed cron lock, duplicate order submission attempt, and integration timeout mocks.',
        ],
        'specs/modernization/feature-inventory.md' => [
            'Edge-case coverage',
            'Boundary, failure, permission, concurrency, stale cache/index, integration outage, and recovery cases required by the feature.',
            'Each critical feature has edge-case and failure-path coverage, not only happy-path acceptance.',
        ],
        'specs/modernization/release-strategy.md' => [
            'Edge-case, failure-path, resilience, observability, and recovery evidence approved for every critical feature.',
            'Rollback rehearsal complete.',
        ],
        'specs/modernization/risk-register.md' => [
            'Happy-path-only verification',
            'Production-readiness gate requiring edge, failure, resilience, observability, recovery, security, performance, and accessibility coverage.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing edge/failure readiness phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateScenarioTaxonomy(array &$errors): void
{
    $path = 'specs/modernization/test-plan.md';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (requiredScenarioTypePatterns() as $scenarioType => $pattern) {
        if (preg_match($pattern, $content) !== 1) {
            $errors[] = "{$path}: missing edge/failure scenario type '{$scenarioType}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalEdgeFailureEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/edge-failure-readiness-evidence.md',
        'specs/modernization/resilience-readiness-evidence.md',
        'docs/content/modernization/edge-failure-readiness-evidence.md',
        'docusaurus/docs/developer/edge-failure-readiness.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final edge/failure/resilience readiness requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    $requiredPhrases = [
        'Feature ID',
        'Scenario Type',
        'Fixture ID',
        'Legacy Evidence',
        'Laravel Evidence',
        'DB Snapshot',
        'Side Effects',
        'Observability',
        'Recovery',
        'Rollback',
        'Owner',
        'Status',
    ];

    foreach ($requiredPhrases as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing edge/failure evidence phrase '{$phrase}'";
        }
    }

    foreach (requiredScenarioTypePatterns() as $scenarioType => $pattern) {
        if (preg_match($pattern, $content) !== 1) {
            $errors[] = "{$path}: final evidence is missing scenario type '{$scenarioType}'";
        }
    }

    foreach (catalogFeatureIds() as $featureId) {
        if (! str_contains($content, $featureId)) {
            $errors[] = "{$path}: missing edge/failure evidence for feature ID {$featureId}";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Missing|Gap|To inventory|Required|Required where applicable|Blocked|Happy path only)\b/', $content) === 1) {
        $errors[] = "{$path}: final edge/failure evidence still contains placeholders or blockers";
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalEdgeFailureTests(array &$errors): void
{
    $aggregateContent = aggregateFileContent(phpFilesUnder('laravel/tests'));

    if ($aggregateContent === '') {
        $errors[] = 'Final edge/failure/resilience readiness requires Laravel PHPUnit tests';

        return;
    }

    $coverage = [
        'feature ID traceability' => '/Feature ID|feature id|SF-\d{3}|AD-\d{3}|CB-\d{3}|API-\d{3}|CJ-\d{3}/i',
        'legacy and Laravel comparison' => '/dual-runtime|Magento|legacy result|legacy response|legacy comparison|baseline comparison/i',
        'invalid input coverage' => '/invalid input|validation|invalid coupon|invalid address|malformed|bad request/i',
        'permission denial coverage' => '/permission denied|denied role|unauthorized|forbidden|ACL|policy|gate/i',
        'concurrency and duplicate coverage' => '/concurrency|concurrent|duplicate submission|idempot|lock|overlap/i',
        'stale cache and stale index coverage' => '/stale cache|stale index|cache flush|reindex|indexer/i',
        'integration outage coverage' => '/integration outage|timeout|failed connection|Http::fake|external service|unavailable shipping|failed payment/i',
        'observability coverage' => '/log|metric|alert|health check|diagnostic|observability/i',
        'recovery and rollback coverage' => '/recovery|recover|retry|restore|rollback|backup restore|queue retry/i',
        'DB snapshot or side-effect coverage' => '/DB snapshot|database snapshot|DB delta|database delta|side effect|assertDatabase/i',
    ];

    foreach ($coverage as $label => $pattern) {
        if (preg_match($pattern, $aggregateContent) !== 1) {
            $errors[] = "Final edge/failure/resilience readiness requires PHPUnit coverage for {$label}";
        }
    }

    foreach (catalogFeatureIds() as $featureId) {
        if (! str_contains($aggregateContent, $featureId)) {
            $errors[] = "Laravel PHPUnit edge/failure tests are missing feature ID {$featureId}";
        }
    }
}

/**
 * @return list<string>
 */
function requiredScenarioTypes(): array
{
    return array_keys(requiredScenarioTypePatterns());
}

/**
 * @return array<string, string>
 */
function requiredScenarioTypePatterns(): array
{
    return [
        'Normal path' => '/\bNormal path\b/i',
        'Edge cases' => '/\bEdge cases\b/i',
        'Failure paths' => '/\bFailure paths\b/i',
        'Invalid input' => '/\bInvalid input\b/i',
        'Permission denial' => '/permission[- ]denied|denied permission|denied admin roles|permission denial/i',
        'Concurrency' => '/\bConcurrency\b|concurrent requests/i',
        'Stale cache' => '/stale[- ]cache(?:\/index)?|stale cache/i',
        'Stale index' => '/stale[- ]cache\/index|stale cache\/index|stale[- ]index|stale index/i',
        'Integration outage' => '/integration timeout|integration outage|external service outages|external service failure/i',
        'Observability' => '/\bObservability\b/i',
        'Recovery' => '/\bRecovery\b/i',
        'Rollback' => '/\bRollback\b/i',
    ];
}

/**
 * @return list<string>
 */
function catalogFeatureIds(): array
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
