#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateCronJobSpecs($errors);

if ($final) {
    validateFinalCronJobImplementation($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Cron/job target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateCronJobSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Preserve all existing storefront and admin screens, flows, URLs, API contracts, cron behavior, order/payment/tax/cart behavior, and visual look and feel unless an explicit retirement or difference is approved.',
            'Build Laravel bootstrap, config, module registry, no-XML manifest system, route strangler/fallback, EAV access layer, auth/session/security foundation, scheduler, queue, events, API contracts, and operations hooks.',
            'API, cron, queue, auth, security, performance, accessibility, deployment, rollback, backup/restore, docs, and operations gates pass.',
        ],
        'specs/modernization/architecture-specs.md' => [
            'Events/jobs',
            'How observers/cron/shell scripts become Laravel events/scheduler/commands.',
            'Cron jobs become scheduler entries and Artisan commands.',
            'Long-running jobs require locking, retry, failure logging, and diagnostics.',
        ],
        'specs/modernization/roadmap.md' => [
            'Phase 10: Events, Jobs, Cron, And Commands',
            'replace observers, cron, and shell scripts with Laravel events, scheduler, queues, and Artisan commands.',
            'Replace cron dispatch with Laravel scheduler entries.',
            'Define queue policy: sync, database, Redis, or external queue. Do not require commerce schema changes unless approved.',
            '`php artisan schedule:list` shows expected tasks.',
        ],
        'specs/modernization/test-plan.md' => [
            'Cron, Queue, And Command Test Plan',
            'Expected jobs appear in Laravel scheduler.',
            'Every `CJ-001` through `CJ-025` job in `specs/modernization/magento-feature-catalog.md` is preserved, bridged, replaced, or retired with evidence.',
            '`schedule:list` or equivalent diagnostics show expected tasks.',
            'Failed jobs produce actionable logs.',
        ],
        'specs/modernization/complex-feature-reverse-engineering.md' => [
            'CJ-001 through CJ-025',
            'Schedule, input rows/config, locks, output rows/files/emails, logs, failure and idempotency behavior.',
            'Laravel scheduler/job parity tests and report table snapshots.',
            'cron jobs all have dual-runtime parity tests.',
        ],
        'specs/modernization/data-fixtures.md' => [
            'Cron and reports',
            'Report aggregates are populated; cron schedule rows are missing.',
        ],
        'specs/modernization/backlog.md' => [
            'Add cron/job parity tests',
            'Every Magento cron feature has retain, replace, bridge, or retire decision and verification.',
            'Scheduler tests, queue tests, report table comparisons.',
        ],
        'specs/modernization/risk-register.md' => [
            'Cron/job duplication',
            'Scheduler lock tests, idempotency review.',
        ],
        'docusaurus/docs/developer/architecture.md' => [
            'Laravel HTTP kernel, routes, middleware, service container, scheduler, queues, and config.',
        ],
        'docusaurus/docs/user/admin-guide.md' => [
            'Operational recovery paths for failed imports, failed emails, stale indexes, cache invalidation, scheduler failures, and integration outages.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing cron/job planning phrase '{$phrase}'";
            }
        }
    }

    validateCronCatalog($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateCronCatalog(array &$errors): void
{
    $path = 'specs/modernization/magento-feature-catalog.md';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (cronFeatureIds() as $featureId) {
        if (! str_contains($content, "| {$featureId} |")) {
            $errors[] = "{$path}: missing cron feature row for {$featureId}";
        }
    }

    foreach (['Schedule Source', 'Legacy Model', 'Required Evidence'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing cron catalog column '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalCronJobImplementation(array &$errors): void
{
    validateScheduleImplementation($errors);
    validateCommandAndJobArtifacts($errors);
    validateCronJobTests($errors);
    validateCronJobEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateScheduleImplementation(array &$errors): void
{
    $scheduleFiles = array_merge(
        ['laravel/routes/console.php'],
        phpFilesUnder('laravel/app/Console'),
        phpFilesUnder('laravel/app/Scheduling')
    );

    if (! filesContain($scheduleFiles, '/\bSchedule::(?:command|job|call|exec)\s*\(/')) {
        $errors[] = 'Final cron/job implementation requires Laravel scheduler entries using Schedule::command, Schedule::job, Schedule::call, or Schedule::exec';
    }

    if (! filesContain($scheduleFiles, '/withoutOverlapping|onOneServer|runInBackground|takeUntilTimeout/')) {
        $errors[] = 'Final cron/job implementation requires scheduler locking or runtime controls for duplicate/long-running tasks';
    }

    if (! filesContain($scheduleFiles, '/schedule:list|diagnostic|diagnostics|status/i')) {
        $errors[] = 'Final cron/job implementation requires schedule diagnostics or schedule:list evidence in scheduler code/docs';
    }
}

/**
 * @param  list<string>  $errors
 */
function validateCommandAndJobArtifacts(array &$errors): void
{
    $commandFiles = phpFilesUnder('laravel/app/Console/Commands');
    $jobFiles = phpFilesUnder('laravel/app/Jobs');
    $eventFiles = phpFilesUnder('laravel/app/Events');
    $listenerFiles = phpFilesUnder('laravel/app/Listeners');

    if ($commandFiles === []) {
        $errors[] = 'Final cron/job implementation requires Artisan command classes under laravel/app/Console/Commands';
    }

    if ($jobFiles === []) {
        $errors[] = 'Final cron/job implementation requires job classes under laravel/app/Jobs';
    }

    if ($eventFiles === [] || $listenerFiles === []) {
        $errors[] = 'Final cron/job implementation requires event and listener classes for observer replacement or compatibility';
    }

    if (! filesContain(array_merge($commandFiles, $jobFiles, $listenerFiles), '/retryUntil|backoff|tries|failed\s*\(|ShouldQueue|ShouldBeUnique|RateLimited/')) {
        $errors[] = 'Final cron/job implementation requires retry, uniqueness, failure, or queue policy controls in command/job/listener code';
    }
}

/**
 * @param  list<string>  $errors
 */
function validateCronJobTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $coverage = [
        'schedule registration' => false,
        'command exit codes and output' => false,
        'queue interactions' => false,
        'locking or idempotency' => false,
        'failure logging or retry' => false,
        'CJ feature IDs' => false,
        'report table snapshots' => false,
    ];

    foreach ($testFiles as $testFile) {
        $content = file_get_contents($testFile);
        if ($content === false || preg_match('/cron|schedule|queue|job|command|artisan|CJ-\d{3}/i', $content.$testFile) !== 1) {
            continue;
        }

        $coverage['schedule registration'] = $coverage['schedule registration'] || preg_match('/schedule:list|Schedule::|scheduler|expected tasks/i', $content) === 1;
        $coverage['command exit codes and output'] = $coverage['command exit codes and output'] || preg_match('/artisan\(|assertExitCode|assertSuccessful|expectsOutput|expectsQuestion/i', $content) === 1;
        $coverage['queue interactions'] = $coverage['queue interactions'] || preg_match('/Queue::fake|Bus::fake|withFakeQueueInteractions|assertPushed|assertDispatched|assertReleased|assertFailed/i', $content) === 1;
        $coverage['locking or idempotency'] = $coverage['locking or idempotency'] || preg_match('/withoutOverlapping|lock|idempot|duplicate/i', $content) === 1;
        $coverage['failure logging or retry'] = $coverage['failure logging or retry'] || preg_match('/failed|retry|backoff|Log::|assertLogged|failure/i', $content) === 1;
        $coverage['CJ feature IDs'] = $coverage['CJ feature IDs'] || allCronFeatureIdsPresent($content);
        $coverage['report table snapshots'] = $coverage['report table snapshots'] || preg_match('/report table|snapshot|aggregate|DB delta|database delta/i', $content) === 1;
    }

    foreach ($coverage as $label => $covered) {
        if (! $covered) {
            $errors[] = "Final cron/job implementation requires PHPUnit coverage for {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateCronJobEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/cron-job-evidence.md',
        'docs/content/modernization/cron-job-evidence.md',
        'docusaurus/docs/developer/cron-jobs.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final cron/job implementation requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (cronFeatureIds() as $featureId) {
        if (! str_contains($content, $featureId)) {
            $errors[] = "{$path}: missing cron/job evidence row for {$featureId}";
        }
    }

    foreach (['Schedule List', 'Queue Policy', 'Locking', 'Retry', 'Failure Log', 'Idempotency', 'Report Snapshot', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing cron/job evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required)\b/', $content) === 1) {
        $errors[] = "{$path}: final cron/job evidence still contains placeholders";
    }
}

/**
 * @return list<string>
 */
function cronFeatureIds(): array
{
    return array_map(
        static fn (int $id): string => sprintf('CJ-%03d', $id),
        range(1, 25)
    );
}

function allCronFeatureIdsPresent(string $content): bool
{
    foreach (cronFeatureIds() as $featureId) {
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
function filesContain(array $files, string $pattern): bool
{
    foreach ($files as $file) {
        if (! is_file($file)) {
            continue;
        }

        $content = file_get_contents($file);
        if ($content !== false && preg_match($pattern, $content) === 1) {
            return true;
        }
    }

    return false;
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
