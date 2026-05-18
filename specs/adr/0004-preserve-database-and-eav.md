# ADR-0004: Preserve Existing Database Schema And EAV

## Status

Proposed

## Context

The modernization must keep the existing Magento database schema, including EAV and original seed/sample data.

## Decision

Use the existing database as the source of truth. Do not require destructive schema migration for modernization. Implement dedicated EAV repositories/query services.

## Consequences

- Existing data remains usable.
- EAV complexity remains explicit and must be tested.
- Eloquent is not a blanket replacement for Magento resource models.

## Verification

- Schema checksum before/after boot.
- EAV parity tests.
- Fixture restore tests.

