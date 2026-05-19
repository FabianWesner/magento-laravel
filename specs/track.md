# Modernization Reasoning Track

This file records the main considerations behind implementation choices. It is not the progress ledger and it is not release evidence. The intent is to preserve the reasoning trail in a form that can later be turned into external writing or internal narrative.

## 2026-05-19 15:12 CEST - Why The Cron Jobs Workbench Exists

The cron/job slice exists because Magento cron is not a single scheduler table. Legacy jobs are declared in module XML, config-driven jobs only become schedulable when a cron expression exists in scoped config, and `cron_schedule` rows are materialized later by the Magento cron observer. A Laravel replacement needs to preserve the operational meaning of missing schedules, bridge decisions, cleanup windows, aggregation windows, and high-risk side effects before it starts running jobs.

I implemented a read-only workbench instead of dispatching scheduler work from the browser. The useful increment is that every `CJ-001` through `CJ-025` mapping is inspectable in Chrome with feature ID, domain, schedule, legacy run model, replacement decision, risk, last-run/next-run diagnostic text, and attention state. Config-driven jobs, bridge decisions, pending XmlConnect decisions, catalog rule application, and price reindexing are deliberately highlighted instead of hidden in a generic job list.

The legacy scan shaped the scope:

- Config-driven jobs such as backup, currency, PayPal reports, log cleanup, product alerts, and sitemap generation may be declared but unscheduled when the cron expression is empty.
- Magento has scheduler windows for generate-ahead, lifetime, success history, and failure history, so a final implementation needs cron-schedule fixture states rather than only config rows.
- Report aggregation jobs use a roughly 25-hour lookback and read aggregate tables, so the workbench separates report jobs from general operations.
- Some legacy jobs swallow or email warnings internally, while scheduler-level failures represent different failure modes.
- Catalog rule application and price reindexing are high-risk because they can change storefront prices; the workbench marks them blocked until commerce fixtures and stale-index parity are accepted.

The tradeoff is intentional: this creates a browser-verifiable local operations surface for all cron feature IDs, not final scheduler parity. Real `cron_schedule` fixtures, scoped config fixtures, queue history, cleanup target tables, integration warning artifacts, report aggregate comparisons, hosted CI, monitoring, rollback, and production runbook evidence remain release work.

## 2026-05-19 14:55 CEST - Why The Tax/Currency Workbench Exists

The tax/currency slice exists because Magento tax and currency behavior is both high-risk and heavily operational. Tax classes, tax rates, rule combinations, calculation settings, cross-border trade, report aggregation, currency rates, symbol overrides, and scheduled imports all influence money-facing behavior. Starting with a final write-capable admin replacement would be premature without canonical fixtures and final parity evidence.

I implemented this as a read-only workbench so the behavior can be inspected in Chrome without saving rates, changing rules, running imports, touching order totals, or mutating configuration. The useful increment is to make the states visible: tax classes, CA and DE rates, retail and VAT rules, a discount-before-tax calculation example, an invalid percent rate, tax report aggregation, base/display currency settings, current and stale currency rates, scheduled and failed import jobs, symbol overrides, empty states, and denied-role behavior.

The legacy scan shaped the scope:

- Tax rules expand across customer classes, product classes, and rates, so the fixture rows carry combination data instead of treating a rule as a single scalar.
- Tax lookup depends on country, region, postcode, range matching, priority, and compound behavior, so this slice exposes rule and rate diagnostics without claiming full calculation parity.
- Cross-border trade and prices-including-tax affect totals, so calculation examples are kept explicit and narrow.
- Currency rates can be direct, inverted, stale, or failed to import, so current and stale rates plus job failures are first-class rows.
- Currency symbol overrides live in serialized configuration and invalidate cache, so the workbench keeps them visible as configuration-sensitive diagnostics.
- Tax reports read aggregate tables refreshed by cron rather than live order totals, so report aggregation appears in the jobs/problem surface instead of being hidden behind normal tax rows.

The tradeoff is intentional: this improves local characterization and Laravel-side inspection for `AD-016`, `CB-006`, `CJ-002`, and `CJ-020`, but it is not final release parity. Full fixture restore, address/range matrices, real project rates, admin ACL integration, Magento/Laravel screenshot manifests, accessibility, performance, hosted CI, production readiness, and cutover evidence remain open.

## 2026-05-19 14:20 CEST - Why The Import/Export Dataflow Workbench Exists

The import/export slice exists because Magento has two overlapping admin concepts here: modern ImportExport screens for CSV product/customer movement, and older Dataflow profiles for wizard-based or advanced batch jobs. They share operational concerns such as uploaded files, generated exports, validation messages, row counts, batch history, failed rows, and permission boundaries, but they do not behave like a simple CRUD grid.

