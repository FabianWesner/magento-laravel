# Modernization Overview

The project is moving from Magento CE `1.9.4.5` to a Laravel architecture while preserving the existing database schema, EAV model, storefront look and feel, admin look and feel, URLs, and business behavior.

The legacy Magento install is used as a baseline for characterization, visual comparison, and operational parity. New implementation work belongs in Laravel and Livewire, with PHP-based modules and typed configuration instead of XML-based extension points.

## Current Baseline

- Magento CE `1.9.4.5` is installed locally from source.
- Magento sample data `1.9.2.4` is loaded for baseline smoke testing.
- Storefront home, category, product, and admin dashboard have been verified in Chrome through Playwright.
- The legacy baseline runs in an isolated PHP `7.4` Docker runtime because Magento 1 is not compatible with current PHP.
- The Laravel target must run on the latest stable PHP branch, currently PHP `8.5.x`.

## Repository Layout

- `core/magento-1.9.4.5/` contains the Magento CE baseline.
- `project/` is reserved for the real project overlay.
- `.localdev/magento-docroot/` is generated from core plus project overlay for local verification.
- `laravel/` contains the target Laravel application.

## Target Outcomes

- Laravel replaces the Zend/Magento runtime.
- Livewire powers migrated storefront and admin interactions.
- Existing EAV and commerce tables remain the source of truth.
- New modules register through PHP manifests, service providers, routes, events, policies, and typed config.
- Migration proceeds route by route with fallback until parity is proven.

## Documentation Targets

- MkDocs provides concise public modernization notes.
- Docusaurus provides the full user documentation and developer documentation for the final system.
- Final documentation must cover production-ready behavior, including edge cases, failure paths, operational recovery, and known limitations.
