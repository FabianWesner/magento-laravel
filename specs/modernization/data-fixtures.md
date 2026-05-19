---
tags:
- Development
---

# Data Fixtures

The database schema stays, including EAV. Fixtures are therefore the safety net for proving Laravel behavior against real Magento data structures.

## Required Fixture Sets

| Fixture | Purpose |
| --- | --- |
| Minimal install | Fast smoke tests and local setup. |
| Original seed/sample data | Proves compatibility with existing seed/sample data. |
| Sanitized project data | Proves real-world behavior without sensitive data. |
| EAV edge data | Covers backend types, scoped values, options, multiselects, required attributes. |
| Multistore data | Covers websites, store groups, store views, currencies, locales, scoped config. |
| Sales lifecycle data | Covers quotes, orders, invoices, shipments, credit memos, refunds. |
| Admin permissions data | Covers full, partial, and denied roles. |
| Integration data | Covers API users, sandbox providers, external IDs. |
| Scale data | Covers large catalogs, attributes, customers, orders, and admin grids. |

## Canonical Demo Data Matrix

The demo fixture must be deterministic and broad enough to exercise every feature ID in `specs/modernization/magento-feature-catalog.md`. Magento sample data is acceptable only as the starting point; missing edge cases must be added by versioned fixture scripts.

| Area | Required Data |
| --- | --- |
| Websites and stores | At least two websites, two store groups, three store views, one disabled store view, two locales, two currencies, scoped base URLs, scoped CMS content. |
| Categories | Root category, nested categories at least four levels deep, anchor category, non-anchor category, empty category, disabled category, category with custom design, category with store-scoped name/URL key/meta. |
| Product types | Simple, virtual, grouped, configurable, bundle fixed price, bundle dynamic price, downloadable, product with required custom options, product with optional custom options, product with related/up-sell/cross-sell links. |
| Product pricing | Regular price, special price with date range, tier price, group price, catalog rule price, MSRP/MAP, price incl/excl tax, bundle option prices, downloadable link prices. |
| Product inventory | In stock, out of stock, backorder enabled, min/max sale quantity, quantity increments, low stock alert, parent product with unavailable child, virtual/downloadable stock behavior. |
| Product attributes | Every EAV backend type, select/multiselect options, required attribute, unique attribute, store-scoped label/value, global/website/store scoped attributes, custom source/backend/frontend model coverage. |
| Customers | Guest, registered customer, customer in each group, locked/disabled edge if project supports it, multiple addresses, default billing/shipping, newsletter subscriber, wishlist owner. |
| Cart states | Empty cart, simple item, configurable item, bundle item, grouped item, downloadable item, virtual-only cart, mixed physical/virtual cart, invalid quantity, expired quote, persistent cart. |
| Promotions | Catalog rule, percent cart discount, fixed item discount, fixed cart discount, buy X get Y, coupon-specific rule, auto rule, free shipping rule, stop-further-rules case, usage limit reached, customer group restriction. |
| Tax | At least two product tax classes, two customer tax classes, multiple tax rates, compound-like rule where applicable, shipping tax, discount before tax, discount after tax, tax included and excluded display modes. |
| Shipping | Free shipping, flat rate, table rate, UPS/USPS/FedEx/DHL sandbox or mocked rates, unavailable shipping address, split-address multishipping case. |
| Payment | Check/money order, bank transfer, cash on delivery, purchase order, free payment, sandbox or mocked PayPal, Authorize.Net/Paygate if enabled, failed payment, payment review. |
| Orders | pending, processing, complete, closed, canceled, holded/payment-review where applicable, partial invoice, partial shipment, partial refund, offline refund, online refund mock, order comments and emails. |
| CMS and content | Home page, standard content page, no-route page, static block, widget instance, WYSIWYG media reference, store-scoped content. |
| Admin users | Full admin, catalog-only role, sales-only role, customer-service role, read-only/report role, no-access negative role, API user, API2/OAuth consumer. |
| APIs | SOAP/XML-RPC user, REST/API2 OAuth tokens, fixtures for catalog/customer/sales resources, invalid auth cases. |
| Cron | Data that causes every cron job in `CJ-001` through `CJ-025` to perform an observable action or an approved no-op. |
| Reports | Sales, tax, shipping, invoiced, refunded, coupon, product, customer, search, carts, reviews, tags, wishlist and bestsellers data. |
| Media | Product images, category images, CMS media, downloadable files, missing media reference, image cache regeneration case. |
| Performance | Large catalog slice with at least 10,000 products, 500 categories, 200 attributes, 10,000 customers, and 50,000 orders for performance environments. |
| Failure and resilience | Invalid coupons, invalid addresses, expired sessions, denied admin roles, failed payments, unavailable shipping methods, missing media, stale indexes, stale cache, failed email queue, failed cron lock, duplicate order submission attempt, and integration timeout mocks. |

