# UI Screen Inventory And Screenshot Specification

Every existing user interface must be inventoried and screenshotted before migration. A Laravel or Livewire replacement is not complete until it can be compared side by side with the Magento baseline for the same URL, data fixture, user role, viewport, and UI state.

This file covers visible `SF-001` through `SF-016` and `AD-001` through `AD-018` feature IDs from `specs/modernization/magento-feature-catalog.md`.

## Required Evidence

For every screen, record:

- Stable screen ID.
- Magento URL and route/front name.
- Laravel URL and route owner.
- Required fixture data.
- Required user role or customer state.
- Required viewports.
- Required UI states.
- Screenshot file paths.
- Dynamic behavior notes.
- Form fields, validation rules, actions, redirects, messages, modals, tabs, grids, exports, and permission boundaries.
- Parity decision: `preserve`, `bridge`, `replace`, or `retire`.

## Screenshot Storage

Screenshots are local evidence and should not be committed unless they are curated baseline artifacts.

Use this structure:

```text
.localdev/visual-baseline/
|-- magento/
|   |-- storefront/<screen-id>/<viewport>/<state>.png
|   `-- admin/<screen-id>/<viewport>/<state>.png
`-- laravel/
    |-- storefront/<screen-id>/<viewport>/<state>.png
    `-- admin/<screen-id>/<viewport>/<state>.png
