#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateModuleSpecs($errors);

if ($final) {
    validateFinalModuleImplementation($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Module target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateModuleSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/adr/0001-use-laravel-runtime.md' => [
            'Use Laravel as the target application runtime for migrated routes, services, commands, events, scheduler, policies, and configuration.',
            'dependency injection, service providers, events, and testing tools.',
        ],
        'specs/adr/0003-no-new-xml.md' => [
            'Do not use XML for new Laravel architecture registration or configuration.',
            'New extension points are declared in PHP manifests, service providers, policies, events, routes, and typed config.',
            'Module system tests.',
        ],
        'specs/modernization/architecture-specs.md' => [
            'No-XML module system',
            'Manifest format, service providers, routes, events, views, commands, permissions, config, dependency ordering.',
            'Modules register behavior through service providers and explicit manifests.',
            'Manifests may declare dependencies, routes, commands, events, listeners, policies, permissions, config, views, assets, Livewire components, and migrations/installers if approved.',
            'Disabled modules must register nothing except diagnostic metadata.',
        ],
        'specs/modernization/proof-of-concepts.md' => [
            'POC 3: No-XML Sample Module',
            'Module registers service provider, route, command, event listener, permission, config, view, and Livewire component from PHP manifest.',
            'Module can be enabled/disabled.',
            'Dependency ordering is tested.',
        ],
        'specs/modernization/test-plan.md' => [
            'Module System Test Plan',
            'Local and Composer modules are discovered from PHP manifests.',
            'Disabled modules do not register services, routes, views, commands, permissions, or listeners.',
            'Module service providers bind expected contracts.',
            'Module config is PHP-based and overrideable.',
            'Module registry command reports status and dependency graph.',
        ],
        'specs/modernization/backlog.md' => [
            'Write module system spec',
            'No-XML sample module',
            'No-XML extension points, manifests, service providers, policies, typed config, and module dependencies are defined.',
            'Module registers services/routes/views/events/config/permissions without XML.',
        ],
        'docs/content/modernization/index.md' => [
            'New modules register through PHP manifests, service providers, routes, events, policies, and typed config.',
        ],
        'docs/content/modernization/architecture.md' => [
            'PHP module registry',
            'New architecture code does not use XML for registration or configuration.',
        ],
        'docusaurus/docs/developer/architecture.md' => [
            'PHP module manifests and service providers for extension.',
            'Policies for permissions.',
            'Events and listeners for domain extension points.',
        ],
        'docusaurus/docs/developer/module-development.md' => [
            'Module manifest with name, version, dependencies, providers, routes, commands, views, permissions, config, events, and jobs.',
            'Service provider for bindings and boot hooks.',
            'Policies for permissions.',
            'Tests covering happy paths, edge cases, permissions, validation, failures, and side effects.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing module planning phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalModuleImplementation(array &$errors): void
{
    $moduleFiles = moduleTargetFiles();

    validateModuleManifests($moduleFiles, $errors);
    validateModuleExtensionArtifacts($moduleFiles, $errors);
    validateModuleTests($errors);
    validateModuleEvidence($errors);
}

/**
 * @param  list<string>  $moduleFiles
 * @param  list<string>  $errors
 */
function validateModuleManifests(array $moduleFiles, array &$errors): void
{
    $manifestFiles = [];

    foreach ($moduleFiles as $file) {
        $basename = basename($file);
        $content = file_get_contents($file);
        if ($content === false) {
            continue;
        }

        if (
            $basename === 'module.php'
            || str_contains($basename, 'Manifest')
            || preg_match('/\bmanifest\b/i', $content) === 1
        ) {
            $manifestFiles[] = $file;
        }
    }

    if ($manifestFiles === []) {
        $errors[] = 'Final module implementation requires PHP manifest files under laravel/modules, laravel/packages, or laravel/app/Modules';

        return;
    }

    $requiredManifestKeys = [
        'name',
        'version',
        'dependencies',
        'providers',
        'routes',
        'commands',
        'events',
        'listeners',
        'permissions',
        'config',
        'views',
        'jobs',
    ];

    foreach ($requiredManifestKeys as $key) {
        $found = false;
        foreach ($manifestFiles as $manifestFile) {
            $content = file_get_contents($manifestFile);
            if ($content !== false && preg_match("/['\"]{$key}['\"]\s*=>/", $content) === 1) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            $errors[] = "Final module manifest requires '{$key}' metadata";
        }
    }
}

/**
 * @param  list<string>  $moduleFiles
 * @param  list<string>  $errors
 */
function validateModuleExtensionArtifacts(array $moduleFiles, array &$errors): void
{
    $requirements = [
        'service provider' => '/ServiceProvider\.php$/',
        'contract or interface' => '/(?:Contract|Interface)\.php$|\/Contracts\//',
        'policy' => '/Policy\.php$|\/Policies\//',
        'event' => '/\/Events\/|Event\.php$/',
        'listener' => '/\/Listeners\/|Listener\.php$/',
        'job' => '/\/Jobs\/|Job\.php$/',
        'config' => '/\/config\/.*\.php$|Config\.php$/',
        'route or command' => '/\/routes\/.*\.php$|RouteServiceProvider\.php$|Command\.php$|\/Console\/Commands\//',
    ];

    foreach ($requirements as $label => $pattern) {
        if (! pathMatches($moduleFiles, $pattern)) {
            $errors[] = "Final module implementation requires a module {$label} artifact";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateModuleTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $coverage = [
        'manifest discovery' => false,
        'dependency ordering' => false,
        'cycle diagnostics' => false,
        'enable/disable behavior' => false,
        'provider registration' => false,
        'permissions or policies' => false,
        'events or jobs' => false,
        'config overrides' => false,
    ];

    foreach ($testFiles as $testFile) {
        $content = file_get_contents($testFile);
        if ($content === false || preg_match('/\bmodule\b/i', $content.$testFile) !== 1) {
            continue;
        }

        $coverage['manifest discovery'] = $coverage['manifest discovery'] || preg_match('/manifest|discover/i', $content) === 1;
        $coverage['dependency ordering'] = $coverage['dependency ordering'] || preg_match('/dependency|dependencies|order/i', $content) === 1;
        $coverage['cycle diagnostics'] = $coverage['cycle diagnostics'] || preg_match('/cycle|circular/i', $content) === 1;
        $coverage['enable/disable behavior'] = $coverage['enable/disable behavior'] || preg_match('/enable|disable|disabled/i', $content) === 1;
        $coverage['provider registration'] = $coverage['provider registration'] || preg_match('/provider|binds?|container|contract/i', $content) === 1;
        $coverage['permissions or policies'] = $coverage['permissions or policies'] || preg_match('/permission|policy|authorize|gate/i', $content) === 1;
        $coverage['events or jobs'] = $coverage['events or jobs'] || preg_match('/event|listener|job|queue/i', $content) === 1;
        $coverage['config overrides'] = $coverage['config overrides'] || preg_match('/config|override/i', $content) === 1;
    }

    foreach ($coverage as $label => $covered) {
        if (! $covered) {
            $errors[] = "Final module implementation requires PHPUnit coverage for {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateModuleEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/module-system-evidence.md',
        'docs/content/modernization/module-system-evidence.md',
        'docusaurus/docs/developer/module-system.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final module implementation requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (['Manifest', 'Service Provider', 'Policy', 'Event', 'Job', 'Config', 'Contract', 'Test', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing module evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required)\b/', $content) === 1) {
        $errors[] = "{$path}: final module evidence still contains placeholders";
    }
}

/**
 * @return list<string>
 */
function moduleTargetFiles(): array
{
    $files = [];

    foreach (['laravel/modules', 'laravel/packages', 'laravel/app/Modules', 'laravel/app/Modernization', 'laravel/app/Providers', 'laravel/config', 'laravel/routes'] as $directory) {
        $files = array_merge($files, phpFilesUnder($directory));
    }

    return array_values(array_unique($files));
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
 * @param  list<string>  $paths
 */
function pathMatches(array $paths, string $pattern): bool
{
    foreach ($paths as $path) {
        if (preg_match($pattern, normalizePath($path)) === 1) {
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
