---
tags:
- Development
---

# Proof Of Concepts

These spikes are required before committing to the full modernization path.

## POC 1: Laravel Bootstrap Beside Magento

Goal: prove Laravel can boot beside Magento in the parallel PHP `8.5+` runtime without changing user-visible Magento behavior.

Acceptance criteria:

- Laravel container resolves services during Laravel web and CLI requests.
- Laravel is not loaded inside Magento's PHP `7.4` process.
- Legacy `index.php`, `api.php`, `cron.php`, and shell scripts still work.
- No routes are moved yet.
- Tests show no behavior change.

Verification:

- Bootstrap smoke test.
- Existing PHPUnit smoke suite.
- Existing storefront/admin smoke tests.

## POC 2: Read-Only EAV Repository

Goal: prove Laravel can read Magento EAV correctly without schema migration.

Acceptance criteria:

- Product, category, and customer reads match legacy fixtures.
- Store-scope fallback matches legacy behavior.
- Query counts are recorded.
- Eloquent is used only where safe; EAV reads go through repositories.

Verification:

- EAV parity PHPUnit tests.
- Query-count report.

## POC 3: No-XML Sample Module

Goal: prove new extensibility works without XML.

Acceptance criteria:

- Module registers service provider, route, command, event listener, permission, config, view, and Livewire component from PHP manifest.
- Module can be enabled/disabled.
- Dependency ordering is tested.

Verification:

- Sample module integration tests.
- Static no-XML check.

## POC 4: Livewire Admin Grid

Goal: prove Livewire can reproduce admin grid behavior and look.

Acceptance criteria:

- Filtering, sorting, pagination, mass actions, row actions, validation, permissions, and loading states work.
- Visual baseline matches current admin.

Verification:

- Livewire component tests.
- Admin E2E test.
- Visual regression report.

## POC 5: Livewire Storefront Page

Goal: prove Blade/Livewire can preserve storefront look and behavior.

Acceptance criteria:

- One low-risk storefront page renders through Laravel.
- Header/footer/messages/assets match baseline.
- Dynamic interaction works without layout shift.

Verification:

- Browser E2E test.
- Visual regression report.
- Performance comparison.

## POC 6: Route Fallback

Goal: prove Laravel can own selected routes while legacy handles unmigrated routes.

Acceptance criteria:

- ADR 0008 is implemented by an approved auth/session boundary spec before coding starts.
- Laravel route group and legacy route group coexist.
- Store scope, URL rewrites, customer sessions, admin sessions, cookies, CSRF/form keys, password hashes, and errors behave correctly.
- Rollback behavior is explicit for each moved route.
- Route ownership is observable in logs.

Verification:

- Route compatibility tests.
- Cross-runtime authenticated storefront, admin, cart, checkout, API, and permission-denied tests.
- E2E tests across Laravel and legacy pages.
