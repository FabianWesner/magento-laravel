#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateManualAcceptancePlanning($errors);

if ($final) {
    validateFinalManualAcceptanceEvidence($errors);
    validateFinalSupportReadinessEvidence($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Manual acceptance/support readiness ';
echo $final ? 'final evidence check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateManualAcceptancePlanning(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Prove happy path, edge-case, failure-path, rollback, operations, security, performance, accessibility, documentation, and support readiness.',
            'Every existing UI must be inventoried and screenshotted for Magento and Laravel across required roles, states, and viewports.',
            'Every Magento feature must be tracked by stable feature ID from specs/modernization/magento-feature-catalog.md and cannot be marked done without fixture, characterization, implementation, test, and release evidence.',
        ],
        'specs/modernization/test-plan.md' => [
            'All required manual acceptance scenarios are signed off.',
            'Manual acceptance checklist.',
            'Docusaurus user docs | Separate user docs explain retained storefront/admin behavior, screenshots, edge cases, support expectations, and known limitations.',
            'Evidence Required For Sign-Off',
        ],
        'specs/modernization/ui-screen-inventory.md' => [
            'A screen cannot be marked done until screenshot evidence, E2E coverage, accessibility checks, and manual acceptance are recorded.',
            'Every interactive screen must capture:',
            'Permission denied state.',
        ],
        'specs/modernization/documentation-plan.md' => [
            'User documentation | Storefront and admin guides for merchandisers, operators, support teams, and business users.',
            'User and developer docs include edge cases, failure states, operational recovery, and production-readiness evidence.',
        ],
        'docusaurus/docs/user/index.md' => [
            'operators, merchandisers, support teams, and business users',
            'Coverage follows every preserved or replaced user-visible feature',
        ],
        'docusaurus/docs/user/feature-coverage.md' => [
            'Known limitations.',
            'Support and rollback notes.',
            'No feature can be marked complete in user documentation until tests and release evidence exist',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing manual acceptance/support planning phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalManualAcceptanceEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/manual-acceptance-evidence.md',
        'specs/modernization/manual-acceptance-checklist.md',
        'docs/content/modernization/manual-acceptance-evidence.md',
        'docusaurus/docs/developer/manual-acceptance.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final manual acceptance readiness requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    $requiredPhrases = [
        'Feature ID',
        'Screen ID',
        'Scenario',
        'Role',
        'Fixture ID',
        'Viewport',
        'Magento Evidence',
        'Laravel Evidence',
        'Accepted By',
        'Accepted At',
        'Support Notes',
        'Known Limitations',
        'Rollback Notes',
        'Status',
    ];

    foreach ($requiredPhrases as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing manual acceptance evidence phrase '{$phrase}'";
        }
    }

    foreach (catalogFeatureIds('specs/modernization/magento-feature-catalog.md') as $featureId) {
        if (! str_contains($content, $featureId)) {
            $errors[] = "{$path}: missing manual acceptance evidence for feature ID {$featureId}";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Missing|Gap|To inventory|Required|Required where applicable|Blocked)\b/', $content) === 1) {
        $errors[] = "{$path}: final manual acceptance evidence still contains placeholders or blockers";
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalSupportReadinessEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/support-readiness-evidence.md',
        'docs/content/modernization/support-readiness-evidence.md',
        'docusaurus/docs/user/support-readiness.md',
        'docusaurus/docs/developer/support-readiness.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final support readiness requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    $requiredPhrases = [
        'Support Owner',
        'Escalation Path',
        'Known Limitations',
        'Rollback Path',
        'User Documentation',
        'Admin Documentation',
        'Operator Documentation',
        'Troubleshooting',
        'Monitoring',
        'Defect Register',
        'Accepted P2 Defects',
        'Status',
    ];

    foreach ($requiredPhrases as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing support readiness evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Missing|Gap|To inventory|Required|Required where applicable|Blocked)\b/', $content) === 1) {
        $errors[] = "{$path}: final support readiness evidence still contains placeholders or blockers";
    }
}

/**
 * @return list<string>
 */
function catalogFeatureIds(string $path): array
{
    if (! is_file($path)) {
        return [];
    }

    $content = file_get_contents($path);
    if ($content === false) {
        return [];
    }

    preg_match_all('/\b(?:SF|AD|CB|API|CJ)-\d{3}\b/', $content, $matches);
    $ids = array_values(array_unique($matches[0] ?? []));
    sort($ids);

    return $ids;
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
