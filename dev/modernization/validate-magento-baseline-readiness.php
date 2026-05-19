#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateBaselinePlanning($errors);
validateBaselineRuntimeFiles($errors);

if ($final) {
    validateFinalBaselineEvidence($errors);
    validateFinalProjectBaselineInputs($errors);
    validateFinalSmokeArtifacts($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Magento baseline readiness ';
echo $final ? 'final evidence check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateBaselinePlanning(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Use Magento CE 1.9.4.5 as the legacy baseline.',
            'Keep Magento CE 1.9.4.5 source under core/magento-1.9.4.5/.',
            'Generate the legacy runtime docroot under .localdev/magento-docroot/ from core plus project.',
            'Run Magento and Laravel side-by-side against restorable fixture data so every feature can be compared before route cutover.',
            'Magento CE 1.9.4.5 baseline can be installed or restored locally, seeded with sample/demo data, and smoke-tested in Chrome.',
        ],
        'specs/modernization/workspace-layout.md' => [
            'Runtime composition copies `core/magento-1.9.4.5/` into `.localdev/magento-docroot/` and then overlays `project/` on top.',
            'Magento baseline',
            'Shared restored Magento fixture DB and media',
            'Legacy behavior, screenshots, DB side effects, API responses, cron outputs.',
        ],
        'specs/modernization/install-verification.md' => [
            'Decision on 2026-05-18: use Magento Community Edition / Magento Open Source `1.9.4.5` as the legacy runtime baseline.',
            'local Magento CE `1.9.4.5` smoke install succeeded with sample data.',
            'Browser verification was performed through the available Playwright browser automation runtime in Chrome.',
            'Magento container uses PHP `7.4.33`; Laravel root artisan proxy uses Herd PHP `8.5.5`.',
            'Project-specific install remains explicitly blocked until project code, DB, and media are available.',
        ],
        'specs/modernization/inventory.md' => [
            'Magento CE / Magento Open Source source tag `1.9.4.5`.',
            'Runtime version check',
            'Seed data',
            'Smoke status',
            'Storefront home/category/product and admin dashboard verified in Chrome through Playwright.',
        ],
        'specs/modernization/data-fixtures.md' => [
            'Original seed/sample data',
            'Sanitized project data',
            'A developer can restore the fixture database and media locally.',
            'The demo fixture covers all product types, promotion types, tax/shipping/payment paths, admin roles, API users, cron jobs, and report aggregates listed above.',
        ],
        'specs/modernization/test-plan.md' => [
            'Legacy characterization',
            'Browser E2E tests',
            'Visual regression',
            'Chrome/Playwright',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing Magento baseline planning phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateBaselineRuntimeFiles(array &$errors): void
{
    $requiredFiles = [
        'core/magento-1.9.4.5/app/Mage.php' => 'Magento core bootstrap',
        'core/magento-1.9.4.5/app/etc/config.xml' => 'Magento core config',
        'core/magento-1.9.4.5/index.php' => 'Magento storefront entrypoint',
        'core/magento-1.9.4.5/api.php' => 'Magento API entrypoint',
        'core/magento-1.9.4.5/cron.php' => 'Magento cron entrypoint',
        'dev/magento/build-docroot.sh' => 'Magento docroot build script',
        'dev/magento/docker-compose.yml' => 'Magento Docker Compose runtime',
        'dev/magento/php/Dockerfile' => 'Magento legacy PHP Dockerfile',
        '.localdev/magento-docroot/app/Mage.php' => 'generated Magento runtime bootstrap',
        '.localdev/magento-docroot/index.php' => 'generated Magento storefront entrypoint',
    ];

    foreach ($requiredFiles as $path => $label) {
        if (! is_file($path)) {
            $errors[] = "{$path}: missing {$label}";
        }
    }

    $dockerfile = readTextFile('dev/magento/php/Dockerfile', $errors);
    if ($dockerfile !== null && ! str_contains($dockerfile, 'FROM php:7.4-fpm')) {
        $errors[] = 'dev/magento/php/Dockerfile: legacy Magento runtime must remain isolated on PHP 7.4';
    }

    $compose = readTextFile('dev/magento/docker-compose.yml', $errors);
    if ($compose !== null) {
        foreach (['mysql:5.7', 'nginx:1.25', '${MAGENTO_PORT:-8090}:80', 'magento1945'] as $snippet) {
            if (! str_contains($compose, $snippet)) {
                $errors[] = "dev/magento/docker-compose.yml: missing Magento baseline runtime snippet '{$snippet}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalBaselineEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/magento-baseline-evidence.md',
        'specs/modernization/install-verification.md',
        'docs/content/modernization/magento-baseline-evidence.md',
        'docusaurus/docs/developer/magento-baseline.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final Magento baseline readiness requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    $requiredPhrases = [
        'Magento CE',
        '1.9.4.5',
        'Source root',
        'Runtime document root',
        'Generated docroot',
        'project repository or overlay',
        'Project database',
        'Project media',
        'Sample data',
        'Seed verification',
        'Products',
        'Categories',
        'CMS pages',
        'Docker services',
        'MySQL health',
        'Runtime PHP',
        'Storefront home',
        'Storefront category',
        'Storefront product',
        'Admin login',
        'Admin dashboard',
        'Chrome',
        'Playwright',
        'Sample schema report',
        'screenshots',
        'zero console warnings or errors',
        'Status',
    ];

    foreach ($requiredPhrases as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing Magento baseline evidence phrase '{$phrase}'";
        }
    }

    $blockedPatterns = [
        '/Project Install Blocker/i',
        '/Project-specific install remains explicitly blocked/i',
        '/Real project overlay/i',
        '/Project database fixture.+Missing/i',
        '/Project media fixture.+Missing/i',
        '/placeholder-only/i',
        '/Not installed:/i',
        '/\b(?:TBD|Pending|Missing|Unknown|Blocked|Gap|Required where applicable)\b/',
    ];

    foreach ($blockedPatterns as $pattern) {
        if (preg_match($pattern, $content) === 1) {
            $errors[] = "{$path}: final Magento baseline evidence still contains source-only blockers, placeholders, or missing project inputs";
            break;
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalProjectBaselineInputs(array &$errors): void
{
    $projectFiles = collectProjectOverlayFiles('project');
    if ($projectFiles === []) {
        $errors[] = 'Final Magento baseline readiness requires non-placeholder project overlay files under project/';
    }

    $requiredRuntimeFiles = [
        '.localdev/magento-docroot/app/etc/local.xml',
        '.localdev/sample-data/magento-sample-data-1.9.2.4/magento_sample_data_for_1.9.2.4.sql',
    ];

    foreach ($requiredRuntimeFiles as $path) {
        if (! is_file($path)) {
            $errors[] = "{$path}: final Magento baseline readiness requires restored local runtime/sample data artifact";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalSmokeArtifacts(array &$errors): void
{
    $requiredScreenshotPatterns = [
        'storefront smoke screenshot' => '.localdev/magento-storefront*.png',
        'admin smoke screenshot' => '.localdev/magento-admin*.png',
    ];

    foreach ($requiredScreenshotPatterns as $label => $pattern) {
        if (glob($pattern) === []) {
            $errors[] = "Final Magento baseline readiness requires {$label} matching {$pattern}";
        }
    }
}

/**
 * @return list<string>
 */
function collectProjectOverlayFiles(string $projectRoot): array
{
    if (! is_dir($projectRoot)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectRoot, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }

        $relativePath = substr($file->getPathname(), strlen($projectRoot) + 1);
        if (shouldIgnoreProjectOverlayFile($relativePath)) {
            continue;
        }

        $files[] = $relativePath;
    }

    sort($files);

    return $files;
}

function shouldIgnoreProjectOverlayFile(string $relativePath): bool
{
    if (in_array($relativePath, ['README.md', '.DS_Store'], true)) {
        return true;
    }

    foreach (['var/cache/', 'var/session/', 'var/report/'] as $ignoredPrefix) {
        if (str_starts_with($relativePath, $ignoredPrefix)) {
            return true;
        }
    }

    return false;
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
