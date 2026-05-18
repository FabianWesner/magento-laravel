#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateBootstrapSpecs($errors);

if ($final) {
    validateFinalBootstrapImplementation($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Bootstrap target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateBootstrapSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Repository and runtime structure:',
            'Do not remove the legacy Magento runtime while any feature still needs characterization, screenshotting, or parity comparison.',
            'The refactored Laravel runtime must completely remove the banned technologies listed in specs/modernization/technology-removal-policy.md.',
            'Laravel foundation:',
            'Build Laravel bootstrap, config, module registry, no-XML manifest system, route strangler/fallback, EAV access layer, auth/session/security foundation, scheduler, queue, events, API contracts, and operations hooks.',
        ],
        'specs/modernization/architecture-specs.md' => [
            'Laravel bootstrap | How Laravel enters the current runtime and eventually replaces it.',
            'Container boot, HTTP kernel, CLI, fallback, config, error handling.',
            'Bootstrap smoke tests, route tests.',
            'Bootstrap Laravel beside the legacy runtime first.',
            'Bind Magento-compatible infrastructure behind Laravel contracts: config, DB, cache, session, events, logger, filesystem, URL, auth, translation.',
            'Treat compatibility adapters as temporary and measurable.',
        ],
        'specs/modernization/roadmap.md' => [
            'Phase 3: Laravel Foundation Beside Legacy Runtime',
            'Run Laravel as a separate PHP `8.5+` runtime under `laravel/`; do not load Laravel inside Magento\'s PHP `7.4` process.',
            'Bind core infrastructure contracts: config, database, cache, session, logging, events, filesystem, URL generation, auth, and translation.',
            'Add Laravel service providers for new architecture.',
            'Add temporary compatibility adapters only where needed for side-by-side migration, each with owner, expiry phase, tests, and no final-release runtime dependency on Magento/Zend/Varien classes.',
            'Add health checks for Laravel bootstrap, database, cache, session, and module registry.',
            'Laravel container boots in the separate Laravel runtime and CLI.',
            'A smoke test can resolve a Laravel service from the container.',
            'CI runs both legacy and Laravel checks.',
        ],
        'specs/modernization/proof-of-concepts.md' => [
            'POC 1: Laravel Bootstrap Beside Magento',
            'Laravel container resolves services during Laravel web and CLI requests.',
            'Laravel is not loaded inside Magento\'s PHP `7.4` process.',
            'Legacy `index.php`, `api.php`, `cron.php`, and shell scripts still work.',
            'No routes are moved yet.',
            'Bootstrap smoke test.',
        ],
        'specs/modernization/test-plan.md' => [
            'Runtime tests | Prove the target Laravel runtime uses the latest stable PHP and compatible Composer packages.',
            'Observability | Logs, metrics, alerts, health checks, and operator diagnostics identify failures without exposing secrets.',
            'Deployment | Build, deploy, warm cache, run health checks.',
            'Architecture overview | Explains Laravel runtime, module system, EAV layer, UI architecture, and replacement of legacy concepts.',
        ],
        'specs/modernization/technology-removal-policy.md' => [
            'Framework runtime | Zend Framework 1 runtime, Magento front controller, `Mage::app()`, `Mage_Core_*` request/bootstrap/config runtime.',
            'Laravel HTTP kernel, service container, routing, middleware, config, scheduler, queues.',
            'Constructor injection, Laravel container bindings, explicit contracts, repositories, policies, events.',
            'Temporary bridges are allowed only to keep delivery incremental.',
            'Have an owner and removal phase.',
        ],
        'docs/content/modernization/architecture.md' => [
            'The target application is a modular Laravel system that keeps Magento data contracts stable while replacing runtime internals.',
            'Request["HTTP request"]',
            'Laravel["Laravel kernel"]',
            'Compatibility bridges are temporary and must have removal criteria.',
            'The Laravel target uses latest stable PHP; legacy PHP is isolated to Magento baseline verification.',
        ],
        'docusaurus/docs/developer/index.md' => [
            'Laravel bootstrap and route strangler.',
        ],
        'docusaurus/docs/developer/architecture.md' => [
            'The target runtime is Laravel, not Magento or Zend.',
            'Laravel HTTP kernel, routes, middleware, service container, scheduler, queues, and config.',
            'New Laravel runtime code must not depend on Magento/Zend/Varien/XML/layout/block/resource-model/Prototype-era systems.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing bootstrap planning phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalBootstrapImplementation(array &$errors): void
{
    validateBootstrapApp($errors);
    validateBootstrapArtifacts($errors);
    validateBootstrapTests($errors);
    validateBootstrapEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateBootstrapApp(array &$errors): void
{
    $path = 'laravel/bootstrap/app.php';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    $requiredSnippets = [
        'Application::configure',
        '->withRouting(',
        'commands:',
        'health:',
        '->withMiddleware(',
        '->withExceptions(',
    ];

    foreach ($requiredSnippets as $snippet) {
        if (! str_contains($content, $snippet)) {
            $errors[] = "{$path}: missing Laravel bootstrap snippet '{$snippet}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateBootstrapArtifacts(array &$errors): void
{
    $files = array_merge(
        phpFilesUnder('laravel/app'),
        phpFilesUnder('laravel/bootstrap'),
        phpFilesUnder('laravel/config'),
        phpFilesUnder('laravel/routes')
    );

    if ($files === []) {
        $errors[] = 'Final bootstrap implementation requires Laravel app, bootstrap, config, or route files';

        return;
    }

    $requiredContexts = [
        'BootstrapServiceProvider',
        'InfrastructureServiceProvider',
        'CoreInfrastructure',
        'HealthCheck',
        'BootstrapHealth',
        'DatabaseHealth',
        'CacheHealth',
        'SessionHealth',
        'ModuleRegistryHealth',
        'RuntimeIsolation',
        'CompatibilityAdapter',
        'AdapterExpiry',
        'ErrorHandler',
        'Observability',
    ];

    foreach ($requiredContexts as $context) {
        if (! pathOrContentMatches($files, "/{$context}|".preg_quote(splitWords($context), '/').'/i')) {
            $errors[] = "Final bootstrap implementation requires {$context} service or foundation coverage";
        }
    }

    $requirements = [
        'core infrastructure contracts or interfaces' => '/\/Contracts\/|Interface\.php$|contract/i',
        'service provider container bindings' => '/ServiceProvider|->(?:bind|singleton|scoped|instance|bindIf|singletonIf)\(/',
        'config database cache session event filesystem URL auth translation contracts' => '/config.*database.*cache.*session|cache.*session.*events|filesystem.*URL|auth.*translation|Translator|UrlGenerator/i',
        'HTTP kernel route or middleware bootstrap coverage' => '/withRouting|Route::|Middleware|Http\/Kernel|middleware/i',
        'CLI bootstrap or command registration coverage' => '/routes\/console|Artisan::command|Command\.php$|Console\/Commands/i',
        'health checks for bootstrap database cache session and module registry' => '/health|HealthCheck|bootstrap.*database.*cache.*session|module registry/i',
        'runtime isolation from Magento PHP process' => '/RuntimeIsolation|separate PHP|PHP 8\.5|PHP85|legacy.*7\.4|not loaded inside Magento/i',
        'compatibility adapter owner and expiry metadata' => '/CompatibilityAdapter|adapter.*owner|owner.*expiry|expiry phase|removal phase|expires/i',
        'error handling and exception reporting' => '/withExceptions|report\(|render\(|ShouldntReport|Exception|ErrorHandler/i',
        'observability logging or diagnostics context' => '/Log::|logger\(|context\(|metric|diagnostic|Observability|health/i',
        'no final Magento Zend Varien runtime dependency' => '/no final-release runtime dependency|no-?xml|without XML|Magento\/Zend\/Varien|removed-technology/i',
    ];

    foreach ($requirements as $label => $pattern) {
        if (! pathOrContentMatches($files, $pattern)) {
            $errors[] = "Final bootstrap implementation requires {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateBootstrapTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $aggregateContent = aggregateFiles($testFiles);

    if ($aggregateContent === '') {
        $errors[] = 'Final bootstrap implementation requires Laravel PHPUnit tests';

        return;
    }

    $coverage = [
        'container service resolution in web requests' => preg_match('/container|app\(|resolve|service.*resolution|bind|singleton/i', $aggregateContent) === 1
            && preg_match('/get\(|post\(|withHeader|HTTP|web request/i', $aggregateContent) === 1,
        'container service resolution in CLI requests' => preg_match('/artisan|command|CLI|console/i', $aggregateContent) === 1
            && preg_match('/container|app\(|resolve|service.*resolution|bind|singleton/i', $aggregateContent) === 1,
        'bootstrap health route' => preg_match('/\/up|health|HealthCheck|assertOk|assertStatus\(200\)/i', $aggregateContent) === 1,
        'database cache session and module registry health' => preg_match('/database|DB::|Cache::|session|module registry|Registry/i', $aggregateContent) === 1,
        'core infrastructure contract bindings' => preg_match('/contract|interface|bind|singleton|config|database|cache|session|filesystem|translation/i', $aggregateContent) === 1,
        'runtime isolation from Magento PHP process' => preg_match('/RuntimeIsolation|separate PHP|PHP 8\.5|PHP85|legacy.*7\.4|not loaded inside Magento/i', $aggregateContent) === 1,
        'compatibility adapter owner expiry and no final runtime dependency' => preg_match('/CompatibilityAdapter|owner|expiry|removal phase|no final-release runtime dependency|Magento\/Zend\/Varien/i', $aggregateContent) === 1,
        'error handling logging and diagnostics' => preg_match('/withExceptions|Exception|Log::|logger\(|context\(|diagnostic|observability/i', $aggregateContent) === 1,
        'legacy behavior remains unchanged during bootstrap' => preg_match('/legacy.*unchanged|Magento smoke|storefront.*admin|no routes are moved|behavior change/i', $aggregateContent) === 1,
    ];

    foreach ($coverage as $label => $covered) {
        if (! $covered) {
            $errors[] = "Final bootstrap implementation requires PHPUnit coverage for {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateBootstrapEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/bootstrap-foundation-evidence.md',
        'docs/content/modernization/bootstrap-foundation-evidence.md',
        'docusaurus/docs/developer/bootstrap-foundation.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final bootstrap implementation requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (['HTTP Kernel', 'CLI', 'Container Binding', 'Infrastructure Contract', 'Health Check', 'Database', 'Cache', 'Session', 'Module Registry', 'Runtime Isolation', 'Compatibility Adapter', 'Owner', 'Expiry', 'Error Handling', 'Observability', 'Legacy Smoke', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing bootstrap evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required|To inventory)\b/', $content) === 1) {
        $errors[] = "{$path}: final bootstrap evidence still contains placeholders";
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
