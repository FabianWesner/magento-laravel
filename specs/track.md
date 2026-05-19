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

## 2026-05-19 13:43 CEST - Why The Communications Workbench Exists

The communications slice exists because Magento's storefront communication behavior sits across several subsystems that are easy to underestimate if they are treated as a single email feature. Newsletter subscribers, contact forms, send-to-friend, product alerts, queue records, suppression/problem reports, store scope, and delivery failures all influence what a customer or operator sees.

I kept this as a read-only workbench instead of starting with a final mailer or scheduler replacement. That lets us expose the behavior shape in Chrome first: subscriber states, invalid contact submissions, guest send-to-friend denial, product alert success/failure, delivery queue status, and DE store-view localization. The goal is to make the domain inspectable before wiring destructive sends, retries, or production cron behavior.

The core reasoning was:

- Model newsletter and contact as the existing domain keys, while carrying `send_to_friend`, `product_alert`, and `contact_form` as communication types inside the payload.
- Store email delivery details under nested `email` payloads, because Magento communication behavior is not just the visible form or subscription row.
- Include failed delivery and problem-report fixtures early, because the migration has to preserve support and operational failure states, not just happy-path messages.
- Keep queue rows derived from delivery status and problem attempts so invalid or denied forms do not masquerade as queued mail.
- Normalize Livewire filters and read nested payload fields defensively, because public component state and fixture shape both need explicit boundaries.
- Use Chrome interactions as the integration check, since the point of the workbench is whether a human can inspect the states across sections, filters, and viewports.

The tradeoff is the same as previous workbench slices: this improves characterization and local Laravel-side inspection, but it is not final communication parity. Real queued mail, scheduler behavior, retry semantics, suppression policy, production fixture restore, and Magento/Laravel screenshot manifests remain release work.