## Current Sample Fixture Coverage

Use the read-only coverage reporter to compare any restored Magento database against the matrix above:

```bash
DB_DSN='mysql:host=127.0.0.1;port=3317;dbname=magento1945' DB_USER=magento DB_PASS=magento php dev/modernization/fixture-coverage-report.php --format=markdown
```

Use strict mode for final fixture acceptance:

```bash
DB_DSN='mysql:host=<host>;dbname=<fixture_db>' DB_USER=<user> DB_PASS=<pass> php dev/modernization/fixture-coverage-report.php --format=markdown --fail-on-gaps
```

Current Magento sample data result on 2026-05-18:

| Area | Feature IDs | Current Evidence | Status |
| --- | --- | --- | --- |
| Product types | SF-005, CB-002, AD-002 | Bundle, configurable, downloadable, grouped, simple, and virtual products are present. | Covered |
| Websites and stores | SF-012, AD-017 | Two websites, two store groups, and four store views are present; no disabled store view is present. | Not release-ready |
| Categories | SF-003, AD-003 | 29 categories and one empty category are present; maximum depth is level 3 and no disabled category is present. | Not release-ready |
| Custom options | SF-005, CB-002 | Required and optional product custom options are present. | Covered |
| EAV attributes | CB-011, AD-004 | All core backend types and global, website, and store scoped catalog attributes are present. | Covered |
| Pricing and promotions | CB-003, CB-004, CB-005, AD-008 | Special prices, tier prices, catalog rules, and cart rules are present; group prices are missing. | Not release-ready |
| Inventory | CB-009, AD-002 | Stock rows and out-of-stock products are present; backorder-enabled products are missing. | Not release-ready |
| Customers | SF-010, AD-007 | Registered customers, groups, and addresses are present. | Covered |
| Sales lifecycle | CB-010, AD-005, AD-006 | Orders, invoices, shipments, and credit memos are present. | Covered |
| Tax and shipping | CB-006, CB-007, AD-016, API-005 | Tax rates and rules are present; table-rate shipping data is missing. | Not release-ready |
| Payment and API users | CB-008, API-001, API-002, API-003, API-004, AD-012 | Active payment config exists; SOAP/XML-RPC API users and OAuth consumers are missing. | Not release-ready |
| CMS and media | SF-002, SF-005, AD-009 | CMS pages, blocks, widgets, and product media rows are present. | Covered |
| Admin users and roles | AD-001, AD-012 | One admin user and two admin roles are present; role coverage is insufficient. | Not release-ready |
| Cron and reports | CJ-001, CJ-002, CJ-003, CJ-004, CJ-005, CJ-006, CJ-007, CJ-008, CJ-009, CJ-010, CJ-011, CJ-012, CJ-013, CJ-014, CJ-015, CJ-016, CJ-017, CJ-018, CJ-019, CJ-020, CJ-021, CJ-022, CJ-023, CJ-024, CJ-025, AD-014 | Report aggregates are populated; cron schedule rows are missing. | Not release-ready |

Summary: Magento sample data covers 6 of 14 checked fixture areas and leaves 8 known gaps. It is useful for smoke tests but is not sufficient as the canonical demo fixture.

## Fixture Traceability

Every fixture record must identify which feature IDs it proves. Use this table shape in the fixture manifest:

| Fixture ID | Feature IDs | Setup Source | Magento Evidence | Laravel Evidence | Notes |
| --- | --- | --- | --- | --- | --- |
| demo-configurable-discount-tax | SF-005, SF-007, CB-002, CB-003, CB-004, CB-006 | Fixture script or dump name | Legacy quote/order snapshots | Laravel parity test IDs | Configurable product with coupon and tax rounding. |

