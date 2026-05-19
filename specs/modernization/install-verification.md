# Current App Install And Smoke Verification

This file records the local Magento 1 baseline used for modernization preparation.

## Version Decision

Decision on 2026-05-18: use Magento Community Edition / Magento Open Source `1.9.4.5` as the legacy runtime baseline.

Rationale:

- Magento `1.9.4.5` is the last Magento 1 release line used by this project.
- The checked-out source reports `Mage::getVersion()` as `1.9.4.5`.
- The modernization target is Laravel on the latest stable PHP. The legacy Magento 1 baseline is verified in an isolated PHP `7.4` container only because Magento 1.9.4.5 is not compatible with current PHP releases.

Local source checkout:

| Field | Value |
| --- | --- |
| Package | Magento CE / Magento Open Source |
| Tag | `1.9.4.5` |
| Checked out commit | `98da842ef7aa59b8b8fee642de8b0b6deec55c31` |
| Source root | `core/magento-1.9.4.5/` |
| Runtime document root | `.localdev/magento-docroot/` |

## Status

Result on 2026-05-18: local Magento CE `1.9.4.5` smoke install succeeded with sample data.

Verified endpoints:

| Surface | URL | Result |
| --- | --- | --- |
| Storefront home | `http://127.0.0.1:8090/` | HTTP `200 OK`, browser title `Madison Island`, seeded homepage rendered. |
| Storefront category | `http://127.0.0.1:8090/women.html` | Browser title `Women`, seeded category rendered. |
| Storefront product | `http://127.0.0.1:8090/tori-tank-590.html` | Browser title `Tori Tank - Women`, seeded product rendered. |
| Admin login | `http://127.0.0.1:8090/admin` | HTTP `200 OK`, browser title `Log into Magento Admin Page`. |
| Admin dashboard | `http://127.0.0.1:8090/index.php/admin/dashboard/...` | Login succeeded, browser title `Dashboard / Magento Admin`, footer reports `Magento ver. 1.9.4.5`. |

Browser verification was performed through the available Playwright browser automation runtime in Chrome. The runtime loaded storefront pages, loaded the admin login form, submitted the local smoke credentials, reached the dashboard, and reported zero console warnings or errors after the final check.

Current recheck on 2026-05-18:

| Check | Result |
| --- | --- |
| Docker services | `magento-nginx-1`, `magento-php-fpm-1`, and `magento-mysql-1` are running. |
| MySQL health | Container reports `healthy`. |
| Storefront curl smoke | `http://127.0.0.1:8090/` returns HTTP `200`, `text/html`, and seeded HTML. |
| Admin curl smoke | `http://127.0.0.1:8090/admin/` returns HTTP `200`. |
| Magento version | Container bootstrap reports `1.9.4.5`. |
| Runtime PHP | Magento container uses PHP `7.4.33`; Laravel root artisan proxy uses Herd PHP `8.5.5`. |
| Generated docroot | `.localdev/magento-docroot/` matches `core/magento-1.9.4.5/` excluding generated local runtime files while `project/` is still placeholder-only. |
| Sample schema report | Escalated `DB_DSN='mysql:host=127.0.0.1;port=3317;dbname=magento1945' DB_USER=magento DB_PASS=magento php dev/modernization/schema-report.php --format=markdown` reports 362 tables and schema signature `08e8347b5d88af787ad673c71ad689fe1acd3dc0cf79dec68a8feac4ba0a9de6`. |
| Playwright browser smoke | Main session Playwright navigation succeeded for storefront home and admin dashboard; screenshots were moved to `.localdev/magento-storefront-home-2026-05-18.png` and `.localdev/magento-admin-dashboard-2026-05-18.png`. |
| Repo Playwright scripts | Blocked until a local Playwright package is installed; `dev/modernization/*.mjs` imports `playwright`, but this repo currently has no root `node_modules/playwright`. |

Next command once a local Playwright package is installed:

```bash
node dev/modernization/capture-visual-baseline.mjs --url=http://127.0.0.1:8090/ --out=.localdev/visual-baseline/magento/storefront/home
```

Local smoke screenshots are ignored by git and stored at:

- `.localdev/magento-storefront-smoke.png`
- `.localdev/magento-admin-smoke.png`

Admin credentials are stored only in `.localdev/magento-smoke.env`, which is ignored by git.

