---
tags:
- Development
---

# Data Fixtures

The database schema stays, including EAV. Fixtures are therefore the safety net for proving Laravel behavior against real Magento data structures.

## Required Fixture Sets

| Fixture | Purpose |
| --- | --- |
| Minimal install | Fast smoke tests and local setup. |
| Original seed/sample data | Proves compatibility with existing seed/sample data. |
| Sanitized project data | Proves real-world behavior without sensitive data. |
| EAV edge data | Covers backend types, scoped values, options, multiselects, required attributes. |
| Multistore data | Covers websites, store groups, store views, currencies, locales, scoped config. |
| Sales lifecycle data | Covers quotes, orders, invoices, shipments, credit memos, refunds. |
| Admin permissions data | Covers full, partial, and denied roles. |
| Integration data | Covers API users, sandbox providers, external IDs. |
| Scale data | Covers large catalogs, attributes, customers, orders, and admin grids. |

## Rules

- Do not destructively migrate the commerce schema.
- Any new infrastructure table requires explicit approval.
- Fixtures must be reproducible.
- Production-derived data must be sanitized.
- Media fixtures must match database references.
- Fixture restore must be documented and automated where possible.

## Acceptance Criteria

- A developer can restore the fixture database and media locally.
- CI can restore the required fixtures.
- EAV parity tests use fixture data.
- Visual tests use stable media fixtures.
- Performance tests have enough volume to expose query problems.

