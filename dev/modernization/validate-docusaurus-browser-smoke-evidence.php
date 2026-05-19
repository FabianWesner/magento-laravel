#!/usr/bin/env php
<?php

declare(strict_types=1);

$repoRoot = realpath(__DIR__.'/../..');
if ($repoRoot === false) {
    fwrite(STDERR, "Unable to resolve repository root.\n");
    exit(1);
}

$path = $argv[1] ?? 'specs/modernization/docusaurus-browser-smoke-evidence.md';
$absolutePath = realpath($path);

if ($absolutePath === false || ! is_file($absolutePath)) {
    fwrite(STDERR, "Missing Docusaurus browser smoke evidence: {$path}\n");
    exit(1);
}

if ($absolutePath !== $repoRoot && ! str_starts_with($absolutePath, $repoRoot.DIRECTORY_SEPARATOR)) {
    fwrite(STDERR, "Docusaurus browser smoke evidence must stay inside the repository: {$path}\n");
    exit(1);
}

$content = file_get_contents($absolutePath);
if ($content === false) {
    fwrite(STDERR, "Unable to read Docusaurus browser smoke evidence: {$path}\n");
    exit(1);
}

$errors = [];

foreach (requiredPhrases() as $phrase) {
    if (! str_contains($content, $phrase)) {
        $errors[] = "{$path}: missing required evidence phrase '{$phrase}'";
    }
}

validateGeneratedAt($content, $path, $errors);
validateSourceHash($content, $path, $repoRoot, $errors);
validatePageRows($content, $path, $errors);

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo "Docusaurus browser smoke evidence check passed.\n";

/**
 * @return list<string>
 */
function requiredPhrases(): array
{
    return [
        '# Docusaurus Browser Smoke Evidence',
        'Command: node dev/modernization/smoke-docusaurus.mjs --evidence',
        'Base URL: http://127.0.0.1:3012',
        'Browser: Chrome via Playwright',
        'Status: Pass',
        'No browser console warnings or errors were reported.',
    ];
}

/**
 * @param  list<string>  $errors
 */
function validateGeneratedAt(string $content, string $path, array &$errors): void
{
    if (preg_match('/^Generated At:\s*(.+)$/m', $content, $matches) !== 1) {
        $errors[] = "{$path}: missing Generated At timestamp";

        return;
    }

    try {
        $generatedAt = new DateTimeImmutable(trim($matches[1]));
    } catch (Throwable) {
        $errors[] = "{$path}: Generated At timestamp is not parseable";

        return;
    }

    if ($generatedAt->getTimestamp() > time() + 300) {
        $errors[] = "{$path}: Generated At timestamp is in the future";
    }
}

/**
 * @param  list<string>  $errors
 */
function validateSourceHash(string $content, string $path, string $repoRoot, array &$errors): void
{
    if (preg_match('/^Source Hash:\s*(sha256:[a-f0-9]{64})$/m', $content, $matches) !== 1) {
        $errors[] = "{$path}: missing Source Hash";

        return;
    }

    $actualHash = $matches[1];
    $expectedHash = docusaurusSourceHash($repoRoot);

    if ($actualHash !== $expectedHash) {
        $errors[] = "{$path}: Source Hash does not match current Docusaurus smoke inputs";
    }
}

/**
 * @param  list<string>  $errors
 */
function validatePageRows(string $content, string $path, array &$errors): void
{
    $rows = evidenceRows($content);
    $expectedRows = [
        '/' => 'Magento Laravel Documentation | Magento Laravel Modernization',
        '/user/' => 'User Documentation | Magento Laravel Modernization',
        '/developer/' => 'Developer Documentation | Magento Laravel Modernization',
    ];

    foreach ($expectedRows as $pagePath => $expectedTitle) {
        if (! isset($rows[$pagePath])) {
            $errors[] = "{$path}: missing smoke result row for {$pagePath}";

            continue;
        }

        if ($rows[$pagePath]['status'] !== 200) {
            $errors[] = "{$path}: smoke result row for {$pagePath} is not HTTP 200";
        }

        if ($rows[$pagePath]['title'] !== $expectedTitle) {
            $errors[] = "{$path}: smoke result row for {$pagePath} has unexpected title";
        }
    }
}

/**
 * @return array<string, array{status: int, title: string}>
 */
function evidenceRows(string $content): array
{
    $rows = [];
    foreach (preg_split('/\R/', $content) ?: [] as $line) {
        if (preg_match('/^\|\s*`([^`]+)`\s*\|\s*(\d+)\s*\|\s*(.+)\s*\|\s*$/', $line, $matches) !== 1) {
            continue;
        }

        $rows[$matches[1]] = [
            'status' => (int) $matches[2],
            'title' => str_replace(['\\|', '\\\\'], ['|', '\\'], trim($matches[3])),
        ];
    }

    return $rows;
}

function docusaurusSourceHash(string $repoRoot): string
{
    $entries = [
        'docusaurus/docs',
        'docusaurus/src',
        'docusaurus/static',
        'docusaurus/docusaurus.config.js',
        'docusaurus/sidebars.js',
        'docusaurus/package.json',
        'docusaurus/package-lock.json',
        'dev/modernization/smoke-docusaurus.mjs',
    ];

    $files = [];
    foreach ($entries as $entry) {
        array_push($files, ...collectSourceFiles($repoRoot.DIRECTORY_SEPARATOR.$entry));
    }

    usort($files, fn (string $left, string $right): int => relativePath($repoRoot, $left) <=> relativePath($repoRoot, $right));

    $context = hash_init('sha256');
    foreach ($files as $file) {
        hash_update($context, relativePath($repoRoot, $file));
        hash_update($context, "\0");
        hash_update_file($context, $file);
        hash_update($context, "\0");
    }

    return 'sha256:'.hash_final($context);
}

/**
 * @return list<string>
 */
function collectSourceFiles(string $path): array
{
    if (! file_exists($path)) {
        return [];
    }

    if (is_file($path)) {
        return [$path];
    }

    if (! is_dir($path)) {
        return [];
    }

    $files = [];
    $children = scandir($path);
    if ($children === false) {
        return [];
    }

    foreach ($children as $child) {
        if ($child === '.' || $child === '..' || $child === 'build' || $child === 'node_modules') {
            continue;
        }

        array_push($files, ...collectSourceFiles($path.DIRECTORY_SEPARATOR.$child));
    }

    return $files;
}

function relativePath(string $repoRoot, string $path): string
{
    return str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($repoRoot) + 1));
}
