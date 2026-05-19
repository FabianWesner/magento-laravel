# Route Fallback Evidence

Generated At: 2026-05-19 10:58 CEST

## Scope

This evidence covers the current Laravel route ownership and legacy fallback foundation. It does not approve route fallback for final cutover. ADR 0008 remains `Proposed`, so final route fallback readiness remains blocked by DEF-005 until that boundary is approved or revised.

## Evidence Summary

| Area | Evidence |
| --- | --- |
| Route Ownership | `laravel/config/route_ownership.php` declares `Laravel`, `legacy`, and `bridge` owners with feature metadata and rollback labels. |
| Fallback | `Route::fallback` dispatches unmigrated routes to `LegacyFallbackController` while Laravel-owned modernization routes stay in Laravel. |
| Feature Flag | Route entries include `feature_flag` values such as `route.modernization.laravel`, `route.admin.bridge`, `route.catalog.legacy`, and `route.checkout.bridge`. |
| Store Scope | `RouteFallbackTest` verifies `/de/catalog/product/view/id/100` preserves store code metadata in `X-Store-Code`. |
| URL Rewrite | The same route fallback test keeps the catalog URL path/query intact when redirecting to the legacy base URL. |
| Admin Frontname | Admin route boundary coverage reports the configured admin frontname and bridge owner for `/admin/catalog_product/index`. |
| Session | Customer and admin session boundaries are explicit non-sharing boundaries until route ownership moves to Laravel. |
| CSRF | Checkout/cart POST coverage reports Magento form-key presence while returning bridge/fallback metadata. |
| Password Hash | Password hash behavior is covered in auth/security evidence and the shared ADR 0008 boundary. |
| Rollback | Route ownership entries expose rollback labels, and tests assert Laravel-owned routes keep rollback metadata out of legacy fallback. |
| Log | `LegacyFallbackController` records route fallback decisions through logging context. |
| Test | `laravel/tests/Feature/RouteFallbackTest.php` passed with the auth/security route tests in the targeted run. |
| Status | Foundation evidence retained; ADR 0008 approval remains the final route fallback blocker. |

## Route Matrix

| Pattern | Owner | Feature | Feature Flag | Rollback |
| --- | --- | --- | --- | --- |
| `_modernization/*` | `Laravel` | `ARCH` | `route.modernization.laravel` | `legacy_disabled` |
| `admin/*` | `bridge` | `ADMIN_ROUTE_BOUNDARY` | `route.admin.bridge` | `legacy_admin_frontname` |
| `*/catalog/*` | `legacy` | `CATALOG_URL_REWRITE` | `route.catalog.legacy` | `legacy_catalog` |
| `checkout/*` | `bridge` | `CHECKOUT_FORM_KEY_BOUNDARY` | `route.checkout.bridge` | `legacy_checkout` |

## Verification

| Command | Result |
| --- | --- |
| `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 artisan test --compact tests/Feature/RouteFallbackTest.php tests/Feature/AuthSecurityFoundationTest.php` | Passed with 12 tests and 80 assertions. |
| `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 dev/modernization/validate-route-fallback-target.php --final` | Before this evidence file, failed because ADR 0008 is still `Proposed` and route fallback evidence was not present; after this file was added, failed only because ADR 0008 is still `Proposed`. |
| `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 dev/modernization/markdown-check.php` | Passed for 80 files. |
| `env PATH=/private/tmp/magento-lts-php85-bin:$PATH bash dev/modernization/gate.sh` | Passed in normal no-DB mode; fixture coverage/schema report skipped because `DB_DSN` is unset, and Docusaurus browser smoke skipped because the sandbox cannot bind the local port. |

## Current Boundaries

- ADR 0008 remains `Proposed`.
- Customer and admin session boundaries are explicit non-sharing boundaries in the current foundation.
- This evidence does not replace cross-runtime staging observation, final security review, final visual checks, or manual acceptance.
