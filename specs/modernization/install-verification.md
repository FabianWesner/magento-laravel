# Current App Install And Smoke Verification

This file records the local Magento 1 baseline used for modernization preparation.

## Version Decision

Decision on 2026-05-18: use Magento Community Edition / Magento Open Source `1.9.4.5` as the legacy runtime baseline.

Rationale:

- Magento Open Source release notes list `1.9.4.5` as the latest Magento 1 release line entry.
- `OpenMage/magento-mirror` exposes tag `1.9.4.5` and describes it as a pristine copy from `magento.com`.
- The local checkout reports `Mage::getVersion()` as `1.9.4.5`.

Local mirror checkout:

| Field | Value |
| --- | --- |
| Repository | `https://github.com/OpenMage/magento-mirror.git` |
| Tag | `1.9.4.5` |
| Local path | `.localdev/magento-mirror-1.9.4.5` |
| Checked out commit | `98da842ef7aa59b8b8fee642de8b0b6deec55c31` |

## Status

Result on 2026-05-18: local Magento CE `1.9.4.5` smoke install succeeded with sample data.

Verified endpoints:

| Surface | URL | Result |
| --- | --- | --- |
| Storefront home | `http://127.0.0.1:8090/` | HTTP `200 OK`, browser title `Madison Island`, seeded homepage rendered. |
| Storefront category | `http://127.0.0.1:8090/women.html` | Browser title `Women`, seeded category rendered. |
| Storefront product | `http://127.0.0.1:8090/tori-tank-590.html` | Browser title `Tori Tank`, seeded product rendered. |
| Admin login | `http://127.0.0.1:8090/admin` | HTTP `200 OK`, browser title `Log into Magento Admin Page`. |
| Admin dashboard | `http://127.0.0.1:8090/index.php/admin/dashboard/...` | Login succeeded, browser title `Dashboard / Magento Admin`, footer reports `Magento ver. 1.9.4.5`. |

Browser verification was performed through the available Playwright browser automation runtime in Chrome. The runtime loaded storefront pages, loaded the admin login form, submitted the local smoke credentials, reached the dashboard, and reported zero current console warnings or errors after the final storefront smoke page.

Local screenshots were saved under ignored local paths:

- `.localdev/magento1945-storefront-smoke.png`
- `.localdev/magento1945-admin-smoke.png`
- `.localdev/magento1945-playwright-mcp/`

Admin credentials are stored only in `.localdev/magento1-smoke.env`, which is ignored by git.

## What Was Installed

This is a fresh Magento CE `1.9.4.5` install from the mirror, not the real project install.

Installed locally:

- Magento mirror tag `1.9.4.5`.
- Docker services in `.localdev/magento1-compose.yml`.
- PHP-FPM/CLI image `ghcr.io/colinmollenhour/docker-openmage:7.4-*` for legacy Magento 1 runtime compatibility.
- MySQL `5.7` database named `magento1945`.
- Nginx `1.25` on `http://127.0.0.1:8090/`.
- Magento sample data `1.9.2.4` SQL, media, and skin assets.
- Generated ignored Magento local config at `.localdev/magento-mirror-1.9.4.5/app/etc/local.xml`.

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
| Google Chrome | Installed: `Google Chrome 148.0.7778.168`. |
| Docker Desktop | Installed and started manually. |
| Docker daemon | Running with local Magento services. |
| Docker version | `29.0.1`. |
| DDEV | Not installed. |
| MySQL client | Not on `PATH`; MySQL was used through Docker. |
| Project Playwright package | Not installed in this repo; browser verification used the available Playwright automation runtime. |

## Install Commands Used

Clone the Magento mirror tag into ignored local workspace storage:

```bash
git clone --branch 1.9.4.5 --depth 1 https://github.com/OpenMage/magento-mirror.git .localdev/magento-mirror-1.9.4.5
```

Create ignored Docker config files:

```bash
mkdir -p .localdev
```

Use `.localdev/magento1-smoke.env` with local-only values:

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

Start the local Magento stack:

```bash
docker compose -f .localdev/magento1-compose.yml up -d mysql php-fpm nginx
```

Copy sample data media and skin assets into the mirror checkout:

```bash
cp -R var/sample_data/magento-sample-data-1.9.2.4/media/. .localdev/magento-mirror-1.9.4.5/media/
cp -R var/sample_data/magento-sample-data-1.9.2.4/skin/. .localdev/magento-mirror-1.9.4.5/skin/
```

Import sample data SQL:

```bash
docker compose -f .localdev/magento1-compose.yml exec -T mysql mysql magento1945 < var/sample_data/magento-sample-data-1.9.2.4/magento_sample_data_for_1.9.2.4.sql
```

Install Magento:

```bash
docker compose -f .localdev/magento1-compose.yml run --rm cli php install.php \
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
docker compose -f .localdev/magento1-compose.yml run --rm cli php -r "require 'app/Mage.php'; echo Mage::getVersion(), PHP_EOL;"
```

Verify seeded table counts:

```bash
docker compose -f .localdev/magento1-compose.yml exec -T mysql mysql -u magento -pmagento magento1945 -e "select count(*) as products from catalog_product_entity; select count(*) as categories from catalog_category_entity; select count(*) as cms_pages from cms_page;"
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

Once those are available, repeat this install verification against the project workspace and replace this mirror-only smoke result with project smoke evidence.

## Acceptance For This Preparation Step

- Magento CE `1.9.4.5` mirror can be installed locally.
- Sample data is imported and visible in storefront pages.
- Storefront home, category, and product pages respond in Chrome.
- Admin login succeeds in Chrome.
- Admin credentials remain in ignored local files only.
- Project-specific install remains explicitly blocked until project code, DB, and media are available.
