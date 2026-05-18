# Magento Feature Catalog

This catalog is the canonical feature list for proving the Laravel modernization is complete. Every feature ID below must be inventoried, characterized against Magento CE `1.9.4.5`, implemented or intentionally retired, tested, and linked to evidence.

The current checkout contains Magento CE core and sample baseline data only. Project-specific custom modules, themes, integrations, store configuration, and production-like data still require the real project overlay.

## Usage Rules

- Every backlog item must reference at least one feature ID.
- Every migrated feature ID must reference fixture data, UI screenshots when visible, characterization tests, Laravel tests, and release evidence.
- A feature is not done until Magento and Laravel can be compared side-by-side from the same fixture database and media set.
- If a feature is retired, document the business owner, approval date, migration impact, and replacement behavior.
- If project code adds custom behavior, add project-specific feature IDs with the prefix `PX-`.

## Feature Status Values

| Status | Meaning |
| --- | --- |
| `baseline-only` | Known from Magento CE core/sample data, not yet checked against project overlay. |
| `project-required` | Requires project overlay or project fixture before final decision. |
| `preserve` | Must behave the same in Laravel. |
| `bridge` | Temporarily served by Magento while Laravel replaces it. |
| `replace` | Rebuilt in Laravel with intentionally equivalent observable behavior. |
| `retire` | Removed by explicit business decision. |

## Storefront Features

| ID | Feature | Required Coverage | Initial Status |
| --- | --- | --- | --- |
| SF-001 | Storefront shell | HTML head, assets, header, top links, navigation, breadcrumbs, messages, mini cart, footer, cookie notice, responsive layout. | baseline-only |
| SF-002 | CMS pages and blocks | Home page, CMS page routing, CMS blocks, widgets, no-route page, redirects, WYSIWYG output, cache behavior. | baseline-only |
| SF-003 | Category browsing | Category tree, URL rewrites, layered navigation, sorting, pagination, view modes, empty categories, scoped category attributes. | baseline-only |
| SF-004 | Product listing items | Price rendering, tax display, stock state, ratings, add to cart, wishlist, compare, swatches where enabled. | baseline-only |
| SF-005 | Product detail pages | Simple, grouped, configurable, virtual, bundle, downloadable, custom options, media gallery, related/up-sell/cross-sell, MSRP, alerts, reviews, tags, send to friend. | baseline-only |
| SF-006 | Search | Quick search, advanced search, result pages, empty results, terms, URL behavior, search indexing. | baseline-only |
| SF-007 | Cart | Add, update, remove, validation messages, custom options, grouped/configurable/bundle/downloadable rows, coupons, shipping/tax estimate, cross-sells, persistent cart. | baseline-only |
| SF-008 | Checkout | Onepage checkout, guest checkout, registered checkout, login during checkout, billing, shipping, shipping methods, payment methods, order review, success/failure, agreements. | baseline-only |
| SF-009 | Multishipping checkout | Multiple addresses, shipping methods per address, billing, overview, place order, success and failure paths. | baseline-only |
| SF-010 | Customer account | Register, confirm email, login, logout, forgot/reset password, dashboard, edit account, change password, address book, order history, reorder. | baseline-only |
| SF-011 | Customer commerce features | Wishlist, shared wishlist, compare list, reviews, tags, newsletter subscription, downloadable products, recurring profiles, billing agreements. | baseline-only |
| SF-012 | Multistore and localization | Website/store view switch, locale, currency switch, scoped config/content/products/categories, base URLs. | baseline-only |
| SF-013 | Transactional communication | Contact form, product alerts, send to friend, newsletter subscription, transactional email rendering and queue. | baseline-only |
| SF-014 | SEO and feeds | URL rewrites, canonical/meta output, HTML sitemap, XML sitemap, RSS feeds. | baseline-only |
| SF-015 | External payment redirects | PayPal, Payflow, Authorize.Net Direct Post, 3-D Secure/Centinel, payment review and cancel/return callbacks. | project-required |
| SF-016 | Optional storefront modules | Polls, tags, Google Analytics, Google Base, XmlConnect/mobile endpoints where enabled. | project-required |

## Admin Features

