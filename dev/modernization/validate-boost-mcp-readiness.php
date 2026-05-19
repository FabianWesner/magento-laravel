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
validateBoostMcpToolsList($root, $errors);

if ($final) {
    validateFinalBoostMcpEvidence($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Boost MCP readiness ';
echo $final ? 'final check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateBoostMcpToolsList(string $root, array &$errors): void
{
    $session = runBoostMcpSession($root);

    if ($session['timedOut']) {
        $errors[] = 'Boost MCP tools/list session timed out.';
    }

    if (trim($session['stderr']) !== '') {
        $errors[] = 'Boost MCP tools/list wrote to stderr: '.trim($session['stderr']);
    }

    if ($session['stdout'] === '') {
        $errors[] = 'Boost MCP tools/list returned no stdout.';

        return;
    }

    $responses = decodeJsonRpcResponses($session['stdout'], $errors);
    $initialize = responseById($responses, 1);
    $toolsList = responseById($responses, 2);

    if ($initialize === null) {
        $errors[] = 'Boost MCP initialize response is missing.';
    } else {
        validateInitializeResponse($initialize, $errors);
    }

    if ($toolsList === null) {
        $errors[] = 'Boost MCP tools/list response is missing.';

        return;
    }

    validateToolsListResponse($toolsList, $errors);
}

/**
 * @return array{stdout: string, stderr: string, timedOut: bool}
 */
function runBoostMcpSession(string $root): array
{
    $process = proc_open(
        ['php', 'artisan', 'boost:mcp'],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $root
    );

    if (! is_resource($process)) {
        return [
            'stdout' => '',
            'stderr' => 'Unable to start php artisan boost:mcp.',
            'timedOut' => false,
        ];
    }

    $messages = [
        [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-11-25',
                'capabilities' => (object) [],
                'clientInfo' => [
                    'name' => 'modernization-gate',
                    'version' => '1.0.0',
                ],
            ],
        ],
        [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
            'params' => [
                'per_page' => 50,
            ],
        ],
    ];

    foreach ($messages as $message) {
        fwrite($pipes[0], json_encode($message, JSON_THROW_ON_ERROR).PHP_EOL);
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $stdout = '';
    $stderr = '';
    $deadline = microtime(true) + 8.0;
    $timedOut = false;

    while (true) {
        $stdout .= readPipe($pipes[1]);
        $stderr .= readPipe($pipes[2]);

        $status = proc_get_status($process);
        if (! ($status['running'] ?? false)) {
            break;
        }

        if (microtime(true) >= $deadline) {
            $timedOut = true;
            proc_terminate($process);
            break;
        }

        usleep(10000);
    }

    $stdout .= readPipe($pipes[1]);
    $stderr .= readPipe($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return [
        'stdout' => trim($stdout),
        'stderr' => trim($stderr),
        'timedOut' => $timedOut,
    ];
}

function readPipe(mixed $pipe): string
{
    $content = stream_get_contents($pipe);

    return $content === false ? '' : $content;
}

/**
 * @param  list<string>  $errors
 * @return list<array<string, mixed>>
 */
function decodeJsonRpcResponses(string $stdout, array &$errors): array
{
    $responses = [];
    foreach (preg_split('/\R/', trim($stdout)) ?: [] as $line) {
        if (trim($line) === '') {
            continue;
        }

        $decoded = json_decode($line, true);
        if (! is_array($decoded)) {
            $errors[] = 'Boost MCP emitted invalid JSON-RPC line: '.json_last_error_msg();

            continue;
        }

        $responses[] = $decoded;
    }

    return $responses;
}

/**
 * @param  list<array<string, mixed>>  $responses
 * @return array<string, mixed>|null
 */
function responseById(array $responses, int $id): ?array
{
    foreach ($responses as $response) {
        if (($response['id'] ?? null) === $id) {
            return $response;
        }
    }

    return null;
}

/**
 * @param  array<string, mixed>  $response
 * @param  list<string>  $errors
 */
function validateInitializeResponse(array $response, array &$errors): void
{
    if (($response['jsonrpc'] ?? null) !== '2.0') {
        $errors[] = 'Boost MCP initialize response must use JSON-RPC 2.0.';
    }

    if (isset($response['error'])) {
        $errors[] = 'Boost MCP initialize returned an error: '.json_encode($response['error']);

        return;
    }

    $result = $response['result'] ?? null;
    if (! is_array($result)) {
        $errors[] = 'Boost MCP initialize response is missing result.';

        return;
    }

    if (($result['serverInfo']['name'] ?? null) !== 'Laravel Boost') {
        $errors[] = 'Boost MCP initialize must report serverInfo.name Laravel Boost.';
    }

    if (! isset($result['capabilities']['tools'])) {
        $errors[] = 'Boost MCP initialize must advertise tools capability.';
    }
}

/**
 * @param  array<string, mixed>  $response
 * @param  list<string>  $errors
 */
function validateToolsListResponse(array $response, array &$errors): void
{
    if (($response['jsonrpc'] ?? null) !== '2.0') {
        $errors[] = 'Boost MCP tools/list response must use JSON-RPC 2.0.';
    }

    if (isset($response['error'])) {
        $errors[] = 'Boost MCP tools/list returned an error: '.json_encode($response['error']);

        return;
    }

    $tools = $response['result']['tools'] ?? null;
    if (! is_array($tools)) {
        $errors[] = 'Boost MCP tools/list response is missing result.tools.';

        return;
    }

    $toolsByName = [];
    foreach ($tools as $tool) {
        if (is_array($tool) && is_string($tool['name'] ?? null)) {
            $toolsByName[$tool['name']] = $tool;
        }
    }

    $requiredTools = [
        'application-info',
        'database-query',
        'database-schema',
        'get-absolute-url',
        'search-docs',
    ];

    foreach ($requiredTools as $requiredTool) {
        if (! isset($toolsByName[$requiredTool])) {
            $errors[] = "Boost MCP tools/list is missing required tool {$requiredTool}.";
        }
    }

    foreach (['application-info', 'database-query', 'database-schema', 'get-absolute-url'] as $readOnlyTool) {
        if (($toolsByName[$readOnlyTool]['annotations']['readOnlyHint'] ?? null) !== true) {
            $errors[] = "Boost MCP tool {$readOnlyTool} must advertise readOnlyHint.";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalBoostMcpEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/boost-mcp-evidence.md',
        'specs/modernization/runtime-tooling-evidence.md',
        'docs/content/modernization/boost-mcp-evidence.md',
        'docusaurus/docs/developer/boost-mcp.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final Boost MCP readiness requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = "{$path}: unable to read Boost MCP evidence";

        return;
    }

    $requiredPhrases = [
        'MCP Server',
        'Laravel Boost',
        'tools/list',
        'application-info',
        'search-docs',
        'database-schema',
        'database-query',
        'read-only',
        'Reload Steps',
        'Run URL/Log',
        'Status',
    ];

    foreach ($requiredPhrases as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing Boost MCP evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Missing|Gap|Blocked|Unknown|Runtime evidence required)\b/', $content) === 1) {
        $errors[] = "{$path}: final Boost MCP readiness evidence still contains placeholders or blockers";
    }
}

/**
 * @param  list<string>  $paths
 */
function firstExistingPath(array $paths): ?string
{
    foreach ($paths as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}
