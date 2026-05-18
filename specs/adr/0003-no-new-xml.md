# ADR-0003: No XML For New Architecture

## Status

Proposed

## Context

Magento 1 uses XML for modules, config, routes, layout, events, ACL, and APIs. The modernization goal is a more explicit, testable, code-based extension system.

## Decision

Do not use XML for new Laravel architecture registration or configuration. Existing XML may be read by compatibility tooling during transition.

## Consequences

- New extension points are declared in PHP manifests, service providers, policies, events, routes, and typed config.
- Static checks must prevent new XML registration in migrated code.
- Legacy XML bridge duration must be tracked.

## Verification

- `dev/modernization/validate-no-new-xml.php`
- Code review.
- Module system tests.

