# Technology Removal Policy

This policy defines technologies that must be removed completely from the refactored Laravel system by final cutover.

The Magento CE `1.9.4.5` runtime remains available under `core/` and `.localdev/magento-docroot/` during migration for characterization and side-by-side comparison. That legacy baseline may contain these technologies. The Laravel target under `laravel/` must not depend on them for migrated behavior.

## Removal Rule

A technology is considered removed only when all of the following are true:

- It is not required by `laravel/composer.json`, `laravel/package.json`, or runtime build tooling.
- It is not referenced by Laravel app, config, route, resource, module, policy, event, job, or view code.
- It is not required to serve any migrated storefront, admin, API, cron, queue, payment, shipping, tax, report, or integration feature.
- It is not used as an adapter layer that hides legacy Magento runtime calls behind Laravel services.
- Any temporary bridge has an owner, expiry phase, tests, and removal issue.

## Must Be Removed From The Laravel Target

| Area | Technologies To Remove | Replacement In Laravel Target | Verification |
| --- | --- | --- | --- |
| Framework runtime | Zend Framework 1 runtime, Magento front controller, `Mage::app()`, `Mage_Core_*` request/bootstrap/config runtime. | Laravel HTTP kernel, service container, routing, middleware, config, scheduler, queues. | Composer/package scan, static forbidden-reference scan, route smoke tests. |
| Service locator and class aliases | `Mage::getModel()`, `Mage::helper()`, `Mage::getResourceModel()`, Magento class aliases, rewrites. | Constructor injection, Laravel container bindings, explicit contracts, repositories, policies, events. | Static scan for `Mage::`, `Mage_`, resource-model usage in Laravel code. |
| XML module/config system | `app/etc/modules/*.xml`, `config.xml`, `system.xml`, `adminhtml.xml`, `api.xml`, `api2.xml`, `widget.xml`, `wsdl.xml`, event XML, route XML, ACL XML, layout XML. | PHP module manifests, service providers, config files, route files, policies, events/listeners, Blade/Livewire components. | No-new-XML gate plus module registry tests. |
| Layout and block rendering | Magento layout handles, layout XML updates, `Mage_Core_Block_*`, `.phtml` block/template rendering for migrated screens. | Blade, Livewire components, view models, explicit layout components. | View scan, visual baseline comparison, Livewire tests. |
| ORM/resource layer | Magento resource models, Varien collections, `Zend_Db`/`Varien_Db` as application abstraction, setup resource scripts. | EAV repositories/services for EAV entities; Eloquent only for approved flat tables/infrastructure; Laravel migrations only for approved new infrastructure tables. | Static scan, architecture tests, schema checksum, EAV parity tests. |
| Data objects and collections | `Varien_Object`, `Varien_Data_Collection`, Magento registry/session/global singleton patterns. | Typed DTOs, value objects, Laravel collections, request/session abstractions. | Static scan and code review. |
| Admin/storefront JavaScript runtime | Prototype.js, Scriptaculous, legacy `varien/js.js`, legacy Magento admin JS widgets, legacy validation JS as runtime requirements for migrated screens. | Livewire, Blade, Laravel validation, minimal modern JavaScript only where needed. | Asset manifest scan, browser tests, visual tests, accessibility tests. |
| Legacy package/update tooling | Magento Connect, downloader, PEAR package channel, Magento compiler, legacy shell installers as operational mechanisms. | Composer, Laravel package discovery, module manifests, CI/CD release pipeline. | Operations runbook review, dependency scan. |
| Legacy API implementation internals | Magento SOAP/XML-RPC/API2 controllers and resource classes as the implementation for migrated endpoints. | Laravel controllers/resources that preserve required contracts or explicitly approved modern API replacements. | API contract tests and static scan. |
| Legacy cron execution | `cron.php`, Magento cron XML dispatch, `Mage_Cron` runtime as final scheduler. | Laravel scheduler, jobs, queues, locks, command diagnostics. | `schedule:list`, cron parity tests, job side-effect checks. |
| Legacy auth/session internals | Magento admin/customer session classes, form key implementation, password reset flow internals as target runtime dependencies. | Laravel guards/providers/session/CSRF/password brokers with compatibility adapters only where approved and expiring. | Security tests and static scan. |
| Zend/Laminas compatibility replacement | Replacing Zend Framework calls with Laminas MVC/Db/Config as a hidden compatibility runtime. | Native Laravel components and explicit domain services. | Composer scan for Zend/Laminas framework packages; ADR required for any exception. |

## Not Removed

These are intentionally retained unless a separate approved decision changes them:

- Magento database schema, including EAV tables, table names, primary keys, indexes, and existing seed/sample/project data.
- Product, customer, order, quote, invoice, shipment, credit memo, rule, tax, report, URL rewrite, and CMS data.
- Public URLs, API contracts, cron outcomes, emails, reports, admin workflows, and storefront/admin visual look and feel.
- The Magento CE source tree under `core/` and generated local baseline under `.localdev/` during migration.
- Algorithms reverse-engineered from Magento behavior, if reimplemented as clean Laravel services without runtime dependency on Magento/Zend/Varien classes.

## Temporary Bridge Policy

Temporary bridges are allowed only to keep delivery incremental. A bridge must:

- Be isolated under an explicit compatibility namespace.
- Have an owner and removal phase.
- Be covered by characterization and parity tests.
- Be listed in the migration backlog.
- Be blocked from final release unless explicitly approved as a non-runtime historical artifact.

Temporary bridges may not introduce new XML configuration in Laravel code.

Final compatibility packages are allowed only for contracts, documentation, typed DTOs, data mappers, and public interface shims that do not call Magento/Zend/Varien runtime code. A final package that executes legacy runtime code blocks cutover unless an ADR explicitly retires that feature or excludes it from Laravel scope.

## Acceptance Criteria

- `dev/modernization/validate-removed-technologies.php` passes for Laravel target paths.
- Composer and npm dependency manifests do not include banned runtime dependencies.
- Migrated routes, jobs, events, policies, modules, views, APIs, and commands do not reference banned technologies.
- Final release evidence includes a removed-technology scan report.
- Any exception has an ADR, expiry date or phase, owner, and explicit risk acceptance.
