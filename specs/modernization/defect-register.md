# Modernization Defect Register

This register tracks release-blocking defects and accepted residual defects for the Magento-to-Laravel modernization. It follows the severity policy in `specs/modernization/test-plan.md`.

The current register is not release acceptance. Open P0 and P1 rows block final cutover until the listed evidence exists and the row is closed.

## Final Gate Snapshot

Command run on 2026-05-19 10:07 CEST:

```bash
env PATH=/private/tmp/magento-lts-php85-bin:$PATH MODERNIZATION_FINAL=1 bash dev/modernization/gate.sh
```

The final gate failed, as expected for the current non-release state. The run confirmed that several implementation and documentation checks pass, while release evidence, approvals, project fixture data, and browser smoke evidence remain open.

| Gate Area | Result | Tracked By | Current Evidence State |
| --- | --- | --- | --- |
| Documentation content | Pass | DEF-010 | Docusaurus and MkDocs content checks pass, but final spec-currency and completion audit evidence are absent. |
| Feature traceability | Pass | DEF-004, DEF-009 | All 79 feature IDs are traceable, but rows still point to non-release-ready evidence states. |
| Source/dependency, runtime tooling, Boost MCP | Pass | DEF-008 | Local source, dependency, runtime, and Boost checks pass; hosted CI evidence is absent. |
| Laravel implementation target checks | Pass | DEF-004, DEF-005 | Bootstrap, EAV, module, cron/job, API, commerce, report, domain, integration, and config checks pass. |
| Operations runbook | Pass | DEF-009 | Runbook content passes; rehearsal, cutover, support, production, and manual acceptance evidence are absent. |
| No-new-XML and removed technology | Pass | DEF-004 | Static migration policy checks pass for the Laravel target. |
| UI screenshot inventory | Fail | DEF-003 | Screenshot manifest and retained Magento/Laravel screenshot artifacts are absent. |
| Fixture/media and DB-backed reports | Fail | DEF-002 | Fixture manifest, restore evidence, `DB_DSN`, fixture coverage report, and schema report are absent from the final run. |
| Magento baseline and docroot | Fail | DEF-001 | `project/` remains placeholder-only. |
| Complex, edge, resilience, and parity evidence | Fail | DEF-004 | Approved complex reverse-engineering evidence and retained dual-runtime artifacts are absent. |
| Route fallback and auth/security | Fail | DEF-005 | ADR 0008 is not Approved or Accepted, and route/auth evidence files are absent. |
| Security and accessibility | Fail | DEF-006 | Security review and accessibility report are absent. |
| Performance budgets | Fail | DEF-007 | Approved numeric budget manifest and retained measurements are absent. |
| Manual acceptance, support, cutover, production readiness | Fail | DEF-009 | Acceptance, support, cutover, and production readiness evidence are absent. |
| Release checklist and defect closure | Fail | DEF-001 through DEF-010 | Release checklist remains unchecked and every P0/P1 defect in this register is still open. |
| Docusaurus browser smoke | Fail in sandbox final mode | DEF-010 | Build passes; browser smoke cannot bind `127.0.0.1:3012` in the sandbox and requires the escalated smoke command for evidence. |

After this snapshot, an escalated Playwright/Chrome smoke run wrote `specs/modernization/docusaurus-browser-smoke-evidence.md`. The final gate now validates that retained evidence, including its Docusaurus source hash, when live browser smoke is unavailable in the restricted sandbox. That evidence proves the Docusaurus static site rendered the home, user, and developer docs locally, but it does not close the broader spec-currency, completion audit, CI, fixture, visual, security, accessibility, cutover, or release checklist defects.

