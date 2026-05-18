#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$testPlanPath = 'specs/modernization/test-plan.md';

if (! is_file($testPlanPath)) {
    fwrite(STDERR, "Missing test plan: {$testPlanPath}\n");
    exit(1);
}

$content = file_get_contents($testPlanPath);
if ($content === false) {
    fwrite(STDERR, "Unable to read test plan: {$testPlanPath}\n");
    exit(1);
}

$errors = [];
array_push($errors, ...validateSecurityTestPlan($content, $testPlanPath));
array_push($errors, ...validateAccessibilityTestPlan($content, $testPlanPath));

if ($final) {
    array_push($errors, ...validateFinalEvidence());
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Security and accessibility ';
echo $final ? 'final evidence check' : 'template check';
echo " passed.\n";

/**
 * @return list<string>
 */
function validateSecurityTestPlan(string $content, string $path): array
{
    $errors = [];
    if (! preg_match('/^## Security Test Plan\s*$/m', $content)) {
        $errors[] = "{$path}: missing Security Test Plan section";
    }

    $requiredAreas = [
        'Authentication',
        'Authorization',
        'CSRF',
        'Session security',
        'Passwords',
        'Uploads',
        'Media/file access',
        'XSS',
        'SQL injection',
        'SSRF/RCE',
        'Dependency safety',
    ];

    foreach ($requiredAreas as $area) {
        if (! preg_match('/^\|\s*'.preg_quote($area, '/').'\s*\|/m', $content)) {
            $errors[] = "{$path}: missing security coverage area '{$area}'";
        }
    }

    $requiredCriteria = [
        'No critical/high security finding remains open.',
        'Admin authorization tests cover every migrated admin route.',
        'File and media tests prove traversal is blocked.',
        'CSRF tests cover all state-changing routes.',
    ];

    foreach ($requiredCriteria as $criterion) {
        if (! str_contains($content, "- {$criterion}")) {
            $errors[] = "{$path}: missing security acceptance criterion '{$criterion}'";
        }
    }

    return $errors;
}

/**
 * @return list<string>
 */
function validateAccessibilityTestPlan(string $content, string $path): array
{
    $errors = [];
    if (! preg_match('/^## Accessibility Test Plan\s*$/m', $content)) {
        $errors[] = "{$path}: missing Accessibility Test Plan section";
    }

    $requiredCoverage = [
        'Keyboard navigation for storefront and admin.',
        'Focus states and focus order.',
        'Form labels, errors, and descriptions.',
        'Color contrast.',
        'Screen-reader semantics for navigation, grids, tabs, dialogs, and Livewire updates.',
        'No keyboard traps.',
        'Admin grid and form usability.',
    ];

    foreach ($requiredCoverage as $coverage) {
        if (! str_contains($content, "- {$coverage}")) {
            $errors[] = "{$path}: missing accessibility coverage item '{$coverage}'";
        }
    }

    $requiredCriteria = [
        'Automated accessibility checks pass for critical pages.',
        'Manual keyboard review passes for checkout and core admin workflows.',
        'Accessibility defects are triaged before release.',
    ];

    foreach ($requiredCriteria as $criterion) {
        if (! str_contains($content, "- {$criterion}")) {
            $errors[] = "{$path}: missing accessibility acceptance criterion '{$criterion}'";
        }
    }

    return $errors;
}

/**
 * @return list<string>
 */
function validateFinalEvidence(): array
{
    $evidenceFiles = [
        'security review' => [
            'specs/modernization/security-review.md',
            'docs/content/modernization/security-review.md',
            'docusaurus/docs/developer/security-review.md',
        ],
        'accessibility report' => [
            'specs/modernization/accessibility-report.md',
            'docs/content/modernization/accessibility-report.md',
            'docusaurus/docs/developer/accessibility-report.md',
        ],
    ];

    $errors = [];
    foreach ($evidenceFiles as $label => $paths) {
        $path = firstExistingPath($paths);
        if ($path === null) {
            $errors[] = 'Final security/accessibility readiness requires '.$label.' evidence at one of: '.implode(', ', $paths);

            continue;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            $errors[] = "Unable to read {$label} evidence: {$path}";

            continue;
        }

        if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required)\b/', $content) === 1) {
            $errors[] = "{$path}: final {$label} evidence still contains placeholders";
        }
    }

    return $errors;
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
