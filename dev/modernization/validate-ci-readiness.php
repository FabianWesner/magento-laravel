#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateCiSpecs($errors);

if ($final) {
    validateFinalCiReadiness($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'CI readiness ';
echo $final ? 'final check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateCiSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Laravel target must run on the latest stable PHP available in this environment and CI; legacy Magento PHP 7.4 may remain isolated only for baseline smoke verification.',
            'Verify Magento CE 1.9.4.5 source, project overlay, generated docroot, Docker/local runtime, sample/project DB, media, PHP versions, Composer/npm dependencies, remotes, and CI.',
            'Restore fixtures locally and in CI without destructive schema changes.',
            'docusaurus/ builds with Docusaurus, separates user and developer docs, and is verified in Chrome/Playwright.',
        ],
        'specs/modernization/test-plan.md' => [
            'All required automated test suites pass in CI.',
            'Laravel target CI runs on the latest stable PHP, currently PHP `8.5.x` as of 2026-05-18, while legacy Magento PHP `7.4` remains isolated to baseline smoke verification.',
            'Fixture manifest maps data to the feature ID, can be restored locally and in CI',
            'CI | Deterministic automated validation. | Versioned fixture database and media fixtures. | Required for merge.',
            'Static analysis | New Laravel code passes agreed PHPStan/Larastan level with no baseline growth.',
            'Docusaurus docs tests | Prove user and developer docs are buildable and browser-rendered.',
            'Runtime tests | Prove the target Laravel runtime uses the latest stable PHP and compatible Composer packages.',
            'CI run links or logs.',
        ],
        'specs/modernization/roadmap.md' => [
            'Latest stable PHP is available in local tooling and CI for the Laravel target.',
            'Test suite runs locally and in CI.',
            'Add CI jobs for Laravel test, Pint or style tooling, Larastan/PHPStan config, and architecture tests.',
            'CI runs both legacy and Laravel checks.',
        ],
        'specs/modernization/architecture-specs.md' => [
            'PHP version policy, Composer platform config, CI matrix, extension list, upgrade cadence.',
            '`php -v`, Composer platform checks, CI build, Laravel Boost install.',
        ],
        'specs/modernization/backlog.md' => [
            'Test plan gates are implemented in CI.',
            'CI wiring is required before final release.',
        ],
        'specs/modernization/data-fixtures.md' => [
            'CI can restore the required fixtures.',
        ],
        'specs/modernization/documentation-plan.md' => [
            '`mkdocs build` succeeds.',
            '`node dev/modernization/smoke-docusaurus.mjs` opens the Docusaurus site in Chrome/Playwright and the user and developer docs render.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing CI readiness planning phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalCiReadiness(array &$errors): void
{
    $workflowFiles = ciWorkflowFiles();
    if ($workflowFiles === []) {
        $errors[] = 'Final CI readiness requires workflow files under .github/workflows, .gitlab-ci.yml, or .circleci/config.yml';

        return;
    }

    $content = aggregateFiles($workflowFiles);
    $requirements = [
        'PHP 8.5 matrix or setup' => '/php-version:\s*[\'"]?8\.5|PHP_VERSION.*8\.5|8\.5\.x|php:\s*[\'"]?8\.5/i',
        'legacy PHP 7.4 isolation' => '/7\.4|legacy.*Magento|Magento.*legacy|baseline.*smoke/i',
        'Composer validation or install' => '/composer (?:install|validate)|ramsey\/composer-install|shivammathur\/setup-php/i',
        'Laravel PHPUnit test command' => '/php artisan test(?:\s+--compact)?|composer (?:run )?test/i',
        'Pint or style command' => '/pint|php-cs-fixer|ecs|coding style/i',
        'PHPStan or Larastan static analysis' => '/phpstan|larastan|static analysis/i',
        'architecture gates' => '/validate-no-new-xml|validate-removed-technologies|architecture/i',
        'modernization gate script' => '/dev\/modernization\/gate\.sh|MODERNIZATION_FINAL=1/i',
        'fixture coverage command' => '/fixture-coverage-report\.php|FIXTURE_COVERAGE_STRICT|DB_DSN/i',
        'schema report command' => '/schema-report\.php|schema preservation|schema checksum/i',
        'MkDocs strict build' => '/mkdocs .*build|mkdocs build --strict/i',
        'Docusaurus build' => '/npm --prefix docusaurus run build|npm.*run build.*docusaurus|docusaurus build/i',
        'Docusaurus browser smoke' => '/smoke-docusaurus\.mjs|Chrome|Playwright/i',
        'artifact upload or retained reports' => '/upload-artifact|artifacts:|store_artifacts|CI run links|reports/i',
    ];

    foreach ($requirements as $label => $pattern) {
        if (preg_match($pattern, $content) !== 1) {
            $errors[] = "Final CI readiness requires {$label}";
        }
    }

    validateCiEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateCiEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/ci-evidence.md',
        'docs/content/modernization/ci-evidence.md',
        'docusaurus/docs/developer/ci-evidence.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final CI readiness requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (['Workflow', 'Run URL', 'Commit', 'PHP 8.5', 'Legacy PHP 7.4', 'Laravel Tests', 'Pint', 'Static Analysis', 'Architecture Gates', 'Fixture Restore', 'Schema Report', 'MkDocs', 'Docusaurus Build', 'Browser Smoke', 'Artifacts', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing CI evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Missing|Gap|To inventory)\b/', $content) === 1) {
        $errors[] = "{$path}: final CI evidence still contains placeholders";
    }
}

/**
 * @return list<string>
 */
function ciWorkflowFiles(): array
{
    $files = [];

    foreach (['.github/workflows', '.circleci'] as $directory) {
        if (! is_dir($directory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            if (preg_match('/\.(?:ya?ml)$/', $path) === 1) {
                $files[] = $path;
            }
        }
    }

    foreach (['.gitlab-ci.yml', '.gitlab-ci.yaml'] as $path) {
        if (is_file($path)) {
            $files[] = $path;
        }
    }

    sort($files);

    return array_values(array_unique($files));
}

/**
 * @param  list<string>  $files
 */
function aggregateFiles(array $files): string
{
    $content = '';

    foreach ($files as $file) {
        $fileContent = file_get_contents($file);
        if ($fileContent !== false) {
            $content .= "\n".$file."\n".$fileContent;
        }
    }

    return $content;
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
