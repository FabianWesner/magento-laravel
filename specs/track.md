# Modernization Reasoning Track

This file records the main considerations behind implementation choices. It is not the progress ledger and it is not release evidence. The intent is to preserve the reasoning trail in a form that can later be turned into external writing or internal narrative.

## 2026-05-19 16:41 CEST - Why The Admin CMS Design Workbench Exists

The admin CMS/design slice exists because Magento CMS administration is not just page content. It includes CMS pages with direct identifier routing, static blocks with WYSIWYG content, widget instances that create layout update rows, URL key uniqueness across selected stores, no-route/home-page configuration, page-level custom design fields, System > Design schedule records, theme/package configuration, and cache invalidation after content or layout changes.

I implemented this as a read-only workbench under `/_modernization/admin/cms-design`. It does not save CMS pages, save static blocks, create widget layout links, validate or render arbitrary layout XML, upload or delete media, activate design schedules, scan theme packages, regenerate rewrites, or refresh caches. The useful increment is a Chrome-verifiable diagnostic surface for CMS page rows, block rows, widget rows, design scope rows, URL rewrite rows, cache dependency rows, problem rows, DE store-view scoping, empty states, and denied viewer behavior.

The legacy scan shaped the scope:

- CMS page admin has grid columns plus Page Information, Content, Design, and Meta Data tabs, so the workbench keeps layout, meta, block, widget, redirect, and no-route signals visible together.
- Page and block saves have store-scoped identifier uniqueness rules, URL key restrictions, self-reference checks, and cache invalidation, so this slice avoids every write path.
- Widget admin is a two-step flow that depends on widget type and design package/theme before writing layout update/link records, which is too risky without final fixture and validator evidence.
- Design behavior is split across page-level custom design fields, System > Design scheduled changes, and configuration package/theme values; the workbench exposes store-scope diagnostics without claiming final theme fallback parity.
- Layout XML and WYSIWYG media have security-sensitive validation and storage behavior, so the workbench shows references and disabled actions instead of rendering or mutating them.

This slice improves admin CMS/design inspection for `AD-009` and links related CMS/domain facts into the domain catalog, but it is not final CMS/design cutover. Real project CMS fixtures, duplicate-key validation, layout XML validator parity, widget layout rows, WYSIWYG media storage/security, design schedule overlap cases, admin ACL integration, hosted CI, manual acceptance, production runbooks, and final Magento/Laravel screenshot evidence remain open.

## 2026-05-19 16:25 CEST - Why The Admin Customer Workbench Exists

The admin customer slice exists because Magento customer administration is not just a table of accounts. It includes an AJAX-backed customer grid, EAV-generated account fields, addresses, website-specific email uniqueness, customer groups, newsletter state, recent activity, orders, carts, wishlist, reviews, tags, default billing and shipping addresses, and ACL-gated tabs.

I implemented this as a read-only workbench under `/_modernization/admin/customer-management`. It does not save customers, create customers, delete customers, mass-update groups, subscribe or unsubscribe newsletters, change passwords, create orders, delete carts, configure wishlists, edit reviews, edit tags, send emails, or write back to Magento tables. The useful increment is a Chrome-verifiable diagnostic surface for customer rows, addresses, wishlist and compare rows, review/tag moderation rows, problem rows, DE store-view values, empty states, and denied viewer behavior.

The legacy scan shaped the scope:

- Customer grids join EAV and default billing data, so the workbench carries account summary and address signals together instead of showing only names and emails.
- Customer account and address forms are generated from EAV form codes, so final parity cannot hardcode only the current fixture fields.
- Existing customer website assignment is immutable in legacy admin, and email uniqueness is per website, which needs real fixture coverage before any write path exists.
- Customer saves can delete omitted addresses, toggle newsletter state, change passwords, dispatch events, and send emails, so this slice deliberately avoids every mutation.
- Wishlist, reviews, and tags are admin-visible customer tabs, but legacy also exposes destructive actions; this slice keeps them inspectable with disabled actions only.

This slice improves admin customer inspection for `AD-007` while also surfacing related `SF-010`, `SF-011`, and `AD-009` signals, but it is not final customer admin cutover. Real customer fixtures, multi-website duplicate-email cases, full EAV forms, order/cart/newsletter queue fixtures, admin ACL integration, hosted CI, manual acceptance, production runbooks, and final Magento/Laravel screenshot evidence remain open.

## 2026-05-19 16:11 CEST - Why The Admin Catalog Workbench Exists

The admin catalog slice exists because Magento product and category administration is much broader than a product grid. It includes product grids, store-view overrides, attribute sets, custom options, configurable attributes, category tree assignments, media gallery roles, missing image states, downloadable files, product status, inventory signals, mass actions, and ACL boundaries.

I implemented this as a read-only workbench under `/_modernization/admin/catalog-management`. It does not save products, save categories, move category nodes, delete rows, run mass actions, upload media, upload downloadable files, update URL rewrites, trigger index/cache side effects, or write EAV values. The useful increment is a Chrome-verifiable diagnostic surface for product rows, category rows, EAV-like attribute signals, media gallery rows, downloadable link rows, problem rows, store-view values, empty states, and denied viewer behavior.

The legacy scan shaped the scope:

