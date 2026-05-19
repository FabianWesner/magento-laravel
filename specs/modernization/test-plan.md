# Modernization Test Plan

This test plan defines how to prove the Laravel modernization is complete. It is intended to be used as the final completion gate for replacing the Magento framework runtime with Laravel while preserving database schema, EAV behavior, storefront/admin look and feel, and business behavior.

The project is not done when the code compiles. It is done when this plan passes with agreed evidence.

## Test Objectives

1. Prove behavior parity between the current Magento 1 runtime and the Laravel runtime.
2. Prove the existing database schema and original seed/sample data work without destructive migration.
3. Prove EAV reads and writes preserve Magento semantics.
4. Prove storefront and admin visual parity.
5. Prove the new Laravel and Livewire architecture is modular, extensible, and free of new XML configuration.
6. Prove the Laravel target runs on the latest stable PHP and supported Composer dependencies.
7. Prove security, performance, reliability, accessibility, and operational behavior are production-ready.
8. Prove documentation is complete enough for users, operators, and module developers.
9. Prove edge cases, failure paths, resilience, recovery, observability, and production readiness, not only happy-path workflows.

## Done Definition

The modernization is done only when all of the following are true:

- Every feature ID in `specs/modernization/magento-feature-catalog.md` has final status, owner, fixture coverage, characterization evidence, Laravel evidence, and release evidence.
- Every visible storefront and admin screen is listed in `specs/modernization/ui-screen-inventory.md` and has screenshots for Magento and Laravel at required viewports and states.
- Every complex commerce behavior listed in `specs/modernization/complex-feature-reverse-engineering.md` has a reverse-engineered spec before Laravel replacement starts.
- The canonical demo fixture in `specs/modernization/data-fixtures.md` covers all product types, promotion types, taxes, shipping, payment, admin roles, API users, reports, cron jobs, and edge cases needed by the feature catalog.
- Every critical feature has normal, edge, failure, invalid input, permission-denied, stale-cache/index, retry, concurrency, and recovery coverage where applicable.
- All required automated test suites pass in CI.
- All required manual acceptance scenarios are signed off.
- All P0 and P1 defects are closed.
- No P2 defect is open without explicit acceptance.
- Storefront and admin visual regression results meet `specs/modernization/visual-tolerances.md`.
- Performance budgets pass for all critical journeys.
- Security checks pass with no unresolved critical/high findings.
- Existing database schema and EAV data are used without destructive migration.
- No new architecture feature uses XML registration/configuration.
- Banned legacy technologies listed in `specs/modernization/technology-removal-policy.md` are removed from the Laravel target runtime.
- Laravel target CI runs on the latest stable PHP, currently PHP `8.5.x` as of 2026-05-18, while legacy Magento PHP `7.4` remains isolated to baseline smoke verification.
- Laravel Boost can be installed in the target Laravel application without Composer platform conflicts.
- Documentation website builds and covers architecture, reasoning, extension points, and feature guides.
- Docusaurus user and developer documentation builds and renders in Chrome/Playwright.
- Rollback and recovery procedures have been tested.

## Production Readiness Gate

Happy-path smoke tests are never enough for completion. Each critical feature must prove production readiness across these categories:

| Category | Required Evidence |
| --- | --- |
| Normal path | Expected user or system workflow succeeds with fixture data. |
| Edge cases | Boundary quantities, missing optional data, scoped config, multistore values, product type variants, tax/shipping/payment variants, and empty states behave correctly. |
| Failure paths | Invalid input, expired session, denied permission, payment failure, unavailable shipping, integration timeout, missing media, stale cache/index, and failed cron/queue job produce safe outcomes. |
| Data integrity | Database side effects are correct, transactional, idempotent where required, and reversible through documented restore/rollback paths. |
| Resilience | Retries, locks, duplicate submission protection, concurrent requests, scheduler overlap, and external service outages are tested. |
| Observability | Logs, metrics, alerts, health checks, and operator diagnostics identify failures without exposing secrets. |
| Security | Auth, authorization, CSRF, XSS, SQL injection, upload, session, secret, and API abuse checks pass. |
| Performance | Latency, memory, query count, cache hit ratio, queue runtime, and report/index runtime stay within budgets. |
| Accessibility | Keyboard, focus, labels, contrast, dialogs, grids, and Livewire updates work for critical flows. |
| Recovery | Cache flush, reindex, queue retry, fixture restore, backup restore, deploy rollback, and failed release recovery are rehearsed. |

## Feature Traceability Gate

The release gate is feature-ID driven. A feature ID is complete only when the following evidence exists:

