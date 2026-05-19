# Integration Parity Evidence

Evidence captured: 2026-05-19 09:40 CEST.

| Integration | Feature IDs | Legacy Payload | Laravel Payload | Sandbox | Mock | Outage | Retry | Rollback | Secret Review | Config Path | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Payment gateway | SF-015, API-004 | Test request includes return URL, cancel URL, webhook, IPN, and callback fields matching legacy payment handoff shape. | `IntegrationGateway::request('payment_gateway')` returns context `PaymentGateway`, status, ok state, and outage state. | Enabled in config. | `Http::fake` supplies provider, webhook, IPN callback, and failed payment payload. | 500 response dispatches `RecoverIntegrationOutage`. | Configured retry attempts are inherited from adapter defaults. | `legacy_runtime_fallback` | Secret key is `services.payment_gateway.token`. | `payment/paypal/sandbox` | Pass |
| Shipping carrier | API-005, CJ-002 | Test request includes carrier rate input such as postcode. | `IntegrationGateway::request('shipping_carrier')` returns unavailable-rate outage metadata. | Enabled in config. | `Http::fake` supplies unavailable-rate payload. | 503 response dispatches `RecoverIntegrationOutage`. | Configured retry attempts are inherited from adapter defaults. | `legacy_runtime_fallback` | Secret key is `services.shipping_carrier.api_key`. | `carriers/ups/sandbox` | Pass |
| Currency, analytics, feed, email, ERP, PIM, CRM, webhook, OAuth | SF-016, AD-018, API-006, CJ-004, CJ-017 | Integration tests include legacy payload snapshots for ERP order export and dual-runtime comparison. | Laravel payload snapshot records queued status and integration timeout state. | Enabled for all configured adapters. | `Http::failedConnection` covers ERP failure; config registration covers the remaining adapters. | Failed connection and outage paths dispatch recovery. | Adapter retry is two attempts; recovery job backoff is `[1, 5, 10]`. | `legacy_runtime_fallback` | Secret Review covers token, password, signature, and OAuth secret keys in `config/integrations.php`. | `currency/import/service`, `google/analytics/account`, `google/base/account`, `system/smtp/host`, `project/erp/api`, `project/pim/api`, `project/crm/api`, `project/feed/api`, `payment/webhook/signature`, `oauth/consumer/key` | Pass |

## Verification

```bash
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan test --compact tests/Feature/IntegrationFoundationTest.php
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 dev/modernization/validate-integration-target.php --final
```

