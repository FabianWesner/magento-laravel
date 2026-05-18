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

## Acceptance Criteria

- `specs/modernization/magento-feature-catalog.md` maps every feature to one or more screen IDs or explicitly marks it non-UI.
- Each screen has a Magento screenshot before Laravel implementation starts.
- Each migrated screen has matching Magento and Laravel screenshots at required viewports and states.
- Visual diff thresholds and override rules are defined in `specs/modernization/visual-tolerances.md`.
- A screen cannot be marked done until screenshot evidence, E2E coverage, accessibility checks, and manual acceptance are recorded.