| Evidence | Required For | Verification |
| --- | --- | --- |
| Inventory row | Every feature ID | `specs/modernization/feature-inventory.md` links the feature to owner, decision, files, routes, cron/API/UI entry points, fixture IDs, risks, acceptance criteria, and verification commands. |
| Legacy characterization | Every preserved/replaced/bridged feature ID | Dual-run test, captured payload, DB snapshot, screenshot, email/log/event evidence, or approved normalized comparison. |
| Laravel implementation tests | Every preserved/replaced feature ID | Unit, integration, Livewire, browser, API, scheduler, visual, performance, and security tests as applicable. |
| UI screenshots | Visible feature IDs | Screenshot manifest entry from `ui-screen-inventory.md` for required roles, states, and viewports. |
| Complex behavior spec | Cart, checkout, pricing, tax, shipping, payment, EAV, indexing, reports, cron | Reverse-engineered algorithm, fixture matrix, side effects, comparison command, and numeric approved tolerances where comparison is not exact. |
| Fixture coverage | Every feature ID | Fixture manifest maps data to the feature ID, can be restored locally and in CI, and `php dev/modernization/fixture-coverage-report.php --format=markdown` reports no fixture matrix gaps for the canonical demo fixture. |
| Release evidence | Every feature ID | CI/report link or local artifact retained under the release evidence path. |
| Progress and commit evidence | Every backlog item | `specs/progress.md` entry and commit hash or evidence artifact. |

No feature ID may be marked complete by visual approval alone, and no cart/checkout/sales feature may be marked complete without DB side-effect comparison.

## Completion Audit Gate

Before final cutover or completion is declared, create `specs/modernization/completion-audit.md` and use it as the final proof ledger. The audit must restate the modernization objective as concrete deliverables, then build a Prompt-to-artifact checklist that maps every explicit requirement, numbered item, named file, command, test, gate, and deliverable from `specs/GOAL.md` to concrete evidence.

The audit must inspect actual files, command output, test reports, browser artifacts, screenshots, CI results, commit state, and release evidence. Passing tests, manifests, verifier success, or green status are proxy signals until the audit maps them to the requirement they prove. Treat uncertainty as not achieved.

The Prompt-to-artifact checklist must include columns for requirement, source, evidence, verification, and status. Each row must cite a concrete artifact, command output, URL, or file path. The final audit can pass only when no missing, incomplete, weakly verified, or uncovered requirements remain.

## Quality Gates

```mermaid
flowchart TD
    Specs["Specs and acceptance criteria approved"]
    Fixtures["Fixtures and baseline data ready"]
    Legacy["Legacy characterization suite green"]
    Laravel["Laravel implementation suite green"]
    Parity["Parity and contract tests green"]
    Visual["Visual regression approved"]
    NFR["Security, performance, accessibility, operations pass"]
    Docs["Docs and feature guide approved"]
    Release["Release readiness sign-off"]

    Specs --> Fixtures --> Legacy --> Laravel --> Parity --> Visual --> NFR --> Docs --> Release
```

## Test Environments

| Environment | Purpose | Required Data | Gate |
| --- | --- | --- | --- |
| Local developer | Fast feedback and focused implementation checks. | Minimal fixtures plus selected domain fixtures. | Required before PR. |
| CI | Deterministic automated validation. | Versioned fixture database and media fixtures. | Required for merge. |
| Staging parity | Full end-to-end validation against representative data. | Sanitized production-like database, media, config, and integrations in sandbox mode. | Required before release. |
| Production shadow or canary | Real traffic confidence where applicable. | Production data, read-only or limited-route rollout. | Required for final cutover if available. |

## Test Data Strategy

### Required Fixture Sets

| Fixture Set | Purpose | Contents |
| --- | --- | --- |
| Minimal install fixture | Fast baseline checks. | Fresh Magento install with default store, admin user, base config. |
| Original seed/sample fixture | Proves existing seed/sample data compatibility. | Original sample catalog, categories, customers if available, orders if available, media. |
| EAV edge fixture | Proves EAV semantics. | Attributes of every backend type, scoped values, options, multiselects, required fields, custom attributes, disabled attributes. |
| Multistore fixture | Proves scope and URL behavior. | Multiple websites, store groups, store views, languages, currencies, store-specific config and attributes. |
| Sales lifecycle fixture | Proves order behavior. | Quotes, orders, invoices, shipments, credit memos, refunds, comments, emails, payment states. |
| Admin permissions fixture | Proves admin ACL replacement. | Roles with full, partial, and no permissions across core admin areas. |
| Integration fixture | Proves API and external behavior. | API users, OAuth/REST credentials if used, sandbox payment/shipping settings. |
| Performance fixture | Proves scale behavior. | Large catalog, many attributes, many categories, many customers, meaningful order history. |

