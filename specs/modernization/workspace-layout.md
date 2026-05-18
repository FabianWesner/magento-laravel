# Project And Core Workspace Layout

The current root checkout is Magento CE `1.9.4.5` source only. The modernization work still needs a two-repository workspace once the real project repository is available.

## Recommended Layout

Use sibling directories under one workspace root:

```text
modernization-workspace/
|-- magento-core-1.9.4.5/ # Magento CE 1.9.4.5 source checkout
|-- modernization-specs/  # This preparation repo/docs/specs if kept separate
|-- project/            # Real project overlay/application checkout
|-- artifacts/          # Ignored local DB, media, screenshots, performance reports
`-- notes/              # Optional local operator notes
```

The current repository should remain intact until the project repository URL or local path is known. Do not delete or move core files just to force the desired shape; create the sibling project checkout and point inventory tooling at both roots.

## Required Inputs

| Input | Required For | Status |
| --- | --- | --- |
| Magento CE 1.9.4.5 source checkout | Source inventory and previous preparation context | Present. |
| Magento CE `1.9.4.5` source checkout | Magento 1 runtime baseline and smoke verification | Present at the repository root. |
| Project repository | Custom modules, themes, integrations, deployment scripts | Missing. |
| Project database fixture | EAV attributes, config, store scopes, real data shape | Missing. |
| Project media fixture | Product/category/CMS visuals and file-storage behavior | Missing. |
| Project env/secrets template | Install, API, payment, shipping, search, email smoke tests | Missing. |
| Project dependency credentials | Private Composer packages or registries | Unknown. |

## Inventory Flow

1. Keep `magento-laravel/` intact as the preparation repository until the project repository path is known.
2. Keep the repository root as the Magento CE baseline for local smoke checks.
3. Checkout the project into `project/`.
4. Record remotes, branches, commit SHAs, and Composer lock hashes for all relevant roots.
5. Run `dev/modernization/inventory.php` against core and project roots.
6. Restore the sanitized project DB and media into ignored local paths.
7. Run schema and visual baseline tooling against the project install.
8. Update `specs/modernization/inventory.md` with project-specific facts.

## Commands To Run Once Project Access Exists

```bash
git -C /path/to/modernization-workspace/project remote -v
git -C /path/to/modernization-workspace/project status --short
php /path/to/modernization-workspace/magento-laravel/dev/modernization/inventory.php --root=/path/to/modernization-workspace/project --format=markdown
DB_DSN='mysql:host=127.0.0.1;dbname=<project_db>' DB_USER=<user> DB_PASS=<pass> php /path/to/modernization-workspace/magento-laravel/dev/modernization/schema-report.php --format=markdown
```

## Decision

Do not delete this core checkout as part of preparation. Restructure only after the real project repository location is known and both repositories are captured in `specs/modernization/inventory.md`.
