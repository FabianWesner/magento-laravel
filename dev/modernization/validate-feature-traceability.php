#!/usr/bin/env php
<?php

declare(strict_types=1);

$strict = in_array('--strict', $argv, true);
$final = in_array('--final', $argv, true);
$strict = $strict || $final;
$catalog = 'specs/modernization/magento-feature-catalog.md';
$requiredFiles = [
    'backlog' => 'specs/modernization/backlog.md',
    'feature inventory' => 'specs/modernization/feature-inventory.md',
    'test plan' => 'specs/modernization/test-plan.md',
    'fixtures' => 'specs/modernization/data-fixtures.md',
    'UI inventory' => 'specs/modernization/ui-screen-inventory.md',
    'complex reverse engineering' => 'specs/modernization/complex-feature-reverse-engineering.md',
];

if (! is_file($catalog)) {
    fwrite(STDERR, "Missing feature catalog: {$catalog}\n");
    exit(1);
}

$catalogContent = file_get_contents($catalog);
if ($catalogContent === false) {
    fwrite(STDERR, "Unable to read feature catalog: {$catalog}\n");
    exit(1);
}

preg_match_all('/\b(?:SF|AD|CB|API|CJ)-\d{3}\b/', $catalogContent, $matches);
$featureIds = array_values(array_unique($matches[0]));
sort($featureIds);

$errors = [];
$warnings = [];
foreach ($requiredFiles as $label => $path) {
    if (! is_file($path)) {
        $errors[] = "Missing {$label} file: {$path}";

        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = "Unable to read {$label} file: {$path}";

        continue;
    }

    if (! preg_match('/\b(?:SF|AD|CB|API|CJ)-\d{3}\b|ALL|ARCH|DOC|OPS|TOOL/', $content)) {
        $errors[] = "{$path}: no feature IDs or approved cross-cutting scopes found";
    }
}

$testPlan = file_get_contents('specs/modernization/test-plan.md') ?: '';
foreach ($featureIds as $featureId) {
    if (! str_contains($testPlan, $featureId) && $strict) {
        $warnings[] = "Strict traceability missing from test plan for {$featureId}";
    }
}

if ($final) {
    $placeholderPatterns = [
        '/\bTBD\b/',
        '/\bPending\b/',
        '/\bTo inventory\b/',
        '/\bRequired\b/',
        '/\bRequired where applicable\b/',
        '/\bOperational evidence required\b/',
    ];
    $featureRowPattern = '/^\|\s*(?:SF|AD|CB|API|CJ)-\d{3}\s*\|/m';

    foreach ($requiredFiles as $label => $path) {
        $content = file_get_contents($path);
        if ($content === false) {
            continue;
        }

        if (preg_match_all($featureRowPattern, $content, $featureRows) === 0) {
            $warnings[] = "Final traceability has no per-feature rows in {$label}: {$path}";

            continue;
        }

        preg_match_all('/^\|\s*(?:SF|AD|CB|API|CJ)-\d{3}\s*\|.*$/m', $content, $featureRows);
        $rows = $featureRows[0] ?? [];
        foreach ($placeholderPatterns as $placeholderPattern) {
            if (preg_grep($placeholderPattern, $rows) !== []) {
                $warnings[] = "Final traceability still contains placeholder evidence in {$label}: {$path}";
                break;
            }
        }
    }

    $finalEvidenceFiles = [
        'progress ledger' => 'specs/progress.md',
        'release strategy' => 'specs/modernization/release-strategy.md',
        'Docusaurus user docs' => 'docusaurus/docs/user/feature-coverage.md',
        'Docusaurus developer docs' => 'docusaurus/docs/developer/testing-and-verification.md',
    ];

    foreach ($finalEvidenceFiles as $label => $path) {
        if (! is_file($path)) {
            $warnings[] = "Missing final evidence file for {$label}: {$path}";
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

if ($strict && $warnings !== []) {
    fwrite(STDERR, implode("\n", $warnings)."\n");
    exit(1);
}

echo 'Feature traceability ';
echo $final ? 'final evidence check' : 'template check';
echo ' passed for '.count($featureIds).' catalog IDs';
if (! $strict && ! $final) {
    echo ' (non-strict preparation mode)';
}
echo ".\n";
