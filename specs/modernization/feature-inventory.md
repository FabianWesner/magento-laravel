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
| SF-001 | modernization | preserve | sample smoke; canonical fixture absent | UI screenshot set absent | No Laravel feature test yet | Not release-ready |
| SF-002 | modernization | preserve | sample smoke; canonical fixture absent | domain-service-evidence; UI screenshot set absent | DomainFoundationTest | Not release-ready |
| SF-003 | modernization | preserve | sample smoke; canonical fixture absent | domain-service-evidence; UI screenshot set absent | DomainFoundationTest | Not release-ready |
| SF-004 | modernization | preserve | sample smoke; canonical fixture absent | domain-service-evidence; UI screenshot set absent | DomainFoundationTest | Not release-ready |
| SF-005 | modernization | preserve | sample smoke; canonical fixture absent | domain-service-evidence; commerce-parity-evidence; UI screenshot set absent | DomainFoundationTest; CommerceFoundationTest | Not release-ready |
| SF-006 | modernization | preserve | sample smoke; canonical fixture absent | domain-service-evidence; UI screenshot set absent | DomainFoundationTest | Not release-ready |
| SF-007 | modernization | preserve | canonical fixture absent | commerce-parity-evidence; UI screenshot set absent | CommerceFoundationTest | Not release-ready |
| SF-008 | modernization | preserve | canonical fixture absent | commerce-parity-evidence; UI screenshot set absent | CommerceFoundationTest | Not release-ready |
| SF-009 | modernization | preserve | canonical fixture absent | commerce-parity-evidence; UI screenshot set absent | CommerceFoundationTest | Not release-ready |
| SF-010 | modernization | preserve | sample smoke; canonical fixture absent | domain-service-evidence; auth-security tests; UI screenshot set absent | DomainFoundationTest; AuthSecurityFoundationTest | Not release-ready |
| SF-011 | modernization | preserve | sample smoke; canonical fixture absent | domain-service-evidence; UI screenshot set absent | DomainFoundationTest | Not release-ready |
| SF-012 | modernization | preserve | sample smoke; canonical fixture absent | config-parity-evidence | ScopedConfigTest | Not release-ready |
| SF-013 | modernization | preserve | canonical fixture absent | domain-service-evidence; commerce-parity-evidence; UI screenshot set absent | DomainFoundationTest; CommerceFoundationTest | Not release-ready |
| SF-014 | modernization | preserve | canonical fixture absent | domain-service-evidence; UI screenshot set absent | DomainFoundationTest | Not release-ready |
| SF-015 | modernization | bridge until project overlay | integration sandbox fixture absent | integration-matrix; integration-parity-evidence; UI screenshot set absent | IntegrationFoundationTest | Not release-ready |
| SF-016 | modernization | bridge until project overlay | integration sandbox fixture absent | domain-service-evidence; integration-matrix; integration-parity-evidence | DomainFoundationTest; IntegrationFoundationTest | Not release-ready |
| AD-001 | modernization | preserve | admin role fixture absent | auth-security tests; UI screenshot set absent | AuthSecurityFoundationTest | Not release-ready |
| AD-002 | modernization | preserve | sample smoke; canonical fixture absent | domain-service-evidence; commerce-parity-evidence; UI screenshot set absent | DomainFoundationTest; CommerceFoundationTest | Not release-ready |
| AD-003 | modernization | preserve | sample smoke; canonical fixture absent | domain-service-evidence; UI screenshot set absent | DomainFoundationTest | Not release-ready |
| AD-004 | modernization | preserve | sample smoke; canonical fixture absent | eav-parity-evidence; UI screenshot set absent | EavAttributeValueReaderTest | Not release-ready |
| AD-005 | modernization | preserve | sample smoke; canonical fixture absent | commerce-parity-evidence; report-parity-evidence; UI screenshot set absent | CommerceFoundationTest; ReportFoundationTest | Not release-ready |
| AD-006 | modernization | preserve | sample smoke; canonical fixture absent | commerce-parity-evidence; report-parity-evidence; UI screenshot set absent | CommerceFoundationTest; ReportFoundationTest | Not release-ready |
| AD-007 | modernization | preserve | sample smoke; canonical fixture absent | domain-service-evidence; UI screenshot set absent | DomainFoundationTest | Not release-ready |
| AD-008 | modernization | preserve | sample smoke; canonical fixture absent | commerce-parity-evidence; report-parity-evidence; UI screenshot set absent | CommerceFoundationTest; ReportFoundationTest | Not release-ready |
| AD-009 | modernization | preserve | sample smoke; canonical fixture absent | domain-service-evidence; UI screenshot set absent | DomainFoundationTest | Not release-ready |
| AD-010 | modernization | preserve | scoped config fixture in test; canonical fixture absent | config-parity-evidence | ScopedConfigTest | Not release-ready |
| AD-011 | modernization | preserve | canonical fixture absent | Livewire foundation tests; UI screenshot set absent | LivewireParityFoundationTest | Not release-ready |
| AD-012 | modernization | preserve | API role fixture absent | auth-security tests; api-contract-evidence | AuthSecurityFoundationTest; ApiContractFoundationTest | Not release-ready |
| AD-013 | modernization | preserve | canonical fixture absent | domain-service-evidence | DomainFoundationTest | Not release-ready |
| AD-014 | modernization | preserve | report fixture in test; canonical fixture absent | report-parity-evidence | ReportFoundationTest | Not release-ready |
| AD-015 | modernization | preserve | canonical fixture absent | domain-service-evidence; UI screenshot set absent | DomainFoundationTest | Not release-ready |
| AD-016 | modernization | preserve | canonical fixture absent | commerce-parity-evidence; config-parity-evidence; UI screenshot set absent | CommerceFoundationTest; ScopedConfigTest | Not release-ready |
| AD-017 | modernization | preserve | canonical fixture absent | domain-service-evidence; UI screenshot set absent | DomainFoundationTest | Not release-ready |
| AD-018 | modernization | bridge until project overlay | integration sandbox fixture absent | integration-matrix; integration-parity-evidence; UI screenshot set absent | IntegrationFoundationTest | Not release-ready |
| CB-001 | modernization | preserve | commerce fixture in test; canonical fixture absent | commerce-parity-evidence | CommerceFoundationTest | Not release-ready |
| CB-002 | modernization | preserve | commerce fixture in test; canonical fixture absent | commerce-parity-evidence; domain-service-evidence | CommerceFoundationTest; DomainFoundationTest | Not release-ready |
| CB-003 | modernization | preserve | commerce fixture in test; canonical fixture absent | commerce-parity-evidence | CommerceFoundationTest | Not release-ready |
| CB-004 | modernization | preserve | commerce fixture in test; canonical fixture absent | commerce-parity-evidence | CommerceFoundationTest | Not release-ready |
| CB-005 | modernization | preserve | commerce fixture in test; canonical fixture absent | commerce-parity-evidence | CommerceFoundationTest | Not release-ready |
| CB-006 | modernization | preserve | commerce fixture in test; canonical fixture absent | commerce-parity-evidence | CommerceFoundationTest | Not release-ready |
| CB-007 | modernization | bridge until project overlay | integration sandbox fixture absent | commerce-parity-evidence; integration-parity-evidence | CommerceFoundationTest; IntegrationFoundationTest | Not release-ready |
| CB-008 | modernization | bridge until project overlay | integration sandbox fixture absent | commerce-parity-evidence; integration-parity-evidence | CommerceFoundationTest; IntegrationFoundationTest | Not release-ready |
| CB-009 | modernization | preserve | commerce fixture in test; canonical fixture absent | commerce-parity-evidence | CommerceFoundationTest | Not release-ready |
| CB-010 | modernization | preserve | commerce fixture in test; canonical fixture absent | commerce-parity-evidence | CommerceFoundationTest | Not release-ready |
| CB-011 | modernization | preserve | EAV fixture in test; canonical fixture absent | eav-parity-evidence; config-parity-evidence | EavAttributeValueReaderTest; ScopedConfigTest | Not release-ready |
| CB-012 | modernization | preserve | canonical fixture absent | domain-service-evidence; commerce-parity-evidence | DomainFoundationTest; CommerceFoundationTest | Not release-ready |
| CB-013 | modernization | preserve | scoped config fixture in test; canonical fixture absent | config-parity-evidence; commerce-parity-evidence | ScopedConfigTest; CommerceFoundationTest | Not release-ready |
| CB-014 | modernization | preserve | commerce fixture in test; canonical fixture absent | commerce-parity-evidence; cron-job-evidence | CommerceFoundationTest; CronJobSchedulerTest | Not release-ready |
| API-001 | modernization | preserve | API fixture absent | api-contract-evidence | ApiContractFoundationTest | Not release-ready |
| API-002 | modernization | preserve | API fixture absent | api-contract-evidence | ApiContractFoundationTest | Not release-ready |
| API-003 | modernization | preserve | API fixture absent | api-contract-evidence | ApiContractFoundationTest | Not release-ready |
| API-004 | modernization | bridge until project overlay | integration sandbox fixture absent | api-contract-evidence; integration-matrix; integration-parity-evidence | ApiContractFoundationTest; IntegrationFoundationTest | Not release-ready |
| API-005 | modernization | bridge until project overlay | integration sandbox fixture absent | api-contract-evidence; integration-matrix; integration-parity-evidence | ApiContractFoundationTest; IntegrationFoundationTest | Not release-ready |
| API-006 | modernization | bridge until project overlay | integration sandbox fixture absent | api-contract-evidence; integration-matrix; integration-parity-evidence | ApiContractFoundationTest; IntegrationFoundationTest | Not release-ready |
| CJ-001 | modernization | bridge | cron config fixture in code; restore rehearsal absent | cron-job-evidence | CronJobSchedulerTest | Not release-ready |
| CJ-002 | modernization | bridge | cron config fixture in code; integration sandbox fixture absent | cron-job-evidence; integration-parity-evidence | CronJobSchedulerTest; IntegrationFoundationTest | Not release-ready |
| CJ-003 | modernization | replace | cron config fixture in code; canonical fixture absent | cron-job-evidence | CronJobSchedulerTest | Not release-ready |
| CJ-004 | modernization | bridge | cron config fixture in code; integration sandbox fixture absent | cron-job-evidence; report-parity-evidence; integration-parity-evidence | CronJobSchedulerTest; ReportFoundationTest; IntegrationFoundationTest | Not release-ready |
| CJ-005 | modernization | replace | cron config fixture in code; canonical fixture absent | cron-job-evidence | CronJobSchedulerTest | Not release-ready |
| CJ-006 | modernization | replace | cron config fixture in code; canonical fixture absent | cron-job-evidence | CronJobSchedulerTest | Not release-ready |
| CJ-007 | modernization | replace | report fixture in test; canonical fixture absent | cron-job-evidence; report-parity-evidence | CronJobSchedulerTest; ReportFoundationTest | Not release-ready |
| CJ-008 | modernization | replace | report fixture in test; canonical fixture absent | cron-job-evidence; report-parity-evidence | CronJobSchedulerTest; ReportFoundationTest | Not release-ready |
| CJ-009 | modernization | replace | report fixture in test; canonical fixture absent | cron-job-evidence; report-parity-evidence | CronJobSchedulerTest; ReportFoundationTest | Not release-ready |
| CJ-010 | modernization | replace | report fixture in test; canonical fixture absent | cron-job-evidence; report-parity-evidence | CronJobSchedulerTest; ReportFoundationTest | Not release-ready |
| CJ-011 | modernization | replace | report fixture in test; canonical fixture absent | cron-job-evidence; report-parity-evidence | CronJobSchedulerTest; ReportFoundationTest | Not release-ready |
| CJ-012 | modernization | replace | cron config fixture in code; canonical fixture absent | cron-job-evidence | CronJobSchedulerTest | Not release-ready |
| CJ-013 | modernization | bridge until project overlay | cron config fixture in code; project decision absent | cron-job-evidence | CronJobSchedulerTest | Not release-ready |
| CJ-014 | modernization | replace | cron config fixture in code; canonical fixture absent | cron-job-evidence; commerce-parity-evidence | CronJobSchedulerTest; CommerceFoundationTest | Not release-ready |
| CJ-015 | modernization | replace | report fixture in test; canonical fixture absent | cron-job-evidence; report-parity-evidence | CronJobSchedulerTest; ReportFoundationTest | Not release-ready |
| CJ-016 | modernization | replace | cron config fixture in code; canonical fixture absent | cron-job-evidence; commerce-parity-evidence | CronJobSchedulerTest; CommerceFoundationTest | Not release-ready |
| CJ-017 | modernization | replace | cron config fixture in code; integration sandbox fixture absent | cron-job-evidence; integration-parity-evidence; commerce-parity-evidence | CronJobSchedulerTest; IntegrationFoundationTest; CommerceFoundationTest | Not release-ready |
| CJ-018 | modernization | replace | cron config fixture in code; canonical fixture absent | cron-job-evidence | CronJobSchedulerTest | Not release-ready |
| CJ-019 | modernization | replace | cron config fixture in code; canonical fixture absent | cron-job-evidence; domain-service-evidence | CronJobSchedulerTest; DomainFoundationTest | Not release-ready |
| CJ-020 | modernization | replace | report fixture in test; canonical fixture absent | cron-job-evidence; report-parity-evidence | CronJobSchedulerTest; ReportFoundationTest | Not release-ready |
| CJ-021 | modernization | replace | cron config fixture in code; canonical fixture absent | cron-job-evidence; commerce-parity-evidence | CronJobSchedulerTest; CommerceFoundationTest | Not release-ready |
| CJ-022 | modernization | replace | cron config fixture in code; canonical fixture absent | cron-job-evidence; domain-service-evidence | CronJobSchedulerTest; DomainFoundationTest | Not release-ready |
| CJ-023 | modernization | replace | cron config fixture in code; canonical fixture absent | cron-job-evidence | CronJobSchedulerTest | Not release-ready |
| CJ-024 | modernization | replace | cron config fixture in code; canonical fixture absent | cron-job-evidence | CronJobSchedulerTest | Not release-ready |
| CJ-025 | modernization | replace | cron config fixture in code; canonical fixture absent | cron-job-evidence; domain-service-evidence | CronJobSchedulerTest; DomainFoundationTest | Not release-ready |

