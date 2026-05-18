#!/usr/bin/env php
<?php

declare(strict_types=1);

$paths = array_slice($argv, 1);
if ($paths === []) {
    $paths = ['docs/content/modernization', 'specs', 'docusaurus/docs'];
}

$files = [];
foreach ($paths as $path) {
    if (is_file($path) && str_ends_with($path, '.md')) {
        $files[] = $path;

        continue;
    }
    if (is_dir($path)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.md')) {
                $files[] = $file->getPathname();
            }
        }
    }
}
sort($files);

$errors = [];
foreach ($files as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        $errors[] = "{$file}: unreadable";

        continue;
    }
    if (substr_count($content, '```') % 2 !== 0) {
        $errors[] = "{$file}: unbalanced fenced code blocks";
    }
    if (preg_match('/[^\x00-\x7F]/', $content) === 1) {
        $errors[] = "{$file}: contains non-ASCII characters";
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Markdown checks passed for '.count($files)." files.\n";
