---
id: architecture
title: Architecture
---

The target runtime is Laravel, not Magento or Zend. Magento CE `1.9.4.5` remains only as the side-by-side characterization baseline until final cutover.

## Target Architecture

- Laravel HTTP kernel, routes, middleware, service container, scheduler, queues, and config.
- Livewire and Blade for storefront and admin screens.
- PHP module manifests and service providers for extension.
- Policies for permissions.
- Events and listeners for domain extension points.
- EAV repositories/services for product, category, customer, and address data.
- Eloquent only for approved flat tables and infrastructure tables.

## Removed Technologies

The removed-technology policy lives in `specs/modernization/technology-removal-policy.md`. New Laravel runtime code must not depend on Magento/Zend/Varien/XML/layout/block/resource-model/Prototype-era systems.
