# ADR-0001: Use Laravel As The Target Runtime

## Status

Proposed

## Context

The current OpenMage runtime is built around Magento 1/Zend-era framework patterns, static access through `Mage`, XML configuration, legacy controllers, blocks, observers, and resource models.

## Decision

Use Laravel as the target application runtime for migrated routes, services, commands, events, scheduler, policies, and configuration.

## Consequences

- New code can use modern Laravel conventions, dependency injection, service providers, events, and testing tools.
- Legacy behavior must be bridged during migration.
- Route ownership must be explicit to avoid unclear behavior.

## Verification

- Laravel bootstrap smoke tests.
- Route ownership tests.
- Static checks preventing direct legacy API usage in migrated code.

