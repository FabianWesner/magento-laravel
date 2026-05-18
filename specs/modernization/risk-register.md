---
tags:
- Development
---

# Risk Register

This register tracks modernization risk. Owners and dates must be filled in once the project team is assigned.

| Risk | Severity | Status | Mitigation | Fallback |
| --- | --- | --- | --- | --- |
| EAV behavior mismatch | P0 | Open | Dedicated EAV service, parity tests, query snapshots. | Keep legacy resource models behind bridge. |
| Checkout regression | P0 | Open | Migrate late, characterize every step, sandbox payment tests. | Keep legacy checkout until parity is proven. |
| Sales/order lifecycle corruption | P0 | Open | Transaction policy, fixture replay, payment/refund tests. | Keep legacy sales writes. |
| Admin permission bypass | P0 | Open | Permission manifest, gates/policies, admin route tests. | Keep legacy ACL for unmigrated admin. |
| Existing extension incompatibility | P1 | Open | Module inventory, compatibility policy, bridge layer. | Retain legacy runtime for specific modules. |
| Visual parity drift | P1 | Open | Screenshot baselines, visual regression, manual review. | Keep legacy templates until replacement is approved. |
| Performance regression from Eloquent/EAV | P1 | Open | Query builders, query-count tests, profiling. | Use optimized SQL/repositories instead of Eloquent. |
| API client breakage | P1 | Open | Contract tests and endpoint inventory. | Route legacy APIs through compatibility layer. |
| Cron/job duplication | P1 | Open | Scheduler lock tests, idempotency review. | Keep legacy cron dispatcher. |
| Data fixture gaps | P1 | Open | Fixture strategy and sanitized production-like data. | Block migration of unrepresented domains. |
| No-XML rule bypass | P2 | Open | Static checks and module review. | Reject PRs adding new XML registration. |
| Tooling split between legacy and Laravel | P2 | Open | Modernization all-gates workflow. | Keep separate required workflows until unified. |
| Documentation drift | P2 | Open | Docs ownership and docs build in CI. | Block release on missing docs. |

## Risk Review Rules

- P0 and P1 risks require an owner before related implementation starts.
- P0 and P1 risks require automated verification before release.
- Every accepted risk needs a written decision.
- Every compatibility bridge needs a removal trigger.