| ID | Feature | Required Coverage | Initial Status |
| --- | --- | --- | --- |
| AD-001 | Admin shell and auth | Login, logout, forgot/reset password, session timeout, dashboard, menu, notifications, ACL denied pages, form keys. | baseline-only |
| AD-002 | Catalog products | Grid, filters, mass actions, product create/edit for all product types, websites, categories, attributes, inventory, prices, custom options, related/up-sell/cross-sell, media. | baseline-only |
| AD-003 | Catalog categories and navigation | Category tree, scoped category edit, product assignment, URL keys, design settings, category widgets. | baseline-only |
| AD-004 | EAV attributes | Product/customer/category attributes, attribute sets, groups, options, validation, store labels, backend/source/frontend models. | baseline-only |
| AD-005 | Sales orders | Order grid, order view, comments, status changes, reorder, cancel, hold/unhold, admin order create/edit, guest order lookup. | baseline-only |
| AD-006 | Invoices, shipments, refunds | Invoice create/view/email, shipment create/view/tracking/email, credit memo/refund/adjustment, totals, PDFs. | baseline-only |
| AD-007 | Customers | Customer grid, create/edit, addresses, groups, online customers, carts, wishlists, reviews, tags, password reset. | baseline-only |
| AD-008 | Promotions | Catalog price rules, shopping cart price rules, coupon generation, rule conditions/actions, reports, scheduled apply. | baseline-only |
| AD-009 | CMS and design | CMS pages, blocks, widgets, layout/design assignments, WYSIWYG media browser, variables. | baseline-only |
| AD-010 | System configuration | Default/website/store scopes, inherited values, encrypted fields, field validation, source models, env overrides. | baseline-only |
| AD-011 | Cache, indexes, compiler | Cache status/actions, index process status/actions, compiler controls if retained, page cache if enabled. | baseline-only |
| AD-012 | Users, roles, API permissions | Admin users, admin roles, ACL resources, API users/roles, API2 attributes/roles, OAuth consumers/tokens. | baseline-only |
| AD-013 | Import/export and dataflow | Import, export, dataflow profiles, validation, batch execution, error files, entity coverage. | baseline-only |
| AD-014 | Reports | Sales, tax, shipping, invoiced, refunded, coupons, products, customers, reviews, tags, carts, search terms, refresh statistics. | baseline-only |
| AD-015 | Newsletter and polls | Templates, queues, subscribers, problem reports, polls and answers. | baseline-only |
| AD-016 | Tax and currency | Tax classes, tax rates, tax rules, import/export rates, currency rates, currency symbols. | baseline-only |
| AD-017 | Store operations | Store/website/store-view management, backups, system info, email templates, URL rewrites, sitemap management. | baseline-only |
| AD-018 | Integration admin | Payment/shipping settings, Google Base, XmlConnect/mobile app admin, notifications, sandbox credentials. | project-required |

## Complex Commerce Features

| ID | Feature | Required Coverage | Initial Status |
| --- | --- | --- | --- |
| CB-001 | Quote lifecycle | Guest quote, customer quote, merge on login, persistent quote, multishipping quote, quote expiration. | baseline-only |
| CB-002 | Product type behavior | Simple, grouped, configurable, virtual, bundle, downloadable, custom options, required options, parent/child item rows. | baseline-only |
| CB-003 | Price resolution | Base price, special price, tier price, group price, catalog rule price, option price, bundle price, currency conversion, rounding. | baseline-only |
| CB-004 | Cart price rules | Percent, fixed item, fixed cart, buy X get Y, coupon, auto rule, free shipping, stop further rules, usage limits, customer groups. | baseline-only |
| CB-005 | Catalog price rules | Rule indexing, website/customer group/date scope, priority, daily apply, interaction with special/tier/group prices. | baseline-only |
| CB-006 | Tax calculation | Tax classes, rates, rules, shipping tax, discounts before/after tax, price incl/excl tax, cross-border trade, rounding. | baseline-only |
| CB-007 | Shipping rates | Free shipping, flat rate, table rate, UPS, USPS, FedEx, DHL, virtual carts, split addresses, tracking. | project-required |
| CB-008 | Payment lifecycle | Authorize, capture, sale, void, refund, partial refund, payment review, fraud review, redirects, IPN/webhooks. | project-required |
| CB-009 | Inventory and stock | Stock status, backorders, min/max sale qty, qty increments, out of stock visibility, stock index. | baseline-only |
| CB-010 | Order state machine | Quote to order, order states/statuses, invoices, shipments, credit memos, comments, emails, PDFs. | baseline-only |
| CB-011 | EAV scope semantics | Attribute fallback, store labels, option labels, entity defaults, backend models, source models, validation. | baseline-only |
| CB-012 | Indexing | Product EAV, price, URL rewrite, flat product/category, category product, stock, search, tag summary. | baseline-only |
| CB-013 | Cache and session behavior | Config cache, layout/block cache, full page cache if enabled, session persistence, form keys, cache invalidation, `Cm_RedisSession` retain/replace/retire decision. | baseline-only |
| CB-014 | Email queue | Queue insert, cron send, templates, localization, failure handling, cleanup. | baseline-only |

