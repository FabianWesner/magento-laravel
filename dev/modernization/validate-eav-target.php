#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateEavSpecs($errors);

if ($final) {
    validateFinalEavImplementation($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'EAV target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateEavSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/adr/0004-preserve-database-and-eav.md' => [
            'Use the existing database as the source of truth.',
            'Do not require destructive schema migration for modernization.',
            'Implement dedicated EAV repositories/query services.',
            'EAV parity tests.',
        ],
        'specs/adr/0006-no-direct-eloquent-eav-writes.md' => [
            'Do not write product, category, customer, or address EAV data directly through generic Eloquent models.',
            'Use dedicated repositories/services with parity tests.',
            'EAV write integration tests.',
            'Legacy parity tests.',
        ],
        'specs/modernization/architecture-specs.md' => [
            'EAV access layer',
            'Product, category, customer, and address EAV access goes through dedicated repositories and query services.',
            'Store-scope fallback must match Magento behavior.',
            'Writes require explicit transaction policy and parity tests.',
            'No destructive schema migration is allowed as part of modernization.',
        ],
        'specs/modernization/proof-of-concepts.md' => [
            'POC 2: Read-Only EAV Repository',
            'Goal: prove Laravel can read Magento EAV correctly without schema migration.',
            'Store-scope fallback matches legacy behavior.',
            'EAV parity PHPUnit tests.',
        ],
        'specs/modernization/test-plan.md' => [
            'Prove EAV reads and writes preserve Magento semantics.',
            'EAV access goes through EAV repositories/services.',
            'Database And EAV Test Plan',
            'Product EAV',
            'Category EAV',
            'Customer EAV',
            'EAV repository output matches legacy resource model output for fixture entities.',
            'Query counts are documented for critical EAV reads.',
        ],
        'specs/modernization/backlog.md' => [
            'Read-only EAV repository',
            'EAV parity tests and query-count report.',
            'Read/write, store scope, validation, backend/source model replacement, and no direct Eloquent writes for EAV are defined.',
        ],
        'docusaurus/docs/developer/index.md' => [
            'EAV repository usage and the no-direct-Eloquent-write rule for EAV data.',
        ],
        'docusaurus/docs/developer/architecture.md' => [
            'EAV repositories/services for product, category, customer, and address data.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing EAV planning phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalEavImplementation(array &$errors): void
{
    validateEavRepositoryFiles($errors);
    validateEavTests($errors);
    validateNoDirectEavWrites($errors);
    validateEavEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateEavRepositoryFiles(array &$errors): void
{
    $candidateFiles = repositoryCandidateFiles();
    if ($candidateFiles === []) {
        $errors[] = 'Final EAV implementation requires repository/service files under laravel/app that reference EAV behavior';

        return;
    }

    $requiredEntities = ['Product', 'Category', 'Customer', 'Address'];
    foreach ($requiredEntities as $entity) {
        $found = false;
        foreach ($candidateFiles as $file) {
            $basename = basename($file);
            $content = file_get_contents($file);
            if ($content !== false && (str_contains($basename, $entity) || str_contains($content, $entity))) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            $errors[] = "Final EAV implementation requires {$entity} EAV repository/service coverage";
        }
    }
}

/**
 * @return list<string>
 */
function repositoryCandidateFiles(): array
{
    $files = phpFilesUnder('laravel/app');
    $matches = [];

    foreach ($files as $file) {
        $content = file_get_contents($file);
        if ($content === false) {
            continue;
        }

        if (
            str_contains($file, '/Eav/')
            || str_contains($file, '/EAV/')
            || preg_match('/\bEav\b|\bEAV\b/', $content) === 1
            || preg_match('/Repository|QueryService|MetadataService/', $file.$content) === 1
        ) {
            $matches[] = $file;
        }
    }

    return array_values(array_unique($matches));
}

/**
 * @param  list<string>  $errors
 */
function validateEavTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $hasParityTest = false;
    $hasQueryCountTest = false;

    foreach ($testFiles as $testFile) {
        $content = file_get_contents($testFile);
        if ($content === false) {
            continue;
        }

        if (preg_match('/\bEav\b|\bEAV\b|store-scope|scope fallback|legacy resource model/i', $content) === 1) {
            $hasParityTest = true;
        }

        if (str_contains($content, 'expectsDatabaseQueryCount') || preg_match('/query count|query-count/i', $content) === 1) {
            $hasQueryCountTest = true;
        }
    }

    if (! $hasParityTest) {
        $errors[] = 'Final EAV implementation requires PHPUnit parity tests for EAV behavior';
    }

    if (! $hasQueryCountTest) {
        $errors[] = 'Final EAV implementation requires query-count tests or assertions for critical EAV reads';
    }
}

/**
 * @param  list<string>  $errors
 */
function validateNoDirectEavWrites(array &$errors): void
{
    $forbiddenTables = [
        'catalog_product_entity_datetime',
        'catalog_product_entity_decimal',
        'catalog_product_entity_int',
        'catalog_product_entity_text',
        'catalog_product_entity_varchar',
        'catalog_category_entity_datetime',
        'catalog_category_entity_decimal',
        'catalog_category_entity_int',
        'catalog_category_entity_text',
        'catalog_category_entity_varchar',
        'customer_entity_datetime',
        'customer_entity_decimal',
        'customer_entity_int',
        'customer_entity_text',
        'customer_entity_varchar',
        'customer_address_entity_datetime',
        'customer_address_entity_decimal',
        'customer_address_entity_int',
        'customer_address_entity_text',
        'customer_address_entity_varchar',
    ];

    $writePattern = '/->\s*(?:insert|update|upsert|delete|truncate|save)\s*\(|::\s*(?:insert|update|upsert|delete|truncate|create)\s*\(/i';

    foreach (phpFilesUnder('laravel/app') as $file) {
        $content = file_get_contents($file);
        if ($content === false) {
            continue;
        }

        foreach ($forbiddenTables as $table) {
            if (! str_contains($content, $table)) {
                continue;
            }

            if (preg_match($writePattern, $content) === 1) {
                $errors[] = "{$file}: final EAV implementation must not directly write Magento EAV value table {$table}";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateEavEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/eav-parity-evidence.md',
        'docs/content/modernization/eav-parity-evidence.md',
        'docusaurus/docs/developer/eav-access.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final EAV implementation requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (['Product', 'Category', 'Customer', 'Address', 'Store Scope', 'Fallback', 'Fixture', 'Parity Test', 'Query Count', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing EAV evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required)\b/', $content) === 1) {
        $errors[] = "{$path}: final EAV evidence still contains placeholders";
    }
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
