#!/usr/bin/env php
<?php

declare(strict_types=1);

function usage(): void
{
    echo "Usage: DB_DSN='mysql:host=127.0.0.1;dbname=magento' DB_USER=root DB_PASS=secret php dev/modernization/schema-report.php [--format=json|markdown] [--evidence=path]\n";
    echo "Optional: DB_TABLE_PREFIX=prefix_\n";
}

$format = 'markdown';
$evidencePath = null;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--help' || $arg === '-h') {
        usage();
        exit(0);
    }
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, 9);

        continue;
    }
    if (str_starts_with($arg, '--evidence=')) {
        $evidencePath = substr($arg, 11);
    }
}

$dsn = getenv('DB_DSN') ?: '';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';
$prefix = getenv('DB_TABLE_PREFIX') ?: '';

function repoRoot(): string
{
    $repoRoot = realpath(__DIR__.'/../..');
    if ($repoRoot === false) {
        throw new RuntimeException('Unable to resolve repository root.');
    }

    return $repoRoot;
}

function resolveEvidencePath(string $path): string
{
    $repoRoot = repoRoot();
    $directory = dirname($path);
    if ($directory !== '' && $directory !== '.' && ! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
        throw new RuntimeException("Unable to create evidence directory: {$directory}");
    }

    $absoluteDirectory = realpath($directory === '' ? '.' : $directory);
    if ($absoluteDirectory === false) {
        throw new RuntimeException("Unable to resolve evidence directory: {$directory}");
    }

    $absolutePath = $absoluteDirectory.DIRECTORY_SEPARATOR.basename($path);
    if ($absolutePath !== $repoRoot && ! str_starts_with($absolutePath, $repoRoot.DIRECTORY_SEPARATOR)) {
        throw new RuntimeException("Evidence path must stay inside the repository: {$path}");
    }

    return $absolutePath;
}

/**
 * @param  array{table_count: int, schema_signature: string, tables: list<array{table: string, columns: int, signature: string}>}  $report
 */
function renderMarkdownReport(array $report): string
{
    $lines = [
        '# Schema Report',
        '',
        "- Tables: {$report['table_count']}",
        "- Schema signature: `{$report['schema_signature']}`",
        '',
        '| Table | Columns | Signature |',
        '| --- | ---: | --- |',
    ];

    foreach ($report['tables'] as $table) {
        $lines[] = "| `{$table['table']}` | {$table['columns']} | `{$table['signature']}` |";
    }

    $lines[] = '';

    return implode("\n", $lines);
}

/**
 * @param  array{table_count: int, schema_signature: string, tables: list<array{table: string, columns: int, signature: string}>}  $report
 */
function writeMarkdownEvidence(string $path, array $report, string $body): void
{
    $absolutePath = resolveEvidencePath($path);
    $evidence = implode("\n", [
        '# Sample Schema Report Evidence',
        '',
        'Generated At: '.gmdate(DateTimeInterface::ATOM),
        'Command: php dev/modernization/schema-report.php --format=markdown --evidence '.$path,
        'Evidence Scope: Local Magento sample data schema only; this is not final project schema preservation evidence.',
        'Status: Sample-only schema signal',
        '',
        $body,
        '## Notes',
        '',
        'This evidence records the local sample database schema signal only. It does not close schema preservation, fixture manifest, restore evidence, project overlay, sanitized project DB/media, CI restore, or final release defects.',
        '',
    ]);

    if (file_put_contents($absolutePath, $evidence) === false) {
        throw new RuntimeException("Unable to write schema evidence: {$path}");
    }

    fwrite(STDERR, "WROTE: {$path}\n");
}

if ($dsn === '') {
    fwrite(STDERR, "DB_DSN is required.\n");
    usage();
    exit(2);
}

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    sort($tables);
    $details = [];
    foreach ($tables as $table) {
        if ($prefix !== '' && ! str_starts_with((string) $table, $prefix)) {
            continue;
        }
        $columns = $pdo->query('SHOW COLUMNS FROM `'.str_replace('`', '``', (string) $table).'`')->fetchAll();
        $signature = hash('sha256', json_encode($columns, JSON_UNESCAPED_SLASHES));
        $details[] = [
            'table' => $table,
            'columns' => count($columns),
            'signature' => $signature,
        ];
    }
    $report = [
        'table_count' => count($details),
        'schema_signature' => hash('sha256', json_encode($details, JSON_UNESCAPED_SLASHES)),
        'tables' => $details,
    ];
} catch (Throwable $throwable) {
    fwrite(STDERR, "Schema report failed: {$throwable->getMessage()}\n");
    exit(1);
}

if ($format === 'json') {
    if ($evidencePath !== null) {
        fwrite(STDERR, "--evidence is only supported with --format=markdown.\n");
        exit(1);
    }

    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    exit(0);
}

if ($format !== 'markdown') {
    fwrite(STDERR, "Unsupported format: {$format}\n");
    exit(1);
}

$body = renderMarkdownReport($report);

if ($evidencePath !== null) {
    try {
        writeMarkdownEvidence($evidencePath, $report, $body);
    } catch (Throwable $throwable) {
        fwrite(STDERR, "Schema evidence failed: {$throwable->getMessage()}\n");
        exit(1);
    }
}

echo $body;
