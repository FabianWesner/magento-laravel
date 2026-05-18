# Operations Runbook

This runbook defines the operational requirements for the modernization.

## Local Verification

```bash
php dev/modernization/gate.sh
php dev/modernization/inventory.php --format=markdown
php dev/modernization/markdown-check.php
```

## Install Verification

Preferred local install path for this core checkout is Docker Compose once Docker is running:

```bash
cd dev/openmage
docker compose up -d mysql redis
docker compose run --rm -u $(id -u):$(id -g) cli composer install
docker compose run --rm cli php -f install.php -- --get_options
```

If running without Docker, use PHP 8.2 and a reachable MySQL database:

```bash
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php82 /Users/fabianwesner/Library/Application\ Support/Herd/bin/composer install
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php82 -f install.php -- --license_agreement_accepted yes ...
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
- rollback command rehearsed

## Rollback

Rollback must preserve the existing Magento database schema. Route-level fallback to legacy remains the preferred rollback path until a migrated route is proven stable.

## Evidence

Record release evidence under a non-committed local directory such as `.localdev/modernization-evidence/`.

