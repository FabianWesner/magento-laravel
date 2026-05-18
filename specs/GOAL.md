# Modernization Goal Prompt

Objective:
Modernize the existing Magento CE 1.9.4.5 project into a Laravel-based architecture while keeping the legacy Magento runtime in place for side-by-side comparison until final cutover. Replace the Zend/Magento framework runtime with Laravel, use Livewire for storefront and admin UI, preserve the existing database schema including EAV and original seed/sample/project data, preserve storefront and admin look and feel, and replace XML-based extensibility with PHP modules, manifests, service providers, policies, events, jobs, typed config, and explicit contracts.

Repository and runtime structure:
- Keep Magento CE 1.9.4.5 source under core/magento-1.9.4.5/.
- Keep project-specific overlay/custom code under project/.
- Generate the legacy runtime docroot under .localdev/magento-docroot/ from core plus project.
- Build the new Laravel target under laravel/.
- Run Magento and Laravel side-by-side against restorable fixture data so every feature can be compared before route cutover.
- Do not remove the legacy Magento runtime while any feature still needs characterization, screenshotting, or parity comparison.

Non-negotiable requirements:
- Use Magento CE 1.9.4.5 as the legacy baseline.
- Laravel is the target framework.
- Livewire is the target storefront and admin UI implementation.
- Laravel target must run on the latest stable PHP available in this environment and CI; legacy Magento PHP 7.4 may remain isolated only for baseline smoke verification.
- Keep the existing commerce database schema, including EAV tables and original seed/sample/project data. No destructive schema migration is allowed.
- EAV reads and writes must go through explicit EAV repositories/services. Do not use direct Eloquent writes for EAV value tables.
- Preserve all existing storefront and admin screens, flows, URLs, API contracts, cron behavior, order/payment/tax/cart behavior, and visual look and feel unless an explicit retirement or difference is approved.
- No new XML is allowed for migrated Laravel modules, routing, events, layout, ACL, config, or extension registration.
- Existing Magento XML may be read for inventory and characterization only.
- Every complex feature, especially cart calculation, pricing, promotions, tax, shipping, payment, order lifecycle, indexing, reports, cron, permissions, and EAV writes, must be reverse-engineered and specified before Laravel replacement starts.
- Demo data must be predefined and reproducible. It must cover all Magento product types, discounts, coupons, tax/shipping/payment paths, customer groups, admin roles, API users, reports, cron jobs, multistore scope, media, and edge cases.
- Every existing UI must be inventoried and screenshotted for Magento and Laravel across required roles, states, and viewports.
- Every Magento feature must be tracked by stable feature ID from specs/modernization/magento-feature-catalog.md and cannot be marked done without fixture, characterization, implementation, test, and release evidence.
- The refactored Laravel runtime must completely remove the banned technologies listed in specs/modernization/technology-removal-policy.md. Magento/Zend/Varien/XML/Prototype-era technology may exist only in the Magento baseline used for characterization, not as a dependency for migrated Laravel behavior.
- Verification must cover happy paths, edge cases, failure paths, invalid input, permission denial, concurrency, stale cache/index states, integration outages, recovery, rollback, and production-readiness criteria.
- Add and maintain a Docusaurus documentation site under docusaurus/ with separate user documentation and developer documentation. It must be installed, built, and verified in Chrome/Playwright.

Codex operating rules:
- Read the relevant code, specs, docs, and current git status before changing files.
- Keep progress in specs/progress.md. Update it with factual status before each commit and whenever a blocker, verification result, or decision changes.
- Commit regularly with small, reviewable commits after coherent increments. Each commit must include the relevant spec/progress updates.
- Keep user changes safe. Do not revert unrelated work. Do not use destructive git commands unless the user explicitly requests them.
- Prefer the existing repository conventions. Keep edits scoped to the current phase and related specs/code.
- Use sub-agents for independent work when useful: read-only inventory, fresh code review, browser/Chrome verification, and bounded implementation tasks with disjoint write scopes.
- Review sub-agent results before accepting them. Fold valid findings into specs, tests, or code.
- Use Chrome/Playwright or available browser automation for storefront/admin verification. If Playwright is blocked, document the blocker, exact command attempted, and next command to run once the blocker is resolved.
- Use Laravel Boost guidance and MCP tools when available. Before implementation work, verify Boost MCP can list tools and run `application-info`, `search-docs`, and read-only database tooling. If Boost MCP tools are missing, verify package install, MCP config, and artisan `boost:mcp` manually, then document the status and exact reload/config steps.

