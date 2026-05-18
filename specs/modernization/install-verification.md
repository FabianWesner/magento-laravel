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
