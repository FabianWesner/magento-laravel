#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateSourceDependencySpecs($errors);
validateKnownDependencyManifests($errors);
validateRepositoryMetadata($errors);

if ($final) {
    validateFinalSourceDependencyReadiness($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Source/dependency target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateSourceDependencySpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Verify Magento CE 1.9.4.5 source, project overlay, generated docroot, Docker/local runtime, sample/project DB, media, PHP versions, Composer/npm dependencies, remotes, and CI.',
            'Laravel target must run on the latest stable PHP available in this environment and CI; legacy Magento PHP 7.4 may remain isolated only for baseline smoke verification.',
            'The refactored Laravel runtime must completely remove the banned technologies listed in specs/modernization/technology-removal-policy.md.',
        ],
        'specs/modernization/workspace-layout.md' => [
            'Record remotes, branches, commit SHAs, and Composer lock hashes for all relevant roots.',
            'Project dependency credentials | Private Composer packages or registries | Unknown.',
            'Project repository | Custom modules, themes, integrations, deployment scripts | Missing.',
        ],
        'specs/modernization/inventory.md' => [
            'Run this inventory against the actual project codebase, not only this source checkout.',
            'Composer packages | Project-specific Composer packages, private packages, patches, replace/conflict rules.',
            'Project-Specific Inventory Required',
            'Every integration has a sandbox/testing strategy.',
        ],
        'specs/modernization/install-verification.md' => [
            'Composer PHP platform',
            'Dependencies',
            'Project private Composer packages.',
            'Project-specific Composer repositories or package credentials, if any.',
            'Host PHP',
        ],
        'specs/modernization/test-plan.md' => [
            'Prove the Laravel target runs on the latest stable PHP and supported Composer dependencies.',
            'Dependency safety | Composer audit and known-vulnerability checks.',
            'Runtime tests | Prove the target Laravel runtime uses the latest stable PHP and compatible Composer packages.',
            'Composer platform check',
            'Composer audit',
        ],
        'specs/modernization/roadmap.md' => [
            'Capture current dependency state from `composer.json`, `composer.lock`, vendor patches, PHP version, extensions, and web server config.',
            'Latest stable PHP is available in local tooling and CI for the Laravel target.',
            'Composer dependency constraints reviewed for conflicts.',
            'dependency audit',
        ],
        'specs/modernization/technology-removal-policy.md' => [
            'Composer and npm dependency manifests do not include banned runtime dependencies.',
            'Composer/package scan',
            'Composer scan for Zend/Laminas framework packages',
        ],
        'specs/modernization/backlog.md' => [
            'Add real project code locally',
            'Verify Laravel Boost installability',
            'Project overlay and Magento CE `1.9.4.5` source are available in one workspace.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing source/dependency planning phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateKnownDependencyManifests(array &$errors): void
{
    $composer = readJsonFile('laravel/composer.json', $errors);
    if ($composer !== null) {
        $phpConstraint = $composer['require']['php'] ?? null;
        if (! is_string($phpConstraint) || ! str_contains($phpConstraint, '8.5')) {
            $errors[] = 'laravel/composer.json: require.php must target PHP 8.5';
        }

        if (! isset($composer['config']['platform']['php'])) {
            $errors[] = 'laravel/composer.json: config.platform.php must pin the target PHP patch version';
        }
    }

    readJsonFile('laravel/composer.lock', $errors);
    readJsonFile('laravel/package.json', $errors);
    readJsonFile('docusaurus/package.json', $errors);
    readJsonFile('docusaurus/package-lock.json', $errors);
}

/**
 * @param  list<string>  $errors
 */
function validateRepositoryMetadata(array &$errors): void
{
    if (! is_dir('.git')) {
        $errors[] = 'Repository root must include git metadata so remotes, branches, and commit SHAs can be recorded.';

        return;
    }

    $head = runCommand(['git', 'rev-parse', 'HEAD']);
    if ($head['exitCode'] !== 0 || preg_match('/^[a-f0-9]{40}$/', trim($head['stdout'])) !== 1) {
        $errors[] = 'git rev-parse HEAD must return a commit SHA.';
    }

    $branch = runCommand(['git', 'branch', '--show-current']);
    if ($branch['exitCode'] !== 0 || trim($branch['stdout']) === '') {
        $errors[] = 'git branch --show-current must return the active branch.';
    }

    $remotes = runCommand(['git', 'remote', '-v']);
    if ($remotes['exitCode'] !== 0 || trim($remotes['stdout']) === '') {
        $errors[] = 'git remote -v must return at least one remote.';
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalSourceDependencyReadiness(array &$errors): void
{
    validateFinalSourceDependencyEvidence($errors);
    validateFinalPackageLockCoverage($errors);
    validateFinalAuditEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateFinalSourceDependencyEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/source-dependency-inventory.md',
        'specs/modernization/dependency-inventory.md',
        'docs/content/modernization/dependency-inventory.md',
        'docusaurus/docs/developer/dependency-inventory.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final source/dependency readiness requires inventory evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    $requiredPhrases = [
        'Repository Root',
        'Remote URL',
        'Branch',
        'Commit SHA',
        'Clean Worktree',
        'Project Overlay',
        'Composer Manifest',
        'Composer Lock Hash',
        'Composer Repositories',
        'Private Package',
        'npm Manifest',
        'npm Lock Hash',
        'Node Version',
        'PHP Version',
        'PHP Extensions',
        'Dependency Audit',
        'Banned Dependency Scan',
        'CI Evidence',
        'Status',
    ];

    foreach ($requiredPhrases as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing source/dependency evidence phrase '{$phrase}'";
        }
    }

    foreach (['core/magento-1.9.4.5', 'project/', 'laravel/', 'docusaurus/'] as $root) {
        if (! str_contains($content, $root)) {
            $errors[] = "{$path}: missing repository/dependency root '{$root}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Missing|Unknown|Blocked|To inventory|Required where applicable)\b/', $content) === 1) {
        $errors[] = "{$path}: final source/dependency evidence still contains placeholders or blockers";
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalPackageLockCoverage(array &$errors): void
{
    foreach (packageJsonFiles() as $packageJson) {
        $directory = dirname($packageJson);
        if (! hasNodeLockfile($directory)) {
            $errors[] = "{$packageJson}: final source/dependency readiness requires a package lockfile in the same package root";
        }
    }

    foreach (composerJsonFiles() as $composerJson) {
        $lockPath = dirname($composerJson).'/composer.lock';
        if (! is_file($lockPath)) {
            $errors[] = "{$composerJson}: final source/dependency readiness requires composer.lock";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalAuditEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/dependency-audit-evidence.md',
        'docs/content/modernization/dependency-audit-evidence.md',
        'docusaurus/docs/developer/dependency-audit.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final source/dependency readiness requires dependency audit evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    $requiredPhrases = [
        'Composer Validate',
        'Composer Audit',
        'npm Audit',
        'Banned Dependency Scan',
        'Zend',
        'Laminas',
        'Magento',
        'Varien',
        'Prototype',
        'CI Run',
        'Status',
    ];

    foreach ($requiredPhrases as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing dependency audit evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Missing|Unknown|Blocked|Critical|High)\b/', $content) === 1) {
        $errors[] = "{$path}: final dependency audit evidence still contains placeholders, blockers, or unresolved high-risk findings";
    }
}

/**
 * @return list<string>
 */
function packageJsonFiles(): array
{
    return filesByName('package.json', ['.localdev', '.git', 'node_modules', 'vendor', 'site', 'build']);
}

/**
 * @return list<string>
 */
function composerJsonFiles(): array
{
    return filesByName('composer.json', ['.localdev', '.git', 'vendor', 'dev/modernization/fixtures']);
}

/**
 * @param  list<string>  $excludedSegments
 * @return list<string>
 */
function filesByName(string $filename, array $excludedSegments): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.', FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getFilename() !== $filename) {
            continue;
        }

        $path = normalizePath($file->getPathname());
        if (pathContainsAnySegment($path, $excludedSegments)) {
            continue;
        }

        $files[] = ltrim($path, './');
    }

    sort($files);

    return $files;
}

/**
 * @param  list<string>  $segments
 */
function pathContainsAnySegment(string $path, array $segments): bool
{
    foreach ($segments as $segment) {
        if (str_contains($path, '/'.$segment.'/') || str_starts_with($path, './'.$segment.'/') || str_starts_with($path, $segment.'/')) {
            return true;
        }
    }

    return false;
}

function hasNodeLockfile(string $directory): bool
{
    foreach (['package-lock.json', 'npm-shrinkwrap.json', 'pnpm-lock.yaml', 'yarn.lock'] as $lockfile) {
        if (is_file($directory.'/'.$lockfile)) {
            return true;
        }
    }

    return false;
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

/**
 * @param  list<string>  $errors
 */
function readTextFile(string $path, array &$errors): ?string
{
    if (! is_file($path)) {
        $errors[] = "{$path}: missing file";

        return null;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = "{$path}: unable to read file";

        return null;
    }

    return $content;
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

function normalizePath(string $path): string
{
    return str_replace(DIRECTORY_SEPARATOR, '/', $path);
}
