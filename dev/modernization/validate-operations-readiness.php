#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$testPlanPath = 'specs/modernization/test-plan.md';
$releaseStrategyPath = 'specs/modernization/release-strategy.md';

$errors = [];
$testPlan = readRequiredFile($testPlanPath, $errors);
$releaseStrategy = readRequiredFile($releaseStrategyPath, $errors);

if ($testPlan !== null) {
    array_push($errors, ...validateOperationalTestPlan($testPlan, $testPlanPath));
}

if ($releaseStrategy !== null) {
    array_push($errors, ...validateReleaseStrategy($releaseStrategy, $releaseStrategyPath));
}

if ($final) {
    array_push($errors, ...validateFinalOperatorRunbook());
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Operations readiness ';
echo $final ? 'final runbook check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function readRequiredFile(string $path, array &$errors): ?string
{
    if (! is_file($path)) {
        $errors[] = "Missing required operations file: {$path}";

        return null;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = "Unable to read required operations file: {$path}";

        return null;
    }

    return $content;
}

/**
 * @return list<string>
 */
function validateOperationalTestPlan(string $content, string $path): array
{
    $errors = [];
    foreach (['Operational Test Plan', 'Documentation Test Plan'] as $section) {
        if (! preg_match('/^##\s+'.preg_quote($section, '/').'\s*$/m', $content)) {
            $errors[] = "{$path}: missing required section '{$section}'";
        }
    }

    $requiredOperationalAreas = [
        'Deployment',
        'Rollback',
        'Backups',
        'Config',
        'Logs',
        'Monitoring',
        'Cache',
        'Sessions',
        'Scheduler',
        'Maintenance mode',
    ];

    foreach ($requiredOperationalAreas as $area) {
        if (! preg_match('/^\|\s*'.preg_quote($area, '/').'\s*\|/m', $content)) {
            $errors[] = "{$path}: missing operational test area '{$area}'";
        }
    }

    $requiredAcceptanceCriteria = [
        'Staging deployment rehearsal passes.',
        'Rollback completes within the numeric RTO/RPO recorded in `specs/modernization/performance-budgets.md` or the release runbook.',
        'Health checks detect app, DB, cache, session, scheduler, and queue failure.',
        'Operator runbook is reviewed.',
    ];

    foreach ($requiredAcceptanceCriteria as $criterion) {
        if (! str_contains($content, "- {$criterion}")) {
            $errors[] = "{$path}: missing operational acceptance criterion '{$criterion}'";
        }
    }

    $requiredDocumentationRows = [
        'Operator guide',
        'Docusaurus user docs',
        'Docusaurus developer docs',
    ];

    foreach ($requiredDocumentationRows as $row) {
        if (! preg_match('/^\|\s*'.preg_quote($row, '/').'\s*\|/m', $content)) {
            $errors[] = "{$path}: missing documentation test row '{$row}'";
        }
    }

    return $errors;
}

/**
 * @return list<string>
 */
function validateReleaseStrategy(string $content, string $path): array
{
    $errors = [];
    foreach (['Rollback Requirements', 'Release Gates'] as $section) {
        if (! preg_match('/^##\s+'.preg_quote($section, '/').'\s*$/m', $content)) {
            $errors[] = "{$path}: missing required section '{$section}'";
        }
    }

    $requiredRollbackItems = [
        'Legacy route fallback remains available until the migrated route has passed staging and production observation.',
        'Database schema remains compatible with legacy runtime.',
        'Feature flags can move traffic back to legacy routes.',
        'Cache/session behavior supports rollback.',
        'ADR 0008 defines auth, session, cookie, CSRF/form-key, password-hash, and cross-runtime rollback behavior before fallback is enabled.',
        'Deployment rollback has been rehearsed.',
    ];

    foreach ($requiredRollbackItems as $item) {
        if (! str_contains($content, "- {$item}")) {
            $errors[] = "{$path}: missing rollback requirement '{$item}'";
        }
    }

    $requiredReleaseGates = [
        'Test plan release checklist complete.',
        'Performance budgets approved.',
        'Security review complete.',
        'Visual regression approved.',
        'Edge-case, failure-path, resilience, observability, and recovery evidence approved for every critical feature.',
        'Removed-technology scan approved for the Laravel target.',
        'Documentation and operator runbook complete.',
        'Docusaurus user and developer documentation builds and renders in Chrome/Playwright.',
        'Rollback rehearsal complete.',
        'Open defect list accepted by severity policy.',
    ];

    foreach ($requiredReleaseGates as $gate) {
        if (! str_contains($content, "- {$gate}")) {
            $errors[] = "{$path}: missing release gate '{$gate}'";
        }
    }

    return $errors;
}

/**
 * @return list<string>
 */
function validateFinalOperatorRunbook(): array
{
    $candidatePaths = [
        'specs/modernization/operations-runbook.md',
        'docs/content/modernization/operations-runbook.md',
        'docusaurus/docs/developer/operations-runbook.md',
        'docusaurus/docs/developer/operations.md',
    ];

    $runbookPath = null;
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            $runbookPath = $candidatePath;
            break;
        }
    }

    if ($runbookPath === null) {
        return ['Final operations readiness requires an operator runbook at one of: '.implode(', ', $candidatePaths)];
    }

    $content = file_get_contents($runbookPath);
    if ($content === false) {
        return ["Unable to read operator runbook: {$runbookPath}"];
    }

    $errors = [];
    $requiredSections = [
        'Deployment',
        'Rollback',
        'Backup',
        'Restore',
        'Health',
        'Cache',
        'Scheduler',
        'Queue',
        'Logs',
        'Monitoring',
        'Troubleshooting',
    ];

    foreach ($requiredSections as $section) {
        if (! preg_match('/^#{2,3}\s+.*'.preg_quote($section, '/').'/mi', $content)) {
            $errors[] = "{$runbookPath}: missing operator runbook section containing '{$section}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required)\b/', $content) === 1) {
        $errors[] = "{$runbookPath}: final operator runbook still contains placeholder evidence";
    }

    return $errors;
}
