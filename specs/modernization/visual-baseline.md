---
tags:
- Development
---

# Visual Baseline

Visual parity is required for storefront and admin. Capture baselines before migration and compare every migrated screen against them.

The authoritative screen manifest is `specs/modernization/ui-screen-inventory.md`. This file defines the visual testing strategy; the screen inventory defines the exact URL, role, state, fixture, viewport, and artifact path list. Visual thresholds and override rules are defined in `specs/modernization/visual-tolerances.md`.

## Required Viewports

The source of truth is `specs/modernization/ui-screen-inventory.md`.

| Label | Size |
| --- | --- |
| Desktop | `1440x1000` |
| Laptop | `1280x900` |
| Tablet | `768x1024` |
| Mobile | `390x844` |

Adjust or add breakpoints if the production analytics show materially different device sizes.

## Storefront Screens

- Every visible `SF-` feature ID from `specs/modernization/magento-feature-catalog.md`.
- Every storefront row in `specs/modernization/ui-screen-inventory.md`.
- Every product type, promotion state, cart state, checkout state, account state, CMS state, and error state covered by the canonical demo fixture.

## Current Local Smoke Evidence

`specs/modernization/magento-home-visual-smoke-evidence.md` records a local Magento storefront home smoke capture for `SF-001` and `SF-002` against the sample data runtime at `http://127.0.0.1:8090/`. The screenshots are local artifacts under `.localdev/visual-baseline/magento/storefront/SF-HOME/home-default/` for desktop, laptop, tablet, and mobile viewports.

This is not the final screenshot manifest. It does not include Laravel comparison screenshots, all storefront/admin screens, required roles, all UI states, visual diff approval, accessibility review, or manual acceptance.

## Admin Screens

- Every visible `AD-` feature ID from `specs/modernization/magento-feature-catalog.md`.
- Every admin row in `specs/modernization/ui-screen-inventory.md`.
- Every admin role and permission state required by the canonical demo fixture.
- Every grid, form, modal, report, import/export, config scope, cache/index, sales document, and integration screen retained or bridged.

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
- The baseline set is captured from the Magento app and the comparison set is captured from the Laravel app using the same database and media fixture.
- Diffs are reviewed per migrated screen.
- Intentional differences are documented.
- Visual regression is part of the final release gate.
- No visible feature ID is marked complete without screenshot evidence, unless the feature has an approved non-UI classification.