The detailed demo matrix is defined in `specs/modernization/data-fixtures.md` and is mandatory for final acceptance. Magento sample data alone is not enough unless it is extended to cover the matrix.

Use the read-only fixture coverage reporter as the repeatable local/CI smoke check for fixture breadth:

```bash
DB_DSN='mysql:host=<host>;dbname=<fixture_db>' DB_USER=<user> DB_PASS=<pass> php dev/modernization/fixture-coverage-report.php --format=markdown --fail-on-gaps
```

The report is a coverage signal, not final proof by itself. Final acceptance still requires per-feature fixture IDs, characterization evidence, Laravel parity tests, visual evidence, and release evidence.

### Data Rules

- Existing commerce schema must not be destructively altered.
- Test setup may create data using existing schema only.
- Any new infrastructure table requires explicit approval and must not be required for preserving the existing commerce database.
- Fixtures must be reproducible from versioned scripts or documented dumps.
- Sensitive production data must be sanitized before use outside production.

## Test Layers

| Layer | Purpose | Tools |
| --- | --- | --- |
| Static checks | Enforce code quality, types, architecture boundaries, and no-new-XML rule. | PHPStan/Larastan, PHP-CS-Fixer/Pint, PHPCS, Rector dry run, custom architecture tests. |
| Unit tests | Prove isolated services, value objects, DTOs, mappers, validators, and policies. | PHPUnit 12. |
| Integration tests | Prove database, EAV, cache, session, filesystem, config, events, queues, and services together. | PHPUnit with fixture DB. |
| Contract tests | Prove compatibility of APIs, events, repositories, and module extension points. | PHPUnit, HTTP contract tests, snapshot assertions. |
| Characterization tests | Prove Laravel behavior matches legacy Magento behavior. | Dual-run tests against legacy and Laravel paths. |
| Livewire component tests | Prove component state, validation, actions, events, and rendering. | Livewire test utilities. |
| Browser E2E tests | Prove user journeys. | Playwright/Chrome. |
| Visual regression | Prove storefront/admin look and feel is preserved. | Playwright/Chrome screenshots and image diff tooling. |
| Accessibility tests | Prove keyboard, semantics, contrast, and admin usability. | Axe, Playwright, manual keyboard checks. |
| Performance tests | Prove latency, query counts, memory, throughput, and cache behavior. | k6, Blackfire/XHProf/SPX, custom query counters. |
| Security tests | Prove auth, authorization, CSRF, sessions, uploads, file access, dependency safety. | PHPUnit, browser tests, composer audit, static checks, manual review. |
| Operational tests | Prove deploy, rollback, logs, scheduler, queues, cache, sessions, backups, and recovery. | Staging rehearsal, scripts, runbooks. |
| Documentation tests | Prove architecture and feature guide are buildable and complete. | MkDocs build, link checks, review checklist. |
| Docusaurus docs tests | Prove user and developer docs are buildable and browser-rendered. | `npm --prefix docusaurus run build`, `node dev/modernization/smoke-docusaurus.mjs`. |
| Runtime tests | Prove the target Laravel runtime uses the latest stable PHP and compatible Composer packages. | `php -v`, Composer platform check, CI matrix, Laravel Boost install. |

## Static And Architecture Tests

### Scope

- Type safety.
- Coding standards.
- No-new-XML rule.
- Module boundary rules.
- Dependency direction.
- Forbidden legacy API usage in migrated code.
- Framework dependency policy.

### Required Checks

| Check | Acceptance Criteria | Verification |
| --- | --- | --- |
| PHP syntax | All PHP files parse on supported PHP versions. | CI syntax job. |
| PHP version | Laravel target uses the latest stable PHP; legacy Magento PHP `7.4` is isolated to smoke testing. | CI matrix, `php -v`, Composer platform config. |
| Laravel Boost | `laravel/boost` installs cleanly as a dev dependency in the target Laravel app. | `composer require laravel/boost --dev` in the Laravel workspace. |
| Laravel Boost MCP | Boost MCP server is available to the active agent/client or a blocker is recorded with exact reload/config steps. | `tools/list`, `application-info`, `search-docs`, and read-only database tool checks. |
| Static analysis | New Laravel code passes agreed PHPStan/Larastan level with no baseline growth. | `composer run phpstan:test` plus Laravel config. |
| Coding style | PHP code follows project style. | Existing ECS/PHP-CS-Fixer plus Laravel style command if introduced. |
| No new XML | New architecture does not add XML module, route, event, layout, ACL, or config registration. | Static search and architecture test fail on forbidden XML paths. |
| Removed technologies | Laravel target does not depend on Magento/Zend/Varien/XML/layout/block/resource/Prototype-era runtime technologies. | `php dev/modernization/validate-removed-technologies.php`, dependency scan, architecture tests. |
| Legacy API isolation | Migrated Laravel code does not call `Mage::getModel()`, `Mage::helper()`, Zend controllers, or XML config directly. | Static architecture tests with allowlist for compatibility adapters. |
| Module boundaries | Modules depend only on declared dependencies and public contracts. | Dependency graph test and module registry diagnostics. |
| Eloquent policy | Eloquent is used only where approved; EAV access goes through EAV repositories/services. | Static checks and code review. |

