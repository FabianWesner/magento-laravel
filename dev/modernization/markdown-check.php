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
    array_push($errors, ...collectLocalLinkErrors($file, $content));
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Markdown checks passed for '.count($files)." files.\n";

/**
 * @return list<string>
 */
function collectLocalLinkErrors(string $file, string $content): array
{
    $linkCount = preg_match_all('/!?\[[^\]\n]*\]\((?<target>[^)\s]+)(?:\s+["\'][^"\']*["\'])?\)/', $content, $matches);
    if ($linkCount === false || $linkCount === 0) {
        return [];
    }

    $errors = [];
    foreach ($matches['target'] as $target) {
        $localPath = normalizeMarkdownLinkTarget($target);
        if ($localPath === null) {
            continue;
        }

        $resolvedPath = dirname($file).DIRECTORY_SEPARATOR.$localPath;
        if (! file_exists($resolvedPath)) {
            $errors[] = "{$file}: broken local link {$target}";
        }
    }

    return $errors;
}

function normalizeMarkdownLinkTarget(string $target): ?string
{
    $target = trim($target);
    if ($target === '') {
        return null;
    }

    $target = trim($target, '<>');
    if ($target === '' || str_starts_with($target, '#') || str_starts_with($target, '/')) {
        return null;
    }

    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $target) === 1) {
        return null;
    }

    $path = preg_split('/[#?]/', $target, 2)[0] ?? '';
    if ($path === '') {
        return null;
    }

    return rawurldecode($path);
}
