---
tags:
- Development
---

# Inventory

This inventory records what is known from the current repository and what must still be gathered from the actual project overlay.

## Current Repository Findings

The current repository keeps Magento CE `1.9.4.5` source under `core/magento-1.9.4.5/` plus local modernization preparation files at the root. `project/` is reserved for the project overlay, but it does not yet contain the real project-specific Magento installation.

| Area | Finding |
| --- | --- |
| Code pools | `app/code` contains `core` and `community`; `app/code/local` is absent. |
| Core modules | 67 module directories under `app/code/core/Mage`. |
| Module declarations | 21 XML files under `app/etc/modules`: Magento declarations plus `Cm_RedisSession` and `Phoenix_Moneybookers`. |
| Compatibility file | `app/etc/modules/Mage_All.xml` is an empty `<config/>` compatibility file. |
| Non-core modules | `Cm_RedisSession` and `Phoenix_Moneybookers` are bundled community dependencies in this Magento CE source tree; no project-specific non-`Mage_*` modules were found. `Cm_RedisSession` maps to `CB-013`; `Phoenix_Moneybookers` maps to `API-004`. |
| Live local config | `core/magento-1.9.4.5/app/etc/local.xml` exists only for the ignored local smoke install. |
| Controllers | 232 controller files across the Magento source tree; `Mage_Adminhtml` is the largest controller surface. |
| Routes | Frontend/admin front names include catalog, customer, checkout, sales, cms, api, api2, admin, and supporting modules. |
| Cron | Core cron jobs are declared in module config XML and must be inventoried into scheduler tasks before migration. |
| Events/observers | Core event/observer declarations are currently XML-backed and must be replaced by PHP event/listener registration for migrated code. |
| API declarations | API-related XML exists under core modules: `api.xml`, `api2.xml`, `wsdl.xml`, and `wsi.xml`. |
| Setup scripts | 775 SQL setup files and 33 data setup files under core modules. |
| Design packages | Adminhtml `default/default`; frontend `base/default`, `default/blank`, `default/default`, `default/iphone`, `default/modern`, and `rwd/default`; install `default/default`. |
| Skin packages | Skin packages mirror the design areas. |
| Browser assets | Legacy assets under `js/calendar`, `js/extjs`, `js/lib/jquery`, `js/mage`, `js/prototype`, `js/scriptaculous`, and `js/varien`. |
| Shell scripts | `shell/abstract.php`, `shell/indexer.php`, `shell/log.php`, and other Magento shell commands. |
| Tests | No project PHPUnit or Playwright/Chrome browser suite is present in this current source-only checkout. |

## Local Magento 1.9.4.5 Baseline

| Area | Finding |
| --- | --- |
| Package | Magento CE / Magento Open Source source tag `1.9.4.5`. |
| Commit | `98da842ef7aa59b8b8fee642de8b0b6deec55c31`. |
| Runtime version check | `Mage::getVersion()` returns `1.9.4.5`. |
| Local URL | `http://127.0.0.1:8090/`. |
| Seed data | Magento sample data `1.9.2.4`; 593 products, 29 categories, 10 CMS pages. |
| Smoke status | Storefront home/category/product and admin dashboard verified in Chrome through Playwright. |

See `specs/modernization/install-verification.md` for the full local setup record.

## Route Front Names Found

Known front names include:

`admin`, `api`, `authorizenet`, `captcha`, `catalog`, `catalogsearch`, `centinel`, `checkout`, `cms`, `contacts`, `core`, `customer`, `directory`, `downloadable`, `giftmessage`, `install`, `media`, `newsletter`, `oauth`, `payflow`, `paygate`, `paypal`, `persistent`, `productalert`, `review`, `rss`, `sales`, `shipping`, `tag`, `usa`, `wishlist`.

## Project-Specific Inventory Required

Run this inventory against the actual project codebase, not only this source checkout.

| Area | Required Output |
| --- | --- |
| Local modules | All modules under `app/code/local`, declaration XML, dependencies, rewrites, events, crons, routes, setup scripts. |
| Community modules | All project modules under `app/code/community`, vendor ownership, upgrade path, compatibility risk. |
| Composer packages | Project-specific Composer packages, private packages, patches, replace/conflict rules. |
| Core overrides | Modified core files, copied core classes, rewrites, class preferences, template overrides. |
| Theme overrides | Active package/theme, layout XML, templates, assets, email templates, CMS dependencies. |
| Admin customizations | Custom admin routes, grids, forms, ACL entries, reports, exports, importers. |
| Storefront features | Custom category/product/customer/cart/checkout/CMS behavior and frontend JS. |
| Integrations | Payment, shipping, tax, ERP, PIM, CRM, email, analytics, search, feeds, webhooks. |
| API consumers | REST, SOAP, XML-RPC, API2 clients, auth method, endpoints used, payload contracts. |
| Cron jobs | All custom cron jobs, schedules, runtime, side effects, failure behavior. |
| Database | Custom tables, triggers, views, stored procedures, EAV attributes, indexes, data volume. |
| Media/files | Media paths, protected files, generated images, imports/exports, file storage mode. |
| Config | `core_config_data`, env variables, per-website/store differences, secrets, admin paths. |
| Operations | Deploy scripts, cache/session backend, queue/cron runner, monitoring, backups, rollback. |

## Inventory Commands

Use these from inside the Magento source root or the generated runtime document root:

```bash
find app/code -maxdepth 3 -type d | sort
find app/etc/modules -name '*.xml' | sort
find app/code -path '*/etc/config.xml' | sort
find app/code -path '*/controllers/*.php' | sort
find app/code -path '*/sql/*/*.php' -o -path '*/data/*/*.php'
find app/design skin js -maxdepth 4 -type f | sort
rg -n '<rewrite>|<events>|<observers>|<crontab>|<routers>|<adminhtml>|<frontend>|<api>|<api2>' app/code app/etc
rg -n 'Mage::getModel|Mage::helper|Mage::dispatchEvent|Zend_|Varien_' app/code app/design shell
```

## Inventory Acceptance Criteria

- Every active module has an owner, purpose, risk level, and migration decision.
- Every custom route, observer, cron, API, setup script, and theme override is listed.
- Every integration has a sandbox/testing strategy.
- Every custom table and custom EAV attribute is recorded.
- Every high-risk business workflow maps to tests in the test plan.