## Legacy Characterization Tests

Before replacing a feature, tests must capture current behavior.

### Required Pattern

For each migrated feature:

1. Build fixtures.
2. Execute the behavior on the current Magento 1 runtime.
3. Record observable output: HTTP status, redirect, rendered content, DB state, events, emails, logs, API payloads, and side effects.
4. Execute the same behavior on the Laravel path.
5. Compare results with exact or approved normalized matching.

Cart calculation, checkout, pricing, tax, shipping, payment, indexing, EAV writes, reports, and cron jobs must follow the deeper reverse-engineering workflow in `specs/modernization/complex-feature-reverse-engineering.md`.

### Acceptance Criteria

- Every critical feature has characterization coverage before migration starts.
- Every migrated feature keeps its characterization tests permanently.
- Differences are documented as intentional changes with approval.

## Database And EAV Test Plan

### Coverage

| Area | Required Tests |
| --- | --- |
| Connection | Laravel connects to the existing Magento database without schema rewrite. |
| Schema preservation | Booting Laravel does not drop, rename, or rewrite existing tables/columns/indexes. |
| Store hierarchy | Websites, groups, stores, default store, admin store, and disabled stores resolve correctly. |
| Config scope | Default, website, store, env override, and cache behavior match legacy resolution. |
| EAV metadata | Entity types, attributes, sets, groups, backend types, frontend input, source models, and options resolve correctly. |
| Product EAV | Product attributes read/write correctly across backend tables and store scopes. |
| Category EAV | Category attributes and hierarchy resolve correctly. |
| Customer EAV | Customer and address attributes resolve correctly. |
| Attribute options | Labels, sort order, store-specific labels, multiselects, and missing values behave correctly. |
| Flat tables | Flat entities mapped by Eloquent preserve primary keys, timestamps, nullability, and relations. |
| Transactions | Write operations commit and roll back correctly. |
| Index data | Index-backed reads remain compatible where indexes are still used. |

### Acceptance Criteria

- Existing database can be used directly.
- EAV repository output matches legacy resource model output for fixture entities.
- No destructive migrations are required.
- Query counts are documented for critical EAV reads.

### Verification

- PHPUnit integration tests against fixture DB.
- Legacy-vs-Laravel parity tests for selected entity snapshots.
- Schema checksum before and after boot.
- Query count assertions for critical catalog pages.

## Module System Test Plan

### Coverage

| Area | Required Tests |
| --- | --- |
| Discovery | Local and Composer modules are discovered from PHP manifests. |
| Dependency ordering | Modules boot in declared dependency order. |
| Cycles | Circular dependencies fail with clear diagnostics. |
| Enable/disable | Disabled modules do not register services, routes, views, commands, permissions, or listeners. |
| Service providers | Module service providers bind expected contracts. |
| Routes | Module routes register only when enabled. |
| Events | Module listeners register and execute correctly. |
| Views | Module views and Livewire components render correctly. |
| Config | Module config is PHP-based and overrideable. |
| Permissions | Module permissions appear in admin authorization. |
| Diagnostics | Module registry command reports status and dependency graph. |

### Acceptance Criteria

- A sample module exercises every extension point without XML.
- Module behavior is deterministic across local, CI, and staging.
- Modules can be added without editing core files.

## Storefront Test Plan

### Critical Journeys

| Journey | Required Coverage |
| --- | --- |
| Home page | Status, layout, header, footer, CMS blocks, assets, cache behavior. |
| Category page | Product listing, filters, sorting, pagination, layered navigation, empty category. |
| Product page | Configurable/simple/bundle/downloadable products, media, options, stock, price, related/up-sell/cross-sell. |
| Search | Query results, empty results, redirects, URL behavior. |
| Cart | Add/update/remove item, coupons, shipping estimate, totals, persistent cart if enabled. |
| Checkout | Guest checkout, registered checkout, addresses, shipping, payment, review, order placement, failure handling. |
| Customer account | Register, login, logout, forgot password, account edit, addresses, orders, wishlist if enabled. |
| CMS | CMS page, CMS block, redirects, 404. |
| Multistore | Store switch, language/currency, scoped product/category values. |
| SEO/URLs | URL rewrites, canonical links, no route, redirects, sitemap where applicable. |

