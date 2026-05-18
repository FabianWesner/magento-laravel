# Security Spec

This spec defines the security work required for the Laravel modernization.

## Scope

- storefront authentication
- admin authentication
- authorization and permissions
- sessions and cookies
- CSRF
- password hashing and resets
- API authentication
- file uploads
- media/file access
- secrets and environment config
- XSS and output escaping
- SQL injection prevention
- dependency security
- PHP runtime security support

## Requirements

| Area | Requirement | Verification |
| --- | --- | --- |
| Customer auth | Preserve login, logout, reset, session, and account-boundary behavior. | Customer E2E and integration tests. |
| Admin auth | Preserve admin login, timeout, password, and session behavior. | Admin E2E tests. |
| Admin authorization | Every migrated admin route/action has a gate, policy, or permission manifest entry. | Permission tests and route audit. |
| CSRF | Every state-changing web route rejects missing or invalid CSRF tokens. | CSRF tests. |
| Sessions | Cookies use secure flags appropriate for environment; logout invalidates sessions. | Browser and integration tests. |
| Uploads | Uploads validate size, type, extension, path, and filename. | Upload security tests. |
| Media access | Media fallback blocks traversal and private-file leakage. | File access tests. |
| XSS | Blade/Livewire output is escaped by default; trusted HTML is explicit. | Static review and browser tests. |
| SQL safety | Dynamic queries use bindings or vetted query builders. | Static checks and repository tests. |
| Secrets | Secrets are not committed and are read from environment/secret stores. | Secret scan and config review. |
| PHP runtime | Laravel target uses the latest stable PHP branch and does not run new code on unsupported legacy PHP. | `php -v`, Composer platform check, CI matrix, dependency audit. |
| Removed technologies | Laravel target must not depend on Magento/Zend/Varien/XML/layout/block/resource/Prototype-era runtime technologies. | Removed-technology scan and architecture tests. |
| Failure-path safety | Invalid input, denied permissions, expired sessions, integration failures, missing media, and stale cache/index states fail safely without leaking data. | Browser, integration, API, and operations tests. |

## Open Decisions

- Password hash migration strategy. Blocked by ADR 0008.
- Admin permission manifest format. Blocked by ADR 0008 and admin architecture spec.
- API authentication compatibility duration. Blocked by ADR 0008 and API compatibility spec.
- Session sharing between legacy and Laravel during route fallback. Blocked by ADR 0008.

No route fallback implementation may start until ADR 0008 is approved and its cross-runtime auth/session tests are defined.
