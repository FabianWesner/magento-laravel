#!/usr/bin/env php
<?php

declare(strict_types=1);

$paths = array_slice($argv, 1);
if ($paths === [] || in_array('--help', $paths, true) || in_array('-h', $paths, true)) {
    echo "Usage: php dev/modernization/validate-no-new-xml.php <path> [<path> ...]\n";
    echo "Scans provided modernization paths for XML files. Existing legacy Magento XML should not be passed to this check.\n";
    exit($paths === [] ? 2 : 0);
}

$violations = [];
foreach ($paths as $path) {
    if (! file_exists($path)) {
        continue;
    }
    $iterator = is_dir($path)
        ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS))
        : new ArrayIterator([new SplFileInfo($path)]);
    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }
        if (strtolower($file->getExtension()) === 'xml') {
            $violations[] = $file->getPathname();
        }
    }
}

if ($violations !== []) {
    fwrite(STDERR, "New XML files are not allowed in modernization paths:\n");
    foreach ($violations as $file) {
        fwrite(STDERR, "- {$file}\n");
    }
    exit(1);
}

echo "No XML files found in modernization paths.\n";
