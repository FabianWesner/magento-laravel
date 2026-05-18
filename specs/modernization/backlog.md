---
tags:
- Development
---

# Migration Backlog

This backlog converts preparation work into executable tasks. Estimates and named owners must be filled in once the actual project inventory is complete. Every implementation task must also link to `specs/progress.md` and to a commit, pull request, screenshot set, test report, or other durable evidence.

## Backlog Fields

Each task must include:

- Phase.
- Domain.
- Feature IDs or scope. Use catalog feature IDs for product behavior and `ARCH`, `DOC`, `OPS`, or `TOOL` for cross-cutting preparation tasks.
- Description.
- Dependencies.
- Risk.
- Acceptance criteria.
- Verification command or evidence.
- Progress entry.
- Commit or evidence reference.
- Owner.
- Status.

Status values:

- `To do`: not started.
- `In progress`: active work.
- `Blocked`: waiting on access, decision, data, service, or dependency.
- `Review`: implementation complete and awaiting review.
- `Done`: accepted with evidence.

Risk values:

- `P0`: critical risk to data, money movement, security, checkout, order lifecycle, or rollback.
- `P1`: important risk to parity, operations, compatibility, performance, integrations, or user productivity.
- `P2`: limited or local risk.

## Phase 0: Inventory And Baseline

| Phase | Scope | Task | Dependencies | Risk | Owner | Status | Progress Entry | Commit/Evidence | Acceptance Criteria | Verification |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 0 | ARCH | Add real project code locally | Repository access | P1 | TBD | Blocked | `specs/progress.md` entry required when access changes. | Pending project repository or overlay evidence. | Project overlay and Magento CE `1.9.4.5` source are available in one workspace. | `git status`, module inventory. |
| 0 | ARCH | Define latest PHP target | PHP runtime decision | P1 | TBD | In progress | Track runtime decisions in `specs/progress.md`. | PHP target recorded in specs. | Laravel target runtime uses the latest stable PHP, currently PHP `8.5.x` as of 2026-05-18; legacy Magento PHP `7.4` is isolated to smoke tests. | `php -v`, Composer platform check, CI matrix. |
| 0 | TOOL | Verify Laravel Boost installability | Latest PHP target | P2 | TBD | Review | Record command result in `specs/progress.md`. | `laravel/composer.lock`; commit hash pending. | `laravel/boost` installs as a dev dependency in the target Laravel workspace without Composer conflicts. | `composer show laravel/boost`, `php artisan boost:install`. |
| 0 | TOOL | Verify Laravel Boost MCP tooling | Laravel Boost installed | P2 | TBD | In progress | Record MCP tool evidence in `specs/progress.md`. | MCP JSON-RPC output in install verification. | Boost MCP server lists tools and can report application info, documentation search, schema, and read-only database checks from the Laravel target. | Manual JSON-RPC `tools/list`, `application-info`, `search-docs`, `database-schema`, and read-only `database-query` checks, or MCP client tool list. |
| 0 | ARCH | Define removed-technology gate | Technology removal policy | P1 | TBD | Review | Record scan result in `specs/progress.md`. | `specs/modernization/technology-removal-policy.md`; commit hash pending. | Banned legacy runtime technologies are listed and scan tooling exists for Laravel target paths. | `php dev/modernization/validate-removed-technologies.php`. |
| 0 | DOC | Install Docusaurus docs site | Node/npm available | P2 | TBD | Review | Record build and browser smoke in `specs/progress.md`. | `docusaurus/package-lock.json`, browser smoke evidence; commit hash pending. | `docusaurus/` contains buildable user and developer docs with lockfile. | `npm --prefix docusaurus run build`, `node dev/modernization/smoke-docusaurus.mjs`. |
| 0 | ALL | Capture database and media fixtures | Data access | P1 | TBD | Blocked | Add progress entry when project dump/media arrives. | Pending sanitized fixture manifest. | Sanitized DB/media fixtures are reproducible and mapped to feature IDs. | Restore fixture locally and compare schema checksum. |
| 0 | ALL | Inventory custom modules | Project code | P1 | TBD | Blocked | Add progress entry when project overlay arrives. | Pending module inventory. | Every module has owner, purpose, risk, migration decision, and affected feature IDs. | Inventory report reviewed. |
| 0 | API-004, API-005, API-006 | Inventory integrations | Business/ops input | P1 | TBD | To do | Track integration owner outreach in `specs/progress.md`. | Pending integration matrix. | Every integration has sandbox, fake, mock, outage, retry, and rollback plan. | Integration matrix reviewed. |
| 0 | SF-001 through SF-016, AD-001 through AD-018 | Capture visual baseline | Running current app | P1 | TBD | In progress | Record screenshot batch in `specs/progress.md`. | Magento smoke screenshots and future full baseline set. | Required storefront/admin screenshots exist for all visible screens and viewports. | Visual baseline report with Chrome screenshots. |
| 0 | ALL | Capture performance baseline | Running current app | P1 | TBD | To do | Record baseline run in `specs/progress.md`. | Pending performance report. | Critical journeys have p50/p95/query/memory metrics. | Performance report. |
| 0 | ALL | Capture edge-case baseline | Feature catalog and fixtures | P1 | TBD | To do | Record characterization batches in `specs/progress.md`. | Pending edge-case evidence. | Normal, boundary, failure, permission-denied, stale-cache/index, integration-outage, concurrency, and recovery cases are represented in fixtures and characterization plans. | Fixture manifest and characterization evidence reviewed. |