Primary work phases:
1. Baseline and inventory:
   - Verify Magento CE 1.9.4.5 source, project overlay, generated docroot, Docker/local runtime, sample/project DB, media, PHP versions, Composer/npm dependencies, remotes, and CI.
   - Generate inventory reports for core and project.
   - Identify all modules, routes, controllers, templates, admin screens, APIs, cron jobs, shell commands, observers, setup scripts, config paths, and integrations.
2. Fixture and visual baseline:
   - Build deterministic demo fixtures from specs/modernization/data-fixtures.md.
   - Restore fixtures locally and in CI without destructive schema changes.
   - Capture Magento storefront/admin screenshots according to specs/modernization/ui-screen-inventory.md.
3. Characterization and reverse engineering:
   - For every feature ID, capture Magento behavior through tests, screenshots, DB deltas, API payloads, events, emails, logs, generated files, and side effects.
   - Write detailed specs for complex behavior before implementation.
4. Laravel foundation:
   - Build Laravel bootstrap, config, module registry, no-XML manifest system, route strangler/fallback, EAV access layer, auth/session/security foundation, scheduler, queue, events, API contracts, and operations hooks.
5. Feature migration:
   - Replace features incrementally behind explicit routes/contracts.
   - Keep Magento and Laravel side-by-side for comparison.
   - Use Livewire for retained storefront/admin UI with visual parity.
6. Verification and release:
   - Run the full test plan in specs/modernization/test-plan.md.
   - Prove feature-ID traceability for every Magento feature.
   - Prove happy path, edge-case, failure-path, rollback, operations, security, performance, accessibility, documentation, and support readiness.

Acceptance criteria:
- Every file under specs/modernization that defines architecture, feature catalog, UI inventory, complex behavior, fixture strategy, backlog, risk, release, operations, security, and test plan is current and internally linked.
- docs/content/modernization builds with MkDocs and explains the public architecture and reasoning without mixing in execution checklists.
- docusaurus/ builds with Docusaurus, separates user and developer docs, and is verified in Chrome/Playwright.
- Magento CE 1.9.4.5 baseline can be installed or restored locally, seeded with sample/demo data, and smoke-tested in Chrome.
- Laravel app exists side-by-side, can run artisan from the repository root, and Laravel Boost can be installed and its MCP server can list tools.
- All feature IDs in specs/modernization/magento-feature-catalog.md have an owner, status, fixtures, screenshots if visible, characterization evidence, Laravel test evidence, and release evidence.
- All UI screens in specs/modernization/ui-screen-inventory.md have Magento and Laravel screenshots for required roles, states, and viewports.
- All complex features in specs/modernization/complex-feature-reverse-engineering.md have approved reverse-engineering specs and dual-runtime parity tests.
- The canonical demo fixture covers all product types, discounts, coupons, taxes, shipping, payment, admin roles, API users, reports, cron jobs, multistore scope, media, and edge cases.
- The no-new-XML gate passes for migrated Laravel code and specs.
- The removed-technology gate passes for the Laravel target, with no runtime dependency on Zend Framework, Magento/Mage runtime classes, Varien, Magento XML configuration, Magento layout/block rendering, legacy resource models/collections, Prototype/Scriptaculous, Magento Connect/downloader/compiler, Magento cron runtime, or hidden Zend-to-Laminas compatibility layers.
- Schema checks prove the commerce schema and EAV data were not destructively changed.
- Storefront/admin visual regression is approved within tolerance.
- API, cron, queue, auth, security, performance, accessibility, deployment, rollback, backup/restore, docs, and operations gates pass.
- Edge-case, failure-path, resilience, recovery, observability, and production-readiness gates pass; happy-path smoke tests alone never satisfy completion.
- No P0/P1 defects remain open, and P2 defects require explicit acceptance.
