#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateLivewireSpecs($errors);

if ($final) {
    validateFinalLivewireImplementation($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Livewire target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateLivewireSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/adr/0002-use-livewire-for-ui.md' => [
            'Use Blade and Livewire for migrated storefront and admin UI surfaces.',
            'Business rules must remain in services, not Livewire components.',
            'Livewire component tests.',
        ],
        'specs/modernization/architecture-specs.md' => [
            'Storefront UI',
            'How Blade/Livewire replaces layout XML/blocks/templates.',
            'Admin UI',
            'How Livewire replaces admin grids/forms.',
            'Livewire components do not directly own business rules.',
            'Admin grids and forms become reusable Livewire components.',
        ],
        'specs/modernization/proof-of-concepts.md' => [
            'POC 4: Livewire Admin Grid',
            'Goal: prove Livewire can reproduce admin grid behavior and look.',
            'Livewire component tests.',
            'POC 5: Livewire Storefront Page',
            'Goal: prove Blade/Livewire can preserve storefront look and behavior.',
        ],
        'specs/modernization/test-plan.md' => [
            'Prove the new Laravel and Livewire architecture is modular, extensible, and free of new XML configuration.',
            'Livewire component tests',
            'New Livewire interactions pass component and browser tests.',
            'Livewire Admin Requirements',
            'Screen-reader semantics for navigation, grids, tabs, dialogs, and Livewire updates.',
        ],
        'specs/modernization/backlog.md' => [
            'Livewire admin grid',
            'Livewire storefront page',
            'Storefront/admin Livewire plans, screenshots, states, and edge cases are defined.',
        ],
        'docusaurus/docs/developer/index.md' => [
            'Livewire storefront and admin UI patterns.',
        ],
        'docusaurus/docs/developer/module-development.md' => [
            'Livewire components for interactive UI.',
        ],
        'docusaurus/docs/developer/testing-and-verification.md' => [
            'Livewire',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing Livewire planning phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalLivewireImplementation(array &$errors): void
{
    validateComposerRequiresLivewire($errors);
    validateLivewireComponentFiles($errors);
    validateLivewireTests($errors);
    validateLivewireEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateComposerRequiresLivewire(array &$errors): void
{
    $composer = readJsonFile('laravel/composer.json', $errors);
    if ($composer === null) {
        return;
    }

    if (! isset($composer['require']['livewire/livewire'])) {
        $errors[] = 'laravel/composer.json: final Livewire implementation requires livewire/livewire in require';
    }

    $lock = readJsonFile('laravel/composer.lock', $errors);
    if ($lock === null) {
        return;
    }

    $lockedPackages = [];
    foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
        if (is_array($package) && isset($package['name'])) {
            $lockedPackages[] = (string) $package['name'];
        }
    }

    if (! in_array('livewire/livewire', $lockedPackages, true)) {
        $errors[] = 'laravel/composer.lock: final Livewire implementation requires livewire/livewire to be installed';
    }
}

/**
 * @param  list<string>  $errors
 */
function validateLivewireComponentFiles(array &$errors): void
{
    $componentPaths = array_merge(
        glob('laravel/app/Livewire/**/*.php') ?: [],
        glob('laravel/app/Livewire/*.php') ?: [],
        glob('laravel/app/Http/Livewire/**/*.php') ?: [],
        glob('laravel/app/Http/Livewire/*.php') ?: []
    );

    if ($componentPaths === []) {
        $errors[] = 'Final Livewire implementation requires PHP component files under laravel/app/Livewire or laravel/app/Http/Livewire';
    }

    $viewPaths = array_merge(
        glob('laravel/resources/views/livewire/**/*.blade.php') ?: [],
        glob('laravel/resources/views/livewire/*.blade.php') ?: []
    );

    if ($viewPaths === []) {
        $errors[] = 'Final Livewire implementation requires Blade views under laravel/resources/views/livewire';
    }
}

/**
 * @param  list<string>  $errors
 */
function validateLivewireTests(array &$errors): void
{
    $testFiles = array_merge(
        glob('laravel/tests/Feature/**/*.php') ?: [],
        glob('laravel/tests/Feature/*.php') ?: [],
        glob('laravel/tests/Unit/**/*.php') ?: [],
        glob('laravel/tests/Unit/*.php') ?: []
    );

    foreach ($testFiles as $testFile) {
        $content = file_get_contents($testFile);
        if ($content !== false && (str_contains($content, 'Livewire::test') || str_contains($content, 'livewire('))) {
            return;
        }
    }

    $errors[] = 'Final Livewire implementation requires Laravel tests that exercise Livewire components';
}

/**
 * @param  list<string>  $errors
 */
function validateLivewireEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/livewire-ui-evidence.md',
        'docs/content/modernization/livewire-ui-evidence.md',
        'docusaurus/docs/developer/livewire-ui.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final Livewire implementation requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (['Storefront', 'Admin', 'Component', 'Fixture', 'Screenshot', 'Test', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing Livewire evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required)\b/', $content) === 1) {
        $errors[] = "{$path}: final Livewire evidence still contains placeholders";
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
 * @param  list<string>  $errors
 * @return array<string, mixed>|null
 */
function readJsonFile(string $path, array &$errors): ?array
{
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return null;
    }

    $decoded = json_decode($content, true);
    if (! is_array($decoded)) {
        $errors[] = "{$path}: invalid JSON: ".json_last_error_msg();

        return null;
    }

    return $decoded;
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