## Phase 1: Test Completion

| Phase | Scope | Task | Dependencies | Risk | Owner | Status | Progress Entry | Commit/Evidence | Acceptance Criteria | Verification |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | SF-001 through SF-016 | Fill storefront E2E gaps | Feature inventory, visual baseline | P1 | TBD | To do | One entry per completed journey batch. | Playwright reports and screenshots. | Critical storefront journeys and edge cases are covered. | Playwright/Chrome green. |
| 1 | AD-001 through AD-018 | Fill admin E2E gaps | Feature inventory, visual baseline | P1 | TBD | To do | One entry per completed admin batch. | Playwright reports and screenshots. | Critical admin journeys, permissions, validation errors, and bulk actions are covered. | Playwright/Chrome green. |
| 1 | CB-001 through CB-014 | Add commerce behavior parity tests | Fixture DB, reverse-engineering specs | P0 | TBD | To do | One entry per behavior cluster. | PHPUnit reports and captured Magento outputs. | Cart, checkout, pricing, tax, shipping, payment, order, EAV, index, cache, and email behavior is locked. | PHPUnit 12 green and characterization evidence reviewed. |
| 1 | API-001 through API-006 | Add API contract tests | API inventory | P1 | TBD | To do | One entry per API surface. | API contract report. | Required SOAP, XML-RPC, REST/API2, payment, shipping, and service endpoints are covered. | PHPUnit 12 API tests green. |
| 1 | CJ-001 through CJ-025 | Add cron/job parity tests | Fixture DB, cron catalog | P1 | TBD | To do | One entry per cron batch. | Scheduler and queue test reports. | Every Magento cron feature has retain, replace, bridge, or retire decision and verification. | Scheduler tests, queue tests, report table comparisons. |
| 1 | ARCH | Add no-new-XML static check | Architecture rules | P1 | TBD | Review | Record gate result in `specs/progress.md`. | Tool output; CI wiring and commit hash pending. | Local gate fails on new XML registration in migrated code/spec areas; CI wiring is required before final release. | `php dev/modernization/validate-no-new-xml.php specs laravel/app laravel/config laravel/routes laravel/resources laravel/database laravel/modules laravel/packages docs/content/modernization docusaurus/docs`. |
| 1 | ARCH | Add removed-technology static check | Technology removal policy | P1 | TBD | Review | Record gate result in `specs/progress.md`. | Tool output; CI wiring and commit hash pending. | Local gate fails when Laravel target code references banned Magento/Zend/Varien/XML/layout/block/resource/Prototype-era runtime technologies; CI wiring is required before final release. | `php dev/modernization/validate-removed-technologies.php`. |
| 1 | ALL | Add production-readiness tests | Feature catalog | P1 | TBD | To do | One entry per readiness gate. | Traceability matrix. | Critical features include edge, failure, resilience, observability, recovery, security, accessibility, and performance coverage. | Test plan traceability matrix green. |

## Phase 2: Specification

