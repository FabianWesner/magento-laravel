# Dependency Audit Evidence

Evidence captured: 2026-05-19 09:08 CEST.

| Check | Command | Result | Status |
| --- | --- | --- | --- |
| Composer Validate | `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 /Users/fabianwesner/Library/Application Support/Herd/bin/composer validate --no-interaction` from `laravel/` | `./composer.json is valid` | Pass |
| Composer Audit | `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 /Users/fabianwesner/Library/Application Support/Herd/bin/composer audit` from `laravel/` | `No security vulnerability advisories found.` | Pass |
| npm Audit | `npm audit --audit-level=high` from `laravel/` | `found 0 vulnerabilities` | Pass |
| npm Audit | `npm audit --audit-level=high` from `docusaurus/` after Docusaurus dependency remediation | `found 0 vulnerabilities` | Pass |
| Banned Dependency Scan | `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 dev/modernization/validate-removed-technologies.php` | Removed-technology check passed for 142 files. | Pass |
| CI Run | `PATH=/private/tmp/magento-lts-php85-bin:$PATH bash dev/modernization/gate.sh` | Normal modernization gate passed locally with DB-backed fixture/schema checks skipped because `DB_DSN` is unset and browser smoke skipped by sandbox bind limits. | Pass for local gate |

## Banned Runtime Dependency Coverage

The banned dependency scan covers Laravel target files, package manifests, lockfiles, documentation paths, workflow paths, and fixture rejection cases for these legacy technology names:

- Zend
- Laminas
- Magento
- Varien
- Prototype

## Dependency Remediation Notes

The Docusaurus dependency audit initially reported advisory findings through `serialize-javascript` and `webpack`. The approved remediation updated:

- `@docusaurus/core` to `3.10.1`.
- `@docusaurus/preset-classic` to `3.10.1`.
- `webpack` override to `5.106.2`.
- `serialize-javascript` override to `7.0.5`.

Post-remediation verification:

```bash
npm ls @docusaurus/core webpack serialize-javascript --depth=4
npm audit --audit-level=high
npm run build
```

The dependency tree now resolves `@docusaurus/core` `3.10.1`, `webpack` `5.106.2`, and `serialize-javascript` `7.0.5`.
