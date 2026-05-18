#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateMkDocsContent($errors);
validateDocusaurusContent($errors, $final);

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Documentation content ';
echo $final ? 'final check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateMkDocsContent(array &$errors): void
{
    $requiredFiles = [
        'docs/content/modernization/index.md' => [
            'Magento CE `1.9.4.5`',
            'Laravel',
            'Livewire',
            'EAV',
            'project/',
            'laravel/',
            'Docusaurus',
        ],
        'docs/content/modernization/architecture.md' => [
            'modular Laravel system',
            'PHP module registry',
            'Blade and Livewire',
            'EAV repositories',
            'New architecture code does not use XML',
            'legacy PHP is isolated',
        ],
        'docs/content/modernization/compatibility.md' => [
            'Database schema',
            'Storefront UI',
            'Admin UI',
            'URLs',
            'APIs',
            'Existing XML may be read',
            'automated verification',
        ],
        'docs/content/modernization/testing.md' => [
            'Legacy characterization tests pass',
            'Laravel implementation tests pass',
            'Storefront and admin visual regression pass',
            'Database and EAV parity tests pass',
            'Security, performance, accessibility, and operations gates pass',
            'Execution details and checklists live in `specs/modernization/test-plan.md`',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readMarkdownFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing public documentation phrase '{$phrase}'";
            }
        }

        validatePublicDocDoesNotContainExecutionChecklist($path, $content, $errors);
    }
}

/**
 * @param  list<string>  $errors
 */
function validateDocusaurusContent(array &$errors, bool $final): void
{
    $requiredFiles = [
        'docusaurus/docs/index.md' => [
            'developer documentation',
            'user documentation',
        ],
        'docusaurus/docs/user/index.md' => [
            'slug: /user/',
            'operators',
            'merchandisers',
            'support teams',
            'business users',
            'specs/modernization/magento-feature-catalog.md',
        ],
        'docusaurus/docs/user/storefront-guide.md' => [
            'Home, CMS, category, product detail, search, cart, checkout',
            'simple, configurable, grouped, bundle, virtual, downloadable',
            'out-of-stock products',
            'failed payments',
            'Magento and Laravel screenshots',
        ],
        'docusaurus/docs/user/admin-guide.md' => [
            'Login, dashboard, catalog',
            'Permission-specific behavior',
            'Operational recovery paths',
            'screenshot evidence',
            'permission tests',
        ],
        'docusaurus/docs/user/feature-coverage.md' => [
            'specs/modernization/magento-feature-catalog.md',
            'Status: legacy, bridged, Laravel complete, or retired.',
            'Known limitations',
            'Support and rollback notes',
        ],
        'docusaurus/docs/developer/index.md' => [
            'slug: /developer/',
            'Laravel bootstrap',
            'PHP module manifests',
            'EAV repository usage',
            'Livewire storefront and admin UI patterns',
            'Technologies that must be removed',
        ],
        'docusaurus/docs/developer/architecture.md' => [
            'Laravel, not Magento or Zend',
            'Livewire and Blade',
            'PHP module manifests',
            'Policies for permissions',
            'EAV repositories/services',
            'Removed Technologies',
        ],
        'docusaurus/docs/developer/module-development.md' => [
            'Modules are PHP-first',
            'must not introduce Magento XML',
            'Service provider',
            'Livewire components',
            'Tests covering happy paths, edge cases',
        ],
        'docusaurus/docs/developer/testing-and-verification.md' => [
            'production readiness',
            'dual-runtime parity',
            'accessibility, security, performance, and operations tests',
            'Database side effects',
            'specs/modernization/test-plan.md',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readMarkdownFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing Docusaurus documentation phrase '{$phrase}'";
            }
        }

        if ($final) {
            validateFinalDocs($path, $content, $errors);
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validatePublicDocDoesNotContainExecutionChecklist(string $path, string $content, array &$errors): void
{
    if (preg_match('/^\s*-\s+\[[ xX]\]/m', $content) === 1) {
        $errors[] = "{$path}: public MkDocs content must not contain execution checklist items";
    }

    $blockedHeadings = [
        'Install Commands',
        'Commands To Run',
        'Entry Template',
        'Next',
        'Blocked',
    ];

    foreach ($blockedHeadings as $heading) {
        if (preg_match('/^#{2,3}\s+'.preg_quote($heading, '/').'\s*$/m', $content) === 1) {
            $errors[] = "{$path}: public MkDocs content must not contain execution heading '{$heading}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalDocs(string $path, string $content, array &$errors): void
{
    $placeholderPattern = '/\b(?:TBD|Pending|Required where applicable|Operational evidence required)\b/';
    if (preg_match($placeholderPattern, $content) === 1) {
        $errors[] = "{$path}: final documentation still contains placeholder language";
    }

    $planningPhrases = [
        'The final version must',
        'Required Coverage',
        'Required Topics',
        'Required User-Facing Status',
        'Completion Rule',
        'Each topic must',
        'Every feature must show',
        'must have screenshot evidence',
    ];

    foreach ($planningPhrases as $phrase) {
        if (str_contains($content, $phrase)) {
            $errors[] = "{$path}: final documentation still contains planning phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function readMarkdownFile(string $path, array &$errors): ?string
{
    if (! is_file($path)) {
        $errors[] = "{$path}: missing documentation file";

        return null;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = "{$path}: unable to read documentation file";

        return null;
    }

    return $content;
}