## Storefront

| Feature IDs | Required Details | Status |
| --- | --- | --- |
| SF-001, SF-002 | CMS content, shell, blocks, product widgets, banners, cache behavior. | Tracked; release evidence open |
| SF-003, SF-004 | Layered navigation, sorting, pagination, product types, empty states. | Tracked; release evidence open |
| SF-005 | Simple, configurable, bundle, grouped, downloadable, virtual, custom options, media. | Tracked; release evidence open |
| SF-006 | Native search, redirects, empty results, terms and advanced search. | Tracked; release evidence open |
| SF-007, CB-001 through CB-006 | Add/update/remove, coupons, estimates, persistent cart, messages, totals. | Tracked; release evidence open |
| SF-008, SF-009, CB-007 through CB-010 | Guest, registered, multishipping, shipping/payment/tax/totals. | Tracked; release evidence open |
| SF-010, SF-011 | Register, login, reset password, addresses, orders, wishlist, newsletter. | Tracked; release evidence open |
| SF-012 | Store switch, scope-specific config/content/products, currencies, locales. | Tracked; release evidence open |
| SF-013, CB-014 | Transactional templates, queue, attachments, localization. | Tracked; release evidence open |
| SF-014 through SF-016 | SEO, feeds, optional modules, external payment redirects. | Tracked; release evidence open |

