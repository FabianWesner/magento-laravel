# EAV Parity Evidence

Evidence captured: 2026-05-19 09:25 CEST.

| Entity | Store Scope | Fallback | Fixture | Parity Test | Query Count | Status |
| --- | --- | --- | --- | --- | --- | --- |
| Product | `ProductEavRepository::value` accepts a store ID for product attributes. | `test_product_eav_scope_fallback_uses_default_store_value_when_store_value_is_missing` verifies fallback from store `3` to default store value. | `EavAttributeValueReaderTest::seedEavFixtureData` seeds `catalog_product_entity`, `catalog_product_entity_varchar`, and SKU data. | `test_product_eav_store_scope_fallback_matches_legacy_resource_model_behavior` verifies scoped product name behavior. | `expectsDatabaseQueryCount(2)` covers the critical scoped product read. | Pass |
| Category | `CategoryEavRepository::value` accepts a store ID for category attributes. | Category read returns the default value when only store `0` data exists. | `EavAttributeValueReaderTest::seedEavFixtureData` seeds `catalog_category_entity` and `catalog_category_entity_varchar`. | `test_category_customer_and_address_eav_repositories_read_supported_entity_values` verifies category reads. | Covered by repository-level query flow in the same fixture. | Pass |
| Customer | Customer EAV reads are unscoped through `CustomerEavRepository`. | No store fallback applies to the customer fixture path. | `EavAttributeValueReaderTest::seedEavFixtureData` seeds `customer_entity` and `customer_entity_varchar`. | `test_category_customer_and_address_eav_repositories_read_supported_entity_values` verifies customer reads. | Covered by repository-level query flow in the same fixture. | Pass |
| Address | Address EAV reads are unscoped through `AddressEavRepository`. | No store fallback applies to the address fixture path. | `EavAttributeValueReaderTest::seedEavFixtureData` seeds `customer_address_entity` and `customer_address_entity_varchar`. | `test_category_customer_and_address_eav_repositories_read_supported_entity_values` verifies address reads. | Covered by repository-level query flow in the same fixture. | Pass |

## Implementation Artifacts

| Area | Evidence | Status |
| --- | --- | --- |
| Contract | `EavAttributeValueReaderContract` defines the repository access boundary. | Pass |
| Shared reader | `EavAttributeValueReader` resolves EAV metadata, static attributes, and value table reads. | Pass |
| Entity repositories | `ProductEavRepository`, `CategoryEavRepository`, `CustomerEavRepository`, and `AddressEavRepository` delegate reads through the EAV reader contract. | Pass |
| Service provider | `EavServiceProvider` binds the reader contract in the Laravel container. | Pass |
| Direct writes | `validate-eav-target.php --final` scans Laravel app code for direct write calls against Magento EAV value tables. | Pass |

## Verification

```bash
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan test --compact tests/Feature/EavAttributeValueReaderTest.php
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 dev/modernization/validate-eav-target.php --final
```

