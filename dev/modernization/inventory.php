#!/usr/bin/env php
<?php

declare(strict_types=1);

function usage(): void
{
    echo "Usage: php dev/modernization/inventory.php [--root=/path] [--format=json|markdown]\n";
}

$cwd = getcwd();
$root = is_dir($cwd.'/app/code') ? $cwd : $cwd.'/core/magento-1.9.4.5';
$format = 'markdown';
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--help' || $arg === '-h') {
        usage();
        exit(0);
    }
    if (str_starts_with($arg, '--root=')) {
        $root = substr($arg, 7);

        continue;
    }
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, 9);

        continue;
    }
}

$root = rtrim(realpath($root) ?: $root, DIRECTORY_SEPARATOR);
if (! is_dir($root)) {
    fwrite(STDERR, "Root not found: {$root}\n");
    exit(1);
}

function files(string $pattern): array
{
    $matches = glob($pattern, GLOB_NOSORT);
    sort($matches);

    return $matches ?: [];
}

function countFiles(string $root, string $relativePattern): int
{
    return count(files($root.DIRECTORY_SEPARATOR.$relativePattern));
}

function countFind(string $root, string $path, string $suffix = ''): int
{
    $base = $root.DIRECTORY_SEPARATOR.$path;
    if (! is_dir($base)) {
        return 0;
    }
    $count = 0;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }
        if ($suffix === '' || str_ends_with($file->getFilename(), $suffix)) {
            $count++;
        }
    }

    return $count;
}

function countPathContains(string $root, string $path, string $contains, string $suffix = ''): int
{
    $base = $root.DIRECTORY_SEPARATOR.$path;
    if (! is_dir($base)) {
        return 0;
    }
    $count = 0;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }
        $pathname = str_replace(DIRECTORY_SEPARATOR, '/', $file->getPathname());
        if (! str_contains($pathname, $contains)) {
            continue;
        }
        if ($suffix === '' || str_ends_with($file->getFilename(), $suffix)) {
            $count++;
        }
    }

    return $count;
}

function immediateDirs(string $path): array
{
    if (! is_dir($path)) {
        return [];
    }
    $dirs = array_values(array_filter(glob($path.DIRECTORY_SEPARATOR.'*') ?: [], 'is_dir'));
    $dirs = array_map('basename', $dirs);
    sort($dirs);

    return $dirs;
}

function countCommunityProjectFiles(string $root): int
{
    $base = $root.'/app/code/community';
    if (! is_dir($base)) {
        return 0;
    }

    $knownPlatformPaths = [
        'app/code/community/Cm/Cache/Backend/Redis.php',
        'app/code/community/Cm/RedisSession',
        'app/code/community/Phoenix/Moneybookers',
    ];

    $count = 0;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $relative = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root.DIRECTORY_SEPARATOR, '', $file->getPathname()));
        $isKnownPlatformPath = false;
        foreach ($knownPlatformPaths as $knownPath) {
            if ($relative === $knownPath || str_starts_with($relative, $knownPath.'/')) {
                $isKnownPlatformPath = true;
                break;
            }
        }

        if (! $isKnownPlatformPath) {
            $count++;
        }
    }

    return $count;
}

$moduleFiles = files($root.'/app/etc/modules/*.xml');
$nonMageModuleFiles = array_values(array_filter($moduleFiles, static fn (string $file): bool => ! str_starts_with(basename($file), 'Mage_')));
$knownPlatformModuleFiles = [
    'Cm_RedisSession.xml',
    'Phoenix_Moneybookers.xml',
];
$unknownNonMageModuleFiles = array_values(array_filter(
    $nonMageModuleFiles,
    static fn (string $file): bool => ! in_array(basename($file), $knownPlatformModuleFiles, true)
));
$communityProjectSourceFiles = countCommunityProjectFiles($root);