| Defect ID | Severity | Status | Owner | Feature IDs | Evidence | Acceptance | Accepted By | Accepted At | Resolution | Workaround |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| DEF-001 | P0 | Open | modernization | ALL | `project/` is placeholder-only; final gate reports project overlay is not present. | Release is blocked; no acceptance granted. | None | None | Add real project overlay or approved source evidence, then rerun Magento docroot verification. | Keep Magento baseline source-only and do not cut over project routes. |
| DEF-002 | P0 | Open | modernization | ALL | Final gate reports no fixture manifest, fixture restore evidence, `DB_DSN`, fixture coverage report, or schema report. Retained sample-only signals exist at `specs/modernization/sample-fixture-coverage-evidence.md`, `specs/modernization/sample-schema-report-evidence.md`, and `specs/modernization/schema-preservation-evidence.md`, but they are not canonical project fixture evidence. | Release is blocked; no acceptance granted. | None | None | Add restorable sanitized DB and media fixtures, restore evidence, and DB-backed reports. | Use sample data only for local smoke checks; do not claim canonical fixture readiness. |
| DEF-003 | P1 | Open | modernization | SF-001 through SF-016, AD-001 through AD-018 | Final gate reports no screenshot manifest with Magento and Laravel artifacts. Local sample-only Magento home, category/listing, admin login, and admin dashboard smokes exist at `specs/modernization/magento-home-visual-smoke-evidence.md`, `specs/modernization/magento-category-visual-smoke-evidence.md`, `specs/modernization/magento-admin-login-visual-smoke-evidence.md`, and `specs/modernization/magento-admin-dashboard-visual-smoke-evidence.md`; scoped Livewire foundation evidence exists at `specs/modernization/livewire-ui-evidence.md`. These are not the final screenshot manifest. | Release is blocked; no acceptance granted. | None | None | Capture Magento and Laravel screenshots for all required roles, states, and viewports. | Keep UI rows marked `Not release-ready`. |
| DEF-004 | P0 | Open | modernization | CB-001 through CB-014, SF-007 through SF-009, AD-005 through AD-008, API-004 through API-005, CJ-001 through CJ-025 | Final gate reports missing complex reverse-engineering evidence and retained parity artifacts. | Release is blocked; no acceptance granted. | None | None | Add approved behavior specs, dual-runtime parity tests, DB deltas, API payloads, scheduler side effects, and retained reports. | Keep legacy Magento paths available for these behaviors. |
| DEF-005 | P1 | Open | modernization | AD-001, AD-012, SF-010, SF-007 through SF-009 | Final route fallback and auth/security gates report ADR 0008 is not approved and evidence files are absent. | Release is blocked; no acceptance granted. | None | None | Approve or revise ADR 0008, then add route fallback and auth/security evidence. | Do not move authenticated storefront, cart, checkout, API, or admin routes to final cutover. |
| DEF-006 | P1 | Open | modernization | ALL | Final gate reports security review and accessibility report evidence are absent. | Release is blocked; no acceptance granted. | None | None | Complete security review, accessibility audit, and retained reports. | Keep release checklist unchecked for security and accessibility. |
| DEF-007 | P1 | Open | modernization | ALL | Final performance gate reports no approved budget manifest with numeric targets and evidence. | Release is blocked; no acceptance granted. | None | None | Record approved performance budgets and attach baseline and Laravel measurements. | Keep performance-sensitive features behind legacy or local-only routes. |
| DEF-008 | P1 | Open | modernization | ALL | Final CI gate reports hosted CI evidence file is absent. | Release is blocked; no acceptance granted. | None | None | Run `.github/workflows/modernization.yml` in hosted CI and retain run URL, commit, logs, and artifacts. | Use local normal gate results for development only. |
| DEF-009 | P1 | Open | modernization | ALL | Final gate reports manual acceptance, support readiness, cutover readiness, production readiness, and release checklist approvals are absent. | Release is blocked; no acceptance granted. | None | None | Complete manual acceptance, support plan, cutover rehearsal, production readiness review, and release checklist sign-off. | Keep final release and route cutover disabled. |
| DEF-010 | P1 | Open | modernization | ALL | Final gate reports completion audit and spec-currency evidence files are absent. | Release is blocked; no acceptance granted. | None | None | Complete spec-currency approval and final prompt-to-artifact completion audit after all evidence is present. | Use progress log and individual validators for interim tracking. |
