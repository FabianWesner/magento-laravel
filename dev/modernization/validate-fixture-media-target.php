#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateFixtureMediaSpecs($errors);
validateFixtureRestoreTooling($errors);
validateFixtureCoverageReporter($errors);

if ($final) {
    validateFinalFixtureMediaReadiness($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Fixture/media target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateFixtureMediaSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Run Magento and Laravel side-by-side against restorable fixture data so every feature can be compared before route cutover.',
            'Demo data must be predefined and reproducible. It must cover all Magento product types, discounts, coupons, tax/shipping/payment paths, customer groups, admin roles, API users, reports, cron jobs, multistore scope, media, and edge cases.',
            'Build deterministic demo fixtures from specs/modernization/data-fixtures.md.',
            'Restore fixtures locally and in CI without destructive schema changes.',
            'The canonical demo fixture covers all product types, discounts, coupons, taxes, shipping, payment, admin roles, API users, reports, cron jobs, multistore scope, media, and edge cases.',
        ],
        'specs/modernization/data-fixtures.md' => [
            'Required Fixture Sets',
            'Canonical Demo Data Matrix',
            'Media | Product images, category images, CMS media, downloadable files, missing media reference, image cache regeneration case.',
            'Fixture Traceability',
            'Media fixtures must match database references.',
            'Fixture restore must be documented and automated where possible.',
            'A developer can restore the fixture database and media locally.',
            'CI can restore the required fixtures.',
            'Visual tests use stable media fixtures.',
            'Every fixture maps back to one or more feature IDs.',
        ],
        'specs/modernization/test-plan.md' => [
            'CI | Deterministic automated validation. | Versioned fixture database and media fixtures. | Required for merge.',
            'Fixture manifest maps data to the feature ID, can be restored locally and in CI',
            'The report is a coverage signal, not final proof by itself.',
            'Sensitive production data must be sanitized before use outside production.',
            'Rollback and recovery procedures have been tested.',
        ],
        'specs/modernization/workspace-layout.md' => [
            'Project database fixture | EAV attributes, config, store scopes, real data shape | Missing.',
            'Project media fixture | Product/category/CMS visuals and file-storage behavior | Missing.',
            'Restore the sanitized project DB and media into ignored local paths.',
            'Do not mutate the shared fixture in-place during comparison without restoring it before the second runtime executes the same scenario.',
        ],
        'specs/modernization/visual-baseline.md' => [
            'Every product type, promotion state, cart state, checkout state, account state, CMS state, and error state covered by the canonical demo fixture.',
            'Every admin role and permission state required by the canonical demo fixture.',
            'same database and media fixture',
        ],
        'specs/modernization/magento-feature-catalog.md' => [
            'A feature is not done until Magento and Laravel can be compared side-by-side from the same fixture database and media set.',
            'Every `preserve`, `bridge`, and `replace` item has fixture coverage.',
        ],
        'specs/modernization/backlog.md' => [
            'Capture database and media fixtures',
            'Awaiting sanitized fixture manifest.',
            'Sanitized DB/media fixtures are reproducible and mapped to feature IDs.',
            'Restore fixture locally and compare schema checksum.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing fixture/media planning phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFixtureRestoreTooling(array &$errors): void
{
    $path = 'dev/modernization/fixture-restore-check.sh';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    if (! is_executable($path)) {
        $errors[] = "{$path}: fixture restore check must be executable";
    }

    $requiredSnippets = [
        '--fixture=',
        '--media=',
        'non-destructive fixture check',
        'It does not import or delete data.',
        'Fixture exists',
        'Media directory exists',
        'mysql',
        'rsync',
    ];

    foreach ($requiredSnippets as $snippet) {
        if (! str_contains($content, $snippet)) {
            $errors[] = "{$path}: missing fixture restore tooling snippet '{$snippet}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFixtureCoverageReporter(array &$errors): void
{
    $path = 'dev/modernization/fixture-coverage-report.php';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    if (! is_executable($path)) {
        $errors[] = "{$path}: fixture coverage reporter must be executable";
    }

    $requiredSnippets = [
        'DB_DSN',
        '--fail-on-gaps',
        'unknownCheckFeatureIds',
        'catalogFeatureIds',
        'Product types',
        'Websites and stores',
        'Pricing and promotions',
        'CMS and media',
        'Cron and reports',
        'Feature IDs',
        'Status',
    ];

    foreach ($requiredSnippets as $snippet) {
        if (! str_contains($content, $snippet)) {
            $errors[] = "{$path}: missing fixture coverage capability '{$snippet}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalFixtureMediaReadiness(array &$errors): void
{
    validateFinalFixtureManifest($errors);
    validateFinalFixtureRestoreEvidence($errors);
    validateFinalFixtureTests($errors);
    validateFinalFixtureCiCoverage($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateFinalFixtureManifest(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/fixture-manifest.md',
        'specs/modernization/fixture-media-manifest.md',
        'dev/modernization/fixtures/manifest.json',
        'dev/modernization/fixtures/fixture-manifest.json',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final fixture/media readiness requires a fixture manifest at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    $requiredPhrases = [
        'Fixture ID',
        'Feature IDs',
        'Database Fixture',
        'Media Fixture',
        'Setup Source',
        'Restore Command',
        'Local Restore',
        'CI Restore',
        'Schema Signature',
        'Sanitization',
        'Sensitive Data',
        'Status',
    ];

    foreach ($requiredPhrases as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing fixture manifest phrase '{$phrase}'";
        }
    }

    foreach (requiredFixtureSets() as $fixtureSet) {
        if (! str_contains($content, $fixtureSet)) {
            $errors[] = "{$path}: missing fixture set '{$fixtureSet}'";
        }
    }

    foreach (requiredMediaAreas() as $mediaArea) {
        if (! str_contains($content, $mediaArea)) {
            $errors[] = "{$path}: missing media fixture coverage '{$mediaArea}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Missing|Gap|To inventory|Required where applicable)\b/', $content) === 1) {
        $errors[] = "{$path}: final fixture manifest still contains placeholders";
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalFixtureRestoreEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/fixture-restore-evidence.md',
        'docs/content/modernization/fixture-restore-evidence.md',
        'docusaurus/docs/developer/fixture-restore.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final fixture/media readiness requires restore evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    $requiredPhrases = [
        'Database Fixture',
        'Media Fixture',
        'Local Restore',
        'CI Restore',
        'Restore Command',
        'Schema Signature Before',
        'Schema Signature After',
        'Fixture Coverage Report',
        'No Gaps',
        'Sanitization Proof',
        'Media Reference Check',
        'Feature ID Mapping',
        'Rollback Rehearsal',
        'Status',
    ];

    foreach ($requiredPhrases as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing fixture restore evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Missing|Gap|To inventory|Blocked|Required where applicable)\b/', $content) === 1) {
        $errors[] = "{$path}: final fixture restore evidence still contains placeholders or blockers";
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalFixtureTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $aggregateContent = aggregateFiles($testFiles);

    if ($aggregateContent === '') {
        $errors[] = 'Final fixture/media readiness requires Laravel PHPUnit tests';

        return;
    }

    $coverage = [
        'fixture manifest feature ID mapping' => preg_match('/Fixture ID|feature ID|fixture manifest|Feature IDs/i', $aggregateContent) === 1,
        'local and CI fixture restore paths' => preg_match('/fixture restore|restore fixture|Local Restore|CI Restore|fixture database/i', $aggregateContent) === 1,
        'strict fixture coverage report with no gaps' => preg_match('/fixture-coverage-report|fail-on-gaps|No Gaps|fixture coverage/i', $aggregateContent) === 1,
        'database schema signature before and after restore' => preg_match('/schema signature|schema checksum|before.*after.*restore|schema-report/i', $aggregateContent) === 1,
        'media references match database records' => preg_match('/media reference|product images|category images|CMS media|downloadable files|missing media/i', $aggregateContent) === 1,
        'sanitized project data proof' => preg_match('/sanitized project|Sensitive Data|sanitization|production-derived/i', $aggregateContent) === 1,
        'rollback or restore rehearsal' => preg_match('/rollback|restore rehearsal|backup restore|recovery/i', $aggregateContent) === 1,
    ];

    foreach ($coverage as $label => $covered) {
        if (! $covered) {
            $errors[] = "Final fixture/media readiness requires PHPUnit coverage for {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalFixtureCiCoverage(array &$errors): void
{
    $workflowFiles = array_merge(
        filesUnder('.github/workflows', ['yml', 'yaml']),
        filesUnder('.gitea/workflows', ['yml', 'yaml']),
    );
    $aggregateContent = aggregateFiles($workflowFiles);

    if ($aggregateContent === '') {
        $errors[] = 'Final fixture/media readiness requires CI workflow coverage for fixture restore';

        return;
    }

    $requiredPatterns = [
        'fixture restore command' => '/fixture-restore|restore.*fixture|fixture.*restore/i',
        'fixture coverage strict mode' => '/fixture-coverage-report|--fail-on-gaps/i',
        'schema report or checksum' => '/schema-report|schema checksum|schema signature/i',
        'media fixture restore' => '/media fixture|restore.*media|rsync.*media/i',
        'artifact retention' => '/upload-artifact|artifact|retention/i',
    ];

    foreach ($requiredPatterns as $label => $pattern) {
        if (preg_match($pattern, $aggregateContent) !== 1) {
            $errors[] = "Final fixture/media readiness requires CI coverage for {$label}";
        }
    }
}

/**
 * @return list<string>
 */
function requiredFixtureSets(): array
{
    return [
        'Minimal install',
        'Original seed/sample data',
        'Sanitized project data',
        'EAV edge data',
        'Multistore data',
        'Sales lifecycle data',
        'Admin permissions data',
        'Integration data',
        'Scale data',
    ];
}

/**
 * @return list<string>
 */
function requiredMediaAreas(): array
{
    return [
        'Product images',
        'Category images',
        'CMS media',
        'Downloadable files',
        'Missing media',
        'Image cache',
    ];
}

/**
 * @return list<string>
 */
function phpFilesUnder(string $directory): array
{
    return filesUnder($directory, ['php']);
}

/**
 * @param  list<string>  $extensions
 * @return list<string>
 */
function filesUnder(string $directory, array $extensions): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    $extensionMap = array_fill_keys($extensions, true);

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        $extension = strtolower($file->getExtension());
        if (isset($extensionMap[$extension])) {
            $files[] = normalizePath($path);
        }
    }

    sort($files);

    return $files;
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

function normalizePath(string $path): string
{
    return str_replace(DIRECTORY_SEPARATOR, '/', $path);
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
