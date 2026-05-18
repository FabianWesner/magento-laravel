#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$path = 'specs/modernization/ui-screen-inventory.md';
$catalogPath = 'specs/modernization/magento-feature-catalog.md';

if (! is_file($path)) {
    fwrite(STDERR, "Missing UI screen inventory: {$path}\n");
    exit(1);
}

$content = file_get_contents($path);
if ($content === false) {
    fwrite(STDERR, "Unable to read UI screen inventory: {$path}\n");
    exit(1);
}

$errors = [];
$visibleFeatureIds = array_merge(
    numberedFeatureIds('SF', 1, 16),
    numberedFeatureIds('AD', 1, 18),
);

$requiredSections = [
    'Required Evidence',
    'Screenshot Storage',
    'Required Viewports',
    'Storefront Screen Catalog',
    'Admin Screen Catalog',
    'Dynamic State Requirements',
    'Acceptance Criteria',
];

foreach ($requiredSections as $section) {
    if (! preg_match('/^##\s+'.preg_quote($section, '/').'\s*$/m', $content)) {
        $errors[] = "{$path}: missing required section '{$section}'";
    }
}

$requiredEvidenceItems = [
    'Stable screen ID.',
    'Magento URL and route/front name.',
    'Laravel URL and route owner.',
    'Required fixture data.',
    'Required user role or customer state.',
    'Required viewports.',
    'Required UI states.',
    'Screenshot file paths.',
    'Parity decision: `preserve`, `bridge`, `replace`, or `retire`.',
];

foreach ($requiredEvidenceItems as $item) {
    if (! str_contains($content, "- {$item}")) {
        $errors[] = "{$path}: missing required evidence item '{$item}'";
    }
}

$requiredStorageFragments = [
    '.localdev/visual-baseline/',
    '|-- magento/',
    '|   |-- storefront/<screen-id>/<viewport>/<state>.png',
    '|   `-- admin/<screen-id>/<viewport>/<state>.png',
    '`-- laravel/',
    '    |-- storefront/<screen-id>/<viewport>/<state>.png',
    '    `-- admin/<screen-id>/<viewport>/<state>.png',
];

foreach ($requiredStorageFragments as $fragment) {
    if (! str_contains($content, $fragment)) {
        $errors[] = "{$path}: missing screenshot storage fragment '{$fragment}'";
    }
}

$requiredViewports = [
    'Desktop' => '1440x1000',
    'Laptop' => '1280x900',
    'Tablet' => '768x1024',
    'Mobile' => '390x844',
];

foreach ($requiredViewports as $name => $size) {
    if (! preg_match('/^\|\s*'.preg_quote($name, '/').'\s*\|\s*`?'.preg_quote($size, '/').'`?\s*\|/m', $content)) {
        $errors[] = "{$path}: missing required viewport {$name} {$size}";
    }
}

foreach ($visibleFeatureIds as $featureId) {
    if (! inventoryMentionsVisibleFeature($content, $featureId)) {
        $errors[] = "{$path}: missing visible feature scope {$featureId}";
    }
}