## Admin

| Feature IDs | Required Details | Status |
| --- | --- | --- |
| AD-001 | Login, logout, timeout, password policy, dashboard, notifications. | Tracked; release evidence open |
| AD-002 through AD-004, CB-011, CB-012 | Product/category grids/forms, attributes, media, inventory, websites, indexes. | Tracked; release evidence open |
| AD-007 | Grids/forms, addresses, groups, customer attributes. | Tracked; release evidence open |
| AD-005, AD-006, CB-010 | Orders, invoices, shipments, credit memos, refunds, comments, emails. | Tracked; release evidence open |
| AD-008, CB-004, CB-005 | Catalog rules, cart rules, coupons. | Tracked; release evidence open |
| AD-009 | Pages, blocks, widgets, design assignments, media browser. | Tracked; release evidence open |
| AD-010 | Scope behavior, validation, secrets, env overrides. | Tracked; release evidence open |
| AD-011, CB-012, CB-013 | Status, actions, permissions, long-running behavior. | Tracked; release evidence open |
| AD-012 | ACL, custom permissions, API roles, OAuth consumers/tokens. | Tracked; release evidence open |
| AD-013, AD-014 | All reports, exports, scheduled exports, custom grids, imports. | Tracked; release evidence open |
| AD-015 through AD-018 | Newsletter, polls, tax, currency, store operations, integration admin. | Tracked; release evidence open |