### Visual Parity

For each migrated page:

- Screenshots are required for the exact viewport matrix in `specs/modernization/ui-screen-inventory.md`: `1440x1000`, `1280x900`, `768x1024`, and `390x844`.
- Header, navigation, content, forms, messages, buttons, modals, and footer must match baseline.
- Dynamic states must be captured: loading, validation error, empty state, success, and failure.
- The screen inventory in `specs/modernization/ui-screen-inventory.md` is authoritative for required URL/state/role/viewport coverage.

### Acceptance Criteria

- Existing storefront E2E tests pass.
- New Livewire interactions pass component and browser tests.
- Visual diffs meet `specs/modernization/visual-tolerances.md`.
- Storefront URLs remain compatible.
- No new layout XML drives migrated pages.

## Admin Test Plan

### Critical Journeys

| Journey | Required Coverage |
| --- | --- |
| Admin login | Login, logout, invalid credentials, password policy, session timeout. |
| Dashboard | Layout, widgets, charts, permissions, data visibility. |
| Cache | View cache status, clean/flush actions, permission checks. |
| Configuration | Default/website/store scopes, save, validation, inherited values, env-overridden values. |
| Catalog product | Grid, filters, edit, save, attributes, media, inventory, websites, categories, price, options. |
| Catalog category | Tree, edit, store scope, products, URL keys. |
| Customer | Grid, create/edit, addresses, groups, password reset. |
| Sales order | Grid, view, comments, status, reorder if enabled. |
| Invoice | Create/view, totals, email, permission checks. |
| Shipment | Create/view, tracking, email, permission checks. |
| Credit memo | Create/view, refund state, permission checks. |
| Promotions | Catalog rules, cart rules, coupon behavior. |
| CMS | Pages, blocks, widgets where used. |
| Users/roles | Create/edit users, roles, permission boundaries. |
| Indexers | View status, run indexer, failure state. |
| Reports | Existing reports that remain supported. |

### Livewire Admin Requirements

- Grid filters, sorting, pagination, mass actions, and exports are tested.
- Forms test validation, dirty state, save success, save failure, and store-scope fields.
- File uploads test valid, invalid, oversized, and malicious filenames.
- Browser back/forward and deep links behave predictably.

### Acceptance Criteria

- Admin E2E tests pass for every migrated screen.
- Partial-permission admin users cannot access forbidden routes/actions/data.
- Visual parity is approved for every critical admin screen.
- No admin functionality relies on new XML configuration.

## API Test Plan

### Coverage

| API Area | Required Tests |
| --- | --- |
| REST | Auth, methods, filters, pagination, response formats, status codes. |
| XML-RPC | Request/response contracts for supported Magento 1 resources. |
| SOAP | If still supported, WSDL compatibility and common calls. |
| API2 | Supported resource behavior and auth. |
| Error handling | Validation errors, unauthorized, forbidden, not found, server error format. |
| Rate/abuse controls | If introduced, compatibility and documentation. |
| Modern API | OpenAPI docs, versioning, auth, resource serialization. |

### Acceptance Criteria

- Legacy clients continue to work for required endpoints.
- Contract tests cover all supported endpoints.
- Modern API docs match implementation.

## Cron, Queue, And Command Test Plan

### Coverage

| Area | Required Tests |
| --- | --- |
| Schedule registration | Expected jobs appear in Laravel scheduler. |
| Legacy parity | Existing cron behavior is reproduced. |
| Job locking | Duplicate execution is prevented where required. |
| Failure handling | Failures are logged, visible, and retryable where appropriate. |
| Commands | Artisan commands validate input, exit codes, output, and side effects. |
| Queue policy | Sync/Redis/external queue behavior is tested without requiring commerce schema changes unless approved. |
| Magento cron catalog | Every `CJ-001` through `CJ-025` job in `specs/modernization/magento-feature-catalog.md` is preserved, bridged, replaced, or retired with evidence. |

### Acceptance Criteria

- `schedule:list` or equivalent diagnostics show expected tasks.
- Cron fixtures prove each required job runs.
- Failed jobs produce actionable logs.
- Legacy `cron.php` behavior is either preserved or replaced with documented compatibility.

## Security Test Plan

### Coverage