| Phase | Scope | Task | Dependencies | Risk | Owner | Status | Progress Entry | Commit/Evidence | Acceptance Criteria | Verification |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 2 | ARCH | Approve compatibility policy | Inventory | P1 | TBD | Review | Record approval in `specs/progress.md`. | Policy sign-off. | No unknown decisions for Phase 3 scope. | Policy sign-off. |
| 2 | ARCH | Write Laravel bootstrap spec | Compatibility policy, PHP runtime decision | P1 | TBD | Review | Record spec review in `specs/progress.md`. | Spec review. | Runtime isolation and fallback are defined without loading Laravel inside Magento PHP `7.4`. | Spec review. |
| 2 | ARCH | Write module system spec | Compatibility policy, technology removal policy | P1 | TBD | Review | Record spec review in `specs/progress.md`. | Spec review. | No-XML extension points, manifests, service providers, policies, typed config, and module dependencies are defined. | Spec review. |
| 2 | CB-011 | Write EAV spec | Inventory, parity tests | P0 | TBD | Review | Record spec review in `specs/progress.md`. | Spec review. | Read/write, store scope, validation, backend/source model replacement, and no direct Eloquent writes for EAV are defined. | Spec review. |
| 2 | SF-001 through SF-016, AD-001 through AD-018 | Write UI specs | Visual baseline | P1 | TBD | Review | Record spec review in `specs/progress.md`. | UI inventory and screenshot manifest. | Storefront/admin Livewire plans, screenshots, states, and edge cases are defined. | Spec review. |
| 2 | DOC | Write Docusaurus docs plan | Documentation decision | P2 | TBD | Review | Record Docusaurus checks in `specs/progress.md`. | Docusaurus source docs and build evidence. | User and developer docs structure, build command, browser verification, and ownership are defined. | Docusaurus build and browser smoke. |
| 2 | ARCH | Write removed-technology policy | Architecture rules | P1 | TBD | Review | Record static scan in `specs/progress.md`. | Policy and scan evidence; commit hash pending. | Banned technologies, allowed legacy baseline scope, bridge policy, and verification are explicit. | Policy review and static scan. |

## Phase 3: POCs

| Phase | Scope | Task | Dependencies | Risk | Owner | Status | Progress Entry | Commit/Evidence | Acceptance Criteria | Verification |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 3 | ARCH | Laravel beside Magento | Bootstrap spec, latest PHP target, legacy PHP isolation | P1 | TBD | To do | One entry when POC starts and one when accepted. | POC branch, smoke report. | Laravel boots in the parallel PHP `8.5+` runtime without changing Magento behavior. | Laravel smoke, Magento smoke, CI matrix. |
| 3 | CB-011 | Read-only EAV repository | EAV spec, fixture DB | P0 | TBD | To do | One entry for each entity class covered. | Parity report. | Fixture product/category/customer reads match legacy including store-scope fallback. | EAV parity tests and query-count report. |
| 3 | ARCH | No-XML sample module | Module spec, technology removal policy | P1 | TBD | To do | One entry when sample module is accepted. | Sample module test report. | Module registers services/routes/views/events/config/permissions without XML. | Sample module tests and static no-XML scan. |
| 3 | AD-002, AD-005, AD-007 | Livewire admin grid | Admin UI spec, ACL spec, visual baseline | P1 | TBD | To do | One entry for grid POC evidence. | Visual and E2E reports. | Grid matches admin look and behavior including permissions, validation, filters, sort, pagination, mass actions, and errors. | Visual/E2E test. |
| 3 | SF-002, SF-003, SF-005 | Livewire storefront page | Storefront UI spec, visual baseline | P1 | TBD | To do | One entry for storefront POC evidence. | Visual and E2E reports. | Page matches storefront baseline, responsive states, messages, assets, and interaction behavior. | Visual/E2E/performance test. |
| 3 | ARCH | Route fallback | Routing spec, ADR 0008, auth/session boundary spec, cross-runtime security tests | P1 | TBD | Blocked | Record boundary approval before implementation starts. | Approved ADR 0008 follow-up spec and route test report. | Laravel and Magento routes coexist with explicit customer/admin session, cookie, CSRF/form-key, password-hash, rollback, and observability behavior. | Route compatibility tests and cross-runtime authenticated storefront/admin/cart/checkout/API tests. |

## Phase 4 And Beyond

Use the roadmap phases for full implementation. Do not start high-risk domains such as checkout or sales until:

- POCs pass.
- Feature inventory is complete.
- Test plan gates are implemented in CI.
- Risk register has owners and mitigations.
- Route fallback auth/session boundary is approved.
- `specs/progress.md` links the latest evidence and commits.
