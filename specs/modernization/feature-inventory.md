---
tags:
- Development
---

# Feature Inventory

This inventory defines the feature coverage that must be completed before a domain is considered ready for migration. The canonical feature list is `specs/modernization/magento-feature-catalog.md`; this file is the per-feature worksheet to fill while inventorying core and project behavior.

Every row must include a feature ID from the catalog. Project-specific features use the `PX-` prefix and must be added to the catalog before implementation.

## Required Per-Feature Fields

| Field | Required Content |
| --- | --- |
| Feature ID | Stable ID from `magento-feature-catalog.md`. |
| Owner | Business or engineering owner who can approve parity or retirement. |
| Magento entry points | Controllers, routes, admin menu items, cron jobs, API resources, shell commands, observers, templates. |
| Project overlay files | Custom modules, theme files, local rewrites, integrations, config paths. |
| Decision | `preserve`, `bridge`, `replace`, or `retire`. |
| Fixture set | Demo, edge, project, or performance fixture that proves the behavior. |
| UI evidence | Screenshot IDs from `ui-screen-inventory.md` when visible. |
| Characterization evidence | Legacy tests, captured payloads, DB snapshots, logs, emails, events, screenshots. |
| Laravel evidence | Unit, integration, Livewire, browser, API, visual, performance, and security tests. |
| Risk | Data loss, order/payment/tax risk, visual risk, integration risk, performance risk. |
| Acceptance criteria | Observable done condition and approved differences. |
| Verification command | Exact command or manual checklist used for sign-off. |
| Edge-case coverage | Boundary, failure, permission, concurrency, stale cache/index, integration outage, and recovery cases required by the feature. |

## Traceability Requirement

The modernization is not done until every feature ID in `specs/modernization/magento-feature-catalog.md` has an inventory row and every inventory row links to test-plan evidence.

## Canonical Per-ID Inventory Worksheet

This worksheet is intentionally one row per catalog feature ID. Grouped planning tables below are only convenience views; final sign-off must update every row here with owner, decision, fixture, evidence, and status.

| Feature ID | Owner | Decision | Fixture IDs | UI/Complex Evidence | Test Evidence | Status |
| --- | --- | --- | --- | --- | --- | --- |
| SF-001 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-002 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-003 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-004 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-005 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-006 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-007 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-008 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-009 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-010 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-011 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-012 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-013 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-014 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-015 | TBD | TBD | TBD | TBD | TBD | To inventory |
| SF-016 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-001 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-002 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-003 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-004 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-005 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-006 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-007 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-008 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-009 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-010 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-011 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-012 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-013 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-014 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-015 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-016 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-017 | TBD | TBD | TBD | TBD | TBD | To inventory |
| AD-018 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CB-001 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CB-002 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CB-003 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CB-004 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CB-005 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CB-006 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CB-007 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CB-008 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CB-009 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CB-010 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CB-011 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CB-012 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CB-013 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CB-014 | TBD | TBD | TBD | TBD | TBD | To inventory |
| API-001 | TBD | TBD | TBD | TBD | TBD | To inventory |
| API-002 | TBD | TBD | TBD | TBD | TBD | To inventory |
| API-003 | TBD | TBD | TBD | TBD | TBD | To inventory |
| API-004 | TBD | TBD | TBD | TBD | TBD | To inventory |
| API-005 | TBD | TBD | TBD | TBD | TBD | To inventory |
| API-006 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-001 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-002 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-003 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-004 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-005 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-006 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-007 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-008 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-009 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-010 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-011 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-012 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-013 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-014 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-015 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-016 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-017 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-018 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-019 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-020 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-021 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-022 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-023 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-024 | TBD | TBD | TBD | TBD | TBD | To inventory |
| CJ-025 | TBD | TBD | TBD | TBD | TBD | To inventory |

## Storefront

| Feature IDs | Required Details | Status |
| --- | --- | --- |
| SF-001, SF-002 | CMS content, shell, blocks, product widgets, banners, cache behavior. | To inventory |
| SF-003, SF-004 | Layered navigation, sorting, pagination, product types, empty states. | To inventory |
| SF-005 | Simple, configurable, bundle, grouped, downloadable, virtual, custom options, media. | To inventory |
| SF-006 | Native search, redirects, empty results, terms and advanced search. | To inventory |
| SF-007, CB-001 through CB-006 | Add/update/remove, coupons, estimates, persistent cart, messages, totals. | To inventory |
| SF-008, SF-009, CB-007 through CB-010 | Guest, registered, multishipping, shipping/payment/tax/totals. | To inventory |
| SF-010, SF-011 | Register, login, reset password, addresses, orders, wishlist, newsletter. | To inventory |
| SF-012 | Store switch, scope-specific config/content/products, currencies, locales. | To inventory |
| SF-013, CB-014 | Transactional templates, queue, attachments, localization. | To inventory |
| SF-014 through SF-016 | SEO, feeds, optional modules, external payment redirects. | To inventory |

## Admin

| Feature IDs | Required Details | Status |
| --- | --- | --- |
| AD-001 | Login, logout, timeout, password policy, dashboard, notifications. | To inventory |
| AD-002 through AD-004, CB-011, CB-012 | Product/category grids/forms, attributes, media, inventory, websites, indexes. | To inventory |
| AD-007 | Grids/forms, addresses, groups, customer attributes. | To inventory |
| AD-005, AD-006, CB-010 | Orders, invoices, shipments, credit memos, refunds, comments, emails. | To inventory |
| AD-008, CB-004, CB-005 | Catalog rules, cart rules, coupons. | To inventory |
| AD-009 | Pages, blocks, widgets, design assignments, media browser. | To inventory |
| AD-010 | Scope behavior, validation, secrets, env overrides. | To inventory |
| AD-011, CB-012, CB-013 | Status, actions, permissions, long-running behavior. | To inventory |
| AD-012 | ACL, custom permissions, API roles, OAuth consumers/tokens. | To inventory |
| AD-013, AD-014 | All reports, exports, scheduled exports, custom grids, imports. | To inventory |
| AD-015 through AD-018 | Newsletter, polls, tax, currency, store operations, integration admin. | To inventory |

## Integrations

| Area | Required Details | Status |
| --- | --- | --- |
| Payments | Providers, auth/capture/refund/void, webhooks, sandbox. | To inventory |
| Shipping | Carriers, rates, labels, tracking, customs. | To inventory |
| Tax | Calculation provider, rules, exemptions, rounding. | To inventory |
| Search | Engine, indexing, query behavior, failover. | To inventory |
| ERP/PIM/CRM | Sync direction, schedule, data ownership, failure handling. | To inventory |
| Email/SMS | Provider, templates, bounce/failure behavior. | To inventory |
| Analytics/tracking | Scripts, data layer, consent, conversions. | To inventory |
| Feeds | Product feeds, order exports, scheduled jobs. | To inventory |

## Completion Criteria

- Each feature ID has an owner.
- Each feature ID has a migration decision: `preserve`, `bridge`, `replace`, or `retire`.
- Each feature ID maps to test-plan coverage.
- Each integration has sandbox credentials or a mock strategy.
- Each visible feature has visual baseline requirements and screenshot evidence.
- Each complex commerce feature has reverse-engineering evidence before Laravel replacement starts.
- Each critical feature has edge-case and failure-path coverage, not only happy-path acceptance.
