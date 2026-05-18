# ADR-0002: Use Livewire For Storefront And Admin UI

## Status

Proposed

## Context

The target keeps storefront and admin look and feel while replacing Magento layout XML, blocks, and templates.

## Decision

Use Blade and Livewire for migrated storefront and admin UI surfaces.

## Consequences

- Interactive UI can be implemented in PHP-first Laravel components.
- Visual parity must be enforced with screenshot baselines.
- Business rules must remain in services, not Livewire components.

## Verification

- Livewire component tests.
- Browser E2E tests.
- Visual regression tests.