## Per-Feature Fixture Traceability

This table tracks fixture coverage per catalog feature ID. It separates current sample or Laravel foundation fixture coverage from the canonical project fixture set that still needs a restorable database and matching media package.

| Feature ID | Fixture Area | Current Fixture Evidence | Additional Data Needed | Status |
| --- | --- | --- | --- | --- |
| SF-001 | Storefront shell | Sample data smoke coverage only. | Project theme, media, and screenshot fixture. | Not release-ready |
| SF-002 | CMS content | Sample CMS rows and domain foundation snapshots. | Project CMS content and media references. | Not release-ready |
| SF-003 | Category browsing | Sample categories and domain foundation snapshots. | Deep category tree, disabled category, scoped category media. | Not release-ready |
| SF-004 | Product listings | Sample products and domain foundation snapshots. | Listing states for all view modes and stock states. | Not release-ready |
| SF-005 | Product detail | Sample products, media rows, domain and commerce foundation fixtures. | Full product-type media and option fixture set. | Not release-ready |
| SF-006 | Search | Domain foundation search fixture labels. | Search index data, redirect and empty-result fixtures. | Not release-ready |
| SF-007 | Cart | Commerce foundation quote and totals payloads. | Restorable quote/cart fixture with coupons and estimates. | Not release-ready |
| SF-008 | Checkout | Commerce foundation checkout-adjacent payloads. | Guest, registered, multishipping, payment, and failure fixtures. | Not release-ready |
| SF-009 | Multishipping checkout | Commerce foundation order payloads only. | Multi-address quote and order fixture. | Not release-ready |
| SF-010 | Customer account | Sample customers plus domain foundation fixture rows. | Account, address, password, and order-history fixture. | Not release-ready |
| SF-011 | Customer commerce features | Domain foundation wishlist, compare, review, tag, newsletter labels. | Wishlist, compare, review, tag, recurring, and billing agreement records. | Not release-ready |
| SF-012 | Multistore and localization | Sample stores and scoped config foundation fixture. | Project store views, locales, currencies, and scoped content. | Not release-ready |
| SF-013 | Transactional communication | Domain mail and notification fakes plus commerce email labels. | Template, queue, attachment, and localization records. | Not release-ready |
| SF-014 | SEO and feeds | Domain SEO and sitemap labels. | URL rewrite, canonical, RSS, and sitemap file fixtures. | Not release-ready |
| SF-015 | External payment redirects | Integration foundation sandbox payloads. | Payment callback, return, cancel, review, and failure fixtures. | Not release-ready |
| SF-016 | Optional storefront modules | Domain and integration foundation labels. | Poll, analytics, Google Base, and XmlConnect project fixtures. | Not release-ready |
| AD-001 | Admin shell and auth | Auth/security foundation test sessions. | Admin roles, denied states, dashboard, and screenshot fixture. | Not release-ready |
| AD-002 | Catalog products | Sample products plus domain and commerce foundation fixtures. | Admin product edit fixture for every product type. | Not release-ready |
| AD-003 | Catalog categories | Sample categories plus domain foundation fixtures. | Admin category edit and product assignment fixture. | Not release-ready |
| AD-004 | EAV attributes | EAV foundation fixture tables. | Attribute sets, groups, option labels, and backend/source model records. | Not release-ready |
| AD-005 | Sales orders | Sample orders plus commerce/report foundation snapshots. | Admin order state, comments, reorder, and guest lookup records. | Not release-ready |
| AD-006 | Invoices, shipments, refunds | Sample sales lifecycle plus commerce/report snapshots. | Partial invoice, shipment, tracking, refund, PDF, and email records. | Not release-ready |
| AD-007 | Customers | Sample customers plus domain foundation rows. | Customer grid, address, group, online, cart, wishlist, and review records. | Not release-ready |
| AD-008 | Promotions | Sample rules plus commerce/report foundation snapshots. | Full catalog/cart rule, coupon, and scheduled apply records. | Not release-ready |
| AD-009 | CMS and design | Sample CMS plus domain foundation labels. | Widget, layout assignment, WYSIWYG media, and variable records. | Not release-ready |
| AD-010 | System configuration | Scoped config foundation fixture. | Project system config, encrypted fields, and inherited-value records. | Not release-ready |
| AD-011 | Cache, indexes, compiler | Livewire admin parity labels and commerce stale-state labels. | Cache/index process state and stale data records. | Not release-ready |
| AD-012 | Users, roles, API permissions | Auth/security and API contract foundation rows. | Admin users, ACL resources, API roles, API2 attributes, and OAuth records. | Not release-ready |
| AD-013 | Import/export and dataflow | Domain import/export validation fixtures. | Import batches, export files, dataflow profiles, and error files. | Not release-ready |
| AD-014 | Reports | Report foundation `report_facts` fixture. | Full sales, tax, shipping, product, customer, search, cart, review, tag, and bestseller report records. | Not release-ready |
| AD-015 | Newsletter and polls | Domain newsletter and communication labels. | Newsletter templates, queues, subscribers, problem reports, poll records. | Not release-ready |
| AD-016 | Tax and currency | Commerce and config foundation fixtures. | Tax classes, rates, rules, import/export rates, currency rates, symbols. | Not release-ready |
| AD-017 | Store operations | Domain store operation labels. | Store/website/store-view management, backup, email template, URL rewrite, sitemap records. | Not release-ready |
| AD-018 | Integration admin | Integration foundation sandbox config. | Payment, shipping, Google Base, XmlConnect, notification, and credential records. | Not release-ready |
| CB-001 | Quote lifecycle | Commerce foundation quote snapshot. | Guest, customer, merge, persistent, multishipping, and expiration records. | Not release-ready |
| CB-002 | Product type behavior | Commerce and domain product-type labels. | Complete simple, grouped, configurable, virtual, bundle, downloadable, and option records. | Not release-ready |
| CB-003 | Price resolution | Commerce totals fixture. | Base, special, tier, group, catalog rule, option, bundle, currency, and rounding records. | Not release-ready |
| CB-004 | Cart price rules | Commerce discount fixture. | Percent, fixed, coupon, free shipping, stop-rule, usage-limit, and group records. | Not release-ready |
| CB-005 | Catalog price rules | Commerce pricing labels. | Website, customer group, date scope, priority, daily apply, and index records. | Not release-ready |
| CB-006 | Tax calculation | Commerce tax fixture. | Cross-border, shipping tax, discount before/after tax, included/excluded display, and rounding records. | Not release-ready |
| CB-007 | Shipping rates | Integration and commerce sandbox labels. | Free, flat, table, UPS, USPS, FedEx, DHL, virtual, split-address, and tracking records. | Not release-ready |
| CB-008 | Payment lifecycle | Integration and commerce sandbox labels. | Authorize, capture, sale, void, refund, review, fraud, redirect, and webhook records. | Not release-ready |
| CB-009 | Inventory and stock | Commerce inventory labels. | Backorder, min/max sale quantity, increments, visibility, and stock index records. | Not release-ready |
| CB-010 | Order state machine | Commerce order labels. | Quote-to-order, status, invoice, shipment, credit memo, email, and PDF records. | Not release-ready |
| CB-011 | EAV scope semantics | EAV and config foundation fixture tables. | Store labels, option labels, entity defaults, backend/source model, and validation records. | Not release-ready |
| CB-012 | Indexing | Commerce/domain index labels. | Product EAV, price, URL rewrite, flat, category product, stock, search, and tag summary records. | Not release-ready |
| CB-013 | Cache and session behavior | Commerce/config stale cache labels. | Config, layout/block, full page cache, session, form key, and invalidation records. | Not release-ready |
| CB-014 | Email queue | Commerce and cron email labels. | Queue insert, send, cleanup, failure, template, and localization records. | Not release-ready |
| API-001 | SOAP API | API contract foundation row. | SOAP user, WSDL, catalog, customer, sales, and error payload fixtures. | Not release-ready |
| API-002 | XML-RPC API | API contract foundation row. | XML-RPC user, catalog, customer, sales, and error payload fixtures. | Not release-ready |
| API-003 | REST/API2 | API contract foundation row. | OAuth token, admin/customer/guest role, JSON/XML renderer, and permission fixtures. | Not release-ready |
| API-004 | Payment integrations | API and integration foundation sandbox rows. | Payment provider, saved CC if enabled, PayPal, Authorize.Net, Paygate, and Skrill fixtures. | Not release-ready |
| API-005 | Shipping integrations | API and integration foundation sandbox rows. | Free, flat, table, UPS, USPS, FedEx, DHL, and DHL international fixtures. | Not release-ready |
| API-006 | External services | API and integration foundation sandbox rows. | Currency, analytics, Google Base, email, ERP, PIM, CRM, and feed fixtures. | Not release-ready |
| CJ-001 | scheduled backup | Cron config fixture row. | Backup command and restore rehearsal records. | Not release-ready |
| CJ-002 | currency rate update | Cron and integration sandbox rows. | Provider response, rate update, and failure log records. | Not release-ready |
| CJ-003 | delete customer flow password | Cron config fixture row. | Expired reset token records. | Not release-ready |
| CJ-004 | PayPal fetch reports | Cron, integration, and report foundation rows. | Sandbox report and idempotent import records. | Not release-ready |
| CJ-005 | log cleanup | Cron config fixture row. | Log retention records. | Not release-ready |
| CJ-006 | clean expired quotes | Cron config fixture row. | Expired quote and active quote control records. | Not release-ready |
| CJ-007 | aggregate sales orders | Cron and report foundation rows. | Sales order report rows. | Not release-ready |
| CJ-008 | aggregate sales shipments | Cron and report foundation rows. | Sales shipment report rows. | Not release-ready |
| CJ-009 | aggregate sales invoiced | Cron and report foundation rows. | Sales invoiced report rows. | Not release-ready |
| CJ-010 | aggregate sales refunded | Cron and report foundation rows. | Sales refunded report rows. | Not release-ready |
| CJ-011 | aggregate bestsellers | Cron and report foundation rows. | Bestseller report rows. | Not release-ready |
| CJ-012 | clear expired persistent sessions | Cron config fixture row. | Persistent session cleanup records. | Not release-ready |
| CJ-013 | XmlConnect scheduled send | Cron config fixture row. | Retain, bridge, or retire decision records. | Not release-ready |
| CJ-014 | daily catalog rule update | Cron and commerce foundation rows. | Rule price and index records. | Not release-ready |
| CJ-015 | aggregate coupon reports | Cron and report foundation rows. | Coupon report rows. | Not release-ready |
| CJ-016 | clean cache | Cron and commerce foundation rows. | Cache cleanup records. | Not release-ready |
| CJ-017 | send email queue | Cron, integration, and commerce foundation rows. | Queued email send and retry records. | Not release-ready |
| CJ-018 | clean email queue | Cron config fixture row. | Queue retention records. | Not release-ready |
| CJ-019 | product alerts | Cron and domain foundation rows. | Price and stock alert email records. | Not release-ready |
| CJ-020 | aggregate tax reports | Cron and report foundation rows. | Tax report rows. | Not release-ready |
| CJ-021 | reindex product prices | Cron and commerce foundation rows. | Price index records. | Not release-ready |
| CJ-022 | newsletter scheduled send | Cron and domain foundation rows. | Newsletter queue and failure records. | Not release-ready |
| CJ-023 | delete old captcha attempts | Cron config fixture row. | Captcha attempt records. | Not release-ready |
| CJ-024 | delete expired captcha images | Cron config fixture row. | Captcha image records. | Not release-ready |
| CJ-025 | generate sitemaps | Cron and domain foundation rows. | Sitemap file and URL records. | Not release-ready |

## Rules

- Do not destructively migrate the commerce schema.
- Any new infrastructure table requires explicit approval.
- Fixtures must be reproducible.
- Production-derived data must be sanitized.
- Media fixtures must match database references.
- Fixture restore must be documented and automated where possible.

## Acceptance Criteria

- A developer can restore the fixture database and media locally.
- CI can restore the required fixtures.
- EAV parity tests use fixture data.
- Visual tests use stable media fixtures.
- Performance tests have enough volume to expose query problems.
- The demo fixture covers all product types, promotion types, tax/shipping/payment paths, admin roles, API users, cron jobs, and report aggregates listed above.
- Every fixture maps back to one or more feature IDs.
