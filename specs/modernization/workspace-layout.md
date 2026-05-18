# Project And Core Workspace Layout

The current repository is split into a Magento CE `1.9.4.5` source tree, an overlay placeholder, and the Laravel target app. The modernization work still needs the real project repository or overlay files.

## Recommended Layout

Use sibling directories under one workspace root:

```text
magento-laravel/
|-- core/magento-1.9.4.5/ # Magento CE 1.9.4.5 source checkout
|-- project/              # Real project overlay/application files
|-- laravel/              # Target Laravel app
|-- .localdev/            # Ignored runtime docroot, DB, media, screenshots, reports
|-- docs/                 # Public docs
|-- specs/                # Migration execution specs
`-- dev/                  # Tooling and local runtime scripts
```

Runtime composition copies `core/magento-1.9.4.5/` into `.localdev/magento-docroot/` and then overlays `project/` on top.

The old Magento runtime stays in place for the whole modernization. Laravel is built in parallel, and features are compared side-by-side until the final route cutover is approved.

## Required Inputs

| Input | Required For | Status |
| --- | --- | --- |
| Magento CE 1.9.4.5 source checkout | Source inventory and previous preparation context | Present. |
| Magento CE `1.9.4.5` source checkout | Magento 1 runtime baseline and smoke verification | Present under `core/magento-1.9.4.5/`. |
| Project repository | Custom modules, themes, integrations, deployment scripts | Missing. |
| Project database fixture | EAV attributes, config, store scopes, real data shape | Missing. |
| Project media fixture | Product/category/CMS visuals and file-storage behavior | Missing. |
| Project env/secrets template | Install, API, payment, shipping, search, email smoke tests | Missing. |
| Project dependency credentials | Private Composer packages or registries | Unknown. |

## Inventory Flow

1. Keep `core/magento-1.9.4.5/` as the Magento CE baseline for local smoke checks.
2. Checkout or copy the real project overlay into `project/`.
3. Run `dev/magento/build-docroot.sh` to generate `.localdev/magento-docroot/`.
4. Record remotes, branches, commit SHAs, and Composer lock hashes for all relevant roots.
5. Run `dev/modernization/inventory.php` against core and project roots.
6. Restore the sanitized project DB and media into ignored local paths.
7. Run schema and visual baseline tooling against the project install.
8. Update `specs/modernization/inventory.md` with project-specific facts.

## Side-By-Side Runtime Model

| Runtime | Directory | URL/Port | Data | Purpose |
| --- | --- | --- | --- | --- |
| Magento baseline | `.localdev/magento-docroot/` generated from `core/` plus `project/` | `http://127.0.0.1:8090` in the current Docker setup | Shared restored Magento fixture DB and media | Legacy behavior, screenshots, DB side effects, API responses, cron outputs. |
| Laravel target | `laravel/` | Developer-selected Laravel port, for example `http://127.0.0.1:8013` | Same restored Magento fixture DB and media, plus approved Laravel infrastructure tables if any | New implementation, Livewire UI, PHP module system, no-new-XML extension model. |

Comparison rules:

- Do not remove the Magento runtime while a feature still needs characterization or parity comparison.
- Do not mutate the shared fixture in-place during comparison without restoring it before the second runtime executes the same scenario.
- Prefer a restore-per-scenario workflow for cart, checkout, sales, payment, tax, cron, and report tests.
- Keep generated runtime files in `.localdev/`; do not commit local credentials, admin passwords, database dumps, media caches, screenshots, or reports unless a spec explicitly says an artifact is public and sanitized.
- The project overlay owns project-specific customizations. Core Magento remains the upstream baseline; Laravel owns the target architecture.

## Commands To Run Once Project Access Exists

```bash
git -C /path/to/modernization-workspace/project remote -v
git -C /path/to/modernization-workspace/project status --short
php /path/to/modernization-workspace/magento-laravel/dev/modernization/inventory.php --root=/path/to/modernization-workspace/project --format=markdown
DB_DSN='mysql:host=127.0.0.1;dbname=<project_db>' DB_USER=<user> DB_PASS=<pass> php /path/to/modernization-workspace/magento-laravel/dev/modernization/schema-report.php --format=markdown
```

## Decision

Do not delete this core checkout as part of preparation. Restructure only after the real project repository location is known and both repositories are captured in `specs/modernization/inventory.md`.
