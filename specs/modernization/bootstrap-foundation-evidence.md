# Bootstrap Foundation Evidence

Evidence captured: 2026-05-19 09:25 CEST.

| Area | Evidence | Owner | Expiry | Status |
| --- | --- | --- | --- | --- |
| HTTP Kernel | `laravel/bootstrap/app.php` configures routing, middleware, health, and exceptions through Laravel's application builder. | modernization | Final Laravel cutover when route strangler ownership is complete. | Pass |
| CLI | `BootstrapFoundationTest::test_container_service_resolution_works_in_cli_requests` runs `modernization:modules` through Artisan. | modernization | No expiry; Artisan remains the Laravel CLI boundary. | Pass |
| Container Binding | `BootstrapServiceProvider`, `InfrastructureServiceProvider`, and `ModernizationServiceProvider` bind foundation services and contracts. | modernization | No expiry for Laravel-native bindings. | Pass |
| Infrastructure Contract | `CoreInfrastructure` aggregates config, Database, Cache, Session, events, filesystem, URL, auth, translation, and Health Check coverage. | modernization | No expiry for Laravel-native contracts. | Pass |
| Health Check | `/_modernization/bootstrap` is tested through `BootstrapFoundationTest` and reports bootstrap, Database, Cache, Session, and Module Registry status. | modernization | No expiry; health output becomes operational diagnostics. | Pass |
| Runtime Isolation | `RuntimeIsolation::report()` proves Laravel runs as the PHP 8.5 target runtime and is not loaded inside the legacy PHP 7.4 Magento smoke process. | modernization | Final cutover removes the legacy smoke dependency. | Pass |
| Compatibility Adapter | `CompatibilityAdapter::metadata()` records owner metadata, adapter expiry metadata, and no final-release runtime dependency. | modernization | Final Laravel cutover. | Pass |
| Error Handling | `ErrorHandler` produces structured exception context and is resolved from the Laravel container. | modernization | No expiry; moves into production exception policy. | Pass |
| Observability | `Observability::context('bootstrap')` records runtime diagnostics for Laravel bootstrap checks. | modernization | No expiry; expands into production monitoring. | Pass |
| Legacy Smoke | Bootstrap tests assert that no routes are moved during foundation bootstrapping and that legacy behavior remains unchanged for this slice. | modernization | Final cutover after feature traceability and route ownership are complete. | Pass |

## Verification

```bash
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan test --compact tests/Feature/BootstrapFoundationTest.php
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 dev/modernization/validate-bootstrap-target.php --final
```