| Area | Required Tests |
| --- | --- |
| Authentication | Storefront and admin login/logout/session timeout. |
| Authorization | Admin permissions, customer data boundaries, API scopes. |
| CSRF | All state-changing web actions reject missing/invalid CSRF tokens. |
| Session security | Cookie flags, fixation prevention, logout invalidation, concurrent sessions policy. |
| Passwords | Hashing, reset tokens, validation, migration compatibility. |
| Uploads | MIME/type validation, filename handling, path traversal prevention. |
| Media/file access | Allowed resources only, no traversal, no private file leakage. |
| XSS | Escaping in Blade/Livewire, admin inputs, CMS rendering policy. |
| SQL injection | Query builder/repository tests for unsafe input. |
| SSRF/RCE | External URL fetches, template execution, file processing. |
| Dependency safety | Composer audit and known-vulnerability checks. |

### Acceptance Criteria

- No critical/high security finding remains open.
- Admin authorization tests cover every migrated admin route.
- File and media tests prove traversal is blocked.
- CSRF tests cover all state-changing routes.

## Performance Test Plan

### Critical Measurements

| Area | Metrics |
| --- | --- |
| Storefront pages | p50/p95 latency, DB queries, memory, cache hit ratio, response size. |
| Admin grids | p50/p95 latency, DB queries, memory, pagination performance. |
| EAV reads | Query count, time per product/category/customer read, batch performance. |
| Checkout | Step latency, total order placement time, external call isolation. |
| APIs | Throughput, p95 latency, error rate. |
| Cron/jobs | Runtime, memory, lock behavior, failure rate. |

### Initial Budgets

Final budgets must be set from Phase 1 baselines. Until then:

- Migrated path must not exceed the numeric baseline-derived p95 latency, query-count, memory, response-size, and cache-hit budgets recorded in `specs/modernization/performance-budgets.md`.
- Query counts must not grow without documented reason.
- Memory usage must not grow without documented reason.
- Cached pages must preserve or improve cache behavior.

### Verification

- Automated performance smoke tests in CI for selected paths.
- Full staging performance run before release.
- Profiling report for any path that exceeds budget.

## Accessibility Test Plan

### Coverage

- Keyboard navigation for storefront and admin.
- Focus states and focus order.
- Form labels, errors, and descriptions.
- Color contrast.
- Screen-reader semantics for navigation, grids, tabs, dialogs, and Livewire updates.
- No keyboard traps.
- Admin grid and form usability.

### Acceptance Criteria

- Automated accessibility checks pass for critical pages.
- Manual keyboard review passes for checkout and core admin workflows.
- Accessibility defects are triaged before release.

## Visual Regression Test Plan

### Required Screens

The full visual list is maintained in `specs/modernization/ui-screen-inventory.md`. At minimum it covers all visible `SF-` and `AD-` feature IDs from `specs/modernization/magento-feature-catalog.md`, including product-type variants, checkout states, admin forms, permission-denied states, report grids, integrations, and cron/index/cache screens.

### Required States

- Default.
- Loading.
- Empty.
- Validation error.
- Permission denied.
- Success message.
- Failure message.
- Every required viewport in `specs/modernization/ui-screen-inventory.md`.

### Acceptance Criteria

- Every migrated screen has a baseline and Laravel comparison screenshot.
- Diffs are reviewed and approved.
- Intentional visual changes are documented.
- The screenshot manifest includes URL, role, fixture ID, viewport, state, timestamp, and artifact path.

## Operational Test Plan

### Coverage

| Area | Required Tests |
| --- | --- |
| Deployment | Build, deploy, warm cache, run health checks. |
| Rollback | Restore previous release and verify app behavior. |
| Backups | Database and media backup/restore rehearsal. |
| Config | Environment config, secret handling, cache invalidation. |
| Logs | Structured logs, error logs, request IDs where applicable. |
| Monitoring | Health endpoints, latency, error rate, queue/scheduler status. |
| Cache | Flush/clear behavior, tag support or documented replacement. |
| Sessions | Session persistence across deploys and cache clears. |
| Scheduler | Jobs run after deploy and do not duplicate unexpectedly. |
| Maintenance mode | Maintenance behavior and admin/operator bypass. |

### Acceptance Criteria

- Staging deployment rehearsal passes.
- Rollback completes within the numeric RTO/RPO recorded in `specs/modernization/performance-budgets.md` or the release runbook.
- Health checks detect app, DB, cache, session, scheduler, and queue failure.
- Operator runbook is reviewed.

## Documentation Test Plan

### Coverage