## API And Integration Features

| ID | Feature | Required Coverage | Initial Status |
| --- | --- | --- | --- |
| API-001 | SOAP API v1/v2 | Auth, WSDL compatibility, catalog/customer/sales calls, error payloads. | baseline-only |
| API-002 | XML-RPC API | Auth, catalog/customer/sales calls, error payloads. | baseline-only |
| API-003 | REST/API2 | OAuth, admin/customer/guest roles, attributes, JSON/XML/query renderers, resource permissions. | baseline-only |
| API-004 | Payment integrations | Check/money order, bank transfer, cash on delivery, purchase order, free, saved CC if enabled, PayPal family, Authorize.Net, Paygate, `Phoenix_Moneybookers`/Skrill behavior. | project-required |
| API-005 | Shipping integrations | Free shipping, flat rate, table rate, UPS, USPS, FedEx, DHL, DHL international. | project-required |
| API-006 | External services | Currency rate imports, Google Analytics, Google Base, email provider, project ERP/PIM/CRM/feed integrations. | project-required |

## Cron And Scheduled Features

Cron jobs must be represented in the Laravel scheduler, tested with fixture data, and compared against Magento side effects.

| ID | Magento Job | Schedule Source | Legacy Model | Required Evidence |
| --- | --- | --- | --- | --- |
| CJ-001 | scheduled backup | Config-driven | `backup/observer::scheduledBackup` | Backup job command, disabled-by-default safety, restore rehearsal. |
| CJ-002 | currency rate update | Config-driven | `directory/observer::scheduledUpdateCurrencyRates` | Sandbox or mocked provider, rate updates, failure logs. |
| CJ-003 | delete customer flow password | `0 0 1 * *` | `customer/observer::deleteCustomerFlowPassword` | Expired reset token cleanup. |
| CJ-004 | PayPal fetch reports | Config-driven | `paypal/observer::fetchReports` | Sandbox/mock reports and idempotent import. |
| CJ-005 | log cleanup | Config-driven | `log/cron::logClean` | Log retention cleanup. |
| CJ-006 | clean expired quotes | `0 0 * * *` | `sales/observer::cleanExpiredQuotes` | Old quote cleanup without active quote loss. |
| CJ-007 | aggregate sales orders | `0 0 * * *` | `sales/observer::aggregateSalesReportOrderData` | Report table parity. |
| CJ-008 | aggregate sales shipments | `0 0 * * *` | `sales/observer::aggregateSalesReportShipmentData` | Report table parity. |
| CJ-009 | aggregate sales invoiced | `0 0 * * *` | `sales/observer::aggregateSalesReportInvoicedData` | Report table parity. |
| CJ-010 | aggregate sales refunded | `0 0 * * *` | `sales/observer::aggregateSalesReportRefundedData` | Report table parity. |
| CJ-011 | aggregate bestsellers | `0 0 * * *` | `sales/observer::aggregateSalesReportBestsellersData` | Report table parity. |
| CJ-012 | clear expired persistent sessions | `0 0 * * *` | `persistent/observer::clearExpiredCronJob` | Persistent session cleanup. |
| CJ-013 | XmlConnect scheduled send | `*/5 * * * *` | `xmlconnect/observer::scheduledSend` | Retain, bridge, or retire decision. |
| CJ-014 | daily catalog rule update | `0 1 * * *` | `catalogrule/observer::dailyCatalogUpdate` | Rule price/index parity. |
| CJ-015 | aggregate coupon reports | `0 0 * * *` | `salesrule/observer::aggregateSalesReportCouponsData` | Coupon report parity. |
| CJ-016 | clean cache | `30 2 * * *` | `core/observer::cleanCache` | Cache cleanup parity or documented replacement. |
| CJ-017 | send email queue | `*/1 * * * *` | `core/email_queue::send` | Queued email send and retry behavior. |
| CJ-018 | clean email queue | `0 0 * * *` | `core/email_queue::cleanQueue` | Queue retention cleanup. |
| CJ-019 | product alerts | Config-driven | `productalert/observer::process` | Price and stock alert emails. |
| CJ-020 | aggregate tax reports | `0 0 * * *` | `tax/observer::aggregateSalesReportTaxData` | Tax report parity. |
| CJ-021 | reindex product prices | `0 2 * * *` | `catalog/observer::reindexProductPrices` | Price index parity. |
| CJ-022 | newsletter scheduled send | `*/5 * * * *` | `newsletter/observer::scheduledSend` | Newsletter queue send and failure handling. |
| CJ-023 | delete old captcha attempts | `*/30 * * * *` | `captcha/observer::deleteOldAttempts` | Captcha attempt retention. |
| CJ-024 | delete expired captcha images | `*/10 * * * *` | `captcha/observer::deleteExpiredImages` | Captcha image retention. |
| CJ-025 | generate sitemaps | Config-driven | `sitemap/observer::scheduledGenerateSitemaps` | Sitemap files and URLs. |

