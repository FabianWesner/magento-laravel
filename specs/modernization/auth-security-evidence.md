# Auth Security Evidence

Generated At: 2026-05-19 10:58 CEST

## Scope

This evidence covers the current Laravel auth/session/security foundation. It does not approve final auth/security cutover. ADR 0008 remains `Proposed`, so final auth/security readiness remains blocked by DEF-005 until the route fallback auth/session boundary is approved or revised.

## Feature Coverage

| Feature ID | Coverage |
| --- | --- |
| `SF-010` | Customer Auth, login/logout session boundary, forgot-password broker, password reset broker, and customer session rollback metadata. |
| `AD-001` | Admin Auth, admin guard/session boundary, timeout metadata, login boundary, and denied-admin response behavior. |
| `AD-012` | Permission Matrix coverage for full, partial, read-only, and denied admin roles through `PermissionManifest`. |
| `API-001` | API Auth contract foundation and role visibility through versioned API contract routes. |
| `API-002` | API Auth contract foundation for preserved request/response metadata. |
| `API-003` | OAuth token and admin/customer/guest role contract coverage. |
| `CB-013` | CSRF and Form Key compatibility for characterized legacy flows. |

## Evidence Summary

| Area | Evidence |
| --- | --- |
| Customer Auth | `AuthSecurityFoundationTest` verifies the customer guard, provider, password broker, customer session endpoint, and logout boundary. |
| Admin Auth | The same test verifies the admin guard, provider, password broker, admin session endpoint, timeout metadata, and denied-role response. |
| Permission Matrix | `PermissionManifest` covers full, partial, read-only, and denied admin roles. |
| CSRF | `FormKeyCompatibility` reports missing, invalid, and valid token/form-key states. |
| Form Key | The `/_modernization/auth/csrf-form-key` test path accepts the characterized Magento form-key field for legacy flows. |
| Session | `SessionCookieBoundary`, `CustomerSessionBridge`, and `AdminSessionBridge` expose explicit customer/admin boundaries. |
| Cookie | `auth_compatibility.php` records cookie name, domain, path, secure flag, same-site policy, and logout invalidation. |
| Password Hash | `PasswordHashCompatibility` verifies Laravel hashes and legacy Magento salted-MD5 hashes with rehash planning. |
| Password Reset | Customer and admin password brokers are configured and resolved by the test suite. |
| API Auth | Versioned API contract coverage records OAuth token behavior and admin/customer/guest roles. |
| Legacy Comparison | Current compatibility config uses explicit non-sharing while legacy Magento remains available for comparison. |
| Rollback | Logout and session boundary metadata records `legacy_runtime_fallback`. |
| Status | Foundation evidence retained; ADR 0008 approval and final security review remain open. |

## Verification

| Command | Result |
| --- | --- |
| `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 artisan test --compact tests/Feature/RouteFallbackTest.php tests/Feature/AuthSecurityFoundationTest.php` | Passed with 12 tests and 80 assertions. |
| `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 dev/modernization/validate-auth-security-target.php --final` | Before this evidence file, failed because ADR 0008 is still `Proposed` and auth/security evidence was not present; after this file was added, failed only because ADR 0008 is still `Proposed`. |
| `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 dev/modernization/markdown-check.php` | Passed for 80 files. |
| `env PATH=/private/tmp/magento-lts-php85-bin:$PATH bash dev/modernization/gate.sh` | Passed in normal no-DB mode; fixture coverage/schema report skipped because `DB_DSN` is unset, and Docusaurus browser smoke skipped because the sandbox cannot bind the local port. |

## Current Boundaries

- ADR 0008 remains `Proposed`.
- This evidence does not replace the final security review required by DEF-006.
- This evidence does not approve cross-runtime session sharing; current compatibility uses explicit non-sharing.
- Manual acceptance, cutover readiness, and production readiness remain tracked by DEF-009.
