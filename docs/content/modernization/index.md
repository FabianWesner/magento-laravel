---
tags:
- Development
---

# Laravel Modernization

This section explains the preparation package for modernizing a Magento 1 codebase into a Laravel-based architecture.

The target architecture keeps the existing Magento database schema, including EAV, while replacing the framework runtime with Laravel, rebuilding interactive UI with Livewire, preserving storefront and admin look and feel, and replacing XML-based extensibility with PHP-based modules, manifests, service providers, events, policies, and configuration.

## Current Status

This checkout began as the OpenMage core/source repository, so the root-level inventory is still a core-only scan. The local legacy runtime baseline has been switched to Magento CE `1.9.4.5` from `OpenMage/magento-mirror` tag `1.9.4.5`, installed under ignored local workspace storage with sample data.

The root scan found no `app/code/local`, no project-specific code under `app/code/community`, and no committed live `app/etc/local.xml`. It does contain the expected OpenMage community module declarations for `Cm_RedisSession` and `MM_Ignition`; these are platform dependencies, not the missing project overlay. Project-specific inventory must still be run against the actual project overlay, database, media, integrations, and deployment configuration.

## Published Reference

- [Current Architecture](architecture.md): current OpenMage runtime and repository shape.
- [Technologies and Dependencies](technologies.md): current runtime, toolchain, and locked package versions.
- [Workflows](workflows.md): current local, CI, test, docs, and modernization tooling workflows.

## Execution Specs

Migration instructions, task lists, checklists, ADRs, and runbooks intentionally live outside the published MkDocs tree in `specs/`.

Primary execution files:

- `specs/modernization/preparation.md`
- `specs/modernization/inventory.md`
- `specs/modernization/compatibility-policy.md`
- `specs/modernization/feature-inventory.md`
- `specs/modernization/architecture-specs.md`
- `specs/modernization/backlog.md`
- `specs/modernization/risk-register.md`
- `specs/modernization/proof-of-concepts.md`
- `specs/modernization/visual-baseline.md`
- `specs/modernization/performance-budgets.md`
- `specs/modernization/data-fixtures.md`
- `specs/modernization/release-strategy.md`
- `specs/modernization/documentation-plan.md`
- `specs/modernization/roadmap.md`
- `specs/modernization/test-plan.md`
- `specs/modernization/install-verification.md`
- `specs/modernization/workspace-layout.md`

## First Execution Order

1. Complete project-specific inventory against the real project code and data.
2. Approve the compatibility policy.
3. Build the missing characterization tests and visual baselines.
4. Write and approve the architecture specs.
5. Run the proof-of-concept spikes.
6. Convert findings into the migration backlog.
7. Start phased implementation only after the completion gates are agreed.