I built this as a read-only workbench because running imports or exports would be the wrong first step without canonical project fixtures and final admin auth boundaries. The useful increment is to make the states inspectable in Chrome: valid product import, failed customer import, generated product export, scheduled customer export, dataflow profile progress, generated files, blocked DE profile, and denied-role behavior.

The core reasoning was:

- Keep modern ImportExport and legacy Dataflow as separate domain snapshots, then combine them in UI sections where an operator would expect to inspect imports, exports, profiles, and files.
- Tighten fixtures to Magento core behavior after the legacy scan: products, customers, CSV, and stock dataflow profiles instead of introducing speculative project-overlay examples.
- Represent row totals, processed counts, failed counts, batch IDs, generated files, and error files directly on cards because those are the migration-critical operational signals.
- Keep buttons disabled and label them as previews/inspection only, avoiding uploads, downloads, batch execution, and file writes in the local workbench.
- Normalize every Livewire filter before it touches snapshots because the component state is browser-controlled.
- Use subagents for parallel context: one inspected Laravel patterns and one inspected Magento ImportExport/Dataflow behavior, while the main path integrated and browser-verified the slice.

The tradeoff is that this improves characterization and local Laravel-side inspection, not production import/export parity. Final project overlay rows, real fixture restore, generated-file retention policy, admin ACL integration, final screenshots, accessibility, performance, CI, and cutover evidence remain open.

## 2026-05-19 14:39 CEST - Why The System Config Workbench Exists

The system configuration slice exists because Magento admin configuration is not just a table of key/value settings. The legacy implementation combines section-level ACL, request-driven default/website/store scope, fallback from stores to websites to default, inherited values represented by missing current-scope rows, source model option lists, backend model save hooks, encrypted/obscured secret fields, environment overrides, and cache invalidation after save or inherit operations.

I kept this implementation read-only because saving system configuration too early would create a false sense of parity. The current useful increment is to expose the behavior shape in Chrome: default and DE store-view rows, inherited website/store values, invalid source-model input, masked gateway token, environment override, cache invalidation states, and denied-role behavior.

The core reasoning was:

- Add `system_config` as its own domain because `AD-010` needs a direct admin configuration surface, while also linking it to `SF-012`, `CB-011`, and `CB-013`.
- Reuse `store_scope` as the companion data because multistore behavior is inseparable from configuration fallback and inherited values.
- Keep secrets masked and treat backend/source model flags as diagnostics instead of editable controls.
- Use deterministic facts instead of touching `core_config_data` from the browser route, because the workbench should be inspectable without mutating configuration state.
- Normalize every public Livewire filter before querying, since store scope, group, state, section, and role are browser-controlled.
- Use subagents in parallel: one checked Laravel conventions and existing config services, while another scanned Magento system configuration behavior and fixture gaps.

The tradeoff is explicit: this improves local characterization and Laravel-side inspection, not final Magento/Laravel system configuration parity. The legacy scan also surfaced fixture gaps for second websites, disabled store views, field definitions, obscured encrypted save behavior, URL validation, and full screenshot coverage. Those remain release work.

## 2026-05-19 14:04 CEST - Why The Cache/Index Workbench Exists

The cache/index slice exists because Magento's admin cache screen is an operations surface, not a simple settings page. It combines cache-type enablement, invalidated tags, stale output, compiler controls, index process state, unprocessed index events, cron scheduling, lock ownership, and failure diagnostics. Those details are easy to flatten away if the replacement starts directly with a final admin controller.

I implemented a read-only workbench first so the team can inspect those states in Chrome before deciding how much of the old operational surface should be preserved, replaced, or retired. The workbench is deliberately fixture-backed and local-only: it helps us exercise the behavior shape without flushing real caches, running indexers, changing compiler state, or pretending final admin parity is already done.

The core reasoning was:

- Use cache and index as explicit domain keys because they cut across admin, commerce, and cron feature IDs.
- Keep stale cache and stale index cases visible as first-class rows, since they are the states operators actually need to reason about during migration.
- Carry cron job and schedule metadata on the rows instead of hiding it in a separate note, because cache cleanup and price reindexing are operationally tied to the admin status screens.
- Include lock owner and failure reason data early, because "Processing" in Magento can mean a healthy job, a stuck lock, or a failure that needs intervention.
- Preserve the compiler surface as a disabled retained-decision row, making the migration choice visible instead of silently dropping it.
- Add filterable cache, index, cron, and lock sections so Chrome verification mirrors how an operator would drill into the screen.
- Normalize Livewire public state before filtering, because every filter is browser-controlled input.
- Fix the Cron type filter when browser verification exposed that the UI option did not yet match the filtering semantics.

The tradeoff is intentional: this is not a production cache management implementation and it does not run destructive operations. It is a characterization and inspection surface that moves the migration forward while final project fixtures, auth boundaries, release screenshots, accessibility, performance, CI, and cutover evidence remain open.

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