```

## Required Viewports

| Viewport | Size | Applies To |
| --- | --- | --- |
| Desktop | `1440x1000` | Storefront and admin. |
| Laptop | `1280x900` | Storefront and admin. |
| Tablet | `768x1024` | Storefront and admin where responsive behavior exists. |
| Mobile | `390x844` | Storefront and admin login; admin internals only where supported. |

## Storefront Screen Catalog

| Screen Group | Required Screens |
| --- | --- |
| Global shell | Header, navigation, mini cart, search box, store switcher, currency switcher, breadcrumbs, footer, cookie notice if enabled. |
| CMS | Home, CMS page, CMS block embedded in layout, widget output, no-route/404, redirects. |
| Catalog category | Category with products, empty category, layered navigation, price filter, attribute filter, toolbar sorting, pagination, grid/list switch, compare links. |
| Product detail | Simple, configurable, grouped, bundle, downloadable, virtual, custom options, tier price, special price, out-of-stock, low stock, related, up-sell, cross-sell, reviews, tags, product alerts. |
| Search | Results, empty results, advanced search, search terms, redirects/synonyms if used. |
| Cart | Empty cart, cart with each product type, quantity update, remove, coupon success/failure, estimate shipping/tax, gift message, cross-sells, persistent cart if enabled. |
| Checkout | Guest checkout, registered checkout, login in checkout, billing, shipping, shipping method, payment method, review, place order success, place order failure, multishipping if enabled. |
| Customer account | Register, login, logout, forgot/reset password, dashboard, account edit, addresses, orders, order view, reorder, wishlist, newsletter, downloadable products, billing agreements if enabled. |
| Wishlist/compare/review/tag | Wishlist list/share/move to cart, compare list, review list/form, product tags if enabled. |
| Contacts/send friend | Contact form, send-to-friend form, success and validation errors. |
| RSS/sitemap | RSS links, HTML sitemap if enabled, generated XML sitemap result. |

## Admin Screen Catalog

| Screen Group | Required Screens |
| --- | --- |
| Global shell | Login, forgot password, dashboard, menu, notifications, messages, help links, locale selector. |
| Catalog | Product grid, product edit tabs for every product type, category tree/edit, attributes, attribute sets, URL rewrites, search terms, tags, reviews. |
| Sales | Orders grid/view/create, invoices, shipments, credit memos, transactions, recurring profiles, billing agreements, tax reports. |
| Customers | Customer grid/edit, addresses, groups, online customers, newsletter subscriptions. |
| Promotions | Catalog price rules, shopping cart price rules, coupons, rule conditions/actions, rule reports. |
| CMS | Pages, static blocks, widgets, polls if enabled. |
| Reports | Sales, tax, shipping, refunds, coupons, products, customers, reviews, tags, search terms, bestsellers, low stock. |
| System | Configuration by scope, cache management, index management, permissions, roles, users, backups, import/export, dataflow, web services, design, stores, currency, transactional emails, custom variables, encryption key if available. |
| Integrations | Payment configuration, shipping methods, tax settings, API users/roles, OAuth consumers/tokens, Google integrations if enabled. |
| Error/permission states | Access denied, invalid form key, validation errors, empty grids, failed save, failed import, failed export. |

## Dynamic State Requirements

Every interactive screen must capture:

- Default loaded state.
- Loading state if asynchronous.
- Empty state.
- Validation error state.
- Permission denied state.
- Success message.
- Failure message.
- Modal/dialog open state.
- Store-view or website-scope override state.
- Disabled/read-only state where permissions or config apply.

## Per-Feature UI Traceability

This table maps each visible catalog feature ID to the screen evidence still needed for release. It is separate from the final screenshot manifest; no row below is a captured screenshot artifact.

| Feature ID | Screen Coverage | Runtime Pair | Role Or State | Fixture Reference | Current UI Evidence | Screenshot Status | Parity Decision | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| SF-001 | Storefront shell, header, navigation, mini cart, search, footer | Magento and Laravel | Guest, customer, store switch | Fixture package absent | Catalog scope defined | Screenshot set absent | preserve | Not release-ready |
| SF-002 | Home, CMS page, CMS block, widget, no-route, redirect | Magento and Laravel | Guest, store scope | CMS fixture package absent | Domain service evidence | Screenshot set absent | preserve | Not release-ready |
| SF-003 | Category listing, layered navigation, pagination, view modes | Magento and Laravel | Guest, customer, store scope | Category fixture package absent | Domain service evidence | Screenshot set absent | preserve | Not release-ready |
| SF-004 | Product listing item, price, tax, stock, ratings, wishlist, compare | Magento and Laravel | Guest, customer | Product fixture package absent | Domain service evidence | Screenshot set absent | preserve | Not release-ready |
| SF-005 | Product detail for all product types, media, related, reviews, alerts | Magento and Laravel | Guest, customer, store scope | Product fixture package absent | Domain and commerce evidence | Screenshot set absent | preserve | Not release-ready |
| SF-006 | Quick search, advanced search, results, empty results | Magento and Laravel | Guest, customer | Search fixture package absent | Domain service evidence | Screenshot set absent | preserve | Not release-ready |
| SF-007 | Empty cart, populated cart, coupon, estimate shipping and tax | Magento and Laravel | Guest, customer, persistent cart | Cart fixture package absent | Commerce evidence | Screenshot set absent | preserve | Not release-ready |
| SF-008 | Onepage checkout, billing, shipping, payment, review, success, failure | Magento and Laravel | Guest, customer | Checkout fixture package absent | Commerce evidence | Screenshot set absent | preserve | Not release-ready |
| SF-009 | Multishipping addresses, methods, overview, success, failure | Magento and Laravel | Customer | Multishipping fixture package absent | Commerce evidence | Screenshot set absent | preserve | Not release-ready |
| SF-010 | Register, login, dashboard, addresses, orders, password reset | Magento and Laravel | Guest, customer, denied state | Customer fixture package absent | Domain and auth evidence | Screenshot set absent | preserve | Not release-ready |
| SF-011 | Wishlist, compare, reviews, tags, newsletter, downloadable products | Magento and Laravel | Customer | Customer commerce fixture package absent | Domain service evidence | Screenshot set absent | preserve | Not release-ready |
| SF-012 | Website/store switch, locale, currency, scoped content | Magento and Laravel | Guest, customer, store scope | Multistore fixture package absent | Config evidence | Screenshot set absent | preserve | Not release-ready |
| SF-013 | Contact, send friend, product alerts, newsletter, email states | Magento and Laravel | Guest, customer, validation states | Communication fixture package absent | Domain and commerce evidence | Screenshot set absent | preserve | Not release-ready |
| SF-014 | URL rewrites, canonical output, HTML sitemap, XML sitemap, RSS | Magento and Laravel | Guest, store scope | SEO fixture package absent | Domain service evidence | Screenshot set absent | preserve | Not release-ready |
| SF-015 | External payment redirect, review, cancel, return callback | Magento and Laravel | Checkout customer | Payment sandbox fixture absent | Integration evidence | Screenshot set absent | bridge | Not release-ready |
| SF-016 | Polls, tags, analytics, Google Base, XmlConnect surfaces | Magento and Laravel | Guest, admin-enabled module states | Optional module fixture absent | Domain and integration evidence | Screenshot set absent | bridge | Not release-ready |
| AD-001 | Login, logout, reset password, dashboard, menu, notifications, denied page | Magento and Laravel | Admin, denied role, timeout state | Admin fixture package absent | Auth evidence | Screenshot set absent | preserve | Not release-ready |
| AD-002 | Product grid, filters, mass actions, product edit tabs | Magento and Laravel | Catalog admin, denied role | Product admin fixture package absent | Domain and commerce evidence | Screenshot set absent | preserve | Not release-ready |
| AD-003 | Category tree, edit form, product assignment, URL key and design settings | Magento and Laravel | Catalog admin, store scope | Category admin fixture package absent | Domain service evidence | Screenshot set absent | preserve | Not release-ready |
| AD-004 | Attribute grid, attribute sets, options, labels, validation | Magento and Laravel | Catalog admin, denied role | EAV admin fixture package absent | EAV evidence | Screenshot set absent | preserve | Not release-ready |
| AD-005 | Order grid, order view, comments, status, reorder, admin create | Magento and Laravel | Sales admin, denied role | Sales order fixture package absent | Commerce and report evidence | Screenshot set absent | preserve | Not release-ready |
| AD-006 | Invoice, shipment, credit memo, refund, tracking, PDFs | Magento and Laravel | Sales admin, denied role | Fulfillment fixture package absent | Commerce and report evidence | Screenshot set absent | preserve | Not release-ready |
| AD-007 | Customer grid, edit, addresses, groups, carts, wishlists | Magento and Laravel | Customer service admin, denied role | Customer admin fixture package absent | Domain service evidence | Screenshot set absent | preserve | Not release-ready |
| AD-008 | Catalog rules, cart rules, coupons, conditions, actions, reports | Magento and Laravel | Marketing admin, denied role | Promotion fixture package absent | Commerce and report evidence | Screenshot set absent | preserve | Not release-ready |
| AD-009 | CMS pages, blocks, widgets, design assignments, media browser | Magento and Laravel | CMS admin, store scope | CMS admin fixture package absent | Domain service evidence | Screenshot set absent | preserve | Not release-ready |
| AD-010 | System config scopes, inherited values, secrets, validation | Magento and Laravel | Full admin, scoped config state | Config fixture package absent | Config evidence | Screenshot set absent | preserve | Not release-ready |
| AD-011 | Cache management, index management, compiler controls | Magento and Laravel | Full admin, stale state | Cache/index fixture package absent | Livewire and commerce labels | Screenshot set absent | preserve | Not release-ready |
| AD-012 | Admin users, roles, ACL, API users, OAuth consumers | Magento and Laravel | Full admin, denied role | Permission fixture package absent | API and auth evidence | Screenshot set absent | preserve | Not release-ready |
| AD-013 | Import, export, dataflow, validation, batch execution, error files | Magento and Laravel | Operations admin | Import/export fixture package absent | Domain service evidence | Screenshot set absent | preserve | Not release-ready |
| AD-014 | Report grids, filters, exports, refresh statistics | Magento and Laravel | Report admin, denied role | Report fixture package absent | Report evidence | Screenshot set absent | preserve | Not release-ready |
| AD-015 | Newsletter templates, queues, subscribers, polls | Magento and Laravel | Marketing admin | Newsletter fixture package absent | Domain service evidence | Screenshot set absent | preserve | Not release-ready |
| AD-016 | Tax classes, rates, rules, currency rates, symbols | Magento and Laravel | Finance admin, store scope | Tax/currency fixture package absent | Commerce and config evidence | Screenshot set absent | preserve | Not release-ready |
| AD-017 | Stores, backups, system info, email templates, URL rewrites, sitemaps | Magento and Laravel | Operations admin | Store operation fixture package absent | Domain service evidence | Screenshot set absent | preserve | Not release-ready |
| AD-018 | Payment and shipping settings, Google integrations, mobile app admin | Magento and Laravel | Integration admin, sandbox state | Integration fixture package absent | Integration evidence | Screenshot set absent | bridge | Not release-ready |

## Acceptance Criteria

- `specs/modernization/magento-feature-catalog.md` maps every feature to one or more screen IDs or explicitly marks it non-UI.
- Each screen has a Magento screenshot before Laravel implementation starts.
- Each migrated screen has matching Magento and Laravel screenshots at required viewports and states.
- Visual diff thresholds and override rules are defined in `specs/modernization/visual-tolerances.md`.
- A screen cannot be marked done until screenshot evidence, E2E coverage, accessibility checks, and manual acceptance are recorded.
