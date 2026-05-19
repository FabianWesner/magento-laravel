# Overall Modernization Tasklist

This is the working tasklist for day-to-day execution. `specs/GOAL.md` remains the source of truth, `specs/modernization/backlog.md` remains the canonical migration backlog, and `specs/open-issues.md` tracks active blockers and defects.

## Maintenance Rules

- Update this file at the end of each verified implementation slice.
- Keep implementation tasks ahead of documentation tasks unless documentation is explicitly requested.
- Do not mark a task `Done` unless the code, tests, browser verification, progress entry, and commit/evidence are complete.
- Keep release readiness separate from local workbench readiness.

## Current Focus

| Status | Task | Why It Matters | Verification |
| --- | --- | --- | --- |
| Review | Storefront CMS/SEO content workbench | Provides a safe, read-only surface for CMS pages, blocks, widgets, redirects, sitemap/RSS, URL rewrites, and store-scope behavior before replacing legacy Magento routing/content behavior. | Focused PHPUnit, Pint, Chrome desktop/mobile, normal gate passed; commit pending. |
| In progress | End-of-slice documentation and commit | Records what changed, why it changed, and what remains blocked after the implementation has been verified. | Markdown check and normal gate passed; git commit pending. |

## Recently Completed Local Slices

| Date | Slice | Local Evidence | Release Status |
| --- | --- | --- | --- |
| 2026-05-19 | Customer commerce workbench | `/_modernization/customer/commerce`, focused PHPUnit, Chrome desktop/mobile. | Not release-ready. |
| 2026-05-19 | Storefront search workbench | `/_modernization/storefront/search`, focused PHPUnit, Chrome desktop/mobile. | Not release-ready. |
| 2026-05-19 | Product detail workbench | `/_modernization/storefront/product-detail`, focused PHPUnit, Chrome desktop/mobile. | Not release-ready. |
| 2026-05-19 | Customer account workbench | `/_modernization/customer/account`, focused PHPUnit, Chrome desktop/mobile. | Not release-ready. |
| 2026-05-19 | Storefront catalog workbench | `/_modernization/storefront/catalog`, focused PHPUnit, Chrome desktop/mobile. | Not release-ready. |

## Near-Term Implementation Queue

| Priority | Task | Dependencies | Notes |
| --- | --- | --- | --- |
| P0 | Finish and commit the CMS/SEO workbench slice. | Current focused tests and Chrome checks. | Documentation is last: `specs/track.md`, `specs/progress.md`, this tasklist, and open issues. |
| P1 | Pick the next low-risk, browser-verifiable storefront/admin slice. | Existing domain facts and routes. | Prefer slices that can be verified in Chrome without touching checkout, payment, order writes, or destructive schema changes. |
| P1 | Expand deterministic domain facts where the next slice needs them. | Domain catalog and legacy behavior scan. | Keep fixture work isolated and test-backed. |
| P1 | Continue non-scripted Chrome checks at each working increment. | Herd URL and seeded data. | Use desktop and mobile viewports; record console state and screenshots. |

## Release-Critical Work Not Yet Started

| Task | Blocking Source |
| --- | --- |
| Real project overlay and module inventory. | `specs/modernization/defect-register.md` DEF-001. |
| Sanitized DB/media fixture manifest and restore evidence. | DEF-002. |
| Full Magento/Laravel screenshot manifest for required screens, roles, states, and viewports. | DEF-003. |
| Complex behavior reverse-engineering and retained parity artifacts. | DEF-004. |
| ADR 0008 approval for route fallback/auth boundary. | DEF-005. |
| Security, accessibility, performance, hosted CI, manual acceptance, cutover, production readiness, completion audit, and spec-currency evidence. | DEF-006 through DEF-010. |
