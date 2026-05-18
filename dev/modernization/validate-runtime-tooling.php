#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$root = getcwd();
if ($root === false) {
    fwrite(STDERR, "Unable to determine working directory.\n");
    exit(1);
}

$errors = [];
validateLaravelComposer($errors);
validateComposerLock($errors);
validateRootArtisanProxy($root, $errors);
validateMcpConfiguration($root, $errors);
validateInstallVerification($errors);
validateRuntimeCommands($errors);

if ($final) {
    validateFinalRuntimeEvidence($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Runtime tooling ';
echo $final ? 'final check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateLaravelComposer(array &$errors): void
{
    $path = 'laravel/composer.json';
    $composer = readJsonFile($path, $errors);
    if ($composer === null) {
        return;
    }

    $requiredPackages = [
        'php' => '/\^8\.5/',
        'laravel/framework' => '/\^13\./',
    ];

    foreach ($requiredPackages as $package => $pattern) {
        $version = $composer['require'][$package] ?? null;
        if (! is_string($version) || preg_match($pattern, $version) !== 1) {
            $errors[] = "{$path}: require.{$package} must match {$pattern}";
        }
    }

    $requiredDevPackages = [
        'laravel/boost' => '/\^2\./',
        'laravel/pail' => '/\^1\./',
        'laravel/pint' => '/\^1\./',
        'phpunit/phpunit' => '/\^12\./',
    ];

    foreach ($requiredDevPackages as $package => $pattern) {
        $version = $composer['require-dev'][$package] ?? null;
        if (! is_string($version) || preg_match($pattern, $version) !== 1) {
            $errors[] = "{$path}: require-dev.{$package} must match {$pattern}";
        }
    }

    $platformPhp = $composer['config']['platform']['php'] ?? null;
    if (! is_string($platformPhp) || preg_match('/^8\.5\.\d+$/', $platformPhp) !== 1) {
        $errors[] = "{$path}: config.platform.php must pin an 8.5.x runtime";
    }

    $testScript = $composer['scripts']['test'] ?? null;
    if (! is_array($testScript) || ! in_array('@php artisan test', $testScript, true)) {
        $errors[] = "{$path}: scripts.test must include '@php artisan test'";
    }
}

/**
 * @param  list<string>  $errors
 */
function validateComposerLock(array &$errors): void
{
    $path = 'laravel/composer.lock';
    $lock = readJsonFile($path, $errors);
    if ($lock === null) {
        return;
    }

    $packages = [];
    foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
        if (is_array($package) && isset($package['name'], $package['version'])) {
            $packages[(string) $package['name']] = (string) $package['version'];
        }
    }

    $requiredLockedPackages = [
        'laravel/framework' => '/^v?13\./',
        'laravel/prompts' => '/^v?0\./',
        'laravel/boost' => '/^v?2\./',
        'laravel/mcp' => '/^v?0\./',
        'laravel/pail' => '/^v?1\./',
        'laravel/pint' => '/^v?1\./',
        'phpunit/phpunit' => '/^v?12\./',
    ];

    foreach ($requiredLockedPackages as $package => $pattern) {
        $version = $packages[$package] ?? null;
        if ($version === null || preg_match($pattern, $version) !== 1) {
            $errors[] = "{$path}: locked {$package} must match {$pattern}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateRootArtisanProxy(string $root, array &$errors): void
{
    $path = 'artisan';
    if (! is_file($path) || ! is_executable($path)) {
        $errors[] = "{$path}: repository root artisan proxy must exist and be executable";

        return;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = "{$path}: unable to read repository root artisan proxy";

        return;
    }

    $requiredSnippets = [
        '#!/usr/bin/env php',
        'declare(strict_types=1);',
        "'/laravel/artisan'",
        'PHP85_BIN',
        'php85',
        'PHP_VERSION_ID < 80500',
        "chdir(\$root . '/laravel');",
    ];

    foreach ($requiredSnippets as $snippet) {
        if (! str_contains($content, $snippet)) {
            $errors[] = "{$path}: missing root artisan proxy snippet '{$snippet}'";
        }
    }

    if (! is_file($root.'/laravel/artisan')) {
        $errors[] = "{$path}: laravel/artisan target is missing";
    }
}

/**
 * @param  list<string>  $errors
 */
function validateMcpConfiguration(string $root, array &$errors): void
{
    $jsonConfigs = [
        '.mcp.json',
        '.cursor/mcp.json',
        '.junie/mcp/mcp.json',
        'laravel/.mcp.json',
        'laravel/.cursor/mcp.json',
        'laravel/.junie/mcp/mcp.json',
    ];

    foreach ($jsonConfigs as $path) {
        $config = readJsonFile($path, $errors);
        if ($config === null) {
            continue;
        }

        $server = $config['mcpServers']['laravel-boost'] ?? null;
        if (! is_array($server)) {
            $errors[] = "{$path}: missing mcpServers.laravel-boost";

            continue;
        }

        $command = (string) ($server['command'] ?? '');
        $args = array_values(array_map('strval', $server['args'] ?? []));
        if (! commandStartsBoostMcp($root, $command, $args)) {
            $errors[] = "{$path}: laravel-boost server must invoke boost:mcp through the root artisan proxy or the PHP 8.5 Laravel artisan";
        }
    }

    $tomlConfigs = [
        '.codex/config.toml',
        'laravel/.codex/config.toml',
    ];

    foreach ($tomlConfigs as $path) {
        if (! is_file($path)) {
            $errors[] = "{$path}: missing Codex MCP configuration";

            continue;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            $errors[] = "{$path}: unable to read Codex MCP configuration";

            continue;
        }

        if (! str_contains($content, 'laravel-boost') || ! str_contains($content, 'boost:mcp')) {
            $errors[] = "{$path}: Codex MCP configuration must register laravel-boost boost:mcp";
        }
    }
}

/**
 * @param  list<string>  $args
 */
function commandStartsBoostMcp(string $root, string $command, array $args): bool
{
    if (! in_array('boost:mcp', $args, true)) {
        return false;
    }

    if ($command === $root.'/artisan') {
        return true;
    }

    if (str_ends_with($command, '/php85') && in_array($root.'/laravel/artisan', $args, true)) {
        return true;
    }

    if ($command === 'php' && in_array('artisan', $args, true)) {
        return true;
    }

    return false;
}

/**
 * @param  list<string>  $errors
 */
function validateInstallVerification(array &$errors): void
{
    $path = 'specs/modernization/install-verification.md';
    if (! is_file($path)) {
        $errors[] = "{$path}: missing install verification evidence";

        return;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = "{$path}: unable to read install verification evidence";

        return;
    }

    $requiredPhrases = [
        'Runtime PHP',
        'Laravel Boost MCP Status',
        '`boost:mcp` command',
        'Manual MCP `tools/list`',
        'Manual MCP `application-info`',
        'Manual MCP `search-docs`',
        'Manual MCP `database-schema`',
        'Manual MCP `database-query`',
        'Composer PHP platform',
        'If the active Codex session still shows no Laravel Boost tools',
    ];

    foreach ($requiredPhrases as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing runtime/tooling evidence phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateRuntimeCommands(array &$errors): void
{
    $version = runCommand(['php', 'artisan', '--version']);
    if ($version['exitCode'] !== 0 || ! str_contains($version['stdout'], 'Laravel Framework 13.')) {
        $errors[] = "php artisan --version must report Laravel Framework 13.x; got exit {$version['exitCode']} with output: ".trim($version['stdout'].$version['stderr']);
    }

    $applicationInfo = runBoostTool('Laravel\\Boost\\Mcp\\Tools\\ApplicationInfo', []);
    if ($applicationInfo['isError']) {
        $errors[] = 'Boost application-info failed: '.$applicationInfo['error'];
    } else {
        validateApplicationInfo($applicationInfo['text'], $errors);
    }

    $databaseQuery = runBoostTool('Laravel\\Boost\\Mcp\\Tools\\DatabaseQuery', ['query' => 'select 1 as ok']);
    if ($databaseQuery['isError']) {
        $errors[] = 'Boost database-query failed: '.$databaseQuery['error'];
    } else {
        $rows = json_decode($databaseQuery['text'], true);
        if (! is_array($rows) || ($rows[0]['ok'] ?? null) !== 1) {
            $errors[] = 'Boost database-query did not return [{"ok":1}]';
        }
    }

    $databaseSchema = runBoostTool('Laravel\\Boost\\Mcp\\Tools\\DatabaseSchema', ['summary' => true]);
    if ($databaseSchema['isError']) {
        $errors[] = 'Boost database-schema failed: '.$databaseSchema['error'];
    } else {
        $schema = json_decode($databaseSchema['text'], true);
        if (! is_array($schema) || ($schema['engine'] ?? null) !== 'sqlite' || ! isset($schema['tables']['migrations'])) {
            $errors[] = 'Boost database-schema must report the Laravel SQLite schema including migrations';
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateApplicationInfo(string $text, array &$errors): void
{
    $info = json_decode($text, true);
    if (! is_array($info)) {
        $errors[] = 'Boost application-info returned invalid JSON';

        return;
    }

    if (($info['php_version'] ?? '') !== '8.5') {
        $errors[] = 'Boost application-info must report php_version 8.5';
    }

    if (! is_string($info['laravel_version'] ?? null) || ! str_starts_with($info['laravel_version'], '13.')) {
        $errors[] = 'Boost application-info must report Laravel 13.x';
    }

    $packages = [];
    foreach ($info['packages'] ?? [] as $package) {
        if (is_array($package) && isset($package['roster_name'], $package['version'])) {
            $packages[(string) $package['roster_name']] = (string) $package['version'];
        }
    }

    $requiredPackages = [
        'LARAVEL' => '/^13\./',
        'PROMPTS' => '/^0\./',
        'BOOST' => '/^2\./',
        'MCP' => '/^0\./',
        'PAIL' => '/^1\./',
        'PINT' => '/^1\./',
        'PHPUNIT' => '/^12\./',
    ];

    foreach ($requiredPackages as $package => $pattern) {
        $version = $packages[$package] ?? null;
        if ($version === null || preg_match($pattern, $version) !== 1) {
            $errors[] = "Boost application-info package {$package} must match {$pattern}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalRuntimeEvidence(array &$errors): void
{
    $path = 'specs/modernization/install-verification.md';
    $content = is_file($path) ? file_get_contents($path) : false;
    if ($content === false) {
        $errors[] = "{$path}: final mode requires install verification evidence";

        return;
    }

    if (preg_match('/\b(?:TBD|Pending runtime evidence|Runtime evidence required)\b/', $content) === 1) {
        $errors[] = "{$path}: final runtime/tooling evidence still contains placeholders";
    }
}

/**
 * @return array{isError: bool, text: string, error: string}
 */
function runBoostTool(string $tool, array $arguments): array
{
    $result = runCommand([
        'php',
        'artisan',
        'boost:execute-tool',
        $tool,
        base64_encode(json_encode($arguments) ?: '[]'),
    ]);

    if ($result['exitCode'] !== 0) {
        return [
            'isError' => true,
            'text' => '',
            'error' => trim($result['stdout'].$result['stderr']),
        ];
    }

    $decoded = json_decode($result['stdout'], true);
    if (! is_array($decoded)) {
        return [
            'isError' => true,
            'text' => '',
            'error' => 'Invalid Boost tool wrapper JSON: '.json_last_error_msg(),
        ];
    }

    $text = (string) ($decoded['content'][0]['text'] ?? '');

    return [
        'isError' => (bool) ($decoded['isError'] ?? true),
        'text' => $text,
        'error' => $text,
    ];
}

/**
 * @param  list<string>  $command
 * @return array{exitCode: int, stdout: string, stderr: string}
 */
function runCommand(array $command): array
{
    $process = proc_open(
        $command,
        [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes
    );

    if (! is_resource($process)) {
        return [
            'exitCode' => 1,
            'stdout' => '',
            'stderr' => 'Unable to start process.',
        ];
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [
        'exitCode' => proc_close($process),
        'stdout' => $stdout === false ? '' : $stdout,
        'stderr' => $stderr === false ? '' : $stderr,
    ];
}

/**
 * @param  list<string>  $errors
 * @return array<string, mixed>|null
 */
function readJsonFile(string $path, array &$errors): ?array
{
    if (! is_file($path)) {
        $errors[] = "{$path}: missing JSON file";

        return null;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = "{$path}: unable to read JSON file";

        return null;
    }

    $decoded = json_decode($content, true);
    if (! is_array($decoded)) {
        $errors[] = "{$path}: invalid JSON: ".json_last_error_msg();

        return null;
    }

    return $decoded;
}
