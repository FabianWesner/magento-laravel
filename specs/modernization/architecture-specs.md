---
tags:
- Development
---

# Architecture Specs

The modernization must be specified before implementation. These specs are required because Magento behavior is configuration-heavy and easy to break invisibly.

## Required Specs

| Spec | Purpose | Must Define | Verification |
| --- | --- | --- | --- |
| Laravel bootstrap | How Laravel enters the current runtime and eventually replaces it. | Container boot, HTTP kernel, CLI, fallback, config, error handling. | Bootstrap smoke tests, route tests. |
| No-XML module system | How modules register without XML. | Manifest format, service providers, routes, events, views, commands, permissions, config, dependency ordering. | Sample module tests. |
| EAV access layer | How Laravel reads/writes Magento EAV. | Metadata, backend tables, store scope fallback, options, validation, transactions. | EAV parity tests. |
| Configuration | How config moves from XML/DB/env to typed Laravel config. | Scope resolution, env override, cache, secrets, admin edits. | Config parity tests. |
| Storefront UI | How Blade/Livewire replaces layout XML/blocks/templates. | Layouts, components, view models, assets, visual parity, caching. | E2E and visual tests. |
| Admin UI | How Livewire replaces admin grids/forms. | Auth, permissions, grids, forms, uploads, store scope, messages. | Admin E2E and permission tests. |
| Routing | How Laravel and legacy routes coexist. | Route ownership, fallback, URL rewrites, admin frontname, store scope. | Route compatibility tests. |
| Events/jobs | How observers/cron/shell scripts become Laravel events/scheduler/commands. | Event classes, compatibility bridge, queues, retries, diagnostics. | Scheduler and event tests. |
| APIs | How legacy APIs are preserved and modern APIs are added. | Auth, payloads, versioning, resources, error formats, docs. | Contract tests. |
| Auth/security | How customer/admin auth and authorization work. | Sessions, CSRF, password policy, gates/policies, roles. | Security tests. |
| Operations | How deploys, cache, sessions, logs, scheduler, queues, and rollback work. | Runbooks, health checks, cache/session strategy, monitoring. | Staging rehearsal. |

## Spec Template

Every spec must include:

- Problem statement.
- Existing Magento behavior.
- Target Laravel behavior.
- Compatibility decisions.
- Public contracts.
- Data model impact.
- UI impact.
- Security impact.
- Performance expectations.
- Rollout approach.
- Rollback approach.
- Test coverage.
- Open questions.

## Architecture Rules

- New modules do not use XML.
- New code depends on contracts, not `Mage::` static access.
- EAV complexity stays behind repositories and query services.
- Livewire components do not directly own business rules.
- Domain services do not depend on UI components.
- Compatibility adapters are isolated and have removal criteria.
- Every route has a declared owner: Laravel, legacy, or bridge.

## Initial Spec Decisions

These decisions are the starting point for detailed design. They can change only through an explicit decision record.

### Laravel Bootstrap

- Bootstrap Laravel beside the legacy runtime first.
- Keep legacy entry points working until route ownership is migrated.
- Bind Magento-compatible infrastructure behind Laravel contracts: config, DB, cache, session, events, logger, filesystem, URL, auth, translation.
- Add a route fallback so Laravel can own selected routes while legacy handles unmigrated routes.
- Treat compatibility adapters as temporary and measurable.

### Module System

- Module metadata is declared in PHP, not XML.
- Modules register behavior through service providers and explicit manifests.
- Manifests may declare dependencies, routes, commands, events, listeners, policies, permissions, config, views, assets, Livewire components, and migrations/installers if approved.
- Dependency ordering and cycle detection are required.
- Disabled modules must register nothing except diagnostic metadata.

### EAV And Database

- Existing Magento tables remain the source of truth.
- Eloquent is allowed for flat/reference tables where it does not hide Magento semantics.
- Product, category, customer, and address EAV access goes through dedicated repositories and query services.
- Store-scope fallback must match Magento behavior.
- Writes require explicit transaction policy and parity tests.
- No destructive schema migration is allowed as part of modernization.

### Storefront UI

- Blade layouts replace layout XML for migrated routes.
- Livewire handles dynamic interactions.
- View models carry data from services into templates.
- Existing visual language, CSS, and assets are preserved where practical.
- Every migrated screen requires visual regression coverage.

### Admin UI

- Admin pages use Blade and Livewire.
- Admin grids and forms become reusable Livewire components.
- Admin authorization uses gates, policies, and module-provided permission manifests.
- Store-scope config/form behavior must be preserved.
- Every migrated admin route requires permission tests.

### Events, Jobs, And Commands

- Magento events map to Laravel event classes where behavior is migrated.
- Legacy event names may be bridged during transition.
- Cron jobs become scheduler entries and Artisan commands.
- Long-running jobs require locking, retry, failure logging, and diagnostics.

### APIs

- Required legacy endpoints are contract-tested before replacement.
- Laravel routes may serve migrated API endpoints behind identical contracts.
- Modern APIs require versioning and OpenAPI documentation.
- Legacy clients must continue to work for all endpoints marked `preserve`.
