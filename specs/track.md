# Modernization Reasoning Track

This file records the main considerations behind implementation choices. It is not the progress ledger and it is not release evidence. The intent is to preserve the reasoning trail in a form that can later be turned into external writing or internal narrative.

## 2026-05-19 13:13 CEST - Why The CMS/SEO Workbench Exists

The current implementation slice intentionally adds a workbench instead of trying to replace Magento CMS routing outright. CMS and SEO behavior in Magento is deceptively broad: a visible storefront page can depend on CMS page identifiers, store-scoped page/block assignments, widgets injected through layout handles, URL rewrites, no-route behavior, redirects, sitemap generation, RSS feeds, and store configuration fallback.

The workbench gives us a controlled surface to try those behaviors before committing to a final replacement. It is read-only, fixture-backed, role-gated, and exposed under `/_modernization/storefront/cms-seo`, so it can coexist with the legacy runtime and be verified in Chrome without pretending the migration is complete.

The core reasoning was:

- Use deterministic domain facts first, because the real project overlay and canonical fixtures are still missing.
- Keep Magento behavior visible as structured rows instead of embedding assumptions in Blade.
- Make store view, content state, freshness, SEO, empty, and denied states directly clickable in Chrome.
- Normalize Livewire public properties before querying, because every public property is client-controlled.
- Treat sitemap/RSS freshness as its own UI concept, since Magento feed generation and stale output are operationally different from normal CMS page visibility.
- Keep the UI modest and diagnostic rather than marketing-like; this is a parity work surface for repeated inspection.
- Use subagents for parallel context and fixture work, while keeping the main path responsible for integration and browser verification.

The most important tradeoff is that this slice improves characterization and Laravel-side inspection, not final parity. It proves a useful modernization increment can be tested and viewed in Chrome, but it does not close the release blockers around final Magento/Laravel screenshot manifests, project overlay, production fixture restore evidence, accessibility, performance, or cutover approval.

## 2026-05-19 13:13 CEST - Why Documentation Stayed Last

The user explicitly redirected the workflow toward implementation-first progress with regular Chrome verification. I kept documentation to the end of the slice so the repository records reflect verified work, not intentions. `specs/progress.md` remains the factual ledger, while this file captures why the implementation shape was chosen.

The new `specs/tasklist.md` and `specs/open-issues.md` are meant to make the work easier to follow without conflating local progress with release readiness.