## What Was Installed

This is a fresh Magento CE `1.9.4.5` install from the Magento CE source tree, not the real project install.

Installed locally:

- Magento CE source tag `1.9.4.5` in `core/magento-1.9.4.5/`.
- Runtime document root generated at `.localdev/magento-docroot/`.
- Docker services in `dev/magento/docker-compose.yml`.
- PHP-FPM/CLI runtime built from `php:7.4-fpm` for legacy Magento 1 verification only.
- MySQL `5.7` database named `magento1945`.
- Nginx `1.25` on `http://127.0.0.1:8090/`.
- Magento sample data `1.9.2.4` SQL, media, and skin assets.
- Generated ignored Magento local config at `.localdev/magento-docroot/app/etc/local.xml`.

Seed verification:

| Data set | Count |
| --- | ---: |
| Products | 593 |
| Categories | 29 |
| CMS pages | 10 |

Not installed:

- Real project overlay.
- Project database.
- Project media.
- Project private Composer packages.
- Project environment secrets.
- Project admin users or production-like seed data.

## Local Environment Notes

Observed local tools:

| Tool | Status |
| --- | --- |
| Docker Desktop | Installed and running. |
| Docker version | `29.0.1`. |
| MySQL client | Not on `PATH`; MySQL was used through Docker. |
| Project Playwright package | Not installed in this repo; browser verification used the available Playwright automation runtime. |
| Host PHP | PHP `8.4.17` observed locally; target modernization must upgrade to the latest stable PHP, currently PHP `8.5.x` as of 2026-05-18. |

## Laravel Boost MCP Status

Laravel Boost is installed in the parallel Laravel app and the repository root `artisan` proxy reaches it.

Verified on 2026-05-18:

| Check | Result |
| --- | --- |
| `boost:mcp` command | Present in `php artisan list --raw`. |
| MCP config | `.codex/config.toml`, `.mcp.json`, `.cursor/mcp.json`, and `.junie/mcp/mcp.json` point to the absolute repository root `artisan` proxy. |
| Manual MCP initialize | Succeeds over stdio and reports server `Laravel Boost`. |
| Manual MCP `tools/list` | Succeeds and returns Boost tools including `application-info`, `database-schema`, `database-query`, `search-docs`, `read-log-entries`, and `tinker`. |
| Manual MCP `application-info` | Reports PHP `8.5`, Laravel `13.9.0`, Laravel Boost `2.4.7`, and Laravel MCP `0.7.0`. |
| Manual MCP `search-docs` | Succeeds for query `routing` when network access to `boost.laravel.com` is available; the restricted sandbox attempt failed with DNS resolution before the escalated retry passed. |
| Manual MCP `database-schema` | Succeeds in summary mode against the Laravel SQLite database. |
| Manual MCP `database-query` | Succeeds for read-only `select 1 as ok` and returns `[{"ok":1}]`. |
| `php artisan boost:install --no-interaction` | Succeeds through the repository root artisan proxy and writes agent/MCP configuration for Laravel Boost. |
| Composer PHP platform | `laravel/composer.json` requires PHP `^8.5` and sets Composer platform PHP `8.5.5`; default Composer setup scripts do not auto-run database migrations. |

If the active Codex session still shows no Laravel Boost tools, restart or reload the client so it reads `.codex/config.toml`; the server itself is working over stdio.

## Docusaurus Documentation Status

Docusaurus is installed under `docusaurus/` for the full user and developer documentation site.

Verified on 2026-05-18:

| Check | Result |
| --- | --- |
| Dependencies | `npm install --prefix docusaurus --no-audit --no-fund` completed and created `docusaurus/package-lock.json`. |
| Build | `npm --prefix docusaurus run build` passed after pinning Docusaurus, React, and Webpack versions. |
| Browser smoke script | `node dev/modernization/smoke-docusaurus.mjs` verifies `/`, `/user/`, and `/developer/` with Playwright/Chrome. |
| Static smoke | Built site served locally and returned HTTP `200` for `/`, `/user/`, and `/developer/`. |
| Browser smoke | Chrome/Playwright loaded `/user/` with title `User Documentation | Magento Laravel Modernization`. |
| Browser smoke | Chrome/Playwright loaded `/developer/` with title `Developer Documentation | Magento Laravel Modernization`. |
| Browser console | No warnings or errors were reported after Docusaurus browser verification. |

