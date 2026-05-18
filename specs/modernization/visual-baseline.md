---
tags:
- Development
---

# Visual Baseline

Visual parity is required for storefront and admin. Capture baselines before migration and compare every migrated screen against them.

## Required Breakpoints

- Desktop: 1440px wide.
- Laptop: 1280px wide.
- Tablet: 768px wide.
- Mobile: 390px wide.

Adjust or add breakpoints if the production analytics show materially different device sizes.

## Storefront Screens

- Home page.
- Category page with products.
- Empty category.
- Product page for each product type in use.
- Search results and empty search.
- Cart.
- Checkout steps.
- Customer login/register/account.
- CMS page.
- 404/no-route.
- Store switcher or multistore pages if used.

## Admin Screens

- Login.
- Dashboard.
- Product grid and product edit.
- Category tree and category edit.
- Customer grid and customer edit.
- Order grid and order view.
- Invoice, shipment, and credit memo screens.
- System configuration.
- Cache management.
- Index management.
- Admin user and role screens.

## Required States

- Default.
- Loading.
- Success message.
- Error message.
- Validation error.
- Empty state.
- Permission denied.
- Modal/dialog open.

## Acceptance Criteria

- Baselines are captured from the current app before migration.
- Diffs are reviewed per migrated screen.
- Intentional differences are documented.
- Visual regression is part of the final release gate.

