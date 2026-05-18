# Operations Runbook

This runbook defines the operational requirements for the modernization.

## Local Verification

```bash
dev/modernization/gate.sh
php dev/modernization/inventory.php --format=markdown
php dev/modernization/markdown-check.php
php dev/modernization/validate-removed-technologies.php
npm --prefix docusaurus run build
node dev/modernization/smoke-docusaurus.mjs
```

## Install Verification

Preferred local install path for this core checkout is Docker Compose once Docker is running:

```bash
dev/magento/build-docroot.sh
docker compose -f dev/magento/docker-compose.yml up -d --build mysql php-fpm nginx
docker compose -f dev/magento/docker-compose.yml run --rm cli -r "require 'app/Mage.php'; echo Mage::getVersion(), PHP_EOL;"
```

The legacy Magento 1 verification runtime intentionally uses PHP `7.4` in Docker because Magento CE `1.9.4.5` is not compatible with current PHP. The Laravel modernization target must run on the latest stable PHP, currently PHP `8.5.x` as of 2026-05-18, and CI must fail when the target runtime falls behind the agreed PHP version.

If running Laravel modernization tooling without Docker, use the latest stable PHP and Composer:

```bash
php -v
composer install
```

## Deployment Checks

- app health endpoint responds
- database reachable
- cache reachable
- sessions persist
- scheduler/cron running
- queue worker status visible
- logs writable
- media writable/readable
- scheduler locks prevent duplicate execution
- queue retries and failed-job diagnostics are visible
- external integrations fail safely under timeout/error conditions
- stale cache and stale index recovery commands are rehearsed
- rollback command rehearsed
- removed-technology gate passes for the Laravel target
- Docusaurus user and developer docs are buildable and published

## Rollback

Rollback must preserve the existing Magento database schema. Route-level fallback to legacy remains the preferred rollback path until a migrated route is proven stable.

Rollback rehearsal must include failure-path evidence: failed deployment, failed queue worker, failed scheduler, stale cache/index, failed payment/shipping sandbox, and restored fixture/database/media state.

## Evidence

Record release evidence under a non-committed local directory such as `.localdev/modernization-evidence/`.
