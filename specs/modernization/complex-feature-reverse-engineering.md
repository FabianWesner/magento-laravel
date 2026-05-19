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

## Per-Feature Reverse-Engineering Traceability

This matrix tracks every catalog feature ID against its current reverse-engineering status. It does not replace the final approved behavior specs or dual-runtime parity evidence.

| Feature ID | Behavior Scope | Current Reverse-Engineering Evidence | Evidence Still Needed | Status |
| --- | --- | --- | --- | --- |
| SF-001 | Storefront shell and global layout | UI inventory and feature inventory rows | Magento/Laravel screenshots, route output, assets, messages, responsive states | Not release-ready |
| SF-002 | CMS pages, blocks, widgets, routing, cache | Domain service evidence | Magento DB deltas, cache traces, rendered CMS snapshots | Not release-ready |
| SF-003 | Category browsing and layered navigation | Domain service evidence | Magento collection filters, URL rewrites, scoped attribute snapshots | Not release-ready |
| SF-004 | Product listing item rendering | Domain service evidence | Magento price, tax, stock, review, wishlist, compare snapshots | Not release-ready |
| SF-005 | Product detail behavior | Domain and commerce evidence | Magento product-type snapshots, media, options, reviews, alerts, related links | Not release-ready |
| SF-006 | Search behavior | Domain service evidence | Magento search index traces, query results, empty states, redirects | Not release-ready |
| SF-007 | Cart behavior | Commerce evidence | Magento quote snapshots, cart UI states, coupon and estimate deltas | Not release-ready |
| SF-008 | Checkout behavior | Commerce evidence | Magento checkout session, address, shipping, payment, review, success/failure traces | Not release-ready |
| SF-009 | Multishipping checkout | Commerce evidence | Magento multishipping quote, address, method, billing, overview traces | Not release-ready |
| SF-010 | Customer account behavior | Domain and auth evidence | Magento customer session, password, address, order, reorder traces | Not release-ready |
| SF-011 | Wishlist, compare, review, tag, newsletter | Domain service evidence | Magento list, email, subscription, downloadable, billing agreement traces | Not release-ready |
| SF-012 | Multistore and localization | Config evidence | Magento store switch, currency, locale, scoped content and URL traces | Not release-ready |
| SF-013 | Transactional communication | Domain and commerce evidence | Magento contact, send-friend, alert, newsletter, email queue traces | Not release-ready |
| SF-014 | SEO, feeds, sitemap | Domain service evidence | Magento rewrite, canonical, RSS, sitemap file and URL traces | Not release-ready |
| SF-015 | External payment redirects | Integration evidence | Magento payment request, callback, review, cancel, return traces | Not release-ready |
| SF-016 | Optional storefront modules | Domain and integration evidence | Magento poll, tag, analytics, Google Base, XmlConnect traces | Not release-ready |
| AD-001 | Admin shell and auth | Auth evidence | Magento ACL, form key, session timeout, dashboard, denied-page traces | Not release-ready |
| AD-002 | Catalog product admin | Domain and commerce evidence | Magento grid filters, mass actions, product edit tabs, save DB deltas | Not release-ready |
| AD-003 | Category admin | Domain service evidence | Magento tree, scoped edit, product assignment, URL key traces | Not release-ready |
| AD-004 | EAV attribute admin | EAV evidence | Magento attribute set, option, label, backend/source model write traces | Not release-ready |
| AD-005 | Sales order admin | Commerce and report evidence | Magento order grid, view, comments, status, reorder, create traces | Not release-ready |
| AD-006 | Invoice, shipment, refund admin | Commerce and report evidence | Magento invoice, shipment, tracking, credit memo, PDF, email traces | Not release-ready |
| AD-007 | Customer admin | Domain service evidence | Magento customer grid, edit, address, group, online, cart traces | Not release-ready |
| AD-008 | Promotion admin | Commerce and report evidence | Magento rule condition/action, coupon generation, scheduled apply traces | Not release-ready |
| AD-009 | CMS and design admin | Domain service evidence | Magento CMS, widget, layout assignment, WYSIWYG media traces | Not release-ready |
| AD-010 | System configuration | Config evidence | Magento system config field, source/backend model, inheritance, secret traces | Not release-ready |
| AD-011 | Cache and index admin | Commerce labels | Magento cache action, index process, stale/rebuild, compiler traces | Not release-ready |
| AD-012 | Users, roles, API permissions | API and auth evidence | Magento ACL resources, API users, OAuth token, API2 role traces | Not release-ready |
| AD-013 | Import, export, dataflow | Domain service evidence | Magento import validation, batch, export, dataflow profile, error-file traces | Not release-ready |
| AD-014 | Reports | Report evidence | Magento report aggregation before/after, filter, export, refresh traces | Not release-ready |
| AD-015 | Newsletter and polls | Domain service evidence | Magento template, queue, subscriber, problem report, poll traces | Not release-ready |
| AD-016 | Tax and currency | Commerce and config evidence | Magento tax class, rate, rule, currency import, symbol traces | Not release-ready |
| AD-017 | Store operations | Domain service evidence | Magento store management, backup, system info, email template traces | Not release-ready |
| AD-018 | Integration admin | Integration evidence | Magento payment, shipping, Google, mobile, credential, notification traces | Not release-ready |
| CB-001 | Quote lifecycle | Commerce evidence | Magento guest/customer quote, merge, persistence, expiration traces | Not release-ready |
| CB-002 | Product type behavior | Commerce and domain evidence | Magento parent/child item, option, bundle, downloadable traces | Not release-ready |
| CB-003 | Price resolution | Commerce evidence | Magento base, special, tier, group, catalog rule, option, bundle price traces | Not release-ready |
| CB-004 | Cart price rules | Commerce evidence | Magento discount, coupon, free shipping, stop-rule, usage-limit traces | Not release-ready |
| CB-005 | Catalog price rules | Commerce evidence | Magento index, website/group/date, priority, daily apply traces | Not release-ready |
| CB-006 | Tax calculation | Commerce evidence | Magento tax class, shipping tax, discount order, rounding traces | Not release-ready |
| CB-007 | Shipping rates | Commerce and integration evidence | Magento carrier request/response, unavailable-rate, split-address traces | Not release-ready |
| CB-008 | Payment lifecycle | Commerce and integration evidence | Magento auth, capture, void, refund, review, webhook traces | Not release-ready |
| CB-009 | Inventory and stock | Commerce evidence | Magento backorder, min/max, increment, stock index traces | Not release-ready |
| CB-010 | Order state machine | Commerce evidence | Magento quote-to-order, state, invoice, shipment, credit memo traces | Not release-ready |
| CB-011 | EAV scope semantics | EAV and config evidence | Magento labels, options, defaults, backend/source model, validation traces | Not release-ready |
| CB-012 | Indexing | Commerce and domain evidence | Magento EAV, price, URL, flat, stock, search, tag index traces | Not release-ready |
| CB-013 | Cache and session behavior | Commerce and config evidence | Magento config, layout/block, session, form key, invalidation traces | Not release-ready |
| CB-014 | Email queue | Commerce and cron evidence | Magento queue insert, send, cleanup, template, localization traces | Not release-ready |
| API-001 | SOAP API | API evidence | Magento auth, WSDL, catalog/customer/sales, error payload traces | Not release-ready |
| API-002 | XML-RPC API | API evidence | Magento auth, catalog/customer/sales, error payload traces | Not release-ready |
| API-003 | REST/API2 | API evidence | Magento OAuth, role, renderer, permission, pagination traces | Not release-ready |
| API-004 | Payment integrations | API and integration evidence | Magento provider request/response, error, secret, callback traces | Not release-ready |
| API-005 | Shipping integrations | API and integration evidence | Magento carrier request/response, error, secret, tracking traces | Not release-ready |
| API-006 | External services | API and integration evidence | Magento currency, analytics, feed, email, ERP, PIM, CRM traces | Not release-ready |
| CJ-001 | scheduled backup | Cron evidence | Magento backup job input, output, restore rehearsal traces | Not release-ready |
| CJ-002 | currency rate update | Cron and integration evidence | Magento provider input, rate output, failure log traces | Not release-ready |
| CJ-003 | delete customer flow password | Cron evidence | Magento expired token input and cleanup DB delta traces | Not release-ready |
| CJ-004 | PayPal fetch reports | Cron, report, integration evidence | Magento report import, idempotency, failure traces | Not release-ready |
| CJ-005 | log cleanup | Cron evidence | Magento log retention input and cleanup DB delta traces | Not release-ready |
| CJ-006 | clean expired quotes | Cron evidence | Magento expired quote input and active quote safety traces | Not release-ready |
| CJ-007 | aggregate sales orders | Cron and report evidence | Magento sales report aggregate before/after traces | Not release-ready |
| CJ-008 | aggregate sales shipments | Cron and report evidence | Magento shipment report aggregate before/after traces | Not release-ready |
| CJ-009 | aggregate sales invoiced | Cron and report evidence | Magento invoiced report aggregate before/after traces | Not release-ready |
| CJ-010 | aggregate sales refunded | Cron and report evidence | Magento refunded report aggregate before/after traces | Not release-ready |
| CJ-011 | aggregate bestsellers | Cron and report evidence | Magento bestseller report aggregate before/after traces | Not release-ready |
| CJ-012 | clear expired persistent sessions | Cron evidence | Magento persistent session cleanup traces | Not release-ready |
| CJ-013 | XmlConnect scheduled send | Cron evidence | Magento XmlConnect input, output, retain/bridge/retire traces | Not release-ready |
| CJ-014 | daily catalog rule update | Cron and commerce evidence | Magento rule price/index before/after traces | Not release-ready |
| CJ-015 | aggregate coupon reports | Cron and report evidence | Magento coupon report aggregate before/after traces | Not release-ready |
| CJ-016 | clean cache | Cron and commerce evidence | Magento cache cleanup and stale cache traces | Not release-ready |
| CJ-017 | send email queue | Cron, integration, commerce evidence | Magento email queue send and retry traces | Not release-ready |
| CJ-018 | clean email queue | Cron evidence | Magento queue retention cleanup traces | Not release-ready |
| CJ-019 | product alerts | Cron and domain evidence | Magento price/stock alert email traces | Not release-ready |
| CJ-020 | aggregate tax reports | Cron and report evidence | Magento tax report aggregate before/after traces | Not release-ready |
| CJ-021 | reindex product prices | Cron and commerce evidence | Magento price index stale/rebuild traces | Not release-ready |
| CJ-022 | newsletter scheduled send | Cron and domain evidence | Magento newsletter queue send/failure traces | Not release-ready |
| CJ-023 | delete old captcha attempts | Cron evidence | Magento captcha attempt cleanup traces | Not release-ready |
| CJ-024 | delete expired captcha images | Cron evidence | Magento captcha image cleanup traces | Not release-ready |
| CJ-025 | generate sitemaps | Cron and domain evidence | Magento sitemap file and URL generation traces | Not release-ready |

## Acceptance Criteria

- No complex feature is implemented in Laravel before its Magento behavior has an approved reverse-engineering spec.
- Cart/totals, checkout, payment, tax, shipping, order lifecycle, EAV writes, permissions, and cron jobs all have dual-runtime parity tests.
- APIs, integrations, reports, indexing, cache/session behavior, admin workflows, and storefront UI states have dual-runtime parity tests where applicable.
- Fixtures cover every listed normal, edge, and failure case.
- Production-readiness evidence covers invalid input, permission denial, concurrency, duplicate submissions, stale indexes, stale caches, external service failure, retries, logging, recovery, and rollback where applicable.
- Any intentional behavior difference has an ADR and user-facing documentation.
