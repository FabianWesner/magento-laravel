#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateRouteFallbackSpecs($errors);

if ($final) {
    validateFinalRouteFallbackImplementation($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Route fallback target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateRouteFallbackSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/adr/0001-use-laravel-runtime.md' => [
            'Route ownership must be explicit to avoid unclear behavior.',
            'Route ownership tests.',
        ],
        'specs/adr/0005-use-route-strangler.md' => [
            'Use Route-By-Route Strangler Migration',
            'Laravel owns selected migrated routes while legacy Magento handles unmigrated routes through fallback.',
            'Compatibility adapters and route ownership tracking are required.',
            'Sessions, store scope, URL rewrites, and errors must work across both runtimes.',
        ],
        'specs/adr/0008-route-fallback-auth-session-boundary.md' => [
            'Route Fallback Auth And Session Boundary',
            'Customer session sharing or explicit non-sharing.',
            'Admin session sharing or explicit non-sharing.',
            'Cookie names, domains, paths, secure flags, same-site policy, and lifetime.',
            'CSRF and Magento form-key compatibility.',
            'Password hash verification and upgrade strategy.',
            'Rollback behavior when traffic moves between Laravel and Magento.',
            'Cross-runtime tests for authenticated storefront, admin, cart, checkout, API, and permission-denied flows.',
        ],
        'specs/security.md' => [
            'No route fallback implementation may start until ADR 0008 is approved and its cross-runtime auth/session tests are defined.',
        ],
        'specs/modernization/architecture-specs.md' => [
            'Every route has a declared owner: Laravel, legacy, or bridge.',
            'Add a route fallback so Laravel can own selected routes while legacy handles unmigrated routes.',
            'How Laravel and legacy routes coexist.',
            'Route ownership, fallback, URL rewrites, admin frontname, store scope.',
        ],
        'specs/modernization/proof-of-concepts.md' => [
            'POC 6: Route Fallback',
            'Goal: prove Laravel can own selected routes while legacy handles unmigrated routes.',
            'Laravel route group and legacy route group coexist.',
            'Route ownership is observable in logs.',
        ],
        'specs/modernization/roadmap.md' => [
            'Phase 6: HTTP Routing And Compatibility Layer',
            'Implement route fallback from Laravel to legacy Magento for unmigrated routes.',
            'Add route-level metrics and logging.',
            'Add feature flags for moving route groups from legacy to Laravel.',
        ],
        'specs/modernization/release-strategy.md' => [
            'Use route-by-route strangler migration with feature flags and route ownership metadata.',
            'Legacy route fallback remains available until the migrated route has passed staging and production observation.',
            'Feature flags can move traffic back to legacy routes.',
            'ADR 0008 defines auth, session, cookie, CSRF/form-key, password-hash, and cross-runtime rollback behavior before fallback is enabled.',
        ],
        'docs/content/modernization/index.md' => [
            'Migration proceeds route by route with fallback until parity is proven.',
        ],
        'docusaurus/docs/developer/index.md' => [
            'Laravel bootstrap and route strangler.',
        ],
        'docusaurus/docs/developer/module-development.md' => [
            'Routes for storefront, admin, API, and fallback behavior.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing route fallback planning phrase '{$phrase}'";
            }
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalRouteFallbackImplementation(array &$errors): void
{
    validateAuthSessionBoundaryApproval($errors);
    validateRouteOwnershipMetadata($errors);
    validateFallbackRouteImplementation($errors);
    validateRouteFallbackTests($errors);
    validateRouteFallbackEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateAuthSessionBoundaryApproval(array &$errors): void
{
    $path = 'specs/adr/0008-route-fallback-auth-session-boundary.md';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    if (preg_match('/## Status\s+(?:Approved|Accepted)\b/is', $content) !== 1) {
        $errors[] = "{$path}: final route fallback requires ADR 0008 status to be Approved or Accepted";
    }
}

/**
 * @param  list<string>  $errors
 */
function validateRouteOwnershipMetadata(array &$errors): void
{
    $candidatePaths = [
        'laravel/config/route_ownership.php',
        'laravel/config/modernization_routes.php',
        'laravel/routes/ownership.php',
        'laravel/routes/legacy.php',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final route fallback implementation requires route ownership metadata at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (['Laravel', 'legacy', 'bridge', 'feature', 'owner', 'rollback'] as $phrase) {
        if (! containsCaseInsensitive($content, $phrase)) {
            $errors[] = "{$path}: missing route ownership metadata phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFallbackRouteImplementation(array &$errors): void
{
    $routeFiles = phpFilesUnder('laravel/routes');
    $appFiles = phpFilesUnder('laravel/app');

    if (! filesContain($routeFiles, '/Route::fallback\s*\(/')) {
        $errors[] = 'Final route fallback implementation requires Route::fallback registration in laravel/routes';
    }

    $hasLegacyFallbackArtifact = false;
    foreach ($appFiles as $file) {
        $content = file_get_contents($file);
        if ($content === false) {
            continue;
        }

        if (preg_match('/Legacy.*Fallback|Fallback.*Legacy|RouteOwnership|RouteOwner|Strangler/i', $file.$content) === 1) {
            $hasLegacyFallbackArtifact = true;
            break;
        }
    }

    if (! $hasLegacyFallbackArtifact) {
        $errors[] = 'Final route fallback implementation requires a Laravel app artifact for legacy fallback or route ownership handling';
    }

    if (! filesContain(array_merge($routeFiles, $appFiles), '/Log::|logger\(|Log::withContext|context\(/')) {
        $errors[] = 'Final route fallback implementation requires route ownership or fallback logging context';
    }
}

/**
 * @param  list<string>  $errors
 */
function validateRouteFallbackTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $coverage = [
        'route ownership' => false,
        'fallback to legacy' => false,
        'store scope or URL rewrites' => false,
        'admin frontname' => false,
        'customer or admin sessions' => false,
        'CSRF or form keys' => false,
        'rollback or feature flags' => false,
        'logging or observability' => false,
    ];

    foreach ($testFiles as $testFile) {
        $content = file_get_contents($testFile);
        if ($content === false || preg_match('/route|fallback|strangler|legacy/i', $content.$testFile) !== 1) {
            continue;
        }

        $coverage['route ownership'] = $coverage['route ownership'] || preg_match('/route ownership|route owner|owner/i', $content) === 1;
        $coverage['fallback to legacy'] = $coverage['fallback to legacy'] || preg_match('/fallback|legacy/i', $content) === 1;
        $coverage['store scope or URL rewrites'] = $coverage['store scope or URL rewrites'] || preg_match('/store scope|store code|URL rewrite|url rewrite/i', $content) === 1;
        $coverage['admin frontname'] = $coverage['admin frontname'] || preg_match('/admin frontname|admin URL|admin url|frontname/i', $content) === 1;
        $coverage['customer or admin sessions'] = $coverage['customer or admin sessions'] || preg_match('/customer session|admin session|session/i', $content) === 1;
        $coverage['CSRF or form keys'] = $coverage['CSRF or form keys'] || preg_match('/CSRF|form key|form-key|csrf/i', $content) === 1;
        $coverage['rollback or feature flags'] = $coverage['rollback or feature flags'] || preg_match('/rollback|feature flag|flag/i', $content) === 1;
        $coverage['logging or observability'] = $coverage['logging or observability'] || preg_match('/log|metric|observable|observability/i', $content) === 1;
    }

    foreach ($coverage as $label => $covered) {
        if (! $covered) {
            $errors[] = "Final route fallback implementation requires PHPUnit coverage for {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateRouteFallbackEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/route-fallback-evidence.md',
        'docs/content/modernization/route-fallback-evidence.md',
        'docusaurus/docs/developer/route-fallback.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final route fallback implementation requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (['Route Ownership', 'Fallback', 'Feature Flag', 'Store Scope', 'URL Rewrite', 'Admin Frontname', 'Session', 'CSRF', 'Password Hash', 'Rollback', 'Log', 'Test', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing route fallback evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required)\b/', $content) === 1) {
        $errors[] = "{$path}: final route fallback evidence still contains placeholders";
    }
}

/**
 * @return list<string>
 */
function phpFilesUnder(string $directory): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        if (str_ends_with($path, '.php')) {
            $files[] = $path;
        }
    }

    sort($files);

    return $files;
}

/**
 * @param  list<string>  $files
 */
function filesContain(array $files, string $pattern): bool
{
    foreach ($files as $file) {
        $content = file_get_contents($file);
        if ($content !== false && preg_match($pattern, $content) === 1) {
            return true;
        }
    }

    return false;
}

function containsCaseInsensitive(string $content, string $needle): bool
{
    return stripos($content, $needle) !== false;
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
