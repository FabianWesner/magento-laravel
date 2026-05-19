# Open Issues Backlog

This file is the human-readable open-issues backlog for ongoing execution. The canonical release-blocking defect register remains `specs/modernization/defect-register.md`; this file summarizes active blockers and working issues so they are easy to review.

Last reviewed: 2026-05-19 18:23 CEST after SF-007 storefront cart diagnostics Chrome verification and the normal gate. No release-blocking issue was closed by this local workbench slice.

## Maintenance Rules

- Update this file at the end of each verified implementation slice.
- Link release-blocking P0/P1 defects back to `specs/modernization/defect-register.md`.
- Keep local workflow issues separate from final release defects.
- Do not close an issue here unless the evidence is linked in `specs/progress.md`.

## Release-Blocking Issues

| Issue | Severity | Status | Canonical Reference | Current Handling |
| --- | --- | --- | --- | --- |
| Project overlay is placeholder-only. | P0 | Open | DEF-001 | Keep Laravel workbenches local-only and retain Magento baseline. |
| Canonical sanitized DB/media fixtures and restore evidence are absent. | P0 | Open | DEF-002 | Use deterministic local `domain_facts` only for scoped workbench verification. |
| Final Magento/Laravel screenshot manifest is absent. | P1 | Open | DEF-003 | Capture local scoped screenshots for each workbench, but do not claim release readiness. |
| Complex behavior reverse-engineering evidence is incomplete. | P0 | Open | DEF-004 | Use legacy scans and subagents before each replacement slice. |
| Route fallback/auth boundary ADR remains proposed. | P1 | Open | DEF-005 | Avoid final route cutover for authenticated storefront, cart, checkout, API, or admin flows. |
| Security/accessibility, performance, hosted CI, manual acceptance, cutover, production readiness, completion audit, and spec-currency evidence remain incomplete. | P1 | Open | DEF-006 through DEF-010 | Keep release checklist unchecked. |

## Local Workflow Issues

| Issue | Status | Impact | Current Handling |
| --- | --- | --- | --- |
| Laravel documentation lookup cannot complete from this environment. Earlier Boost attempts could not resolve `boost.laravel.com`; the current local docs command tries to open a remote Laravel docs URL from the sandbox. | Open | Required docs lookup cannot complete before code changes. | Record the failure per slice; rely on existing app conventions and local rules without bypassing sandbox or approval restrictions. |
| Boost `get-absolute-url` returns `http://localhost/...`, but the working Herd host is `http://magento-lts.test/...`. | Open | Browser verification must use the reachable Herd URL. | Use Boost result as a signal, then verify with the working Herd URL and record the difference. |
| Boost browser logs currently report no browser log file. | Open | Browser console validation relies on Playwright console output for this slice. | Use Playwright console checks and note the missing Boost browser log file. |
| Normal gate skips DB fixture coverage/schema because `DB_DSN` is unset. | Open | Full fixture/schema evidence remains unavailable. | Treat normal gate as local development evidence only. |
| Docusaurus browser smoke is skipped by sandbox bind restrictions. | Open | Full docs browser evidence remains incomplete in this environment. | Keep retained build/static evidence separate from final release evidence. |

## Recently Mitigated Local Issues

| Issue | Date | Evidence |
| --- | --- | --- |
| Admin newsletter/polls browser verification initially showed `0 polls` and `0 answers` because the live Herd database had not been reseeded after adding poll facts, and the seeder cleanup list omitted poll entity ids. | 2026-05-19 | Added poll entity ids to `DomainFactSeeder` cleanup, ran the deterministic seeder locally, and verified `4 polls`, `8 answers`, and `4 problems` in Chrome. |
| Unsandboxed integration adapters could be labeled healthy in local diagnostics. | 2026-05-19 | Fixed `IntegrationDiagnosticsCatalog` so missing sandbox metadata becomes `mock-required` and attention-worthy; covered by focused PHPUnit. |
| Browser favicon 404 polluted current Chrome console checks. | 2026-05-19 | Added a lightweight `/favicon.ico` 204 route and rechecked Playwright console output with no current warnings/errors. |
