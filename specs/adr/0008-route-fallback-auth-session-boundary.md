# ADR 0008: Route Fallback Auth And Session Boundary

## Status

Proposed.

## Context

The migration uses a side-by-side Magento CE `1.9.4.5` baseline and Laravel target. Route fallback cannot be production-ready until authentication, session, cookie, CSRF/form-key, password-hash, and rollback behavior are explicit across both runtimes.

## Decision

Before route fallback work begins, write and approve a detailed auth/session boundary spec covering:

- Customer session sharing or explicit non-sharing.
- Admin session sharing or explicit non-sharing.
- Cookie names, domains, paths, secure flags, same-site policy, and lifetime.
- CSRF and Magento form-key compatibility.
- Password hash verification and upgrade strategy.
- Remember-me and persistent cart behavior.
- Logout invalidation across runtimes.
- Rollback behavior when traffic moves between Laravel and Magento.
- Cross-runtime tests for authenticated storefront, admin, cart, checkout, API, and permission-denied flows.

Laravel must run in the Laravel PHP `8.5+` runtime. It must not be loaded inside Magento's PHP `7.4` process to achieve fallback.

## Consequences

- Route fallback is blocked until this boundary is approved.
- Any temporary session bridge must have owner, expiry phase, tests, and security review.
- Final cutover cannot depend on Magento/Zend/Varien session runtime code.
