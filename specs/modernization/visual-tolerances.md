---
tags:
- Development
---

# Visual Tolerances

This file defines the default visual-regression thresholds for Magento-to-Laravel UI parity. The screen manifest in `specs/modernization/ui-screen-inventory.md` defines which screens, roles, states, fixtures, and viewports must be captured.

## Default Thresholds

These defaults apply unless a row in the override manifest below explicitly says otherwise.

| Area | Default Threshold | Blocks Release When |
| --- | --- | --- |
| Full-page pixel diff | `<= 0.10%` changed pixels per viewport after approved masks. | Any critical screen exceeds the threshold without signed approval. |
| Critical commerce regions | `<= 0.02%` changed pixels for price, totals, cart, checkout, order, invoice, shipment, refund, tax, and payment regions. | Money, quantity, status, or action regions differ unexpectedly. |
| Admin data grids/forms | `<= 0.05%` changed pixels for grid chrome, filters, action bars, validation, and form fields. | A user could misread data, miss an action, or lose productivity. |
| Text overflow/overlap | `0` tolerated occurrences. | Text is clipped, overlaps, or escapes its container. |
| Layout shift | `CLS <= 0.02` for migrated pages during normal load. | A critical action, message, price, or form control shifts after initial render. |
| Accessibility-affecting visual changes | `0` unapproved contrast, focus, label, or state regressions. | Contrast or focus indicator falls below WCAG target in the test plan. |

## Allowed Masks

Only these dynamic regions may be masked:

- Timestamps.
- CSRF/form keys.
- Captcha images.
- Randomized recommendation order where Magento itself is non-deterministic.
- Third-party iframe content where a deterministic mock is not available.

Every mask must list the screen ID, selector/region, reason, owner, and expiry phase. Masking product prices, totals, tax, payment state, order state, admin permissions, validation messages, or destructive actions is not allowed.

## Override Manifest

Overrides are exceptions, not defaults. They require product/design approval and must expire by a named migration phase.

| Screen ID | Viewport | State | Region | Override | Approver | Rationale | Expiry |
| --- | --- | --- | --- | --- | --- | --- | --- |
| TBD | TBD | TBD | TBD | TBD | TBD | TBD | TBD |

## Approval Rules

- A visual diff can pass only when automated thresholds pass or an override row is approved.
- All critical storefront and admin screens require manual review of baseline and Laravel screenshots before release.
- Visual approval cannot override functional, security, accessibility, performance, pricing, tax, payment, order, or data side-effect failures.
- The final release evidence must include screenshot artifact paths, diff reports, masks, overrides, approvers, and dates.
