---
tags:
- Development
---

# Release Strategy

The modernization should ship through a controlled strangler release, not a single unbounded rewrite.

## Candidate Cutover Models

| Model | Use When | Risk |
| --- | --- | --- |
| Route-by-route strangler | Laravel and legacy can share sessions, scope, and database safely. | Lowest operational risk, more bridge complexity. |
| Admin-first | Admin replacement is lower customer-facing risk. | Admin permissions and grids must be solid. |
| Storefront-first | Storefront pages are mostly read-oriented. | Visual parity and checkout boundaries are sensitive. |
| API-first | API contracts are clear and clients are known. | Hidden client behavior can break. |
| Parallel Laravel app | New runtime can run beside legacy with routed traffic. | Requires strong data ownership and routing discipline. |
| Full cutover | Only after all domains pass gates. | Highest risk; should be avoided until proven safe. |

## Recommended Default

Use route-by-route strangler migration with feature flags and route ownership metadata.

Start with:

1. Low-risk admin utility screen.
2. Low-risk storefront CMS page.
3. Read-only catalog route.
4. API endpoint with strong contract coverage.
5. Cron/command replacement.

Defer checkout, payment, tax, and sales writes until late phases.

## Rollback Requirements

- Legacy route fallback remains available until the migrated route has passed staging and production observation.
- Database schema remains compatible with legacy runtime.
- Feature flags can move traffic back to legacy routes.
- Cache/session behavior supports rollback.
- Deployment rollback has been rehearsed.

## Release Gates

- Test plan release checklist complete.
- Performance budgets approved.
- Security review complete.
- Visual regression approved.
- Documentation and operator runbook complete.
- Rollback rehearsal complete.
- Open defect list accepted by severity policy.

