# ADR 0007: Target Latest Stable PHP For Laravel

## Status

Accepted

## Context

The legacy Magento CE `1.9.4.5` runtime is used only as a baseline for characterization and visual comparison. It requires an isolated legacy PHP runtime for local smoke verification.

The modernization target is a new Laravel application, so it should not inherit legacy PHP constraints.

## Decision

All Laravel target code must run on the latest stable PHP release available at implementation time. As of 2026-05-18, PHP `8.5.x` is the latest stable branch.

Magento CE `1.9.4.5` may run on PHP `7.4` only inside isolated local verification tooling. PHP `7.4` must not be used for new Laravel code, CI target jobs, or production target deployments.

## Consequences

- Composer platform configuration and CI must enforce the selected latest PHP branch.
- Dependency selection must prefer packages compatible with the selected PHP branch.
- Laravel Boost must install cleanly as a dev dependency in the Laravel workspace.
- The PHP branch must be revisited before each major migration phase and before final cutover.
