---
tags:
- Development
---

# Feature Inventory

This inventory defines the feature coverage that must be completed before a domain is considered ready for migration.

## Storefront

| Feature | Required Details | Status |
| --- | --- | --- |
| Home page | CMS content, blocks, product widgets, banners, cache behavior. | To inventory |
| Category pages | Layered navigation, sorting, pagination, product types, empty states. | To inventory |
| Product pages | Simple, configurable, bundle, grouped, downloadable, virtual, custom options, media. | To inventory |
| Search | Native search, redirects, empty results, spelling/synonyms if used. | To inventory |
| Cart | Add/update/remove, coupons, estimates, persistent cart, messages. | To inventory |
| Checkout | Guest, registered, multishipping if used, shipping/payment/tax/totals. | To inventory |
| Customer account | Register, login, reset password, addresses, orders, wishlist, newsletter. | To inventory |
| CMS | Pages, blocks, widgets, redirects, 404/no-route. | To inventory |
| Multistore | Store switch, scope-specific config/content/products, currencies, locales. | To inventory |
| Emails | Transactional templates, queue, attachments, localization. | To inventory |

## Admin

| Feature | Required Details | Status |
| --- | --- | --- |
| Auth/session | Login, logout, timeout, password policy. | To inventory |
| Dashboard | Widgets, charts, permissions, data source. | To inventory |
| Catalog | Product/category grids/forms, attributes, media, inventory, websites. | To inventory |
| Customers | Grids/forms, addresses, groups, customer attributes. | To inventory |
| Sales | Orders, invoices, shipments, credit memos, refunds, comments, emails. | To inventory |
| Promotions | Catalog rules, cart rules, coupons. | To inventory |
| CMS | Pages, blocks, widgets. | To inventory |
| System config | Scope behavior, validation, secrets, env overrides. | To inventory |
| Cache/indexers | Status, actions, permissions, long-running behavior. | To inventory |
| Users/roles | ACL, custom permissions, role boundaries. | To inventory |
| Reports/exports | All reports, exports, scheduled exports, custom grids. | To inventory |
| Imports | Dataflow/import-export/custom imports. | To inventory |

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

- Each feature has an owner.
- Each feature has a migration decision: `preserve`, `bridge`, `replace`, or `retire`.
- Each feature maps to test-plan coverage.
- Each integration has sandbox credentials or a mock strategy.
- Each feature has visual baseline requirements if it affects UI.

