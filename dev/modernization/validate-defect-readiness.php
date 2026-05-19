#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateDefectPolicySpecs($errors);

if ($final) {
    validateFinalDefectReadiness($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Defect readiness ';
echo $final ? 'final check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateDefectPolicySpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'No P0/P1 defects remain open, and P2 defects require explicit acceptance.',
        ],
        'specs/modernization/test-plan.md' => [
            'All P0 and P1 defects are closed.',
            'No P2 defect is open without explicit acceptance.',
            'Open defects reviewed and accepted according to severity policy.',
            '| P0 | Data loss, payment/order corruption, security critical, app unavailable. | Blocks release. |',
            '| P1 | Broken critical journey, authorization bypass, severe performance regression. | Blocks release. |',
            '| P2 | Important feature regression with workaround or limited scope. | Requires explicit acceptance. |',
        ],
        'specs/modernization/risk-register.md' => [
            'P0 and P1 risks require an owner before related implementation starts.',
            'P0 and P1 risks require automated verification before release.',
            'Any accepted residual P0/P1 risk requires explicit approval in release evidence.',
            'Every accepted risk needs a written decision.',
        ],
        'specs/modernization/release-strategy.md' => [
            'Open defect list accepted by severity policy.',
        ],
        'docs/content/modernization/index.md' => [
            'known limitations.',
        ],
        'docusaurus/docs/user/feature-coverage.md' => [
            'No feature can be marked complete in user documentation until tests and release evidence exist in the migration specs.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing defect readiness planning phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalDefectReadiness(array &$errors): void
{
    validateReleaseChecklist($errors);
    validateDefectRegister($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateReleaseChecklist(array &$errors): void
{
    $path = 'specs/modernization/test-plan.md';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    if (! preg_match('/^\s*-\s+\[[xX]\]\s*Open defects reviewed and accepted according to severity policy\.\s*$/m', $content)) {
        $errors[] = "{$path}: final defect readiness requires checked release item 'Open defects reviewed and accepted according to severity policy.'";
    }
}

/**
 * @param  list<string>  $errors
 */
function validateDefectRegister(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/defect-register.md',
        'docs/content/modernization/defect-register.md',
        'docusaurus/docs/developer/defect-register.md',
        'specs/modernization/release-defects.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final defect readiness requires a defect register at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (['Defect ID', 'Severity', 'Status', 'Owner', 'Feature IDs', 'Evidence', 'Acceptance', 'Accepted By', 'Accepted At', 'Resolution', 'Workaround'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing defect register phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|To inventory)\b/', $content) === 1) {
        $errors[] = "{$path}: final defect register still contains placeholders";
    }

    $rows = markdownTableRows($content);
    if ($rows === []) {
        $errors[] = "{$path}: final defect register requires at least one markdown table";

        return;
    }

    $header = defectRegisterHeader($rows);
    if ($header === null) {
        $errors[] = "{$path}: unable to locate a defect register table with Defect ID, Severity, and Status columns";

        return;
    }

    foreach ($rows as $rowNumber => $row) {
        $cells = splitMarkdownRow($row);
        if ($cells === [] || isSeparatorRow($cells) || cellsEqual($cells, $header['cells'])) {
            continue;
        }

        $defectId = cellValue($cells, $header['indexes']['Defect ID']);
        $severity = strtoupper(cellValue($cells, $header['indexes']['Severity']));
        $status = strtolower(cellValue($cells, $header['indexes']['Status']));
        $acceptance = cellValue($cells, $header['indexes']['Acceptance'] ?? null);
        $acceptedBy = cellValue($cells, $header['indexes']['Accepted By'] ?? null);
        $acceptedAt = cellValue($cells, $header['indexes']['Accepted At'] ?? null);

        if ($defectId === '') {
            $errors[] = "{$path}: defect table row {$rowNumber} is missing a Defect ID";
        }

        if (! in_array($severity, ['P0', 'P1', 'P2', 'P3'], true)) {
            $errors[] = "{$path}: defect {$defectId} has invalid severity '{$severity}'";

            continue;
        }

        if (in_array($severity, ['P0', 'P1'], true) && isOpenStatus($status)) {
            $errors[] = "{$path}: {$severity} defect {$defectId} is still open";
        }

        if ($severity === 'P2' && isOpenStatus($status) && ! hasExplicitAcceptance($acceptance, $acceptedBy, $acceptedAt)) {
            $errors[] = "{$path}: open P2 defect {$defectId} requires explicit acceptance, accepted by, and accepted at";
        }
    }
}

/**
 * @return list<string>
 */
function markdownTableRows(string $content): array
{
    preg_match_all('/^\|.*\|\s*$/m', $content, $matches);

    return array_values($matches[0] ?? []);
}

/**
 * @param  list<string>  $rows
 * @return array{cells: list<string>, indexes: array<string, int>}|null
 */
function defectRegisterHeader(array $rows): ?array
{
    foreach ($rows as $row) {
        $cells = splitMarkdownRow($row);
        $required = ['Defect ID', 'Severity', 'Status'];
        if ($cells === [] || array_diff($required, $cells) !== []) {
            continue;
        }

        $indexes = [];
        foreach ($cells as $index => $cell) {
            $indexes[$cell] = $index;
        }

        return [
            'cells' => $cells,
            'indexes' => $indexes,
        ];
    }

    return null;
}

/**
 * @return list<string>
 */
function splitMarkdownRow(string $row): array
{
    $trimmed = trim($row);
    if ($trimmed === '' || ! str_starts_with($trimmed, '|')) {
        return [];
    }

    $trimmed = trim($trimmed, '|');

    return array_map(
        static fn (string $cell): string => trim($cell),
        explode('|', $trimmed),
    );
}

/**
 * @param  list<string>  $cells
 */
function isSeparatorRow(array $cells): bool
{
    foreach ($cells as $cell) {
        if (! preg_match('/^:?-{3,}:?$/', $cell)) {
            return false;
        }
    }

    return true;
}

/**
 * @param  list<string>  $left
 * @param  list<string>  $right
 */
function cellsEqual(array $left, array $right): bool
{
    return count($left) === count($right) && array_values($left) === array_values($right);
}

/**
 * @param  list<string>  $cells
 */
function cellValue(array $cells, ?int $index): string
{
    if ($index === null) {
        return '';
    }

    return trim($cells[$index] ?? '');
}

function isOpenStatus(string $status): bool
{
    return preg_match('/\b(open|new|triage|investigating|in progress|blocked|reopened|unresolved)\b/i', $status) === 1
        && preg_match('/\b(closed|resolved|accepted|waived|deferred)\b/i', $status) !== 1;
}

function hasExplicitAcceptance(string $acceptance, string $acceptedBy, string $acceptedAt): bool
{
    if ($acceptance === '' || $acceptedBy === '' || $acceptedAt === '') {
        return false;
    }

    return preg_match('/\b(accepted|approved|waived|deferred)\b/i', $acceptance) === 1
        && preg_match('/\d{4}-\d{2}-\d{2}/', $acceptedAt) === 1;
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
