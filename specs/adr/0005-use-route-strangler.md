# ADR-0005: Use Route-By-Route Strangler Migration

## Status

Proposed

## Context

A full rewrite would make regression risk too high across catalog, checkout, sales, admin, APIs, and cron.

## Decision

Migrate route groups and workflows incrementally. Laravel owns selected migrated routes while legacy OpenMage handles unmigrated routes through fallback.

## Consequences

- Rollout risk is lower.
- Compatibility adapters and route ownership tracking are required.
- Sessions, store scope, URL rewrites, and errors must work across both runtimes.

## Verification

- Route compatibility tests.
- Feature flags.
- Legacy fallback smoke tests.

