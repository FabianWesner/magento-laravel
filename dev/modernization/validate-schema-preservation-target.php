#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateSchemaPreservationSpecs($errors);

if ($final) {
    validateFinalSchemaPreservation($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Schema preservation target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateSchemaPreservationSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'preserve the existing database schema including EAV and original seed/sample/project data',
            'Run Magento and Laravel side-by-side against restorable fixture data so every feature can be compared before route cutover.',
            'Keep the existing commerce database schema, including EAV tables and original seed/sample/project data. No destructive schema migration is allowed.',
            'Restore fixtures locally and in CI without destructive schema changes.',
            'For every feature ID, capture Magento behavior through tests, screenshots, DB deltas, API payloads, events, emails, logs, generated files, and side effects.',
            'Schema checks prove the commerce schema and EAV data were not destructively changed.',
        ],
        'specs/modernization/compatibility-policy.md' => [
            'Existing database schema and data remain compatible.',
            'Database schema | Preserve | Schema checksum, fixture import, no destructive migration.',
            'EAV model | Preserve | EAV parity tests for products, categories, customers, and addresses.',
            'Existing setup scripts | Do not run destructive schema changes in Laravel modernization | Fixture DB and schema policy review.',
            'Every preserved contract has automated verification in the test plan.',
        ],
        'specs/modernization/test-plan.md' => [
            'Prove the existing database schema and original seed/sample data work without destructive migration.',
            'Existing database schema and EAV data are used without destructive migration.',
            'Existing commerce schema must not be destructively altered.',
            'Test setup may create data using existing schema only.',
            'Any new infrastructure table requires explicit approval and must not be required for preserving the existing commerce database.',
            'Schema preservation | Booting Laravel does not drop, rename, or rewrite existing tables/columns/indexes.',
            'Schema checksum before and after boot.',
            'Database schema preservation checks green.',
        ],
        'specs/modernization/roadmap.md' => [
            'Keep the existing database schema, including EAV tables and existing seed/original data.',
            'Existing commerce tables, including EAV, remain the source of truth.',
            'Database compatibility | Existing database loads without destructive schema migration. Existing EAV data resolves correctly. Original seed/sample data remains usable.',
            'Capture a sanitized database dump containing schema and original seed/sample data.',
            'Goal: make the existing database usable from Laravel without schema migration.',
            'Define a database ownership policy: existing commerce tables are preserved.',
            'Add fixtures and factories that use the existing schema.',
        ],
        'specs/modernization/data-fixtures.md' => [
            'The database schema stays, including EAV.',
            'Original seed/sample data | Proves compatibility with existing seed/sample data.',
            'Sanitized project data | Proves real-world behavior without sensitive data.',
            'Do not destructively migrate the commerce schema.',
            'A developer can restore the fixture database and media locally.',
            'CI can restore the required fixtures.',
            'EAV parity tests use fixture data.',
        ],
        'specs/modernization/technology-removal-policy.md' => [
            'Magento database schema, including EAV tables, table names, primary keys, indexes, and existing seed/sample/project data.',
            'Laravel migrations only for approved new infrastructure tables.',
            'schema checksum',
        ],
        'specs/modernization/workspace-layout.md' => [
            'Project database fixture | EAV attributes, config, store scopes, real data shape | Missing.',
            'Do not mutate the shared fixture in-place during comparison without restoring it before the second runtime executes the same scenario.',
            'DB_DSN=',
            'schema-report.php --format=markdown',
        ],
        'docs/content/modernization/compatibility.md' => [
            'Database schema | Preserve existing tables, columns, indexes, and EAV data.',
            'Each compatibility promise needs automated verification before a migrated route or workflow can be considered complete.',
        ],
        'docs/content/modernization/index.md' => [
            'preserving the existing database schema, EAV model',
            'Existing EAV and commerce tables remain the source of truth.',
        ],
        'docs/content/modernization/testing.md' => [
            'Database and EAV parity tests pass.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing schema preservation planning phrase '{$phrase}'";
            }
        }
    }

    validateSchemaReportTool($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateSchemaReportTool(array &$errors): void
{
    $path = 'dev/modernization/schema-report.php';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (['DB_DSN', 'SHOW TABLES', 'SHOW COLUMNS', 'schema_signature', '--format=json|markdown'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing schema report capability '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalSchemaPreservation(array &$errors): void
{
    validateMigrationSafety($errors);
    validateSchemaPreservationTests($errors);
    validateSchemaPreservationEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateMigrationSafety(array &$errors): void
{
    $migrationFiles = phpFilesUnder('laravel/database/migrations');

    foreach ($migrationFiles as $migrationFile) {
        $content = readTextFile($migrationFile, $errors);
        if ($content === null) {
            continue;
        }

        foreach (commerceSchemaTablePatterns() as $pattern) {
            if (preg_match($pattern, $content) !== 1) {
                continue;
            }

            if (preg_match('/Schema::(?:drop|dropIfExists|rename|table)\s*\(|->(?:drop|dropColumn|renameColumn|change)\s*\(/', $content) === 1) {
                $errors[] = "{$migrationFile}: final schema preservation forbids destructive migration operations on existing Magento commerce/EAV tables";
            }
        }
    }

    $appFiles = array_merge(phpFilesUnder('laravel/app'), phpFilesUnder('laravel/config'), phpFilesUnder('laravel/database'));
    $requiredArtifacts = [
        'database ownership policy artifact' => '/DatabaseOwnership|SchemaPreservation|SchemaPolicy|preserve.*schema|commerce.*tables/i',
        'approved infrastructure table handling' => '/infrastructure table|approved.*table|Schema::create|migrations/i',
        'fixture restore or checksum command integration' => '/schema-report|fixture-restore|SchemaChecksum|schema signature|checksum/i',
        'core entity count or data snapshot support' => '/catalog_product_entity|catalog_category_entity|customer_entity|sales_flat_order|core_config_data|row count|snapshot/i',
        'EAV table signature support' => '/catalog_product_entity_(?:int|varchar|decimal|text|datetime)|catalog_category_entity_(?:int|varchar|decimal|text|datetime)|customer_entity_(?:int|varchar|decimal|text|datetime)|EAV.*signature/i',
    ];

    foreach ($requiredArtifacts as $label => $pattern) {
        if (! pathOrContentMatches($appFiles, $pattern)) {
            $errors[] = "Final schema preservation requires {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateSchemaPreservationTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $aggregateContent = aggregateFiles($testFiles);

    if ($aggregateContent === '') {
        $errors[] = 'Final schema preservation requires Laravel PHPUnit tests';

        return;
    }

    $coverage = [
        'schema checksum before and after Laravel boot' => preg_match('/schema checksum|schema signature|before.*after.*boot|schema-report/i', $aggregateContent) === 1,
        'no destructive migration against commerce or EAV tables' => preg_match('/destructive migration|dropColumn|dropIfExists|renameColumn|commerce schema|EAV table/i', $aggregateContent) === 1,
        'fixture restore locally and CI' => preg_match('/fixture restore|restore fixture|CI.*fixture|locally.*CI|fixture database/i', $aggregateContent) === 1,
        'original seed sample and project data compatibility' => preg_match('/original seed|sample data|project data|sanitized project|seed\/sample/i', $aggregateContent) === 1,
        'core entity row count snapshots' => preg_match('/catalog_product_entity|catalog_category_entity|customer_entity|sales_flat_order|core_config_data|row count|snapshot/i', $aggregateContent) === 1,
        'EAV data signatures or snapshots' => preg_match('/catalog_product_entity_(?:int|varchar|decimal|text|datetime)|catalog_category_entity_(?:int|varchar|decimal|text|datetime)|customer_entity_(?:int|varchar|decimal|text|datetime)|EAV.*snapshot|EAV.*signature/i', $aggregateContent) === 1,
        'DB deltas for migrated feature behavior' => preg_match('/DB delta|database delta|before.*after|side effects|characterization/i', $aggregateContent) === 1,
        'new infrastructure tables approved and isolated' => preg_match('/infrastructure table|approved.*migration|approved.*table|isolated.*table/i', $aggregateContent) === 1,
    ];

    foreach ($coverage as $label => $covered) {
        if (! $covered) {
            $errors[] = "Final schema preservation requires PHPUnit coverage for {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateSchemaPreservationEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/schema-preservation-evidence.md',
        'docs/content/modernization/schema-preservation-evidence.md',
        'docusaurus/docs/developer/schema-preservation.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final schema preservation requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (['Baseline Schema Signature', 'Post-Boot Schema Signature', 'Table Count', 'EAV Tables', 'Core Entity Counts', 'Fixture Restore', 'Local Restore', 'CI Restore', 'Migration Review', 'Infrastructure Tables', 'DB Delta', 'Project Data', 'Media Fixture', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing schema preservation evidence phrase '{$phrase}'";
        }
    }

    foreach (['catalog_product_entity', 'catalog_category_entity', 'customer_entity', 'customer_address_entity', 'sales_flat_order', 'core_config_data', 'eav_attribute'] as $table) {
        if (! str_contains($content, $table)) {
            $errors[] = "{$path}: missing schema preservation evidence for {$table}";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required|Missing|Gap|To inventory)\b/', $content) === 1) {
        $errors[] = "{$path}: final schema preservation evidence still contains placeholders";
    }
}

/**
 * @return list<string>
 */
function commerceSchemaTablePatterns(): array
{
    return [
        '/catalog_(?:product|category)_entity/i',
        '/customer_(?:entity|address_entity)/i',
        '/sales_flat_/i',
        '/quote/i',
        '/eav_/i',
        '/core_(?:config_data|store|website|store_group)/i',
        '/cataloginventory_/i',
        '/catalogrule|salesrule/i',
        '/tax_/i',
        '/shipping|payment/i',
        '/api|oauth/i',
    ];
}

/**
 * @return list<string>
 */
function phpFilesUnder(string $directory): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        if (str_ends_with($path, '.php')) {
            $files[] = $path;
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

/**
 * @param  list<string>  $files
 */
function pathOrContentMatches(array $files, string $pattern): bool
{
    foreach ($files as $file) {
        $content = file_get_contents($file);
        if ($content !== false && preg_match($pattern, normalizePath($file)."\n".$content) === 1) {
            return true;
        }
    }

    return false;
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
