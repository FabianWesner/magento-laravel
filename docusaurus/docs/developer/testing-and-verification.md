---
id: testing-and-verification
title: Testing And Verification
---

Verification must prove production readiness, not only happy-path behavior.

## Required Test Coverage

- Unit, integration, characterization, dual-runtime parity, browser, Livewire, API contract, scheduler, queue, visual, accessibility, security, performance, and operations tests.
- Normal, edge, failure, invalid input, permission-denied, timeout, retry, race-condition, stale-cache, stale-index, missing-media, integration-outage, and rollback scenarios.
- Database side effects for cart, checkout, order, payment, tax, shipping, refund, reports, indexes, and cron.

## Done Rule

The system is done only when the full test plan in `specs/modernization/test-plan.md` passes and every feature ID has retained evidence.

## Per-Feature Verification Ledger

This ledger mirrors the catalog IDs from `specs/modernization/magento-feature-catalog.md` so developer documentation can track test evidence without claiming completion before artifacts exist.

| Feature ID | Feature | Verification scope | Current developer evidence | Release status |
| --- | --- | --- | --- | --- |
| SF-001 | Storefront shell | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-002 | CMS pages and blocks | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-003 | Category browsing | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-004 | Product listing items | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-005 | Product detail pages | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-006 | Search | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-007 | Cart | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-008 | Checkout | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-009 | Multishipping checkout | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-010 | Customer account | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-011 | Customer commerce features | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-012 | Multistore and localization | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-013 | Transactional communication | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-014 | SEO and feeds | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-015 | External payment redirects | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| SF-016 | Optional storefront modules | Fixture, characterization, Laravel parity, browser, visual, performance, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-001 | Admin shell and auth | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-002 | Catalog products | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-003 | Catalog categories and navigation | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-004 | EAV attributes | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-005 | Sales orders | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-006 | Invoices, shipments, refunds | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-007 | Customers | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-008 | Promotions | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-009 | CMS and design | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-010 | System configuration | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-011 | Cache, indexes, compiler | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-012 | Users, roles, API permissions | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-013 | Import/export and dataflow | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-014 | Reports | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, performance, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-015 | Newsletter and polls | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-016 | Tax and currency | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-017 | Store operations | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, operations, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| AD-018 | Integration admin | Fixture, characterization, Laravel parity, browser, visual, accessibility, security, operations, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CB-001 | Quote lifecycle | Fixture, legacy characterization, Laravel parity, DB side-effect comparison, resilience, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CB-002 | Product type behavior | Fixture, legacy characterization, Laravel parity, DB side-effect comparison, resilience, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CB-003 | Price resolution | Fixture, legacy characterization, Laravel parity, DB side-effect comparison, performance, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CB-004 | Cart price rules | Fixture, legacy characterization, Laravel parity, DB side-effect comparison, resilience, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CB-005 | Catalog price rules | Fixture, legacy characterization, Laravel parity, DB side-effect comparison, scheduler/index coverage, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CB-006 | Tax calculation | Fixture, legacy characterization, Laravel parity, DB side-effect comparison, rounding tolerance, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CB-007 | Shipping rates | Fixture, legacy characterization, Laravel parity, carrier contract handling, outage behavior, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CB-008 | Payment lifecycle | Fixture, legacy characterization, Laravel parity, gateway contract handling, refund behavior, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CB-009 | Inventory and stock | Fixture, legacy characterization, Laravel parity, DB side-effect comparison, indexing behavior, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CB-010 | Order state machine | Fixture, legacy characterization, Laravel parity, DB side-effect comparison, email/PDF behavior, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CB-011 | EAV scope semantics | Fixture, legacy characterization, Laravel parity, EAV metadata comparison, store-scope behavior, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CB-012 | Indexing | Fixture, legacy characterization, Laravel parity, index side-effect comparison, stale-index recovery, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CB-013 | Cache and session behavior | Fixture, legacy characterization, Laravel parity, cache/session side effects, recovery behavior, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CB-014 | Email queue | Fixture, legacy characterization, Laravel parity, queue side effects, retry/cleanup behavior, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| API-001 | SOAP API v1/v2 | Fixture, legacy payload capture, Laravel contract tests, auth errors, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| API-002 | XML-RPC API | Fixture, legacy payload capture, Laravel contract tests, auth errors, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| API-003 | REST/API2 | Fixture, OAuth contract tests, role/attribute checks, renderer parity, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| API-004 | Payment integrations | Fixture, gateway contract tests, sandbox callbacks, failure handling, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| API-005 | Shipping integrations | Fixture, carrier contract tests, sandbox responses, outage handling, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| API-006 | External services | Fixture, service contract tests, sandbox responses, timeout handling, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-001 | scheduled backup | Fixture, scheduler command test, lock behavior, side-effect comparison, restore rehearsal, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-002 | currency rate update | Fixture, scheduler command test, provider contract handling, side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-003 | delete customer flow password | Fixture, scheduler command test, cleanup side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-004 | PayPal fetch reports | Fixture, scheduler command test, sandbox report handling, idempotency, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-005 | log cleanup | Fixture, scheduler command test, retention side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-006 | clean expired quotes | Fixture, scheduler command test, active quote protection, side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-007 | aggregate sales orders | Fixture, scheduler command test, report table side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-008 | aggregate sales shipments | Fixture, scheduler command test, report table side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-009 | aggregate sales invoiced | Fixture, scheduler command test, report table side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-010 | aggregate sales refunded | Fixture, scheduler command test, report table side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-011 | aggregate bestsellers | Fixture, scheduler command test, report table side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-012 | clear expired persistent sessions | Fixture, scheduler command test, session cleanup side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-013 | XmlConnect scheduled send | Fixture, scheduler command test, delivery decision evidence, side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-014 | daily catalog rule update | Fixture, scheduler command test, rule price side-effect comparison, index behavior, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-015 | aggregate coupon reports | Fixture, scheduler command test, coupon report side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-016 | clean cache | Fixture, scheduler command test, cache side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-017 | send email queue | Fixture, scheduler command test, queue send side-effect comparison, retry behavior, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-018 | clean email queue | Fixture, scheduler command test, queue retention side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-019 | product alerts | Fixture, scheduler command test, alert email side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-020 | aggregate tax reports | Fixture, scheduler command test, tax report side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-021 | reindex product prices | Fixture, scheduler command test, price index side-effect comparison, stale-index recovery, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-022 | newsletter scheduled send | Fixture, scheduler command test, newsletter queue side-effect comparison, failure handling, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-023 | delete old captcha attempts | Fixture, scheduler command test, captcha attempt cleanup side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-024 | delete expired captcha images | Fixture, scheduler command test, captcha image cleanup side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
| CJ-025 | generate sitemaps | Fixture, scheduler command test, sitemap file/URL side-effect comparison, and release artifact. | Spec traceability exists; retained test artifact absent. | Not release-ready. |