| Documentation | Acceptance Criteria |
| --- | --- |
| Architecture overview | Explains Laravel runtime, module system, EAV layer, UI architecture, and replacement of legacy concepts. |
| Reasoning | Explains why Laravel, Livewire, no XML, existing DB/EAV, and visual parity were chosen. |
| Module guide | Shows how to create, register, test, enable, and disable a module without XML. |
| Feature guide | Covers storefront, admin, API, cron/jobs, config, extension points, and operational behavior. |
| Developer guide | Covers local setup, tests, coding standards, debugging, and architecture rules. |
| Operator guide | Covers deployment, rollback, cache, scheduler, logs, backups, and troubleshooting. |
| Migration status | Shows which bounded contexts are legacy, bridged, or fully Laravel. |
| Docusaurus user docs | Separate user docs explain retained storefront/admin behavior, screenshots, edge cases, support expectations, and known limitations. |
| Docusaurus developer docs | Separate developer docs explain architecture, modules, EAV, Livewire, testing, removed technologies, operations, and release workflow. |

### Verification

- `mkdocs build` succeeds.
- `npm --prefix docusaurus run build` succeeds.
- `node dev/modernization/smoke-docusaurus.mjs` opens the built Docusaurus site in Chrome/Playwright and the user and developer docs render.
- Navigation includes modernization docs.
- Links are checked.
- A new developer can follow the module guide to build the sample module.

## Domain Completion Matrix

Each domain is done only when all columns are complete.

| Domain | Unit | Integration | Characterization | E2E | Visual | Security | Performance | Docs |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Bootstrap/config | Required | Required | Required | Smoke | N/A | Required | Required | Required |
| Module system | Required | Required | Contract | Smoke | N/A | Required | N/A | Required |
| EAV/catalog data | Required | Required | Required | Required | Required | N/A | Required | Required |
| Storefront catalog | Required | Required | Required | Required | Required | Required | Required | Required |
| Customer/account | Required | Required | Required | Required | Required | Required | Required | Required |
| Cart | Required | Required | Required | Required | Required | Required | Required | Required |
| Checkout | Required | Required | Required | Required | Required | Required | Required | Required |
| Sales/order lifecycle | Required | Required | Required | Required | Required | Required | Required | Required |
| Admin shell | Required | Required | Required | Required | Required | Required | Required | Required |
| Admin catalog | Required | Required | Required | Required | Required | Required | Required | Required |
| Admin sales | Required | Required | Required | Required | Required | Required | Required | Required |
| APIs | Required | Required | Required | Contract/E2E | N/A | Required | Required | Required |
| Cron/jobs | Required | Required | Required | Smoke | N/A | Required | Required | Required |
| Media/files | Required | Required | Required | Required | Required | Required | Required | Required |
| Operations | N/A | Required | N/A | Smoke | N/A | Required | Required | Required |

## Feature ID Traceability Matrix

The final test report must include this matrix populated for every ID in `specs/modernization/magento-feature-catalog.md`.

