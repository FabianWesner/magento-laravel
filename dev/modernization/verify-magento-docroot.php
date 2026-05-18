#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$repoRoot = dirname(__DIR__, 2);
$coreRoot = getenv('MAGENTO_CORE_ROOT') ?: $repoRoot.'/core/magento-1.9.4.5';
$projectRoot = getenv('MAGENTO_PROJECT_ROOT') ?: $repoRoot.'/project';
$runtimeRoot = getenv('MAGENTO_RUNTIME_ROOT') ?: $repoRoot.'/.localdev/magento-docroot';

$errors = [];
if (! is_file($coreRoot.'/app/Mage.php')) {
    $errors[] = "Magento core root not found: {$coreRoot}";
}

if (! is_dir($runtimeRoot)) {
    fwrite(STDERR, "Magento runtime docroot not found: {$runtimeRoot}\n");
    exit(2);
}

if ($errors === []) {
    $coreMarkers = [
        'LICENSE.txt',
        'app/Mage.php',
        'app/bootstrap.php',
        'app/etc/config.xml',
        'index.php',
        'api.php',
        'cron.php',
    ];

    foreach ($coreMarkers as $relativePath) {
        compareFiles($coreRoot, $runtimeRoot, $relativePath, $errors);
    }
}

$projectFiles = is_dir($projectRoot) ? collectProjectOverlayFiles($projectRoot) : [];
if ($final && $projectFiles === []) {
    $errors[] = "Project overlay is placeholder-only: {$projectRoot}";
}

foreach ($projectFiles as $relativePath) {
    compareFiles($projectRoot, $runtimeRoot, $relativePath, $errors);
}

if (is_file($runtimeRoot.'/README.md') && is_file($projectRoot.'/README.md')) {
    $errors[] = 'Project overlay README.md was copied into the runtime docroot.';
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo "Magento docroot verification passed.\n";
echo "- Runtime docroot: {$runtimeRoot}\n";
echo "- Core markers checked: 7\n";
echo '- Project overlay files checked: '.count($projectFiles)."\n";
if ($projectFiles === []) {
    echo "- Project overlay status: placeholder-only\n";
}

/**
 * @param  list<string>  $errors
 */
function compareFiles(string $sourceRoot, string $runtimeRoot, string $relativePath, array &$errors): void
{
    $sourcePath = $sourceRoot.'/'.$relativePath;
    $runtimePath = $runtimeRoot.'/'.$relativePath;

    if (! is_file($sourcePath)) {
        $errors[] = "Source file missing: {$sourcePath}";

        return;
    }

    if (! is_file($runtimePath)) {
        $errors[] = "Runtime file missing: {$runtimePath}";

        return;
    }

    if (hash_file('sha256', $sourcePath) !== hash_file('sha256', $runtimePath)) {
        $errors[] = "Runtime file differs from source: {$relativePath}";
    }
}

/**
 * @return list<string>
 */
function collectProjectOverlayFiles(string $projectRoot): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectRoot, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
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
