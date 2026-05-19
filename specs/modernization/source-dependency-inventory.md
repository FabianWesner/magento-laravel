# Source And Dependency Inventory

Evidence captured: 2026-05-19 09:08 CEST.

| Field | Evidence |
| --- | --- |
| Repository Root | `/Users/fabianwesner/Herd/magento-lts` |
| Remote URL | `origin https://github.com/FabianWesner/magento-laravel` |
| Branch | `main` |
| Commit SHA | `258d98b36ba5f302dfb10075abf54f655b2b0f9c` |
| Clean Worktree | Yes, before writing this evidence file. |
| Project Overlay | `project/` exists; current checkout overlay status is placeholder-only and remains governed by the Magento baseline gate. |
| Composer Manifest | `laravel/composer.json` |
| Composer Lock Hash | `5d6ece3fe13699c5c9cb31f5cb7de85fd8c22573a6710394eb8511226bd411a2` for `laravel/composer.lock` |
| Composer Repositories | No custom repositories are declared in `laravel/composer.json`. |
| Private Package | No private packages are declared in committed Composer or npm manifests. |
| npm Manifest | `laravel/package.json`; `docusaurus/package.json` |
| npm Lock Hash | `8c054e5a3ab00309571120323ef25c6a061ae74745835a5102a1c4eda2a94bf9` for `laravel/package-lock.json`; `86dd2f7e8083269c78e3d543072e8f36f275c02af8d3ba511ec2e8ddb346e4c2` for `docusaurus/package-lock.json` |
| Node Version | `v22.22.2`; npm `10.9.7` |
| PHP Version | PHP `8.5.5` from Laravel Herd for Laravel target commands. |
| PHP Extensions | `bcmath`, `bz2`, `calendar`, `curl`, `dom`, `fileinfo`, `gd`, `imagick`, `intl`, `json`, `mbstring`, `mysqli`, `openssl`, `PDO`, `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`, `redis`, `soap`, `sqlite3`, `xml`, `zip`, `zlib`, and `Zend OPcache` were present in `php85 -m`. |
| Dependency Audit | Composer, Laravel npm, and Docusaurus npm audit results are recorded in `specs/modernization/dependency-audit-evidence.md`. |
| Banned Dependency Scan | `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 dev/modernization/validate-removed-technologies.php` passed for 142 files. |
| CI Evidence | `.github/workflows/modernization.yml` exists and the local normal modernization gate passed at this commit line; hosted CI run evidence is tracked by the CI readiness final gate. |
| Status | Recorded for source/dependency readiness; project overlay and DB/media inputs remain tracked by their dedicated gates. |

## Repository Roots

- `core/magento-1.9.4.5` remains the Magento CE 1.9.4.5 baseline source.
- `project/` remains the project overlay root.
- `laravel/` contains the Laravel target application, Composer manifest, Composer lockfile, npm manifest, and npm lockfile.
- `docusaurus/` contains the documentation site, npm manifest, and npm lockfile.

## Commands

```bash
git remote -v
git branch --show-current
git rev-parse HEAD
git status --short
shasum -a 256 laravel/composer.lock laravel/package-lock.json docusaurus/package-lock.json
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 -v
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 -m
node -v
npm -v
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 dev/modernization/validate-removed-technologies.php
```
