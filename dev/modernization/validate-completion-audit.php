#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateCompletionAuditSpecs($errors);

if ($final) {
    validateFinalCompletionAudit($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Completion audit ';
echo $final ? 'final check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateCompletionAuditSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/modernization/test-plan.md' => [
            '## Completion Audit Gate',
            'Prompt-to-artifact checklist',
            'Passing tests, manifests, verifier success, or green status are proxy signals until the audit maps them to the requirement they prove.',
            'Treat uncertainty as not achieved.',
            '`specs/modernization/completion-audit.md`',
        ],
        'specs/modernization/release-strategy.md' => [
            'Completion audit approved against `specs/GOAL.md` with concrete evidence for every requirement, named file, command, test, gate, and deliverable.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing completion audit planning phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalCompletionAudit(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/completion-audit.md',
        'docs/content/modernization/completion-audit.md',
        'docusaurus/docs/developer/completion-audit.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final completion audit requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    validateFinalAuditStructure($path, $content, $errors);
    validateFinalAuditCoverage($path, $content, $errors);
    validateFinalAuditChecklist($path, $content, $errors);
}

/**
 * @param  list<string>  $errors
 */
function validateFinalAuditStructure(string $path, string $content, array &$errors): void
{
    $requiredHeadings = [
        'Objective Restatement',
        'Prompt-to-Artifact Checklist',
        'Evidence Inspection',
        'Missing Or Weak Coverage',
        'Completion Decision',
    ];

    foreach ($requiredHeadings as $heading) {
        if (! preg_match('/^##\s+'.preg_quote($heading, '/').'\s*$/m', $content)) {
            $errors[] = "{$path}: missing final audit section '{$heading}'";
        }
    }

    $requiredPhrases = [
        'Concrete deliverables',
        'Proxy signals were inspected against actual requirements.',
        'No missing, incomplete, weakly verified, or uncovered requirements remain.',
        'Completion decision: complete',
    ];

    foreach ($requiredPhrases as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing final audit phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalAuditCoverage(string $path, string $content, array &$errors): void
{
    foreach (requiredCoveragePhrases() as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: completion audit does not cover '{$phrase}'";
        }
    }

    foreach (requiredVerificationCommands() as $command) {
        if (! str_contains($content, $command)) {
            $errors[] = "{$path}: completion audit does not include verification command '{$command}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalAuditChecklist(string $path, string $content, array &$errors): void
{
    $table = markdownTableWithColumns($content, ['requirement', 'source', 'evidence', 'verification', 'status']);
    if ($table === null) {
        $errors[] = "{$path}: final audit requires a Prompt-to-artifact checklist table with Requirement, Source, Evidence, Verification, and Status columns";

        return;
    }

    if (count($table['rows']) < 25) {
        $errors[] = "{$path}: final audit checklist must include at least 25 concrete requirement rows";
    }

    $placeholderPattern = '/\b(?:TBD|Pending|Required|Missing|Gap|Unverified|Unknown|Unclear|Blocked|Not run|Not achieved|Incomplete)\b/i';

    foreach ($table['rows'] as $rowNumber => $row) {
        $rowLabel = 'completion audit row '.($rowNumber + 1);
        $rowText = implode(' | ', $row);

        if (preg_match($placeholderPattern, $rowText) === 1) {
            $errors[] = "{$path}: {$rowLabel} still contains placeholder or incomplete evidence";
        }

        foreach (['requirement', 'source', 'evidence', 'verification', 'status'] as $column) {
            if (trimValue($row[$column] ?? '') === '') {
                $errors[] = "{$path}: {$rowLabel} is missing {$column}";
            }
        }

        if (! hasConcreteEvidence($row['evidence'] ?? '')) {
            $errors[] = "{$path}: {$rowLabel} evidence must cite a concrete artifact, command output, URL, or file path";
        }

        if (! preg_match('/\b(?:approved|accepted|passed|verified|complete)\b/i', $row['status'] ?? '')) {
            $errors[] = "{$path}: {$rowLabel} status must show approved, accepted, passed, verified, or complete evidence";
        }
    }
}

/**
 * @return list<string>
 */
function requiredCoveragePhrases(): array
{
    return [
        'specs/GOAL.md',
        'core/magento-1.9.4.5/',
        'project/',
        'laravel/',
        '.localdev/magento-docroot/',
        'Docusaurus',
        'Livewire',
        'EAV',
        'No destructive schema migration',
        'no new XML',
        'technology-removal-policy.md',
        'magento-feature-catalog.md',
        'ui-screen-inventory.md',
        'complex-feature-reverse-engineering.md',
        'data-fixtures.md',
        'test-plan.md',
        'progress.md',
        'P0/P1',
        'Chrome/Playwright',
        'Laravel Boost MCP',
    ];
}

/**
 * @return list<string>
 */
function requiredVerificationCommands(): array
{
    return [
        'bash dev/modernization/gate.sh',
        'php artisan test --compact',
        'php dev/modernization/validate-no-new-xml.php',
        'php dev/modernization/validate-removed-technologies.php',
        'php dev/modernization/fixture-coverage-report.php',
        'php dev/modernization/schema-report.php',
        'npm --prefix docusaurus run build',
        'node dev/modernization/smoke-docusaurus.mjs',
    ];
}

/**
 * @param  list<string>  $requiredColumns
 * @return array{headers: list<string>, rows: list<array<string, string>>}|null
 */
function markdownTableWithColumns(string $content, array $requiredColumns): ?array
{
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

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    return null;
}

function hasConcreteEvidence(string $value): bool
{
    $trimmed = trimValue($value);

    if (preg_match('/https?:\/\/\S+/', $trimmed) === 1) {
        return true;
    }

    if (preg_match('/`[^`]+`/', $trimmed) === 1) {
        return true;
    }

    if (preg_match('/\b(?:commit|artifact|report|screenshot|log|run)\b/i', $trimmed) === 1 && strlen($trimmed) >= 20) {
        return true;
    }

    return false;
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
function splitMarkdownRow(string $row): array
{
    $trimmed = trim($row);
    $trimmed = trim($trimmed, '|');

    return array_map(trim(...), explode('|', $trimmed));
}

function normalizeHeader(string $header): string
{
    return strtolower(trimValue($header));
}

function trimValue(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/<br\s*\/?>/i', ' ', $value) ?? $value;
    $value = preg_replace('/\s+/', ' ', $value) ?? $value;

    return trim($value);
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
