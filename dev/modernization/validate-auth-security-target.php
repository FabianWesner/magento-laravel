#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateAuthSecuritySpecs($errors);

if ($final) {
    validateFinalAuthSecurityImplementation($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Auth/security target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateAuthSecuritySpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Build Laravel bootstrap, config, module registry, no-XML manifest system, route strangler/fallback, EAV access layer, auth/session/security foundation, scheduler, queue, events, API contracts, and operations hooks.',
            'Every complex feature, especially cart calculation, pricing, promotions, tax, shipping, payment, order lifecycle, indexing, reports, cron, permissions, and EAV writes, must be reverse-engineered and specified before Laravel replacement starts.',
            'API, cron, queue, auth, security, performance, accessibility, deployment, rollback, backup/restore, docs, and operations gates pass.',
        ],
        'specs/security.md' => [
            'storefront authentication',
            'admin authentication',
            'authorization and permissions',
            'sessions and cookies',
            'password hashing and resets',
            'API authentication',
            'Every migrated admin route/action has a gate, policy, or permission manifest entry.',
            'No route fallback implementation may start until ADR 0008 is approved and its cross-runtime auth/session tests are defined.',
        ],
        'specs/adr/0008-route-fallback-auth-session-boundary.md' => [
            'Route Fallback Auth And Session Boundary',
            'Customer session sharing or explicit non-sharing.',
            'Admin session sharing or explicit non-sharing.',
            'Cookie names, domains, paths, secure flags, same-site policy, and lifetime.',
            'CSRF and Magento form-key compatibility.',
            'Password hash verification and upgrade strategy.',
            'Remember-me and persistent cart behavior.',
            'Logout invalidation across runtimes.',
            'Cross-runtime tests for authenticated storefront, admin, cart, checkout, API, and permission-denied flows.',
        ],
        'specs/modernization/architecture-specs.md' => [
            'Auth/security',
            'How customer/admin auth and authorization work.',
            'Sessions, CSRF, password policy, gates/policies, roles.',
            'Admin authorization uses gates, policies, and module-provided permission manifests.',
            'Every migrated admin route requires permission tests.',
        ],
        'specs/modernization/roadmap.md' => [
            'Customer/session/auth architecture.',
            'Implement Laravel admin authentication.',
            'Implement admin authorization using gates/policies/permissions.',
            'Auth/authorization spec.',
            'Permission tests prove unauthorized users cannot access actions.',
            'Implement customer services for accounts, addresses, sessions, passwords, and groups.',
            'Preserve authentication and authorization behavior where compatibility is required.',
        ],
        'specs/modernization/complex-feature-reverse-engineering.md' => [
            'Admin ACL resources and menu visibility.',
            'Form key behavior.',
            'Admin session timeout.',
            'Acceptance requires tests for full, partial, and denied admin roles.',
            'Cart/totals, checkout, payment, tax, shipping, order lifecycle, EAV writes, permissions, and cron jobs all have dual-runtime parity tests.',
        ],
        'specs/modernization/test-plan.md' => [
            'Security tests',
            'Prove auth, authorization, CSRF, sessions, uploads, file access, dependency safety.',
            'Admin permission checks green.',
        ],
        'specs/modernization/data-fixtures.md' => [
            'Admin users | Full admin, catalog-only role, sales-only role, customer-service role, read-only/report role, no-access negative role, API user, API2/OAuth consumer.',
            'Invalid coupons, invalid addresses, expired sessions, denied admin roles, failed payments, unavailable shipping methods, missing media, stale indexes, stale cache, failed email queue, failed cron lock, duplicate order submission attempt, and integration timeout mocks.',
        ],
        'specs/modernization/magento-feature-catalog.md' => [
            'Customer account',
            'Admin shell and auth',
            'Users, roles, API permissions',
            'REST/API2',
        ],
        'docusaurus/docs/user/admin-guide.md' => [
            'Permission-specific behavior for full, partial, read-only, and denied admin roles.',
            'Each retained admin screen is tracked with screenshot evidence, feature IDs, fixtures, and permission tests',
        ],
        'docusaurus/docs/user/storefront-guide.md' => [
            'session expiration',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing auth/security planning phrase '{$phrase}'";
            }
        }
    }

    validateAuthSecurityCatalog($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateAuthSecurityCatalog(array &$errors): void
{
    $path = 'specs/modernization/magento-feature-catalog.md';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (authSecurityFeatureIds() as $featureId) {
        if (! str_contains($content, "| {$featureId} |")) {
            $errors[] = "{$path}: missing auth/security feature row for {$featureId}";
        }
    }

    foreach (['login', 'logout', 'forgot/reset password', 'session timeout', 'ACL denied pages', 'form keys', 'admin roles', 'OAuth'] as $phrase) {
        if (! containsCaseInsensitive($content, $phrase)) {
            $errors[] = "{$path}: missing auth/security catalog phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalAuthSecurityImplementation(array &$errors): void
{
    validateBoundaryApproval($errors);
    validateAuthConfiguration($errors);
    validateCompatibilityConfiguration($errors);
    validateAuthArtifacts($errors);
    validateAuthRoutes($errors);
    validateAuthTests($errors);
    validateAuthEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateBoundaryApproval(array &$errors): void
{
    $path = 'specs/adr/0008-route-fallback-auth-session-boundary.md';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    if (preg_match('/## Status\s+(?:Approved|Accepted)\b/is', $content) !== 1) {
        $errors[] = "{$path}: final auth/security implementation requires ADR 0008 status to be Approved or Accepted";
    }
}

/**
 * @param  list<string>  $errors
 */
function validateAuthConfiguration(array &$errors): void
{
    $authPath = 'laravel/config/auth.php';
    $auth = readTextFile($authPath, $errors);
    if ($auth === null) {
        return;
    }

    $requirements = [
        'customer guard' => '/[\'"]customer[\'"]\s*=>/',
        'admin guard' => '/[\'"]admin[\'"]\s*=>/',
        'customer provider' => '/[\'"]customers[\'"]\s*=>/',
        'admin provider' => '/[\'"]admins[\'"]\s*=>/',
        'customer password broker' => '/[\'"]customers[\'"]\s*=>.*(?:expire|throttle)/is',
        'admin password broker' => '/[\'"]admins[\'"]\s*=>.*(?:expire|throttle)/is',
    ];

    foreach ($requirements as $label => $pattern) {
        if (preg_match($pattern, $auth) !== 1) {
            $errors[] = "{$authPath}: final auth/security implementation requires {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateCompatibilityConfiguration(array &$errors): void
{
    $candidatePaths = [
        'laravel/config/auth_compatibility.php',
        'laravel/config/magento_auth.php',
        'laravel/config/security.php',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final auth/security implementation requires compatibility config at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (['customer', 'admin', 'cookie', 'form key', 'password hash', 'session', 'rollback'] as $phrase) {
        if (! containsCaseInsensitive($content, $phrase)) {
            $errors[] = "{$path}: missing auth/session compatibility phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateAuthArtifacts(array &$errors): void
{
    $appFiles = phpFilesUnder('laravel/app');

    if ($appFiles === []) {
        $errors[] = 'Final auth/security implementation requires Laravel app files';

        return;
    }

    $requirements = [
        'customer authentication artifact' => '/Customer.*Auth|Auth.*Customer|Customer.*Session|Session.*Customer/i',
        'admin authentication artifact' => '/Admin.*Auth|Auth.*Admin|Admin.*Session|Session.*Admin/i',
        'custom guard or user provider' => '/\/Auth\/|Guard|UserProvider|Authenticatable/i',
        'policy or gate artifact' => '/\/Policies\/|Policy\.php$|Gate::|permission|authorize\(/i',
        'permission manifest artifact' => '/Permission.*Manifest|Manifest.*Permission|permissions/i',
        'CSRF or form-key compatibility artifact' => '/FormKey|form key|CSRF|Csrf|VerifyCsrf/i',
        'password hash compatibility artifact' => '/PasswordHash|Hash::check|needsRehash|legacy password|PasswordBroker/i',
        'session or cookie boundary artifact' => '/Session.*Bridge|Bridge.*Session|Cookie|logout|invalidate|regenerate|persistent cart/i',
    ];

    foreach ($requirements as $label => $pattern) {
        if (! pathOrContentMatches($appFiles, $pattern)) {
            $errors[] = "Final auth/security implementation requires {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateAuthRoutes(array &$errors): void
{
    $routeFiles = phpFilesUnder('laravel/routes');
    $routeContent = aggregateFiles($routeFiles);

    if ($routeContent === '') {
        $errors[] = 'Final auth/security implementation requires Laravel route files';

        return;
    }

    $requirements = [
        'customer auth route guard' => '/auth:customer|customer.*login|login.*customer/i',
        'admin auth route guard' => '/auth:admin|admin.*login|login.*admin/i',
        'guest routes for login/reset' => '/guest|forgot-password|reset-password|password\./i',
        'permission or policy middleware' => '/can:|permission|authorize|policy/i',
        'state-changing CSRF-protected web routes' => '/csrf|form key|POST|PUT|PATCH|DELETE/i',
    ];

    foreach ($requirements as $label => $pattern) {
        if (preg_match($pattern, $routeContent) !== 1) {
            $errors[] = "Final auth/security implementation requires {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateAuthTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $aggregateContent = aggregateFiles($testFiles);

    if ($aggregateContent === '') {
        $errors[] = 'Final auth/security implementation requires Laravel PHPUnit tests';

        return;
    }

    $coverage = [
        'all auth/security feature IDs' => allAuthSecurityFeatureIdsPresent($aggregateContent),
        'customer login logout password reset and sessions' => preg_match('/customer.*login|login.*customer|logout|forgot password|reset password|password reset|session/i', $aggregateContent) === 1,
        'admin login timeout form-key and ACL denial' => preg_match('/admin.*login|login.*admin|timeout|form key|form-key|ACL|access denied|forbidden/i', $aggregateContent) === 1,
        'full partial read-only and denied admin roles' => preg_match('/full.*role|partial.*role|read-only|readonly|denied.*role|no-access/i', $aggregateContent) === 1,
        'policies gates and permission manifests' => preg_match('/policy|Gate::|permission manifest|permissions|authorize|can\(/i', $aggregateContent) === 1,
        'CSRF missing and invalid token failures' => preg_match('/CSRF|csrf|missing token|invalid token|TokenMismatch|form key/i', $aggregateContent) === 1,
        'password hash verification upgrade and broker flows' => preg_match('/Hash::check|needsRehash|legacy password|password broker|Password::|reset token/i', $aggregateContent) === 1,
        'API authentication and OAuth roles' => preg_match('/API-\d{3}|api auth|OAuth|token|admin role|customer role|guest role/i', $aggregateContent) === 1,
        'dual-runtime or legacy auth/session comparison' => preg_match('/dual-runtime|legacy comparison|Magento|legacy session|cross-runtime/i', $aggregateContent) === 1,
        'session cookie flags logout invalidation and rollback' => preg_match('/cookie|same-site|samesite|secure flag|logout invalidation|invalidate|rollback/i', $aggregateContent) === 1,
    ];

    foreach ($coverage as $label => $covered) {
        if (! $covered) {
            $errors[] = "Final auth/security implementation requires PHPUnit coverage for {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateAuthEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/auth-security-evidence.md',
        'docs/content/modernization/auth-security-evidence.md',
        'docusaurus/docs/developer/auth-security.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final auth/security implementation requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (authSecurityFeatureIds() as $featureId) {
        if (! str_contains($content, $featureId)) {
            $errors[] = "{$path}: missing auth/security evidence row for {$featureId}";
        }
    }

    foreach (['Customer Auth', 'Admin Auth', 'Permission Matrix', 'CSRF', 'Form Key', 'Session', 'Cookie', 'Password Hash', 'Password Reset', 'API Auth', 'Legacy Comparison', 'Rollback', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing auth/security evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required|To inventory)\b/', $content) === 1) {
        $errors[] = "{$path}: final auth/security evidence still contains placeholders";
    }
}

/**
 * @return list<string>
 */
function authSecurityFeatureIds(): array
{
    return [
        'SF-010',
        'AD-001',
        'AD-012',
        'API-001',
        'API-002',
        'API-003',
        'CB-013',
    ];
}

function allAuthSecurityFeatureIdsPresent(string $content): bool
{
    foreach (authSecurityFeatureIds() as $featureId) {
        if (! str_contains($content, $featureId)) {
            return false;
        }
    }

    return true;
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
function aggregateFiles(array $files): string
{
    $content = '';

    foreach ($files as $file) {
        $fileContent = file_get_contents($file);
        if ($fileContent !== false) {
            $content .= "\n".$file."\n".$fileContent;
        }
    }

    return $content;
}

/**
 * @param  list<string>  $files
 */
function pathOrContentMatches(array $files, string $pattern): bool
{
    foreach ($files as $file) {
        $content = file_get_contents($file);
        if ($content !== false && preg_match($pattern, normalizePath($file)."\n".$content) === 1) {
            return true;
        }
    }

    return false;
}

function containsCaseInsensitive(string $content, string $needle): bool
{
    return stripos($content, $needle) !== false;
}

function normalizePath(string $path): string
{
    return str_replace(DIRECTORY_SEPARATOR, '/', $path);
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
