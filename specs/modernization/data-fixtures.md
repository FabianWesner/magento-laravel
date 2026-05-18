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
| Orders | Pending, processing, complete, closed, canceled, holded/payment-review where applicable, partial invoice, partial shipment, partial refund, offline refund, online refund mock, order comments and emails. |
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

Current Magento sample data result on 2026-05-18:

| Area | Current Evidence | Status |
| --- | --- | --- |
| Product types | Bundle, configurable, downloadable, grouped, simple, and virtual products are present. | Covered |
| Websites and stores | Two websites, two store groups, and four store views are present; no disabled store view is present. | Gap |
| Categories | 29 categories and one empty category are present; maximum depth is level 3 and no disabled category is present. | Gap |
| Custom options | Required and optional product custom options are present. | Covered |
| EAV attributes | All core backend types and global, website, and store scoped catalog attributes are present. | Covered |
| Pricing and promotions | Special prices, tier prices, catalog rules, and cart rules are present; group prices are missing. | Gap |
| Inventory | Stock rows and out-of-stock products are present; backorder-enabled products are missing. | Gap |
| Customers | Registered customers, groups, and addresses are present. | Covered |
| Sales lifecycle | Orders, invoices, shipments, and credit memos are present. | Covered |
| Tax and shipping | Tax rates and rules are present; table-rate shipping data is missing. | Gap |
| Payment and API users | Active payment config exists; SOAP/XML-RPC API users and OAuth consumers are missing. | Gap |
| CMS and media | CMS pages, blocks, widgets, and product media rows are present. | Covered |
| Admin users and roles | One admin user and two admin roles are present; role coverage is insufficient. | Gap |
| Cron and reports | Report aggregates are populated; cron schedule rows are missing. | Gap |

Summary: Magento sample data covers 6 of 14 checked fixture areas and leaves 8 known gaps. It is useful for smoke tests but is not sufficient as the canonical demo fixture.

## Fixture Traceability

Every fixture record must identify which feature IDs it proves. Use this table shape in the fixture manifest:

| Fixture ID | Feature IDs | Setup Source | Magento Evidence | Laravel Evidence | Notes |
| --- | --- | --- | --- | --- | --- |
| demo-configurable-discount-tax | SF-005, SF-007, CB-002, CB-003, CB-004, CB-006 | Fixture script or dump name | Legacy quote/order snapshots | Laravel parity test IDs | Configurable product with coupon and tax rounding. |

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
