#!/usr/bin/env php
<?php

declare(strict_types=1);

function usage(): void
{
    echo "Usage: DB_DSN='mysql:host=127.0.0.1;dbname=magento' DB_USER=root DB_PASS=secret php dev/modernization/schema-report.php [--format=json|markdown]\n";
    echo "Optional: DB_TABLE_PREFIX=prefix_\n";
}

$format = 'markdown';
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--help' || $arg === '-h') {
        usage();
        exit(0);
    }
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, 9);
    }
}

$dsn = getenv('DB_DSN') ?: '';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';
$prefix = getenv('DB_TABLE_PREFIX') ?: '';

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
        if ($prefix !== '' && !str_starts_with((string) $table, $prefix)) {
            continue;
        }
        $columns = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', (string) $table) . '`')->fetchAll();
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
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

echo "# Schema Report\n\n";
echo "- Tables: {$report['table_count']}\n";
echo "- Schema signature: `{$report['schema_signature']}`\n\n";
echo "| Table | Columns | Signature |\n";
echo "| --- | ---: | --- |\n";
foreach ($report['tables'] as $table) {
    echo "| `{$table['table']}` | {$table['columns']} | `{$table['signature']}` |\n";
}

