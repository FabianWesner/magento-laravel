# Complex Feature Reverse Engineering Specification

Magento behavior that affects money, inventory, permissions, integrations, or background processing must be reverse-engineered before replacement. The goal is to preserve observable behavior while replacing implementation internals.

## Reverse Engineering Method

For every complex feature:

1. Identify the Magento entry points: controller, block, model, resource model, helper, observer, cron job, indexer, API resource, and admin configuration.
2. Identify relevant database tables, EAV attributes, config paths, cache keys, session keys, events, emails, and generated files.
3. Build fixture data that exercises normal, edge, and failure paths.
4. Execute the Magento baseline and capture HTTP output, DB deltas, session changes, quote/order totals, events, emails, logs, and generated files.
5. Write an executable parity spec for Laravel.
6. Implement Laravel behavior behind contracts while keeping Magento available for side-by-side comparison.
7. Run dual-runtime parity tests until outputs match or an intentional difference is approved.

## Money And Cart Calculation

Cart, checkout, and order totals are the highest-risk domain. They require a dedicated reverse-engineered spec before any Laravel replacement.

### Required Calculation Coverage

| Area | Required Cases |
| --- | --- |
| Quote lifecycle | Empty quote, guest quote, customer quote, merged quote after login, persistent cart, multishipping quote. |
| Product lines | Simple, configurable child/parent, grouped, bundle dynamic/fixed price, downloadable, virtual, custom options, tier price, special price, tax class, backorders. |
| Quantity behavior | Min/max qty, qty increments, decimal qty if enabled, out of stock, low stock, backorder, grouped child qty, bundle option qty. |
| Price sources | Base price, special price date window, tier/group price, catalog price rules, custom options price, bundle option price, website currency, rounding. |
| Cart rules | Percent discount, fixed discount, fixed cart discount, buy X get Y, free shipping, coupon code, auto rule, stop further rules, usage limits, customer group, website scope. |
| Tax | Product tax class, customer tax class, shipping tax, discount before/after tax, catalog prices incl/excl tax, cross-border trade, tax rounding, store display settings. |
| Shipping | Free shipping, flat rate, table rate, UPS/USPS/FedEx/DHL sandbox or mock, virtual-only carts, split shipments, shipping discounts. |
| Totals order | Subtotal, discount, shipping, tax, WEEE/FPT, gift message, grand total, base/display currency totals. |
| Payment | Free payment, check/money order, purchase order, bank transfer, cash on delivery, Authorize.Net, PayPal methods if enabled, auth/capture/refund/void state. |
| Order conversion | Quote to order, order item parent/child mapping, invoices, shipments, credit memos, refunds, emails, comments, status/state transitions. |

### Required Outputs

- Quote item rows and option rows.
- `sales_flat_quote*` before/after snapshots.
- Totals collector sequence and output per collector.
- Rendered cart, checkout review, order view, invoice, shipment, and credit memo totals.
- Order/invoice/credit memo grand totals in base and display currency.
- Rule and tax calculation trace.
- Payment and shipping request/response payloads or approved mocks.

## Pricing, Promotions, And Indexing

Reverse-engineer:

- Catalog price index behavior.
- Stock status index behavior.
- Category/product index behavior.
- Search fulltext index behavior.
- Catalog rule daily reindex behavior.
- URL rewrite generation and collision behavior.
- Configurable swatch price/media behavior.
- Product alert price/stock processing.

Acceptance requires parity tests for indexed and non-indexed reads, plus tests that prove stale index states are visible and recoverable.

## EAV And Scope Behavior

Reverse-engineer:

- Attribute metadata by entity type.
- Attribute set/group assignment.
- Store-scope fallback for product, category, customer, and address attributes.
- Source model option resolution.
- Backend models and frontend models.
- Required/default values.
- Multiselect serialization.
- Static vs backend-table fields.

Acceptance requires fixture snapshots for each backend type and each scope level.

## Admin Configuration And Permissions

Reverse-engineer:

- System config path, scope, inheritance, frontend/backend/source model behavior.
- Admin ACL resources and menu visibility.
- Form key behavior.
- Admin session timeout.
- Grid collection filters, mass actions, exports, and ACL restrictions.

Acceptance requires tests for full, partial, and denied admin roles.

## Cron, Email, And Reports

Reverse-engineer every cron job listed in `specs/modernization/magento-feature-catalog.md`.

For each job capture:

- Schedule source and effective schedule.
- Input rows/config.
- Locking/concurrency behavior.
- Output rows/files/emails.
- Logs and failure behavior.
- Idempotency expectations.

Reports must be tested both before and after aggregation jobs run.

## API And Integration Behavior

Reverse-engineer required SOAP, REST, API2, OAuth, payment, shipping, tax, feed, analytics, and external sync behavior.

For each integration capture:

- Auth method.
- Request and response payloads.
- Status/error mapping.
- Retry behavior.
- Sandbox or mock strategy.
- Secrets/config paths.

## Feature Mapping

| Feature IDs | Required Reverse-Engineering Output | Required Parity Evidence |
| --- | --- | --- |
| CB-001 through CB-006 | Quote lifecycle, product type rows, price source order, cart rule application, tax order, totals collector sequence, DB snapshots. | Dual-runtime quote/cart/order totals tests, rendered totals screenshots, DB delta comparison. |
| CB-007 through CB-010 | Shipping rates, payment lifecycle, inventory/stock behavior, order state machine, invoices, shipments, credit memos, refunds. | Sandbox/mocked integration tests, order lifecycle DB snapshots, retry/failure tests. |
| CB-011 through CB-014 | EAV scope semantics, indexing, cache/session behavior, email queue. | EAV snapshot tests, index stale/rebuild tests, cache/session tests, email queue send/cleanup tests. |
| API-001 through API-003 | SOAP, XML-RPC, REST/API2 auth, payloads, errors, filters, pagination, permissions. | Contract tests with legacy response comparison and error mapping. |
| API-004 through API-006 | Payment, shipping, external services, feeds, analytics, project ERP/PIM/CRM/email integrations. | Sandbox or mock payload tests, timeout/retry/failure behavior, secret/config tests. |
| CJ-001 through CJ-025 | Schedule, input rows/config, locks, output rows/files/emails, logs, failure and idempotency behavior. | Laravel scheduler/job parity tests and report table snapshots. |
| AD-001 through AD-018 | Admin ACL/menu/forms/grids/reports/import-export/config/cache/index/integration workflows. | Permission matrix tests, Livewire/admin browser tests, screenshots, DB side-effect tests. |
| SF-001 through SF-016 | Storefront routes, UI states, customer/session/cart/checkout/CMS/SEO/feed behavior. | Playwright/Chrome tests, screenshots, URL compatibility tests, DB/session/email side-effect tests. |

## Acceptance Criteria

- No complex feature is implemented in Laravel before its Magento behavior has an approved reverse-engineering spec.
- Cart/totals, checkout, payment, tax, shipping, order lifecycle, EAV writes, permissions, and cron jobs all have dual-runtime parity tests.
- APIs, integrations, reports, indexing, cache/session behavior, admin workflows, and storefront UI states have dual-runtime parity tests where applicable.
- Fixtures cover every listed normal, edge, and failure case.
- Production-readiness evidence covers invalid input, permission denial, concurrency, duplicate submissions, stale indexes, stale caches, external service failure, retries, logging, recovery, and rollback where applicable.
- Any intentional behavior difference has an ADR and user-facing documentation.
