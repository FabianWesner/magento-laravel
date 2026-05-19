---
id: module-development
title: Module Development
---

Modules are PHP-first. They must not introduce Magento XML registration, routing, layout, ACL, event, API, or config files.

## Required Module Shape

- Module manifest with name, version, dependencies, providers, routes, commands, views, permissions, config, events, and jobs.
- Service provider for bindings and boot hooks.
- Routes for storefront, admin, API, and fallback behavior.
- Policies for permissions.
- Livewire components for interactive UI.
- Tests covering happy paths, edge cases, permissions, validation, failures, and side effects.

## Module Acceptance

A module reaches release readiness with feature IDs, fixture coverage, characterization tests, Laravel tests, visual evidence if visible, security review, performance evidence where applicable, and user/developer documentation.
