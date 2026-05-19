# API Contract Evidence

Evidence captured: 2026-05-19 09:25 CEST.

| Feature ID | Protocol | OAuth / Auth Boundary | Legacy Response | Error Format | Status |
| --- | --- | --- | --- | --- | --- |
| API-001 | SOAP | Legacy API role boundary represented in `LegacyApiContractRepository`. | `ApiContractFoundationTest::test_soap_and_xml_rpc_contracts_are_available_for_legacy_response_comparison` verifies the SOAP inventory row. | Versioned JSON error envelope is covered by the same API controller surface. | Pass |
| API-002 | XML-RPC | Legacy API role boundary represented in `LegacyApiContractRepository`. | `ApiContractFoundationTest::test_soap_and_xml_rpc_contracts_are_available_for_legacy_response_comparison` verifies the XML-RPC inventory row. | Versioned JSON error envelope is covered by the same API controller surface. | Pass |
| API-003 | REST/API2 | `ApiContractFoundationTest::test_rest_api2_oauth_roles_and_error_formats_have_status_codes` verifies OAuth token metadata for admin role, customer role, and guest role. | `/api/v1/contracts/API-003` returns the versioned REST/API2 contract resource. | Not-found and read-only method responses include `error.code` and `error.status`. | Pass |
| API-004 | Payment integration | `ExternalIntegrationProbe` records retry and timeout behavior for the payment adapter boundary. | Mocked payment probe returns an explicit adapter health payload. | Probe output records status code, retry count, timeout, and ok state. | Pass |
| API-005 | Shipping integration | `ExternalIntegrationProbe` records retry and timeout behavior for the shipping adapter boundary. | Mocked shipping probe returns an explicit adapter health payload. | Probe output records status code, retry count, timeout, and ok state. | Pass |
| API-006 | External service integration | `IntegrationFoundationTest` covers configured external service adapter IDs and outage recovery flow. | Integration adapters expose deterministic contract metadata for parity comparison. | Outage recovery job and adapter probe metadata cover retry, timeout, and failure mapping. | Pass |

## Implementation Artifacts

| Area | Evidence | Status |
| --- | --- | --- |
| Versioned routes | `laravel/routes/api.php` exposes `Route::apiResource('contracts', LegacyApiContractController::class)` under `/api/v1`. | Pass |
| Controller | `laravel/app/Http/Controllers/Api/LegacyApiContractController.php` serves list/show operations and read-only method errors. | Pass |
| Request validation | `laravel/app/Http/Requests/Api/LegacyApiContractIndexRequest.php` validates protocol, role, feature ID, and version filters. | Pass |
| Resource serialization | `laravel/app/Http/Resources/LegacyApiContractResource.php` serializes contract metadata and versioned response data. | Pass |
| Inventory repository | `laravel/app/Modernization/Api/LegacyApiContractRepository.php` contains SOAP, XML-RPC, REST/API2, payment, shipping, and external service contract rows. | Pass |
| OpenAPI | `laravel/openapi.yaml` documents `/contracts`, `/contracts/{contract}`, security, responses, version, and API-001 through API-006. | Pass |

## Verification

```bash
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan test --compact tests/Feature/ApiContractFoundationTest.php
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 dev/modernization/validate-api-target.php --final
```

