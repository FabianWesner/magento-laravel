#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$testPlanPath = 'specs/modernization/test-plan.md';
$releaseStrategyPath = 'specs/modernization/release-strategy.md';
$riskRegisterPath = 'specs/modernization/risk-register.md';

$errors = [];
$testPlan = readRequiredFile($testPlanPath, $errors);
$releaseStrategy = readRequiredFile($releaseStrategyPath, $errors);
$riskRegister = readRequiredFile($riskRegisterPath, $errors);

if ($testPlan !== null) {
    array_push($errors, ...validateTestPlan($testPlan, $testPlanPath));
}

if ($releaseStrategy !== null) {
    array_push($errors, ...validateReleaseStrategy($releaseStrategy, $releaseStrategyPath));
}

if ($riskRegister !== null) {
    array_push($errors, ...validateRiskRegister($riskRegister, $riskRegisterPath));
}

if ($final) {
    array_push($errors, ...validateFinalEvidence());
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Production readiness ';
echo $final ? 'final evidence check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function readRequiredFile(string $path, array &$errors): ?string
{
    if (! is_file($path)) {
        $errors[] = "Missing required production readiness file: {$path}";

        return null;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = "Unable to read required production readiness file: {$path}";

        return null;
    }

    return $content;
}

/**
 * @return list<string>
 */
function validateTestPlan(string $content, string $path): array
{
    $errors = [];
    if (! preg_match('/^## Production Readiness Gate\s*$/m', $content)) {
        $errors[] = "{$path}: missing Production Readiness Gate section";
    }

    foreach (requiredProductionReadinessCategories() as $category) {
        if (! preg_match('/^\|\s*'.preg_quote($category, '/').'\s*\|/m', $content)) {
            $errors[] = "{$path}: missing production readiness category '{$category}'";
        }
    }

    $requiredDoneCriteria = [
        'Every critical feature has normal, edge, failure, invalid input, permission-denied, stale-cache/index, retry, concurrency, and recovery coverage where applicable.',
        'All P0 and P1 defects are closed.',
        'No P2 defect is open without explicit acceptance.',
    ];

    foreach ($requiredDoneCriteria as $criterion) {
        if (! str_contains($content, "- {$criterion}")) {
            $errors[] = "{$path}: missing done criterion '{$criterion}'";
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
    $requiredReleaseGates = [
        'Edge-case, failure-path, resilience, observability, and recovery evidence approved for every critical feature.',
        'Open defect list accepted by severity policy.',
    ];

    foreach ($requiredReleaseGates as $gate) {
        if (! str_contains($content, "- {$gate}")) {
            $errors[] = "{$path}: missing production readiness release gate '{$gate}'";
        }
    }

    return $errors;
}

/**
 * @return list<string>
 */
function validateRiskRegister(string $content, string $path): array
{
    $errors = [];
    if (! preg_match('/^\|\s*Happy-path-only verification\s*\|/m', $content)) {
        $errors[] = "{$path}: missing Happy-path-only verification risk row";
    }

    $requiredRiskRules = [
        'P0 and P1 risks require an owner before related implementation starts.',
        'P0 and P1 risks require automated verification before release.',
        'Any accepted residual P0/P1 risk requires explicit approval in release evidence.',
    ];

    foreach ($requiredRiskRules as $rule) {
        if (! str_contains($content, "- {$rule}")) {
            $errors[] = "{$path}: missing risk rule '{$rule}'";
        }
    }

    return $errors;
}

/**
 * @return list<string>
 */
function validateFinalEvidence(): array
{
    $candidatePaths = [
        'specs/modernization/production-readiness-evidence.md',
        'docs/content/modernization/production-readiness-evidence.md',
        'docusaurus/docs/developer/production-readiness-evidence.md',
    ];

    $evidencePath = firstExistingPath($candidatePaths);
    if ($evidencePath === null) {
        return ['Final production readiness requires evidence at one of: '.implode(', ', $candidatePaths)];
    }

    $content = file_get_contents($evidencePath);
    if ($content === false) {
        return ["Unable to read production readiness evidence: {$evidencePath}"];
    }

    $rows = evidenceRows($content);
    if ($rows === null) {
        return ["{$evidencePath}: final mode requires an evidence manifest table with Category, Scope, Owner, Evidence, and Status columns"];
    }

    return collectFinalEvidenceErrors($rows, $evidencePath);
}

/**
 * @return list<string>
 */
function requiredProductionReadinessCategories(): array
{
    return [
        'Normal path',
        'Edge cases',
        'Failure paths',
        'Data integrity',
        'Resilience',
        'Observability',
        'Security',
        'Performance',
        'Accessibility',
        'Recovery',
    ];
}

/**
 * @return list<array<string, string>>|null
 */
function evidenceRows(string $content): ?array
{
    $requiredColumns = ['category', 'scope', 'owner', 'evidence', 'status'];
    $lines = preg_split('/\R/', $content) ?: [];
    $lineCount = count($lines);

    for ($index = 0; $index < $lineCount - 1; $index++) {
        $line = $lines[$index];
        $nextLine = $lines[$index + 1] ?? '';
        if (! isMarkdownTableRow($line) || ! isMarkdownSeparatorRow($nextLine)) {
            continue;
        }

        $headers = array_map('normalizeHeader', splitMarkdownRow($line));
        if (array_diff($requiredColumns, $headers) !== []) {
            continue;
        }

        $rows = [];
        for ($rowIndex = $index + 2; $rowIndex < $lineCount; $rowIndex++) {
            $rowLine = $lines[$rowIndex];
            if (! isMarkdownTableRow($rowLine) || isMarkdownSeparatorRow($rowLine)) {
                break;
            }

            $values = splitMarkdownRow($rowLine);
            $row = [];
            foreach ($headers as $headerIndex => $header) {
                $row[$header] = $values[$headerIndex] ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    return null;
}

/**
 * @param  list<array<string, string>>  $rows
 * @return list<string>
 */
function collectFinalEvidenceErrors(array $rows, string $path): array
{
    if ($rows === []) {
        return ["{$path}: final mode requires at least one production readiness evidence row"];
    }

    $errors = [];
    $seenCategories = [];
    $placeholderPattern = '/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required)\b/';

    foreach ($rows as $rowNumber => $row) {
        $rowLabel = 'production readiness row '.($rowNumber + 1);
        $rowText = implode(' | ', $row);
        if (preg_match($placeholderPattern, $rowText) === 1) {
            $errors[] = "{$path}: {$rowLabel} still contains placeholder evidence";
        }

        foreach (['category', 'scope', 'owner', 'evidence', 'status'] as $column) {
            if (trimValue($row[$column] ?? '') === '') {
                $errors[] = "{$path}: {$rowLabel} is missing {$column}";
            }
        }

        if (! preg_match('/\b(?:approved|accepted|passed)\b/i', $row['status'] ?? '')) {
            $errors[] = "{$path}: {$rowLabel} status must show approved, accepted, or passed evidence";
        }

        $seenCategories[] = trimValue($row['category'] ?? '');
    }

    foreach (requiredProductionReadinessCategories() as $category) {
        if (! in_array($category, $seenCategories, true)) {
            $errors[] = "{$path}: final evidence is missing category '{$category}'";
        }
    }

    return $errors;
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

function isMarkdownTableRow(string $line): bool
{
    return preg_match('/^\s*\|.*\|\s*$/', $line) === 1;
}

function isMarkdownSeparatorRow(string $line): bool
{
    return preg_match('/^\s*\|(?:\s*:?-{3,}:?\s*\|)+\s*$/', $line) === 1;
}

/**
 * @return list<string>
 */
function splitMarkdownRow(string $line): array
{
    $line = trim($line);
    $line = trim($line, '|');

    return array_map('trim', explode('|', $line));
}

function normalizeHeader(string $header): string
{
    return preg_replace('/[^a-z0-9]+/', '', strtolower($header)) ?? '';
}

function trimValue(string $value): string
{
    return trim($value, " \t\n\r\0\x0B`");
}
