---
tags:
- Development
---

# Documentation Plan

The modernization requires documentation that explains the new architecture, the reasoning behind it, and a full feature guide.

Two documentation channels are required:

- MkDocs under `docs/content/modernization` for concise public modernization notes.
- Docusaurus under `docusaurus/` for full user and developer documentation.

## Documentation Structure

| Section | Purpose |
| --- | --- |
| Overview | Explain modernization goals, scope, and status. |
| Architecture | Explain Laravel runtime, module system, EAV access, UI architecture, and compatibility bridge. |
| Reasoning | Explain why Laravel, Livewire, no XML, database preservation, and visual parity were chosen. |
| Feature guide | Explain storefront, admin, API, cron/jobs, configuration, and extension points. |
| Module guide | Teach developers how to build modules without XML. |
| EAV guide | Teach developers how to read/write existing EAV safely. |
| UI guide | Teach Blade/Livewire patterns for storefront and admin. |
| Testing guide | Explain characterization, parity, visual, performance, and release gates. |
| Operations guide | Explain deploy, rollback, cache, sessions, scheduler, queues, logs, and backups. |
| Migration status | Show legacy, bridged, and Laravel-complete domains. |
| ADRs | Record major decisions and rejected alternatives. |

## Docusaurus Documentation

The Docusaurus site must be installed, buildable, and verified in a browser.

| Section | Required Content |
| --- | --- |
| User documentation | Storefront and admin guides for merchandisers, operators, support teams, and business users. |
| Developer documentation | Architecture, module development, EAV access, Livewire UI patterns, testing, removed technologies, operations, and release workflow. |
| Feature coverage | User-visible status for every feature ID in `magento-feature-catalog.md`. |
| Verification | Links to screenshots, fixtures, test evidence, edge cases, and release status. |

The user docs and developer docs must stay separate so business users are not forced through migration internals, while developers still have precise implementation rules.

## Documentation Acceptance Criteria

- `mkdocs build` succeeds.
- `npm --prefix docusaurus run build` succeeds.
- `node dev/modernization/smoke-docusaurus.mjs` opens the Docusaurus site in Chrome/Playwright and the user and developer docs render.
- Navigation exposes the modernization section.
- Each implemented feature has user-facing and developer-facing documentation.
- Every module extension point has an example.
- Every architecture decision has reasoning.
- Every compatibility bridge has an exit criterion.
- A new developer can build the sample module by following the docs.
- User and developer docs include edge cases, failure states, operational recovery, and production-readiness evidence.