if ($final) {
    array_push($errors, ...collectFinalManifestErrors($content, $path, $catalogPath, $visibleFeatureIds, $requiredViewports));
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'UI screen inventory ';
echo $final ? 'final screenshot evidence check' : 'template check';
echo ' passed for '.count($visibleFeatureIds)." visible feature IDs.\n";

/**
 * @return list<string>
 */
function numberedFeatureIds(string $prefix, int $start, int $end): array
{
    return array_map(
        fn (int $number): string => sprintf('%s-%03d', $prefix, $number),
        range($start, $end),
    );
}

function inventoryMentionsVisibleFeature(string $content, string $featureId): bool
{
    if (str_contains($content, $featureId)) {
        return true;
    }

    if (str_starts_with($featureId, 'SF-')) {
        return preg_match('/`?SF-001`?\s+through\s+`?SF-016`?/', $content) === 1;
    }

    if (str_starts_with($featureId, 'AD-')) {
        return preg_match('/`?AD-001`?\s+through\s+`?AD-018`?/', $content) === 1;
    }

    return false;
}

/**
 * @param  list<string>  $visibleFeatureIds
 * @param  array<string, string>  $requiredViewports
 * @return list<string>
 */
function collectFinalManifestErrors(string $content, string $path, string $catalogPath, array $visibleFeatureIds, array $requiredViewports): array
{
    $manifestRows = screenshotManifestRows($content);
    if ($manifestRows === []) {
        return [
            "{$path}: final mode requires a screenshot manifest table with columns Screen ID, Feature IDs, Runtime, URL, Role, Fixture ID, Viewport, State, Captured At, Artifact Path, and Parity Decision",
        ];
    }

    $errors = [];
    $catalogFeatureIds = catalogFeatureIds($catalogPath);
    $runtimesByFeature = [];
    $observedViewports = [];
    $placeholderPattern = '/\b(?:TBD|Pending|To inventory|Required|Required where applicable|Operational evidence required)\b/';

    foreach ($manifestRows as $rowNumber => $row) {
        $rowLabel = 'manifest row '.($rowNumber + 1);
        $rowText = implode(' | ', $row);

        if (preg_match($placeholderPattern, $rowText) === 1) {
            $errors[] = "{$path}: {$rowLabel} still contains placeholder evidence";
        }

        $screenId = trimValue($row['screenid'] ?? '');
        if ($screenId === '') {
            $errors[] = "{$path}: {$rowLabel} is missing Screen ID";
        }

        $featureIds = featureIdsFromContent($row['featureids'] ?? '');
        if ($featureIds === []) {
            $errors[] = "{$path}: {$rowLabel} is missing catalog Feature IDs";
        }

        foreach (array_diff($featureIds, $catalogFeatureIds) as $featureId) {
            $errors[] = "{$path}: {$rowLabel} references unknown catalog feature ID {$featureId}";
        }

        $runtime = strtolower(trimValue($row['runtime'] ?? ''));
        if (! in_array($runtime, ['magento', 'laravel'], true)) {
            $errors[] = "{$path}: {$rowLabel} runtime must be magento or laravel";
        }

        foreach ($featureIds as $featureId) {
            if (in_array($featureId, $visibleFeatureIds, true) && in_array($runtime, ['magento', 'laravel'], true)) {
                $runtimesByFeature[$featureId][$runtime] = true;
            }
        }

        foreach (['url', 'role', 'fixtureid', 'state', 'capturedat'] as $requiredColumn) {
            if (trimValue($row[$requiredColumn] ?? '') === '') {
                $errors[] = "{$path}: {$rowLabel} is missing {$requiredColumn}";
            }
        }

        $viewport = normalizeViewport(trimValue($row['viewport'] ?? ''));
        if ($viewport === null) {
            $errors[] = "{$path}: {$rowLabel} has an invalid viewport";
        } else {
            $observedViewports[$viewport] = true;
        }

        $parityDecision = strtolower(trimValue($row['paritydecision'] ?? ''));
        if (! in_array($parityDecision, ['preserve', 'bridge', 'replace', 'retire'], true)) {
            $errors[] = "{$path}: {$rowLabel} parity decision must be preserve, bridge, replace, or retire";
        }

        $artifactPath = trimValue($row['artifactpath'] ?? '');
        if ($artifactPath === '') {
            $errors[] = "{$path}: {$rowLabel} is missing Artifact Path";
        } elseif (! str_ends_with($artifactPath, '.png')) {
            $errors[] = "{$path}: {$rowLabel} artifact path must point to a PNG screenshot";
        } else {
            if (in_array($runtime, ['magento', 'laravel'], true) && ! str_starts_with($artifactPath, ".localdev/visual-baseline/{$runtime}/")) {
                $errors[] = "{$path}: {$rowLabel} artifact path must be under .localdev/visual-baseline/{$runtime}/";
            }

            if (! is_file($artifactPath)) {
                $errors[] = "{$path}: {$rowLabel} artifact path does not exist: {$artifactPath}";
            }
        }
    }

    foreach ($visibleFeatureIds as $featureId) {
        foreach (['magento', 'laravel'] as $runtime) {
            if (! isset($runtimesByFeature[$featureId][$runtime])) {
                $errors[] = "{$path}: final screenshot manifest missing {$runtime} evidence for {$featureId}";
            }
        }
    }

    foreach (array_keys($requiredViewports) as $viewportName) {
        $viewport = strtolower($viewportName);
        if (! isset($observedViewports[$viewport])) {
            $errors[] = "{$path}: final screenshot manifest missing {$viewport} viewport evidence";
        }
    }

    return $errors;
}

/**
 * @return list<array<string, string>>
 */
function screenshotManifestRows(string $content): array
{
    $requiredColumns = [
        'screenid',
        'featureids',
        'runtime',
        'url',
        'role',
        'fixtureid',
        'viewport',
        'state',
        'capturedat',
        'artifactpath',
        'paritydecision',
    ];

    $rows = [];
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
    }

    return $rows;
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

/**
 * @return list<string>
 */
function featureIdsFromContent(string $content): array
{
    preg_match_all('/\b(?:SF|AD|CB|API|CJ)-\d{3}\b/', $content, $matches);
    $ids = array_values(array_unique($matches[0] ?? []));
    sort($ids);

    return $ids;
}

/**
 * @return list<string>
 */
function catalogFeatureIds(string $path): array
{
    if (! is_file($path)) {
        throw new RuntimeException("Missing feature catalog: {$path}");
    }

    $content = file_get_contents($path);
    if ($content === false) {
        throw new RuntimeException("Unable to read feature catalog: {$path}");
    }

    return featureIdsFromContent($content);
}

function normalizeViewport(string $viewport): ?string
{
    $viewport = strtolower($viewport);
    $viewport = trim($viewport, " \t\n\r\0\x0B`");

    return match ($viewport) {
        'desktop', '1440x1000' => 'desktop',
        'laptop', '1280x900' => 'laptop',
        'tablet', '768x1024' => 'tablet',
        'mobile', '390x844' => 'mobile',
        default => null,
    };
}