Retained evidence recorded on 2026-05-19:

| Check | Evidence |
| --- | --- |
| Browser smoke | `specs/modernization/docusaurus-browser-smoke-evidence.md` records an escalated Playwright/Chrome smoke run for `/`, `/user/`, and `/developer/`. |
| Browser console | The retained evidence reports no browser console warnings or errors. |
| Sandbox note | The final gate can still fail the browser smoke step inside the restricted sandbox because the script cannot bind `127.0.0.1:3012`; run the same smoke command outside the sandbox to refresh retained evidence. |

The Docusaurus static build output remains ignored under `docusaurus/build/`; source docs and lockfile are committed.

## Install Commands

Start the local Magento stack:

```bash
dev/magento/build-docroot.sh
docker compose -f dev/magento/docker-compose.yml up -d --build mysql php-fpm nginx
```

Use `.localdev/magento-smoke.env` with local-only values:

```dotenv
BASE_URL=http://127.0.0.1:8090/
DB_NAME=magento1945
DB_USER=magento
DB_PASS=magento
DB_HOST=mysql
ADMIN_EMAIL=admin@example.com
ADMIN_USERNAME=admin
ADMIN_PASSWORD=<local-only-password>
```

Download and unpack sample data into `.localdev/sample-data/`, then copy media and skin assets into the Magento source root:

```bash
cp -R .localdev/sample-data/magento-sample-data-1.9.2.4/media/. core/magento-1.9.4.5/media/
cp -R .localdev/sample-data/magento-sample-data-1.9.2.4/skin/. core/magento-1.9.4.5/skin/
dev/magento/build-docroot.sh
```

Import sample data SQL:

```bash
docker compose -f dev/magento/docker-compose.yml exec -T mysql mysql magento1945 < .localdev/sample-data/magento-sample-data-1.9.2.4/magento_sample_data_for_1.9.2.4.sql
```

Install Magento:

```bash
docker compose -f dev/magento/docker-compose.yml run --rm cli install.php \
  --license_agreement_accepted yes \
  --locale en_US \
  --timezone Europe/Berlin \
  --default_currency EUR \
  --db_host mysql \
  --db_name magento1945 \
  --db_user magento \
  --db_pass magento \
  --url http://127.0.0.1:8090/ \
  --use_rewrites yes \
  --use_secure no \
  --secure_base_url http://127.0.0.1:8090/ \
  --use_secure_admin no \
  --enable_charts yes \
  --skip_url_validation \
  --admin_firstname Magento \
  --admin_lastname User \
  --admin_email admin@example.com \
  --admin_username admin \
  --admin_password '<local-only-password>' \
  --session_save files
```

Verify installed version:

```bash
docker compose -f dev/magento/docker-compose.yml run --rm cli -r "require 'app/Mage.php'; echo Mage::getVersion(), PHP_EOL;"
```

Verify seeded table counts:

```bash
docker compose -f dev/magento/docker-compose.yml exec -T mysql mysql -u magento -pmagento magento1945 -e "select count(*) as products from catalog_product_entity; select count(*) as categories from catalog_category_entity; select count(*) as cms_pages from cms_page;"
```

Smoke check with curl:

```bash
curl --silent --show-error --fail --max-time 20 -I http://127.0.0.1:8090/
curl --silent --show-error --fail --max-time 20 -I http://127.0.0.1:8090/admin
```

Install local Playwright support for the repository scripts if the external browser automation runtime is unavailable:

```bash
npm init -y
npm install --save-dev playwright
npx playwright install chromium
```

## Project Install Blocker

The real project cannot be installed yet because this checkout still does not contain:

- The project repository or overlay.
- The project database dump or sanitized fixture.
- Project media files.
- Project secrets and integration credentials.
- Project-specific Composer repositories or package credentials, if any.

Once those are available, repeat this install verification against the generated project-overlaid runtime and replace this source-only smoke result with project smoke evidence.

## Acceptance For This Preparation Step

- Magento CE `1.9.4.5` can be installed locally.
- Sample data is imported and visible in storefront pages.
- Storefront home, category, and product pages respond in Chrome.
- Admin login succeeds in Chrome.
- Admin credentials remain in ignored local files only.
- Project-specific install remains explicitly blocked until project code, DB, and media are available.
