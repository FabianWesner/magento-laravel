# Operations Runbook

This runbook defines the operator procedures for the Magento-to-Laravel modernization while both runtimes remain available for comparison. It is scoped to the repository layout in `specs/GOAL.md`: Magento source under `core/magento-1.9.4.5/`, project overlay under `project/`, generated legacy docroot under `.localdev/magento-docroot/`, and the Laravel target under `laravel/`.

Use this runbook together with `specs/modernization/release-strategy.md`, `specs/modernization/test-plan.md`, and `specs/modernization/performance-budgets.md`.

## Deployment

1. Confirm the working tree contains the intended release commit and no unrelated local edits:

    ```bash
    git status --short
    git log -1 --oneline
    ```

2. Install Laravel PHP dependencies with the PHP 8.5 runtime:

    ```bash
    cd laravel
    /Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 /Users/fabianwesner/Library/Application\ Support/Herd/bin/composer install --no-interaction --prefer-dist
    ```

3. Install JavaScript dependencies from their lockfiles:

    ```bash
    cd laravel
    npm ci
    cd ../docusaurus
    npm ci
    ```

4. Build frontend and documentation assets:

    ```bash
    cd laravel
    npm run build
    cd ../docusaurus
    npm run build
    ```

5. Run the normal modernization gate before exposing a release to traffic:

    ```bash
    PATH=/private/tmp/magento-lts-php85-bin:$PATH bash dev/modernization/gate.sh
    ```

## Rollback

Rollback keeps legacy Magento available until a migrated route has staging and production observation. Operators move traffic back to legacy routes through the route ownership and fallback configuration, then verify both runtimes still share compatible schema and fixture state.

1. Stop Laravel traffic for the affected route or feature flag.
2. Route requests back to the generated Magento docroot under `.localdev/magento-docroot/`.
3. Clear Laravel and Magento caches.
4. Verify the legacy smoke path in a browser.
5. Record the rollback commit, affected feature IDs, observed impact, and recovery time in the release notes or defect register.

The rollback target is governed by `specs/modernization/release-strategy.md` and ADR 0008 for route fallback, auth, session, cookie, CSRF/form-key, password-hash, and cross-runtime behavior.

## Backup

Back up database and media before fixture restore, staging rehearsal, production deployment, or any route cutover exercise.

Database backup command shape:

```bash
mysqldump --single-transaction --routines --triggers --events "$DB_NAME" > .localdev/backups/magento-$(date +%Y%m%d%H%M%S).sql
```

Media backup command shape:

```bash
rsync -a project/media/ .localdev/backups/media-$(date +%Y%m%d%H%M%S)/
```

Backups stay outside committed source unless explicitly curated and sanitized.

## Restore

Restore must not run destructive schema migrations against Magento commerce or EAV tables. Schema signatures before and after restore are compared with the modernization schema tooling.

Fixture restore dry-run check:

```bash
bash dev/modernization/fixture-restore-check.sh --fixture=/path/to/fixture.sql.gz --media=/path/to/media
```

Schema report check:

```bash
DB_DSN='mysql:host=<host>;dbname=<fixture_db>' DB_USER=<user> DB_PASS=<pass> php dev/modernization/schema-report.php --format=markdown
```

Fixture coverage check:

```bash
DB_DSN='mysql:host=<host>;dbname=<fixture_db>' DB_USER=<user> DB_PASS=<pass> php dev/modernization/fixture-coverage-report.php --format=markdown --fail-on-gaps
```

## Health

Health checks cover the app container, database connection, cache store, session store, scheduler, queue worker, and documentation build.

Application smoke:

```bash
cd laravel
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan about
```

Boost application smoke:

```bash
cd laravel
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan boost:execute-tool 'Laravel\Boost\Mcp\Tools\ApplicationInfo' e30=
```

Documentation smoke:

```bash
node dev/modernization/smoke-docusaurus.mjs
```

## Cache

Clear Laravel caches after deployment, rollback, config changes, route ownership changes, and module manifest changes.

```bash
cd laravel
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan optimize:clear
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan config:cache
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan route:cache
```

Magento cache remains legacy-runtime owned until each route is cut over. Do not mask cache, index, price, tax, payment, permission, validation, or order-state differences in visual or parity reports.

## Scheduler

Magento cron jobs stay inventoried in `specs/modernization/magento-feature-catalog.md` as `CJ-001` through `CJ-025`. Laravel replacements run through scheduler commands and queued jobs with idempotency, locking, retry, and failure-path tests.

Local scheduler smoke:

```bash
cd laravel
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan schedule:list
```

After deployment, verify expected scheduled tasks have run once and no duplicate legacy dispatcher is running for a migrated job.

## Queue

Queue workers process Laravel jobs only. Legacy Magento email and cron behavior remains under Magento characterization until the corresponding feature IDs are migrated and approved.

Worker smoke:

```bash
cd laravel
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan queue:work --once --queue=default
```

Operational checks:

- Failed jobs are reviewed after deployment and rollback.
- Retry behavior is compared against the feature's characterization notes.
- Long-running jobs define timeout and retry policy.
- External integration jobs use sandbox endpoints or mocks during parity testing.

## Logs

Collect logs from Laravel, web server, queue workers, scheduler, browser console, and legacy Magento runtime during characterization and cutover rehearsal.

Laravel log:

```bash
tail -n 200 laravel/storage/logs/laravel.log
```

Recent browser logs should be checked with the available browser tooling during UI verification. Ignore stale browser entries from previous runs.

## Monitoring

Minimum monitored signals:

- HTTP status and latency for migrated storefront, admin, and API routes.
- Database connection failures and query error rate.
- Cache and session backend failures.
- Queue depth, failed jobs, retries, and job runtime.
- Scheduler last-run time and duplicate execution.
- Error log rate by feature ID and route owner.
- Visual, accessibility, security, and performance gate status for release candidates.

Alert triage maps each issue to a feature ID, runtime owner, route owner, current traffic target, and rollback decision.

## Troubleshooting

Start with the smallest failing gate or feature ID.

1. Check `specs/progress.md` for the latest blocker and command output summary.
2. Re-run the focused validator for the affected domain under `dev/modernization/`.
3. Run the focused Laravel PHPUnit file under `laravel/tests/Feature/`.
4. Inspect logs for the current runtime owner.
5. Compare Magento and Laravel behavior against the same fixture data.
6. If the failure affects production traffic, route the feature back to legacy Magento and record the rollback in the defect register.

Common commands:

```bash
PATH=/private/tmp/magento-lts-php85-bin:$PATH bash dev/modernization/gate.sh
MODERNIZATION_FINAL=1 PATH=/private/tmp/magento-lts-php85-bin:$PATH bash dev/modernization/gate.sh
cd laravel && /Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan test --compact
```