- Admin product grids include store-aware columns and filters such as ID, name, type, attribute set, SKU, price, quantity, visibility, status, and websites.
- Product and category saves are high-risk because they can mutate EAV backend values, website/category relations, stock rows, media files, downloadable links, URL rewrite history, index state, and cache state.
- Category administration includes tree/root behavior and product positions, but moves and saves remain out of scope for this local slice.
- Media gallery behavior needs labels, positions, base/small/thumbnail roles, disabled flags, missing media, and store defaults visible before any upload flow exists.
- Downloadable products have file/sample/title/shareability/group-permission behavior, but uploads and deletes remain disabled.

This slice improves admin catalog inspection for `AD-002`, `AD-003`, and `AD-004`, but it is not final catalog cutover. Real project catalog fixtures, full product type coverage, EAV backend/source validation artifacts, stock/media/downloadable write characterization, admin ACL integration, hosted CI, manual acceptance, production runbooks, and final Magento/Laravel screenshot evidence remain open.

## 2026-05-19 15:51 CEST - Why The Admin Permissions Workbench Exists

The admin permissions slice exists because Magento admin authorization is more than a role name on a user. It includes admin users, admin roles, menu ACL filtering, direct controller action checks, session-cached ACL state, SOAP/XML-RPC API users and roles, REST/API2 roles and attributes, OAuth consumers and tokens, and several denied-state formats. A Laravel cutover needs those boundaries visible before any user, role, token, or permission mutation exists.

I implemented this as a read-only workbench under `/_modernization/admin/admin-permissions`. It does not create users, save roles, rotate keys, revoke tokens, log users in, reset passwords, or call API endpoints. The useful increment is a Chrome-verifiable diagnostic surface for the current `PermissionManifest`, admin role fixtures, ACL resources, classic API users/roles, REST/API2 admin/customer/guest roles, OAuth consumers/tokens, denied states, rollback metadata, empty states, and denied viewer behavior.

The legacy scan shaped the scope:

- Admin menus can be ACL-filtered differently from direct controller action checks, so the workbench makes menu-visible and direct-URL denial concerns explicit.
- Legacy admin user and role saves require current admin password plus form/secret key checks; this local slice deliberately avoids every write path.
- SOAP/XML-RPC API ACL uses separate API user, role, rule, and session tables, and failures surface as API faults such as access denied or session expired.
- REST/API2 falls back to Guest when an OAuth Authorization header is absent, so guest behavior must be explicit rather than treated as generic auth failure.
- API2 and OAuth declared ACL XML paths do not always match runtime controller checks; the workbench carries that mismatch as a parity risk instead of smoothing it away.

This slice is deliberately not final auth cutover. ADR 0008 remains proposed, final admin auth and API permission fixtures are absent, and the release still needs real admin/API users, API roles, API2 attribute rules, OAuth token lifecycle fixtures, direct URL denial evidence, manual security review, hosted CI, production runbook, and final Magento/Laravel screenshot evidence.

## 2026-05-19 15:32 CEST - Why The Integrations/API Workbench Exists

The integrations/API slice exists because Magento's external surface is not one API. The legacy application has classic SOAP and XML-RPC entrypoints, REST/API2 with OAuth and guest behavior, admin-managed API users and roles, payment redirects and callbacks, shipping and currency service calls, webhook-style integrations, retry/timeout behavior, secrets, and rollback concerns. Replacing any of that without a visible diagnostic layer would hide the highest-risk parts of the cutover.

I implemented a read-only workbench instead of calling live integrations from the browser. The route does not call `IntegrationGateway`, send HTTP requests, dispatch recovery jobs, rotate tokens, save credentials, or trigger callbacks. The useful increment is that operators can inspect the mapped contracts and adapters in Chrome: SOAP, XML-RPC, REST/API2, payment, shipping, external services, OAuth, webhooks, sandbox status, config paths, providers, secrets-by-reference, retry, timeout, rollback, attention rows, empty states, and denied roles.

The legacy scan shaped the scope:

- Classic API behavior includes SOAP, XML-RPC, WSDL, admin API credentials, roles, and fault formats.
- REST/API2 behavior includes OAuth, possible guest fallback, content negotiation, HTTP error statuses, and role/attribute permissions.
- Payment callback paths such as IPN, silent posts, and direct-post flows need retained payload and signature comparison before cutover.
- Shipping, currency, feed, email, ERP, PIM, CRM, and other service adapters need sandbox, outage, retry, timeout, and rollback evidence.
- Admin Web Services settings are part of `AD-018`, so API contracts and integration configuration have to be visible together.

A sidecar review found a useful latent bug: an adapter without sandbox metadata would have been labeled healthy if it was not OAuth or webhook-based. I changed that to `mock-required` and attention-worthy, then added focused PHPUnit coverage and rechecked the affected status filter in Chrome. That is exactly why the workbench exists: it lets us reason about operational readiness before any destructive integration behavior is enabled.

The tradeoff remains explicit. This creates local inspection and traceability for `AD-018`, `API-001` through `API-006`, and related integration-facing storefront/cron features, but it is not final parity. Real API payload snapshots, OAuth exchanges, payment callbacks, carrier responses, currency imports, admin ACL mapping, sandbox credentials, outage drills, hosted CI, security review, production runbook, rollback rehearsal, and final Magento/Laravel screenshot evidence remain release work.

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