| Feature ID | Fixture IDs | Legacy Test/Evidence | Laravel Test/Evidence | Visual Evidence | Performance/Security Evidence | Final Status |
| --- | --- | --- | --- | --- | --- | --- |
| SF-001 | Required | Required | Required | Required | Required where applicable | Pending |
| SF-002 | Required | Required | Required | Required | Required where applicable | Pending |
| SF-003 | Required | Required | Required | Required | Required where applicable | Pending |
| SF-004 | Required | Required | Required | Required | Required where applicable | Pending |
| SF-005 | Required | Required | Required | Required | Required where applicable | Pending |
| SF-006 | Required | Required | Required | Required | Required where applicable | Pending |
| SF-007 | Required | Required | Required | Required | Required | Pending |
| SF-008 | Required | Required | Required | Required | Required | Pending |
| SF-009 | Required | Required | Required | Required | Required | Pending |
| SF-010 | Required | Required | Required | Required | Required | Pending |
| SF-011 | Required | Required | Required | Required | Required | Pending |
| SF-012 | Required | Required | Required | Required | Required | Pending |
| SF-013 | Required | Required | Required | Required | Required | Pending |
| SF-014 | Required | Required | Required | Required | Required | Pending |
| SF-015 | Required | Required | Required | Required | Required | Pending |
| SF-016 | Required | Required | Required | Required | Required | Pending |
| AD-001 | Required | Required | Required | Required | Required | Pending |
| AD-002 | Required | Required | Required | Required | Required | Pending |
| AD-003 | Required | Required | Required | Required | Required | Pending |
| AD-004 | Required | Required | Required | Required | Required | Pending |
| AD-005 | Required | Required | Required | Required | Required | Pending |
| AD-006 | Required | Required | Required | Required | Required | Pending |
| AD-007 | Required | Required | Required | Required | Required | Pending |
| AD-008 | Required | Required | Required | Required | Required | Pending |
| AD-009 | Required | Required | Required | Required | Required | Pending |
| AD-010 | Required | Required | Required | Required | Required | Pending |
| AD-011 | Required | Required | Required | Required | Required | Pending |
| AD-012 | Required | Required | Required | Required | Required | Pending |
| AD-013 | Required | Required | Required | Required | Required | Pending |
| AD-014 | Required | Required | Required | Required | Required | Pending |
| AD-015 | Required | Required | Required | Required | Required | Pending |
| AD-016 | Required | Required | Required | Required | Required | Pending |
| AD-017 | Required | Required | Required | Required | Required | Pending |
| AD-018 | Required | Required | Required | Required | Required | Pending |
| CB-001 | Required | Required | Required | N/A unless visible | Required | Pending |
| CB-002 | Required | Required | Required | N/A unless visible | Required | Pending |
| CB-003 | Required | Required | Required | N/A unless visible | Required | Pending |
| CB-004 | Required | Required | Required | N/A unless visible | Required | Pending |
| CB-005 | Required | Required | Required | N/A unless visible | Required | Pending |
| CB-006 | Required | Required | Required | N/A unless visible | Required | Pending |
| CB-007 | Required | Required | Required | N/A unless visible | Required | Pending |
| CB-008 | Required | Required | Required | N/A unless visible | Required | Pending |
| CB-009 | Required | Required | Required | N/A unless visible | Required | Pending |
| CB-010 | Required | Required | Required | N/A unless visible | Required | Pending |
| CB-011 | Required | Required | Required | N/A unless visible | Required | Pending |
| CB-012 | Required | Required | Required | N/A unless visible | Required | Pending |
| CB-013 | Required | Required | Required | N/A unless visible | Required | Pending |
| CB-014 | Required | Required | Required | N/A unless visible | Required | Pending |
| API-001 | Required | Required | Required | N/A | Required | Pending |
| API-002 | Required | Required | Required | N/A | Required | Pending |
| API-003 | Required | Required | Required | N/A | Required | Pending |
| API-004 | Required | Required | Required | N/A | Required | Pending |
| API-005 | Required | Required | Required | N/A | Required | Pending |
| API-006 | Required | Required | Required | N/A | Required | Pending |
| CJ-001 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-002 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-003 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-004 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-005 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-006 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-007 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-008 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-009 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-010 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-011 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-012 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-013 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-014 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-015 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-016 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-017 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-018 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-019 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-020 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-021 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-022 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-023 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-024 | Required | Required | Required | N/A | Operational evidence required | Pending |
| CJ-025 | Required | Required | Required | N/A | Operational evidence required | Pending |

The final report must keep one row per `SF-*`, `AD-*`, `CB-*`, `API-*`, and `CJ-*` ID from `specs/modernization/magento-feature-catalog.md`; grouped ranges are not accepted in the final release report.

Preparation verification uses `php dev/modernization/validate-feature-traceability.php --strict` as a template coverage check. Final release verification must use `php dev/modernization/validate-feature-traceability.php --final`, which fails while placeholder evidence such as `TBD`, `Required`, or `Pending` remains in the traceability artifacts.

## Release Readiness Checklist

The final release cannot proceed unless every item is checked:

- Specs approved.
- Test fixtures approved.
- Legacy characterization suite green.
- Laravel unit/integration suite green.
- E2E suite green.
- API contract suite green.
- Visual regression approved.
- Accessibility checks approved.
- Performance budgets approved.
- Security checks approved.
- No-new-XML checks green.
- Database schema preservation checks green.
- EAV parity checks green.
- Admin permission checks green.
- Scheduler/queue checks green.
- Deployment rehearsal complete.
- Rollback rehearsal complete.
- Documentation website builds.
- Feature guide complete.
- Operator runbook complete.
- Open defects reviewed and accepted according to severity policy.

## Defect Severity Policy

| Severity | Meaning | Release Rule |
| --- | --- | --- |
| P0 | Data loss, payment/order corruption, security critical, app unavailable. | Blocks release. |
| P1 | Broken critical journey, authorization bypass, severe performance regression. | Blocks release. |
| P2 | Important feature regression with workaround or limited scope. | Requires explicit acceptance. |
| P3 | Minor bug, copy issue, visual deviation within low-risk area. | Can ship if tracked. |

## Evidence Required For Sign-Off

For each phase and final release, retain:

- CI run links or logs.
- Test reports.
- Visual regression reports.
- Performance reports.
- Security scan output.
- Schema preservation report.
- EAV parity report.
- Manual acceptance checklist.
- Documentation build output.
- Release and rollback rehearsal notes.
