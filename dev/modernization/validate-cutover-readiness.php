#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateCutoverPlanning($errors);
validateLegacyRuntimePresence($errors);

if ($final) {
    validateFinalCutoverEvidence($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Cutover readiness ';
echo $final ? 'final evidence check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateCutoverPlanning(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Modernize the existing Magento CE 1.9.4.5 project into a Laravel-based architecture while keeping the legacy Magento runtime in place for side-by-side comparison until final cutover.',
            'Do not remove the legacy Magento runtime while any feature still needs characterization, screenshotting, or parity comparison.',
            'Run Magento and Laravel side-by-side against restorable fixture data so every feature can be compared before route cutover.',
        ],
        'specs/modernization/workspace-layout.md' => [
            'The old Magento runtime stays in place for the whole modernization.',
            'features are compared side-by-side until the final route cutover is approved.',
            'Do not remove the Magento runtime while a feature still needs characterization or parity comparison.',
        ],
        'specs/modernization/release-strategy.md' => [
            'Use route-by-route strangler migration with feature flags and route ownership metadata.',
            'Legacy route fallback remains available until the migrated route has passed staging and production observation.',
            'Feature flags can move traffic back to legacy routes.',
            'Rollback rehearsal complete.',
        ],
        'specs/modernization/roadmap.md' => [
            'Phase 14: Release And Cutover',
            'Freeze legacy behavior changes before final cutover.',
            'Cut over route groups or full application according to rollout plan.',
            'Monitoring shows no critical regressions after cutover.',
        ],
        'specs/modernization/proof-of-concepts.md' => [
            'Rollback behavior is explicit for each moved route.',
            'Route ownership is observable in logs.',
        ],
        'specs/modernization/test-plan.md' => [
            'Release Readiness Checklist',
            'Release and rollback rehearsal notes.',
            'Production shadow or canary',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing cutover planning phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateLegacyRuntimePresence(array &$errors): void
{
    $requiredPaths = [
        'core/magento-1.9.4.5/index.php',
        'core/magento-1.9.4.5/app/Mage.php',
        'dev/magento/build-docroot.sh',
    ];

    foreach ($requiredPaths as $path) {
        if (! is_file($path)) {
            $errors[] = "{$path}: required legacy runtime/cutover path is missing";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalCutoverEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/cutover-readiness-evidence.md',
        'specs/modernization/cutover-plan.md',
        'docs/content/modernization/cutover-readiness-evidence.md',
        'docusaurus/docs/developer/cutover-readiness.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final cutover readiness requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    $requiredPhrases = [
        'Legacy Runtime Status',
        'Laravel Runtime Status',
        'Route Group',
        'Route Owner',
        'Feature Flag',
        'Fixture ID',
        'Parity Evidence',
        'Visual Evidence',
        'Staging Observation',
        'Production Observation',
        'Legacy Freeze',
        'Rollback Path',
        'Backup Restore Rehearsal',
        'Monitoring',
        'Defect Review',
        'Stakeholder Approval',
        'Cutover Decision',
        'Status',
    ];

    foreach ($requiredPhrases as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing cutover readiness evidence phrase '{$phrase}'";
        }
    }

    foreach (['SF-', 'AD-', 'CB-', 'API-', 'CJ-'] as $featurePrefix) {
        if (! str_contains($content, $featurePrefix)) {
            $errors[] = "{$path}: missing cutover readiness evidence for {$featurePrefix} feature IDs";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Missing|Gap|To inventory|Required|Required where applicable|Blocked)\b/', $content) === 1) {
        $errors[] = "{$path}: final cutover readiness evidence still contains placeholders or blockers";
    }
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
