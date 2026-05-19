# Module System Evidence

Evidence captured: 2026-05-19 09:25 CEST.

| Area | Evidence | Test | Status |
| --- | --- | --- | --- |
| Manifest | `laravel/config/modernization.php` defines the PHP-first foundation Manifest with name, version, dependencies, providers, routes, commands, events, listeners, permissions, config, views, jobs, policies, and feature IDs. | `ModernizationModuleRegistryTest::test_module_registry_resolves_from_php_config` | Pass |
| Service Provider | `ModernizationServiceProvider` binds `ModuleRegistryContract`, registers the module Policy, maps the Event listener, and exposes module diagnostics. | `ModernizationModuleRegistryTest::test_module_registry_resolves_from_php_config` | Pass |
| Policy | `ModuleRegistryPolicy` authorizes local diagnostics for the module registry surface. | `ModernizationModuleRegistryTest::test_module_registry_policy_allows_local_diagnostics` | Pass |
| Event | `ModuleRegistryChecked` carries module health context for diagnostics and jobs. | `VerifyModuleRegistryTest::test_job_dispatches_module_registry_health_event` | Pass |
| Job | `VerifyModuleRegistry` dispatches registry health events from the queue boundary. | `VerifyModuleRegistryTest::test_job_dispatches_module_registry_health_event` | Pass |
| Config | `ModuleRegistry::fromConfig` builds manifests from PHP config and reports duplicate, missing dependency, and cycle diagnostics. | `ModernizationModuleRegistryTest::test_module_registry_reports_invalid_manifest_configuration` and `test_module_registry_reports_dependency_cycle_diagnostics` | Pass |
| Contract | `ModuleRegistryContract` is bound to `ModuleRegistry` and resolved from the Laravel container. | `ModernizationModuleRegistryTest::test_module_registry_resolves_from_php_config` | Pass |
| Route and command | `/_modernization/modules` and `modernization:modules` expose registry diagnostics. | `ModernizationModuleRegistryTest::test_module_registry_diagnostic_route_returns_health_and_manifests` and `test_module_registry_console_diagnostic_lists_enabled_modules` | Pass |

## Verification

```bash
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan test --compact tests/Feature/ModernizationModuleRegistryTest.php tests/Feature/Jobs/Modernization/Modules/VerifyModuleRegistryTest.php tests/Feature/Listeners/Modernization/Modules/RecordModuleRegistryCheckTest.php
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 dev/modernization/validate-module-target.php --final
```

