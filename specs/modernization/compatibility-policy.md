---
tags:
- Development
---

# Compatibility Policy

The Laravel modernization keeps the database stable but must explicitly decide which legacy runtime contracts remain compatible.

## Default Policy

Unless a decision record says otherwise:

- Existing database schema and data remain compatible.
- Existing storefront and admin user-visible behavior remains compatible.
- Existing public URLs remain compatible.
- Existing critical API behavior remains compatible.
- Existing business outcomes remain compatible.
- New architecture does not use XML for registration/configuration.
- Legacy XML can be read by compatibility tooling during transition, but new modules must not depend on XML.

## Compatibility Matrix

| Contract | Default Decision | Verification |
| --- | --- | --- |
| Database schema | Preserve | Schema checksum, fixture import, no destructive migration. |
| EAV model | Preserve | EAV parity tests for products, categories, customers, and addresses. |
| Storefront look and feel | Preserve | Visual regression tests and manual review. |
| Admin look and feel | Preserve | Visual regression tests and admin acceptance review. |
| Storefront URLs | Preserve | URL rewrite and E2E route tests. |
| Admin frontname/URLs | Preserve unless changed by deployment decision | Admin route tests. |
| Existing API clients | Preserve for approved endpoints | API contract tests. |
| Existing modules | Case-by-case | Module inventory decision. |
| Existing theme XML | Compatibility only during transition | Static check blocks new XML usage. |
| Existing `Mage::` static API | Compatibility bridge only | Static checks block direct calls in migrated code. |
| Existing cron behavior | Preserve behavior, replace implementation | Scheduler parity tests. |
| Existing setup scripts | Do not run destructive schema changes in Laravel modernization | Fixture DB and schema policy review. |

## Decision States

Use these states for each compatibility decision:

- `preserve`: must remain compatible.
- `bridge`: legacy behavior remains through a compatibility adapter during migration.
- `replace`: behavior remains, implementation changes.
- `retire`: no compatibility required after documented approval.
- `unknown`: inventory incomplete.

## Required Decision Records

Before implementation starts, create decision records for:

- Existing extension/module compatibility.
- Legacy XML compatibility duration.
- Admin URL compatibility.
- API endpoint compatibility.
- Payment/shipping/tax integration behavior.
- Checkout and order lifecycle compatibility.
- Theme/layout compatibility scope.
- Supported PHP, MySQL, Redis, and browser versions.

## Acceptance Criteria

- No phase starts with `unknown` compatibility decisions in its scope.
- Every `retire` decision has explicit approval and user-facing documentation.
- Every `bridge` decision has an exit criterion.
- Every preserved contract has automated verification in the test plan.

