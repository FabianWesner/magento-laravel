#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$path = 'specs/modernization/test-plan.md';

if (! is_file($path)) {
    fwrite(STDERR, "Missing test plan: {$path}\n");
    exit(1);
}

$content = file_get_contents($path);
if ($content === false) {
    fwrite(STDERR, "Unable to read test plan: {$path}\n");
    exit(1);
}

$requiredItems = [
    'Specs approved.',
    'Test fixtures approved.',
    'Legacy characterization suite green.',
    'Laravel unit/integration suite green.',
    'E2E suite green.',
    'API contract suite green.',
    'Visual regression approved.',
    'Accessibility checks approved.',
    'Performance budgets approved.',
    'Security checks approved.',
    'No-new-XML checks green.',
    'Database schema preservation checks green.',
    'EAV parity checks green.',
    'Admin permission checks green.',
    'Scheduler/queue checks green.',
    'Deployment rehearsal complete.',
    'Rollback rehearsal complete.',
    'Documentation website builds.',
    'Feature guide complete.',
    'Operator runbook complete.',
    'Open defects reviewed and accepted according to severity policy.',
];

$errors = [];
$checklist = releaseReadinessChecklist($content);
if ($checklist === null) {
    $errors[] = "{$path}: missing Release Readiness Checklist section";
} else {
    foreach ($requiredItems as $requiredItem) {
        if (! checklistContainsItem($checklist, $requiredItem)) {
            $errors[] = "{$path}: release readiness checklist missing '{$requiredItem}'";
        }

        if ($final && ! checklistContainsCheckedItem($checklist, $requiredItem)) {
            $errors[] = "{$path}: final mode requires checked release readiness item '{$requiredItem}'";
        }
    }
}

foreach (['P0', 'P1', 'P2', 'P3'] as $severity) {
    if (! preg_match('/^\|\s*'.preg_quote($severity, '/').'\s*\|/m', $content)) {
        $errors[] = "{$path}: missing defect severity policy row for {$severity}";
    }
}

$requiredEvidenceItems = [
    'CI run links or logs.',
    'Test reports.',
    'Visual regression reports.',
    'Performance reports.',
    'Security scan output.',
    'Schema preservation report.',
    'EAV parity report.',
    'Manual acceptance checklist.',
    'Documentation build output.',
    'Release and rollback rehearsal notes.',
];

foreach ($requiredEvidenceItems as $requiredEvidenceItem) {
    if (! str_contains($content, "- {$requiredEvidenceItem}")) {
        $errors[] = "{$path}: missing sign-off evidence item '{$requiredEvidenceItem}'";
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Release readiness ';
echo $final ? 'final checklist check' : 'template check';
echo ' passed for '.count($requiredItems)." checklist items.\n";

/**
 * @return list<string>|null
 */
function releaseReadinessChecklist(string $content): ?array
{
    if (preg_match('/^## Release Readiness Checklist\s*$(?<body>.*?)(?:^##\s|\z)/ms', $content, $matches) !== 1) {
        return null;
    }

    preg_match_all('/^\s*-\s+(?<item>(?:\[[ xX]\]\s*)?.+?)\s*$/m', $matches['body'], $lines);

    return array_values($lines['item'] ?? []);
}

/**
 * @param  list<string>  $checklist
 */
function checklistContainsItem(array $checklist, string $requiredItem): bool
{
    foreach ($checklist as $item) {
        if (normalizeChecklistItem($item) === $requiredItem) {
            return true;
        }
    }

    return false;
}

/**
 * @param  list<string>  $checklist
 */
function checklistContainsCheckedItem(array $checklist, string $requiredItem): bool
{
    foreach ($checklist as $item) {
        if (preg_match('/^\[[xX]\]\s*(?<item>.+)$/', $item, $matches) !== 1) {
            continue;
        }

        if (normalizeChecklistItem($matches['item']) === $requiredItem) {
            return true;
        }
    }

    return false;
}

function normalizeChecklistItem(string $item): string
{
    return trim(preg_replace('/^\[[ xX]\]\s*/', '', $item) ?? $item);
}
