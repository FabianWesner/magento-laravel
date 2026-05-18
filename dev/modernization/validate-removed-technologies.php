#!/usr/bin/env php
<?php

declare(strict_types=1);

$paths = array_slice($argv, 1);
if ($paths === []) {
    $paths = [
        'laravel/app',
        'laravel/config',
        'laravel/database',
        'laravel/modules',
        'laravel/packages',
        'laravel/routes',
        'laravel/resources',
        'laravel/composer.json',
        'laravel/composer.lock',
        'laravel/package.json',
        'laravel/package-lock.json',
        'laravel/vite.config.js',
        '.github/workflows',
    ];
}

$patterns = [
    'Mage static service locator' => '/\bMage::/',
    'Magento class dependency' => '/\bMage_[A-Za-z0-9_]+/',
    'Magento namespace dependency' => '/\bMagento\\\\[A-Za-z0-9_\\\\]+/',
    'Magento runtime package dependency' => '/\b(?:magento|openmage|mage-os)\/[a-z0-9_.-]+/i',
    'Varien dependency' => '/\bVarien_[A-Za-z0-9_]+/',
    'Zend Framework dependency' => '/\bZend_[A-Za-z0-9_]+|zendframework\//i',
    'Laminas compatibility dependency' => '/\bLaminas\\\\|laminas\//i',
    'Prototype or Scriptaculous dependency' => '/\bPrototype\b|\bscriptaculous\b|varien\/js\.js/i',
    'Magento XML config in target code' => '/<(config|layout|modules|adminhtml|acl|global|frontend|crontab)\b/i',
];

$excludedPathFragments = [
    '/vendor/',
    '/node_modules/',
    '/storage/',
    '/bootstrap/cache/',
];

$files = [];
$violations = [];
foreach ($paths as $path) {
    if (! file_exists($path)) {
        continue;
    }

    if (is_file($path)) {
        $files[] = $path;

        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        $pathname = $file->getPathname();

        if (! $file->isFile()) {
            continue;
        }

        foreach ($excludedPathFragments as $excluded) {
            if (str_contains($pathname, $excluded)) {
                continue 2;
            }
        }

        $filename = strtolower($file->getFilename());
        $extension = strtolower($file->getExtension());
        if ($extension === 'phtml') {
            $violations[] = "{$pathname}: Magento phtml template in Laravel target";

            continue;
        }

        if (
            ! in_array($extension, ['php', 'json', 'js', 'ts', 'tsx', 'vue', 'css', 'xml'], true)
            && ! str_ends_with($filename, '.blade.php')
        ) {
            continue;
        }

        $files[] = $pathname;
    }
}

sort($files);
foreach ($files as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        $violations[] = "{$file}: unreadable";

        continue;
    }

    if (basename($file) === 'composer.lock') {
        $lock = json_decode($content, true);
        if (is_array($lock)) {
            $packageLines = [];
            foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
                if (! is_array($package)) {
                    continue;
                }

                $packageLines[] = (string) ($package['name'] ?? '');
                foreach (['require', 'require-dev', 'replace', 'provide', 'conflict'] as $section) {
                    foreach (array_keys($package[$section] ?? []) as $dependency) {
                        $packageLines[] = (string) $dependency;
                    }
                }
            }

            $content = implode("\n", $packageLines);
        }
    }

    foreach ($patterns as $label => $pattern) {
        if (preg_match($pattern, $content) === 1) {
            $violations[] = "{$file}: {$label}";
        }
    }
}

if ($violations !== []) {
    fwrite(STDERR, implode("\n", $violations)."\n");
    exit(1);
}

echo 'Removed-technology check passed for '.count($files)." files.\n";
