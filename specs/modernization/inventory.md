---
tags:
- Development
---

# Inventory

This inventory records what is known from the current repository and what must still be gathered from the actual project overlay.

## Current Repository Findings

The current root checkout appears to be OpenMage core/source rather than a project-specific Magento installation. The local runnable baseline for smoke testing has been switched to Magento CE `1.9.4.5` from `OpenMage/magento-mirror` tag `1.9.4.5`.

| Area | Finding |
| --- | --- |
| Code pools | `app/code` contains `core` and community dependency symlinks; `app/code/local` is absent. |
| Core modules | 60 module directories under `app/code/core/Mage`. |
| Module declarations | 63 XML files under `app/etc/modules`: 61 `Mage_*` declarations plus `Cm_RedisSession` and `MM_Ignition`. |
| Compatibility file | `app/etc/modules/Mage_All.xml` is an empty `<config/>` compatibility file. |
| Non-core modules | `Cm_RedisSession` and `MM_Ignition` are platform dependency declarations from this checkout; no project-specific non-`Mage_*` modules were found. |
| Live local config | No `app/etc/local.xml`; only `local.xml.template` and `local.xml.additional`. |
| Controllers | 192 controller files across 35 controller directories; `Mage_Adminhtml` is the largest controller surface. |
| Routes | 31 frontend/admin front names plus admin route extension declarations. |
| Cron | 26 core cron jobs declared in config XML. |
| Events/observers | More than 200 core event/observer declarations in XML config. |
| API declarations | 37 API-related XML files: `api.xml`, `api2.xml`, `wsdl.xml`, and `wsi.xml`. |
| Setup scripts | 146 SQL setup files and 33 data setup files under core modules. |
| Design packages | Adminhtml `base/default`, `default/default`, `openmage/default`; frontend `base/default`, `rwd/default`; install `default/default`. |
| Skin packages | Skin packages mirror the design areas. |
| Browser assets | Legacy assets under `js/calendar`, `js/extjs`, `js/lib/jquery`, `js/mage`, `js/prototype`, `js/scriptaculous`, and `js/varien`. |
| Shell scripts | `shell/abstract.php`, `shell/indexer.php`, and `shell/log.php`. |
| Tests | Thousands of PHPUnit tests and 45 Cypress E2E specs are present. |

## Local Magento 1.9.4.5 Baseline

| Area | Finding |
| --- | --- |
| Repository | `https://github.com/OpenMage/magento-mirror.git`. |
| Tag | `1.9.4.5`. |
| Local path | `.localdev/magento-mirror-1.9.4.5`. |
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

Run this inventory against the actual project codebase, not only this core checkout.

| Area | Required Output |
| --- | --- |
| Local modules | All modules under `app/code/local`, declaration XML, dependencies, rewrites, events, crons, routes, setup scripts. |
| Community modules | All modules under `app/code/community`, vendor ownership, upgrade path, compatibility risk. |
| Composer packages | Project-specific Composer packages, private packages, patches, replace/conflict rules. |
| Core overrides | Modified core files, copied core classes, rewrites, class preferences, template overrides. |
| Theme overrides | Active package/theme, layout XML, templates, assets, email templates, CMS dependencies. |
| Admin customizations | Custom admin routes, grids, forms, ACL entries, reports, exports, importers. |
| Storefront features | Custom category/product/customer/cart/checkout/CMS behavior and frontend JS. |
| Integrations | Payment, shipping, tax, ERP, PIM, CRM, email, analytics, search, feeds, webhooks. |
| API consumers | REST, SOAP, JSON-RPC, API2 clients, auth method, endpoints used, payload contracts. |
| Cron jobs | All custom cron jobs, schedules, runtime, side effects, failure behavior. |
| Database | Custom tables, triggers, views, stored procedures, EAV attributes, indexes, data volume. |
| Media/files | Media paths, protected files, generated images, imports/exports, file storage mode. |
| Config | `core_config_data`, env variables, per-website/store differences, secrets, admin paths. |
| Operations | Deploy scripts, cache/session backend, queue/cron runner, monitoring, backups, rollback. |

## Inventory Commands

Use these as starting points in the real project checkout:

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
