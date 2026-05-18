---
tags:
- Development
---

# Migration Backlog

This backlog converts preparation work into executable tasks. Estimates and owners must be filled in once the actual project inventory is complete.

## Backlog Fields

Each task must include:

- Phase.
- Domain.
- Description.
- Dependencies.
- Risk.
- Acceptance criteria.
- Verification command or evidence.
- Owner.
- Status.

## Phase 0: Inventory And Baseline

| Task | Dependencies | Acceptance Criteria | Verification |
| --- | --- | --- | --- |
| Add real project code locally | Repository access | Project overlay and Magento CE `1.9.4.5` source are available in one workspace. | `git status`, module inventory. |
| Define latest PHP target | PHP runtime decision | Laravel target runtime uses the latest stable PHP, currently PHP `8.5.x` as of 2026-05-18; legacy Magento PHP `7.4` is isolated to smoke tests. | `php -v`, Composer platform check, CI matrix. |
| Verify Laravel Boost installability | Latest PHP target | `laravel/boost` installs as a dev dependency in the target Laravel workspace without Composer conflicts. | `composer require laravel/boost --dev`. |
| Capture database and media fixtures | Data access | Sanitized DB/media fixtures are reproducible. | Restore fixture locally. |
| Inventory custom modules | Project code | Every module has owner, purpose, risk, migration decision. | Inventory report reviewed. |
| Inventory integrations | Business/ops input | Every integration has sandbox/mock plan. | Integration matrix reviewed. |
| Capture visual baseline | Running current app | Required storefront/admin screenshots stored. | Visual baseline report. |
| Capture performance baseline | Running current app | Critical journeys have p50/p95/query/memory metrics. | Performance report. |

## Phase 1: Test Completion

| Task | Dependencies | Acceptance Criteria | Verification |
| --- | --- | --- | --- |
| Fill storefront E2E gaps | Feature inventory | Critical storefront journeys covered. | Cypress/Playwright green. |
| Fill admin E2E gaps | Feature inventory | Critical admin journeys covered. | Cypress/Playwright green. |
| Add EAV parity tests | Fixture DB | Product/category/customer EAV behavior locked. | PHPUnit green. |
| Add API contract tests | API inventory | Required legacy endpoints covered. | API tests green. |
| Add no-new-XML static check | Architecture rules | CI fails on new XML registration. | Static check green. |

## Phase 2: Specification

| Task | Dependencies | Acceptance Criteria | Verification |
| --- | --- | --- | --- |
| Approve compatibility policy | Inventory | No unknown decisions for Phase 3 scope. | Policy sign-off. |
| Write Laravel bootstrap spec | Policy | Runtime and fallback defined. | Spec review. |
| Write module system spec | Policy | No-XML extension points defined. | Spec review. |
| Write EAV spec | Inventory/tests | Read/write and scope rules defined. | Spec review. |
| Write UI specs | Visual baseline | Storefront/admin Livewire plans defined. | Spec review. |

## Phase 3: POCs

| Task | Dependencies | Acceptance Criteria | Verification |
| --- | --- | --- | --- |
| Laravel beside Magento | Bootstrap spec | Laravel container boots without behavior change. | Smoke test. |
| Read-only EAV repository | EAV spec | Fixture product/category reads match legacy. | Parity tests. |
| No-XML sample module | Module spec | Module registers services/routes/views/events without XML. | Sample module tests. |
| Livewire admin grid | Admin UI spec | Grid matches admin look and behavior. | Visual/E2E test. |
| Livewire storefront page | Storefront UI spec | Page matches storefront baseline. | Visual/E2E test. |
| Route fallback | Routing spec | Laravel and legacy routes coexist. | Route tests. |

## Phase 4 And Beyond

Use the roadmap phases for full implementation. Do not start high-risk domains such as checkout or sales until:

- POCs pass.
- Feature inventory is complete.
- Test plan gates are implemented in CI.
- Risk register has owners and mitigations.
