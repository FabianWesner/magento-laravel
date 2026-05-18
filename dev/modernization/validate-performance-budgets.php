#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$path = 'specs/modernization/performance-budgets.md';

if (! is_file($path)) {
    fwrite(STDERR, "Missing performance budgets file: {$path}\n");
    exit(1);
}

$content = file_get_contents($path);
if ($content === false) {
    fwrite(STDERR, "Unable to read performance budgets file: {$path}\n");
    exit(1);
}

$errors = [];
$requiredSections = [
    'Baseline Metrics',
    'Critical Journeys',
    'Initial Budget Policy',
    'Required Numeric Budgets',
    'Verification',
];

foreach ($requiredSections as $section) {
    if (! preg_match('/^##\s+'.preg_quote($section, '/').'\s*$/m', $content)) {
        $errors[] = "{$path}: missing required section '{$section}'";
    }
}

$requiredMetrics = [
    'p50 latency.',
    'p95 latency.',
    'DB query count.',
    'Slowest queries.',
    'Peak memory.',
    'Response size.',
    'Cache hit/miss behavior.',
    'External service time.',
];

foreach ($requiredMetrics as $metric) {
    if (! str_contains($content, "- {$metric}")) {
        $errors[] = "{$path}: missing baseline metric '{$metric}'";
    }
}

$requiredJourneyAreas = ['Storefront', 'Admin', 'API', 'EAV', 'Cron/jobs'];
foreach ($requiredJourneyAreas as $area) {
    if (! preg_match('/^\|\s*'.preg_quote($area, '/').'\s*\|/m', $content)) {
        $errors[] = "{$path}: missing critical journey area '{$area}'";
    }
}

$requiredBudgetAreas = ['HTTP journeys', 'APIs', 'Admin grids', 'EAV reads/writes', 'Cron/jobs', 'Operations'];
foreach ($requiredBudgetAreas as $area) {
    if (! preg_match('/^\|\s*'.preg_quote($area, '/').'\s*\|/m', $content)) {
        $errors[] = "{$path}: missing required numeric budget area '{$area}'";
    }
}

$requiredPolicyItems = [
    'No migrated route, job, API, or admin workflow may be accepted until its budget row has an owner, baseline date, numeric target, and exception policy.',
    'Any exception must include owner, approval date, expiry/revisit date, risk, and compensating control.',
];

foreach ($requiredPolicyItems as $policyItem) {
    if (! str_contains($content, "- {$policyItem}")) {
        $errors[] = "{$path}: missing budget policy item '{$policyItem}'";
    }
}

if ($final) {
    $approvedRows = approvedBudgetManifestRows($content);
    if ($approvedRows === null) {
        $errors[] = "{$path}: final mode requires an approved budget manifest table with Area, Journey, Owner, Baseline Date, Numeric Targets, Exception Policy, Evidence, and Status columns";
    } else {
        array_push($errors, ...collectFinalBudgetManifestErrors($approvedRows, $path));
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Performance budgets ';
echo $final ? 'final approval check' : 'template check';
echo " passed.\n";

/**
 * @return list<array<string, string>>|null
 */
function approvedBudgetManifestRows(string $content): ?array
{
    $requiredColumns = [
        'area',
        'journey',
        'owner',
        'baselinedate',
        'numerictargets',
        'exceptionpolicy',
        'evidence',
        'status',
    ];

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
 * @param  list<array<string, string>>  $approvedRows
 * @return list<string>
 */
function collectFinalBudgetManifestErrors(array $approvedRows, string $path): array
{
    if ($approvedRows === []) {
        return ["{$path}: final mode requires at least one approved budget manifest row"];
    }

    $errors = [];
    $placeholderPattern = '/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required)\b/';
    foreach ($approvedRows as $rowNumber => $row) {
        $rowLabel = 'budget row '.($rowNumber + 1);
        $rowText = implode(' | ', $row);
        if (preg_match($placeholderPattern, $rowText) === 1) {
            $errors[] = "{$path}: {$rowLabel} still contains placeholder evidence";
        }

        foreach (['area', 'journey', 'owner', 'baselinedate', 'numerictargets', 'exceptionpolicy', 'evidence', 'status'] as $column) {
            if (trimValue($row[$column] ?? '') === '') {
                $errors[] = "{$path}: {$rowLabel} is missing {$column}";
            }
        }

        if (! containsConcreteNumericTarget($row['numerictargets'] ?? '')) {
            $errors[] = "{$path}: {$rowLabel} numeric targets must include concrete threshold values";
        }

        if (! preg_match('/\b(?:approved|accepted)\b/i', $row['status'] ?? '')) {
            $errors[] = "{$path}: {$rowLabel} status must show approved or accepted budget evidence";
        }
    }

    return $errors;
}

function containsConcreteNumericTarget(string $value): bool
{
    if (preg_match('/(?:<=|>=|<|>|=)\s*\d+(?:\.\d+)?\s*(?:ms|s|sec|seconds|MB|MiB|%|rps|rpm|queries|rows|minutes|hours)?/i', $value) === 1) {
        return true;
    }

    return preg_match('/\b\d+(?:\.\d+)?\s*(?:ms|s|sec|seconds|MB|MiB|%|rps|rpm|queries|rows|minutes|hours)\b/i', $value) === 1;
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
