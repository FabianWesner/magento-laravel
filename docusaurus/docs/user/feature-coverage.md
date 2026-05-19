---
id: feature-coverage
title: Feature Coverage
---

Feature coverage is tracked by stable IDs in `specs/modernization/magento-feature-catalog.md`.

## User-Facing Status

Feature rows include:

- Status: legacy, bridged, Laravel complete, or retired.
- User-visible impact.
- Screenshots or non-UI evidence.
- Known limitations.
- Support and rollback notes.

No feature can be marked complete in user documentation until tests and release evidence exist in the migration specs.

## Per-Feature Coverage

This page mirrors the catalog IDs from `specs/modernization/magento-feature-catalog.md` for user-facing documentation. These rows document coverage status only; they do not claim feature completion.

| Feature ID | Feature | User impact | Current evidence status | Release status |
| --- | --- | --- | --- | --- |
| SF-001 | Storefront shell | Storefront-facing shell and navigation behavior. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-002 | CMS pages and blocks | Storefront-facing content pages, blocks, and redirects. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-003 | Category browsing | Storefront-facing category navigation and filtering. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-004 | Product listing items | Storefront-facing listing cards and product actions. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-005 | Product detail pages | Storefront-facing product details for all product types. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-006 | Search | Storefront-facing search journeys and empty states. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-007 | Cart | Storefront-facing cart editing, totals, and promotions. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-008 | Checkout | Storefront-facing checkout, payment, and order placement. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-009 | Multishipping checkout | Storefront-facing multi-address checkout. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-010 | Customer account | Storefront-facing registration, login, dashboard, and order history. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-011 | Customer commerce features | Storefront-facing wishlist, compare, reviews, tags, newsletter, and downloads. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-012 | Multistore and localization | Storefront-facing store view, locale, currency, and scoped content. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-013 | Transactional communication | Storefront-facing contact, alerts, send-to-friend, newsletter, and email output. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-014 | SEO and feeds | Storefront-facing URL, meta, sitemap, and RSS output. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-015 | External payment redirects | Storefront-facing off-site payment return and failure flows. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| SF-016 | Optional storefront modules | Storefront-facing optional poll, tag, analytics, feed, and mobile endpoint behavior. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-001 | Admin shell and auth | Admin-facing login, dashboard, menu, and access-denied behavior. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-002 | Catalog products | Admin-facing product management for all product types. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-003 | Catalog categories and navigation | Admin-facing category tree and scoped category management. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-004 | EAV attributes | Admin-facing attribute, set, option, and validation management. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-005 | Sales orders | Admin-facing order grid, detail, status, comments, and order creation. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-006 | Invoices, shipments, refunds | Admin-facing invoice, shipment, credit memo, refund, and PDF workflows. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-007 | Customers | Admin-facing customer, address, group, cart, wishlist, and reset workflows. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-008 | Promotions | Admin-facing catalog and cart price rule management. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-009 | CMS and design | Admin-facing CMS, widget, media browser, and design assignment workflows. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-010 | System configuration | Admin-facing scoped configuration and encrypted field workflows. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-011 | Cache, indexes, compiler | Admin-facing cache, index, and retained compiler controls. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-012 | Users, roles, API permissions | Admin-facing users, roles, ACL, API2, and OAuth permissions. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-013 | Import/export and dataflow | Admin-facing import, export, validation, and batch execution. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-014 | Reports | Admin-facing sales, catalog, customer, review, search, and statistics reports. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-015 | Newsletter and polls | Admin-facing newsletter, queue, subscriber, and poll management. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-016 | Tax and currency | Admin-facing tax class, tax rate, tax rule, and currency workflows. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-017 | Store operations | Admin-facing store, backup, system info, email template, URL rewrite, and sitemap workflows. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| AD-018 | Integration admin | Admin-facing payment, shipping, Google Base, XmlConnect, and sandbox credential settings. | Spec coverage exists; screenshot and release evidence absent. | Not release-ready. |
| CB-001 | Quote lifecycle | Commerce behavior behind cart, customer, persistent cart, and multishipping journeys. | Spec coverage exists; dual-runtime release evidence absent. | Not release-ready. |
| CB-002 | Product type behavior | Commerce behavior behind product detail, cart, checkout, and admin product journeys. | Spec coverage exists; dual-runtime release evidence absent. | Not release-ready. |
| CB-003 | Price resolution | Commerce behavior behind displayed prices, totals, and reports. | Spec coverage exists; dual-runtime release evidence absent. | Not release-ready. |
| CB-004 | Cart price rules | Commerce behavior behind cart discounts and coupon outcomes. | Spec coverage exists; dual-runtime release evidence absent. | Not release-ready. |
| CB-005 | Catalog price rules | Commerce behavior behind catalog price display and rule indexing. | Spec coverage exists; dual-runtime release evidence absent. | Not release-ready. |
| CB-006 | Tax calculation | Commerce behavior behind tax display, totals, reports, and invoices. | Spec coverage exists; dual-runtime release evidence absent. | Not release-ready. |
| CB-007 | Shipping rates | Commerce behavior behind shipping methods, split addresses, and tracking. | Spec coverage exists; dual-runtime release evidence absent. | Not release-ready. |
| CB-008 | Payment lifecycle | Commerce behavior behind authorization, capture, void, refund, fraud, and redirects. | Spec coverage exists; dual-runtime release evidence absent. | Not release-ready. |
| CB-009 | Inventory and stock | Commerce behavior behind stock display, cart validation, and stock indexing. | Spec coverage exists; dual-runtime release evidence absent. | Not release-ready. |
| CB-010 | Order state machine | Commerce behavior behind order state, invoice, shipment, refund, email, and PDF workflows. | Spec coverage exists; dual-runtime release evidence absent. | Not release-ready. |
| CB-011 | EAV scope semantics | Commerce behavior behind scoped labels, values, defaults, and validation. | Spec coverage exists; dual-runtime release evidence absent. | Not release-ready. |
| CB-012 | Indexing | Commerce behavior behind product, price, URL, stock, search, and tag indexes. | Spec coverage exists; dual-runtime release evidence absent. | Not release-ready. |
| CB-013 | Cache and session behavior | Commerce behavior behind cache invalidation, form keys, sessions, and persistence. | Spec coverage exists; dual-runtime release evidence absent. | Not release-ready. |
| CB-014 | Email queue | Commerce behavior behind transactional queueing, send attempts, localization, and cleanup. | Spec coverage exists; dual-runtime release evidence absent. | Not release-ready. |
| API-001 | SOAP API v1/v2 | Integration-facing SOAP compatibility for catalog, customer, and sales clients. | Spec coverage exists; contract release evidence absent. | Not release-ready. |
| API-002 | XML-RPC API | Integration-facing XML-RPC compatibility for catalog, customer, and sales clients. | Spec coverage exists; contract release evidence absent. | Not release-ready. |
| API-003 | REST/API2 | Integration-facing REST/API2 OAuth, role, attribute, and renderer behavior. | Spec coverage exists; contract release evidence absent. | Not release-ready. |
| API-004 | Payment integrations | Integration-facing payment method, redirect, and gateway behavior. | Spec coverage exists; contract release evidence absent. | Not release-ready. |
| API-005 | Shipping integrations | Integration-facing shipping carrier and rate behavior. | Spec coverage exists; contract release evidence absent. | Not release-ready. |
| API-006 | External services | Integration-facing currency, analytics, feed, email, ERP, PIM, CRM, and feed behavior. | Spec coverage exists; contract release evidence absent. | Not release-ready. |
| CJ-001 | scheduled backup | Operator-facing backup scheduling and restore confidence. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-002 | currency rate update | Operator-facing currency update scheduling and failure visibility. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-003 | delete customer flow password | Operator-facing cleanup of expired customer reset tokens. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-004 | PayPal fetch reports | Operator-facing PayPal report import scheduling and idempotency. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-005 | log cleanup | Operator-facing log retention cleanup. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-006 | clean expired quotes | Operator-facing quote retention cleanup. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-007 | aggregate sales orders | Operator-facing sales order report aggregation. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-008 | aggregate sales shipments | Operator-facing shipment report aggregation. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-009 | aggregate sales invoiced | Operator-facing invoiced report aggregation. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-010 | aggregate sales refunded | Operator-facing refund report aggregation. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-011 | aggregate bestsellers | Operator-facing bestseller report aggregation. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-012 | clear expired persistent sessions | Operator-facing persistent session cleanup. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-013 | XmlConnect scheduled send | Operator-facing XmlConnect scheduled delivery. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-014 | daily catalog rule update | Operator-facing catalog rule daily apply and index behavior. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-015 | aggregate coupon reports | Operator-facing coupon report aggregation. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-016 | clean cache | Operator-facing cache cleanup behavior. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-017 | send email queue | Operator-facing transactional email send scheduling. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-018 | clean email queue | Operator-facing transactional email queue cleanup. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-019 | product alerts | Operator-facing price and stock alert email processing. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-020 | aggregate tax reports | Operator-facing tax report aggregation. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-021 | reindex product prices | Operator-facing product price reindexing. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-022 | newsletter scheduled send | Operator-facing newsletter queue delivery. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-023 | delete old captcha attempts | Operator-facing captcha attempt cleanup. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-024 | delete expired captcha images | Operator-facing captcha image cleanup. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
| CJ-025 | generate sitemaps | Operator-facing sitemap generation. | Spec coverage exists; scheduler release evidence absent. | Not release-ready. |