## Core Module Coverage

The Magento CE `1.9.4.5` core modules present in this checkout are:

`Admin`, `AdminNotification`, `Adminhtml`, `Api`, `Api2`, `Authorizenet`, `Backup`, `Bundle`, `Captcha`, `Catalog`, `CatalogIndex`, `CatalogInventory`, `CatalogRule`, `CatalogSearch`, `Centinel`, `Checkout`, `Cms`, `Compiler`, `ConfigurableSwatches`, `Connect`, `Contacts`, `Core`, `Cron`, `CurrencySymbol`, `Customer`, `Dataflow`, `Directory`, `Downloadable`, `Eav`, `GiftMessage`, `GoogleAnalytics`, `GoogleBase`, `GoogleCheckout`, `ImportExport`, `Index`, `Install`, `Log`, `Media`, `Newsletter`, `Oauth`, `Page`, `PageCache`, `Paygate`, `Payment`, `Paypal`, `PaypalUk`, `Persistent`, `Poll`, `ProductAlert`, `Rating`, `Reports`, `Review`, `Rss`, `Rule`, `Sales`, `SalesRule`, `Sendfriend`, `Shipping`, `Sitemap`, `Tag`, `Tax`, `Uploader`, `Usa`, `Weee`, `Widget`, `Wishlist`, `XmlConnect`.

For each module, the inventory must record:

- Whether the module is enabled in the project.
- Which feature IDs it affects.
- Whether it is preserved, bridged, replaced, or retired.
- Which routes, blocks, helpers, models, setup scripts, cron jobs, observers, and templates it contributes.
- Which Magento XML declarations are read only for legacy characterization and which Laravel PHP declarations replace them.

## Completion Gate

The modernization cannot be called complete until:

- Every feature ID in this file has a final status.
- Every `preserve`, `bridge`, and `replace` item has fixture coverage.
- Every UI feature maps to `specs/modernization/ui-screen-inventory.md`.
- Every complex behavior maps to `specs/modernization/complex-feature-reverse-engineering.md`.
- Every cron job maps to a Laravel scheduler entry or an approved retirement decision.
- Every feature ID is present in the test plan traceability matrix.
- Preparation mode: `php dev/modernization/validate-feature-traceability.php --strict` passes as a template coverage check, proving every catalog ID has an explicit test-plan traceability row.
- Final release mode: `php dev/modernization/validate-feature-traceability.php --final` passes against final inventory, fixture, screenshot, test, documentation, and release evidence, with no placeholder status values.
