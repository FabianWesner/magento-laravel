#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$path = 'specs/modernization/visual-tolerances.md';

if (! is_file($path)) {
    fwrite(STDERR, "Missing visual tolerances file: {$path}\n");
    exit(1);
}

$content = file_get_contents($path);
if ($content === false) {
    fwrite(STDERR, "Unable to read visual tolerances file: {$path}\n");
    exit(1);
}

$errors = [];
$requiredSections = [
    'Default Thresholds',
    'Allowed Masks',
    'Override Manifest',
    'Approval Rules',
];

foreach ($requiredSections as $section) {
    if (! preg_match('/^##\s+'.preg_quote($section, '/').'\s*$/m', $content)) {
        $errors[] = "{$path}: missing required section '{$section}'";
    }
}

$requiredThresholds = [
    'Full-page pixel diff' => '<= 0.10%',
    'Critical commerce regions' => '<= 0.02%',
    'Admin data grids/forms' => '<= 0.05%',
    'Text overflow/overlap' => '0',
    'Layout shift' => 'CLS <= 0.02',
    'Accessibility-affecting visual changes' => '0',
];

foreach ($requiredThresholds as $area => $threshold) {
    if (! preg_match('/^\|\s*'.preg_quote($area, '/').'\s*\|\s*`?'.preg_quote($threshold, '/').'`?/m', $content)) {
        $errors[] = "{$path}: missing default visual threshold '{$area}' with '{$threshold}'";
    }
}

$requiredAllowedMasks = [
    'Timestamps.',
    'CSRF/form keys.',
    'Captcha images.',
    'Randomized recommendation order where Magento itself is non-deterministic.',
    'Third-party iframe content where a deterministic mock is not available.',
];

foreach ($requiredAllowedMasks as $mask) {
    if (! str_contains($content, "- {$mask}")) {
        $errors[] = "{$path}: missing allowed mask '{$mask}'";
    }
}

if (! str_contains($content, 'Masking product prices, totals, tax, payment state, order state, admin permissions, validation messages, or destructive actions is not allowed.')) {
    $errors[] = "{$path}: missing forbidden mask rule for commerce-critical regions";
}

$overrideRows = overrideManifestRows($content);
if ($overrideRows === null) {
    $errors[] = "{$path}: missing override manifest table with Screen ID, Viewport, State, Region, Override, Approver, Rationale, and Expiry columns";
}

if ($final && $overrideRows !== null) {
    array_push($errors, ...collectFinalOverrideErrors($overrideRows, $path));
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Visual tolerances ';
echo $final ? 'final approval check' : 'template check';
echo " passed.\n";

/**
 * @return list<array<string, string>>|null
 */
function overrideManifestRows(string $content): ?array
{
    $requiredColumns = [
        'screenid',
        'viewport',
        'state',
        'region',
        'override',
        'approver',
        'rationale',
        'expiry',
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
 * @param  list<array<string, string>>  $overrideRows
 * @return list<string>
 */
function collectFinalOverrideErrors(array $overrideRows, string $path): array
{
    if ($overrideRows === []) {
        return [];
    }

    $errors = [];
    $placeholderPattern = '/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required)\b/';

    foreach ($overrideRows as $rowNumber => $row) {
        $rowLabel = 'override row '.($rowNumber + 1);
        $rowText = implode(' | ', $row);

        if (preg_match($placeholderPattern, $rowText) === 1) {
            $errors[] = "{$path}: final mode cannot contain placeholder visual override evidence in {$rowLabel}";
        }

        foreach (['screenid', 'viewport', 'state', 'region', 'override', 'approver', 'rationale', 'expiry'] as $column) {
            if (trimValue($row[$column] ?? '') === '') {
                $errors[] = "{$path}: {$rowLabel} is missing {$column}";
            }
        }
    }

    return $errors;
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
