#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateSpecCurrencyPlanning($errors);
validateRequiredSpecFiles($errors);
validateRequiredSpecLinks($errors);

if ($final) {
    validateFinalSpecCurrencyEvidence($errors);
    validateFinalSpecPlaceholders($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Spec currency/linkage ';
echo $final ? 'final evidence check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateSpecCurrencyPlanning(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Every file under specs/modernization that defines architecture, feature catalog, UI inventory, complex behavior, fixture strategy, backlog, risk, release, operations, security, and test plan is current and internally linked.',
            'Run the full test plan in specs/modernization/test-plan.md.',
            'Every Magento feature must be tracked by stable feature ID from specs/modernization/magento-feature-catalog.md',
        ],
        'specs/modernization/test-plan.md' => [
            'The release gate is feature-ID driven.',
            'specs/modernization/magento-feature-catalog.md',
            'specs/modernization/ui-screen-inventory.md',
            'specs/modernization/complex-feature-reverse-engineering.md',
            'specs/modernization/data-fixtures.md',
            'specs/modernization/technology-removal-policy.md',
        ],
        'specs/modernization/magento-feature-catalog.md' => [
            'Every UI feature maps to `specs/modernization/ui-screen-inventory.md`.',
            'Every complex behavior maps to `specs/modernization/complex-feature-reverse-engineering.md`.',
            'Every feature ID is present in the test plan traceability matrix.',
        ],
        'specs/modernization/feature-inventory.md' => [
            'The canonical feature list is `specs/modernization/magento-feature-catalog.md`',
            'Screenshot IDs from `ui-screen-inventory.md` when visible.',
            'every inventory row links to test-plan evidence.',
        ],
        'specs/modernization/roadmap.md' => [
            '[test plan](test-plan.md)',
            '[inventory](inventory.md)',
            '[comprehensive test plan](test-plan.md)',
        ],
        'specs/modernization/documentation-plan.md' => [
            'Every architecture decision has reasoning.',
            'Feature coverage | User-visible status for every feature ID in `magento-feature-catalog.md`.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing spec currency/linkage phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateRequiredSpecFiles(array &$errors): void
{
    foreach (requiredSpecFilesByCategory() as $category => $paths) {
        $found = false;
        foreach ($paths as $path) {
            if (is_file($path)) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            $errors[] = "Missing modernization spec for {$category}: expected one of ".implode(', ', $paths);
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateRequiredSpecLinks(array &$errors): void
{
    $aggregateContent = '';
    foreach (uniqueRequiredSpecFiles() as $path) {
        $content = readTextFile($path, $errors);
        if ($content !== null) {
            $aggregateContent .= "\n{$path}\n{$content}";
        }
    }

    foreach (requiredLinkedSpecTargets() as $target) {
        if (! str_contains($aggregateContent, $target)) {
            $errors[] = "Modernization specs are missing internal reference to {$target}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalSpecCurrencyEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/spec-currency-evidence.md',
        'specs/modernization/spec-linkage-evidence.md',
        'docs/content/modernization/spec-currency-evidence.md',
        'docusaurus/docs/developer/spec-currency-evidence.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final spec currency/linkage readiness requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    $requiredPhrases = [
        'Spec File',
        'Category',
        'Owner',
        'Last Reviewed',
        'Source Of Truth',
        'Linked Specs',
        'Feature IDs',
        'Verification Command',
        'Evidence Artifact',
        'Approved By',
        'Approved At',
        'Status',
    ];

    foreach ($requiredPhrases as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing spec currency evidence phrase '{$phrase}'";
        }
    }

    foreach (uniqueRequiredSpecFiles() as $requiredSpecFile) {
        if (! str_contains($content, $requiredSpecFile)) {
            $errors[] = "{$path}: missing spec currency evidence for {$requiredSpecFile}";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Missing|Gap|To inventory|Required|Required where applicable|Blocked|Unknown)\b/', $content) === 1) {
        $errors[] = "{$path}: final spec currency/linkage evidence still contains placeholders or blockers";
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalSpecPlaceholders(array &$errors): void
{
    $placeholderPattern = '/\b(?:TBD|Pending|Missing|Gap|To inventory|Required where applicable|Operational evidence required|Blocked|Unknown)\b/';

    foreach (uniqueRequiredSpecFiles() as $path) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        if (preg_match($placeholderPattern, $content) === 1) {
            $errors[] = "{$path}: final spec currency/linkage readiness requires removing placeholders or blockers from core modernization specs";
        }
    }
}

/**
 * @return array<string, list<string>>
 */
function requiredSpecFilesByCategory(): array
{
    return [
        'architecture' => ['specs/modernization/architecture-specs.md'],
        'feature catalog' => ['specs/modernization/magento-feature-catalog.md'],
        'UI inventory' => ['specs/modernization/ui-screen-inventory.md'],
        'complex behavior' => ['specs/modernization/complex-feature-reverse-engineering.md'],
        'fixture strategy' => ['specs/modernization/data-fixtures.md'],
        'backlog' => ['specs/modernization/backlog.md'],
        'risk' => ['specs/modernization/risk-register.md'],
        'release' => ['specs/modernization/release-strategy.md'],
        'operations' => ['specs/modernization/test-plan.md', 'specs/modernization/release-strategy.md'],
        'security' => ['specs/modernization/test-plan.md', 'specs/modernization/compatibility-policy.md'],
        'test plan' => ['specs/modernization/test-plan.md'],
        'documentation plan' => ['specs/modernization/documentation-plan.md'],
        'technology removal' => ['specs/modernization/technology-removal-policy.md'],
        'feature inventory' => ['specs/modernization/feature-inventory.md'],
        'performance budgets' => ['specs/modernization/performance-budgets.md'],
    ];
}

/**
 * @return list<string>
 */
function uniqueRequiredSpecFiles(): array
{
    $paths = [];
    foreach (requiredSpecFilesByCategory() as $categoryPaths) {
        array_push($paths, ...$categoryPaths);
    }

    $paths = array_values(array_unique($paths));
    sort($paths);

    return $paths;
}

/**
 * @return list<string>
 */
function requiredLinkedSpecTargets(): array
{
    return [
        'specs/modernization/magento-feature-catalog.md',
        'specs/modernization/ui-screen-inventory.md',
        'specs/modernization/complex-feature-reverse-engineering.md',
        'specs/modernization/data-fixtures.md',
        'specs/modernization/feature-inventory.md',
        'specs/modernization/test-plan.md',
        'specs/modernization/technology-removal-policy.md',
        'specs/modernization/visual-tolerances.md',
        'test-plan.md',
        'inventory.md',
    ];
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
