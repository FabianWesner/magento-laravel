#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateConfigSpecs($errors);

if ($final) {
    validateFinalConfigImplementation($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Config target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateConfigSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'typed config',
            'No new XML is allowed for migrated Laravel modules, routing, events, layout, ACL, config, or extension registration.',
            'Identify all modules, routes, controllers, templates, admin screens, APIs, cron jobs, shell commands, observers, setup scripts, config paths, and integrations.',
            'Build Laravel bootstrap, config, module registry, no-XML manifest system, route strangler/fallback, EAV access layer, auth/session/security foundation, scheduler, queue, events, API contracts, and operations hooks.',
        ],
        'specs/modernization/architecture-specs.md' => [
            'Configuration',
            'How config moves from XML/DB/env to typed Laravel config.',
            'Scope resolution, env override, cache, secrets, admin edits.',
            'Config parity tests.',
            'Bind Magento-compatible infrastructure behind Laravel contracts: config, DB, cache, session, events, logger, filesystem, URL, auth, translation.',
            'Store-scope config/form behavior must be preserved.',
        ],
        'specs/modernization/roadmap.md' => [
            'XML config tree | Laravel config repository and typed config objects',
            'Add configuration tests for XML + DB + environment override behavior.',
            'Bind core infrastructure contracts: config, database, cache, session, logging, events, filesystem, URL generation, auth, and translation.',
            'Add typed configuration objects backed by Laravel config.',
            'Implement store-scope resolution matching Magento behavior.',
            'Implement config resolution matching default/website/store fallback.',
            'Rebuild system configuration.',
            'No new admin XML config is used.',
            'Config and store scope services.',
            'Services do not depend on `Mage::getModel()` or XML config.',
        ],
        'specs/modernization/test-plan.md' => [
            'Config scope | Default, website, store, env override, and cache behavior match legacy resolution.',
            'Configuration | Default/website/store scopes, save, validation, inherited values, env-overridden values.',
            'Forms test validation, dirty state, save success, save failure, and store-scope fields.',
            'Config | Environment config, secret handling, cache invalidation.',
            'Bootstrap/config | Required | Required | Required | Smoke | N/A | Required | Required | Required',
        ],
        'specs/modernization/data-fixtures.md' => [
            'Multistore data | Covers websites, store groups, store views, currencies, locales, scoped config.',
            'Websites and stores | At least two websites, two store groups, three store views, one disabled store view, two locales, two currencies, scoped base URLs, scoped CMS content.',
            'Product attributes | Every EAV backend type, select/multiselect options, required attribute, unique attribute, store-scoped label/value, global/website/store scoped attributes, custom source/backend/frontend model coverage.',
            'CMS and content | Home page, standard content page, no-route page, static block, widget instance, WYSIWYG media reference, store-scoped content.',
        ],
        'specs/modernization/complex-feature-reverse-engineering.md' => [
            'System config path, scope, inheritance, frontend/backend/source model behavior.',
            'Identify relevant database tables, EAV attributes, config paths, cache keys, session keys, events, emails, and generated files.',
            'Secrets/config paths.',
        ],
        'docs/content/modernization/index.md' => [
            'typed configuration instead of XML-based extension points.',
            'New modules register through PHP manifests, service providers, routes, events, policies, and typed config.',
        ],
        'docusaurus/docs/developer/index.md' => [
            'PHP module manifests, service providers, policies, events, jobs, typed config, and no-new-XML rules.',
        ],
        'docusaurus/docs/user/admin-guide.md' => [
            'Scope behavior for default, website, and store-view configuration.',
            'Operational recovery paths for failed imports, failed emails, stale indexes, cache invalidation, scheduler failures, and integration outages.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing config planning phrase '{$phrase}'";
            }
        }
    }

    validateConfigCatalog($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateConfigCatalog(array &$errors): void
{
    $path = 'specs/modernization/magento-feature-catalog.md';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (configFeatureIds() as $featureId) {
        if (! str_contains($content, "| {$featureId} |")) {
            $errors[] = "{$path}: missing config feature row for {$featureId}";
        }
    }

    foreach (['Multistore and localization', 'System configuration', 'Default/website/store scopes', 'env overrides', 'EAV scope semantics', 'Config cache'] as $phrase) {
        if (! containsCaseInsensitive($content, $phrase)) {
            $errors[] = "{$path}: missing config catalog phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalConfigImplementation(array &$errors): void
{
    validateConfigArtifacts($errors);
    validateConfigTests($errors);
    validateConfigEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateConfigArtifacts(array &$errors): void
{
    $appFiles = phpFilesUnder('laravel/app');
    $configFiles = phpFilesUnder('laravel/config');
    $files = array_merge($appFiles, $configFiles);

    if ($appFiles === []) {
        $errors[] = 'Final config implementation requires Laravel app files';

        return;
    }

    $requiredContexts = [
        'ConfigRepository',
        'ScopedConfig',
        'ConfigScope',
        'StoreScope',
        'WebsiteScope',
        'StoreView',
        'ConfigCache',
        'SecretConfig',
        'AdminConfig',
        'EnvOverride',
        'ConfigValue',
        'SourceModel',
        'BackendModel',
    ];

    foreach ($requiredContexts as $context) {
        if (! pathOrContentMatches($files, "/{$context}|".preg_quote(splitWords($context), '/').'/i')) {
            $errors[] = "Final config implementation requires {$context} service or domain coverage";
        }
    }

    $requirements = [
        'service contracts or interfaces' => '/\/Contracts\/|Interface\.php$|contract/i',
        'typed config DTOs or value objects' => '/\/Data\/|\/DTO\/|Dto\.php$|Data\.php$|ValueObject|readonly class|enum /i',
        'repository or query service reading core config data' => '/core_config_data|ConfigRepository|Repository|QueryService|ReadModel|DB::table|Model/i',
        'default website store fallback resolution' => '/default.*website.*store|store.*website.*default|fallback|scope/i',
        'environment override support' => '/env\(|config\(|override|environment|EnvOverride/i',
        'secret or encrypted config handling' => '/secret|encrypt|decrypt|encrypted|credential|password|token/i',
        'cache invalidation or tagging' => '/Cache::|cache\(|forget|flush|tags|invalidate|ConfigCache/i',
        'admin config save and validation handling' => '/AdminConfig|system configuration|validate|Validator|FormRequest|save/i',
        'source backend or frontend model replacement' => '/SourceModel|BackendModel|FrontendModel|source model|backend model|frontend model/i',
        'store website model support' => '/StoreView|StoreScope|WebsiteScope|website|store view/i',
        'no XML dependency in config artifacts' => '/no-?xml|without XML|typed config|PHP config/i',
    ];

    foreach ($requirements as $label => $pattern) {
        if (! pathOrContentMatches($files, $pattern)) {
            $errors[] = "Final config implementation requires {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateConfigTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $aggregateContent = aggregateFiles($testFiles);

    if ($aggregateContent === '') {
        $errors[] = 'Final config implementation requires Laravel PHPUnit tests';

        return;
    }

    $coverage = [
        'all config feature IDs' => allConfigFeatureIdsPresent($aggregateContent),
        'default website store fallback behavior' => preg_match('/default.*website.*store|store.*website.*default|fallback|scope/i', $aggregateContent) === 1,
        'environment override and secret encrypted fields' => preg_match('/env|override|secret|encrypted|decrypt|credential|token/i', $aggregateContent) === 1,
        'cache invalidation behavior' => preg_match('/Cache::fake|Cache::shouldReceive|cache invalidation|forget|flush|tags/i', $aggregateContent) === 1,
        'admin save validation inherited values and source or backend models' => preg_match('/admin|system configuration|save|validation|inherited|source model|backend model/i', $aggregateContent) === 1,
        'core config data database assertions' => preg_match('/core_config_data|assertDatabase|DB snapshot|database snapshot|DB delta|database delta/i', $aggregateContent) === 1,
        'store switch localization currency and base URL behavior' => preg_match('/store switch|store view|localization|locale|currency|base URL|base_url/i', $aggregateContent) === 1,
        'dual-runtime legacy comparison' => preg_match('/dual-runtime|legacy comparison|Magento|baseline|legacy snapshot/i', $aggregateContent) === 1,
        'no XML implementation dependency' => preg_match('/no-?xml|without XML|typed config|PHP config/i', $aggregateContent) === 1,
    ];

    foreach ($coverage as $label => $covered) {
        if (! $covered) {
            $errors[] = "Final config implementation requires PHPUnit coverage for {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateConfigEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/config-parity-evidence.md',
        'docs/content/modernization/config-parity-evidence.md',
        'docusaurus/docs/developer/config-parity.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final config implementation requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (configFeatureIds() as $featureId) {
        if (! str_contains($content, $featureId)) {
            $errors[] = "{$path}: missing config evidence row for {$featureId}";
        }
    }

    foreach (['Config Path', 'Default Scope', 'Website Scope', 'Store Scope', 'Env Override', 'Cache', 'Secret', 'Admin Save', 'Legacy Snapshot', 'Laravel Test', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing config evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required|To inventory)\b/', $content) === 1) {
        $errors[] = "{$path}: final config evidence still contains placeholders";
    }
}

/**
 * @return list<string>
 */
function configFeatureIds(): array
{
    return [
        'SF-012',
        'AD-010',
        'CB-011',
        'CB-013',
    ];
}

function allConfigFeatureIdsPresent(string $content): bool
{
    foreach (configFeatureIds() as $featureId) {
        if (! str_contains($content, $featureId)) {
            return false;
        }
    }

    return true;
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

function containsCaseInsensitive(string $content, string $needle): bool
{
    return stripos($content, $needle) !== false;
}

function splitWords(string $value): string
{
    return strtolower((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $value));
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
