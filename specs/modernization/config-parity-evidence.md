# Config Parity Evidence

Evidence captured: 2026-05-19 09:40 CEST.

| Feature IDs | Config Path | Default Scope | Website Scope | Store Scope | Env Override | Cache | Secret | Admin Save | Legacy Snapshot | Laravel Test | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| SF-012, AD-010, CB-011, CB-013 | `web/unsecure/base_url`, `web/secure/base_url`, `general/locale/code`, `currency/options/base`, `payment/gateway/token`, and `catalog/frontend/list_mode` are seeded in `ScopedConfigTest`. | `core_config_data` default rows cover base URL, secure base URL, locale, secret token, and list mode. | Website scope row covers `currency/options/base` for website `1`. | Store scope row covers `web/unsecure/base_url` and admin list-mode save for store `3`. | `scoped_config.env_overrides` overrides `web/secure/base_url`. | `ConfigCache` key invalidation is asserted after admin save. | `SecretConfig` encrypts and decrypts `payment/gateway/token`. | `AdminConfig::save` covers validation, inherited value handling, backend model handling, and cache invalidation. | `test_default_website_store_fallback_matches_magento_baseline_legacy_comparison_for_config_features` captures the baseline comparison path. | `ScopedConfigTest` covers all config feature IDs and store switch localization/currency/base URL without XML dependency. | Pass |

## Verification

```bash
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan test --compact tests/Feature/ScopedConfigTest.php
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 dev/modernization/validate-config-target.php --final
```

