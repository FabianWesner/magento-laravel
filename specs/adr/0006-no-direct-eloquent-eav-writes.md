# ADR-0006: Do Not Use Direct Eloquent Writes For EAV Entities

## Status

Proposed

## Context

Magento EAV writes involve entity metadata, backend tables, store scope, options, validation, indexes, and side effects. Direct Eloquent models can hide or bypass those semantics.

## Decision

Do not write product, category, customer, or address EAV data directly through generic Eloquent models. Use dedicated repositories/services with parity tests.

## Consequences

- EAV write behavior remains explicit.
- Repositories can coordinate transactions, validation, events, and indexes.
- Flat/reference tables may still use Eloquent where approved.

## Verification

- Static/code review policy.
- EAV write integration tests.
- Legacy parity tests.