## Integrations

| Area | Required Details | Status |
| --- | --- | --- |
| Payments | Providers, auth/capture/refund/void, webhooks, sandbox. | Tracked; release evidence open |
| Shipping | Carriers, rates, labels, tracking, customs. | Tracked; release evidence open |
| Tax | Calculation provider, rules, exemptions, rounding. | Tracked; release evidence open |
| Search | Engine, indexing, query behavior, failover. | Tracked; release evidence open |
| ERP/PIM/CRM | Sync direction, schedule, data ownership, failure handling. | Tracked; release evidence open |
| Email/SMS | Provider, templates, bounce/failure behavior. | Tracked; release evidence open |
| Analytics/tracking | Scripts, data layer, consent, conversions. | Tracked; release evidence open |
| Feeds | Product feeds, order exports, scheduled jobs. | Tracked; release evidence open |

## Completion Criteria

- Each feature ID has an owner.
- Each feature ID has a migration decision: `preserve`, `bridge`, `replace`, or `retire`.
- Each feature ID maps to test-plan coverage.
- Each integration has sandbox credentials or a mock strategy.
- Each visible feature has visual baseline requirements and screenshot evidence.
- Each complex commerce feature has reverse-engineering evidence before Laravel replacement starts.
- Each critical feature has edge-case and failure-path coverage, not only happy-path acceptance.