$report = [
    'root' => $root,
    'core_only_assessment' => [
        'has_app_code_local' => is_dir($root.'/app/code/local'),
        'has_app_code_community' => is_dir($root.'/app/code/community'),
        'app_code_community_project_files' => $communityProjectSourceFiles,
        'non_mage_module_declarations' => array_map(static fn (string $file): string => str_replace($root.'/', '', $file), $nonMageModuleFiles),
        'unknown_non_mage_module_declarations' => array_map(static fn (string $file): string => str_replace($root.'/', '', $file), $unknownNonMageModuleFiles),
        'has_local_xml' => is_file($root.'/app/etc/local.xml'),
    ],
    'counts' => [
        'core_modules' => count(immediateDirs($root.'/app/code/core/Mage')),
        'module_declarations' => count($moduleFiles),
        'controller_files' => countPathContains($root, 'app/code', '/controllers/', '.php'),
        'api_xml' => countFiles($root, 'app/code/core/Mage/*/etc/api.xml'),
        'api2_xml' => countFiles($root, 'app/code/core/Mage/*/etc/api2.xml'),
        'wsdl_xml' => countFiles($root, 'app/code/core/Mage/*/etc/wsdl.xml'),
        'wsi_xml' => countFiles($root, 'app/code/core/Mage/*/etc/wsi.xml'),
        'sql_setup_files' => countFiles($root, 'app/code/core/Mage/*/sql/*/*.php'),
        'data_setup_files' => countFiles($root, 'app/code/core/Mage/*/data/*/*.php'),
        'unit_tests' => countFind($root, 'tests/unit', 'Test.php'),
        'browser_specs' => countFind($root, 'tests/browser', '.php') + countFind($root, 'playwright', '.spec.ts'),
    ],
    'code_pools' => immediateDirs($root.'/app/code'),
    'design_areas' => immediateDirs($root.'/app/design'),
    'skin_areas' => immediateDirs($root.'/skin'),
    'entrypoints' => array_values(array_filter(['index.php', 'api.php', 'get.php', 'install.php', 'cron.php', 'cron.sh'], static fn (string $file): bool => is_file($root.'/'.$file))),
    'missing_project_overlay_items' => [
        'app/code/local project modules',
        'app/code/community project modules',
        'project-specific non-Mage module declarations',
        'project app/etc/local.xml',
        'project database dump',
        'project media fixtures',
        'project deployment configuration',
    ],
];

if ($format === 'json') {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    exit(0);
}

if ($format !== 'markdown') {
    fwrite(STDERR, "Unsupported format: {$format}\n");
    exit(1);
}

echo "# Modernization Inventory Report\n\n";
echo "- Root: `{$report['root']}`\n";
echo '- Code pools: `'.implode('`, `', $report['code_pools'])."`\n";
echo "- Community project source files: {$report['core_only_assessment']['app_code_community_project_files']}\n";
echo "- Core modules: {$report['counts']['core_modules']}\n";
echo "- Module declarations: {$report['counts']['module_declarations']}\n";
echo "- Controller files: {$report['counts']['controller_files']}\n";
echo "- SQL setup files: {$report['counts']['sql_setup_files']}\n";
echo "- Data setup files: {$report['counts']['data_setup_files']}\n";
echo "- Unit tests: {$report['counts']['unit_tests']}\n";
echo "- Browser specs: {$report['counts']['browser_specs']}\n";
echo '- Live local config: '.($report['core_only_assessment']['has_local_xml'] ? 'yes' : 'no')."\n\n";
echo "## Core-Only Assessment\n\n";
if (
    ! $report['core_only_assessment']['has_app_code_local']
    && $report['core_only_assessment']['app_code_community_project_files'] === 0
    && $report['core_only_assessment']['unknown_non_mage_module_declarations'] === []
) {
    echo "This checkout appears to be core/source only. It may include platform dependency declarations, but project-specific code, data, media, and deployment configuration still need to be inventoried.\n";
} else {
    echo "This checkout contains project-specific indicators. Review the JSON report for details.\n";
}
echo "\n## Missing Project Overlay Items\n\n";
foreach ($report['missing_project_overlay_items'] as $item) {
    echo "- {$item}\n";
}
