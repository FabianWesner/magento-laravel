# Schema Preservation Evidence

Generated At: 2026-05-19 11:03 CEST

## Scope

This evidence covers the current Laravel schema-preservation foundation and the retained local Magento sample-data schema signal. It does not approve cutover by itself. DEF-001 and DEF-002 remain open for the project overlay, canonical fixture package, restore proof, media package, and project data inputs.

## Evidence Summary

| Area | Evidence |
| --- | --- |
| Baseline Schema Signature | Local Magento sample-data schema report retained in `specs/modernization/sample-schema-report-evidence.md` with signature `08e8347b5d88af787ad673c71ad689fe1acd3dc0cf79dec68a8feac4ba0a9de6`. |
| Post-Boot Schema Signature | `laravel/tests/Feature/SchemaPreservationFoundationTest.php` compares `SchemaSnapshotRepository` snapshots before and after Laravel boot and asserts the schema signature remains stable. |
| Table Count | The retained sample schema report records `362` tables. |
| EAV Tables | The sample schema report includes catalog, category, customer, address, and attribute EAV tables; the Laravel policy lists those tables in `SchemaPreservationPolicy::eavSignatureTables()`. |
| Core Entity Counts | The PHPUnit foundation test snapshots `catalog_product_entity`, `catalog_category_entity`, `customer_entity`, `sales_flat_order`, and `core_config_data` and asserts stable row-count behavior. |
| Fixture Restore | `SchemaPreservationPolicy::fixtureRestoreCommand()` points restore verification to `bash dev/modernization/fixture-restore-check.sh --fixture=<fixture> --media=<media>`. |
| Local Restore | The local Magento sample schema signal is retained in `specs/modernization/sample-schema-report-evidence.md`; canonical project restore remains tracked by DEF-002. |
| CI Restore | Hosted CI restore evidence remains tracked by DEF-002 and DEF-008; the policy exposes the same restore/checksum commands for CI execution. |
| Migration Review | `dev/modernization/validate-schema-preservation-target.php --final` reviews Laravel migrations for destructive operations on preserved commerce and EAV tables. |
| Infrastructure Tables | `SchemaPreservationPolicy::approvedInfrastructureTables()` isolates approved Laravel infrastructure tables from preserved commerce tables. |
| DB Delta | The PHPUnit foundation test records a before/after side-effect delta on `sales_flat_order` while keeping `catalog_product_entity` unchanged. |
| Project Data | The PHPUnit fixture includes `core_config_data` project-data compatibility coverage; canonical sanitized project data remains tracked by DEF-001 and DEF-002. |
| Media Fixture | Media-package readiness is covered separately by `FixtureMediaReadinessTest` and DEF-002. |
| Status | Development schema-preservation evidence retained; release remains blocked by open final-readiness defects. |

## Key Table Signatures

| Table | Evidence |
| --- | --- |
| `catalog_product_entity` | Sample schema report row: 9 columns, signature `0ce6bcc392130a6e03a9ba103f74b0bfb51d7c67644db0e3c1e7d3f2ad5697d2`. |
| `catalog_category_entity` | Sample schema report row: 10 columns, signature `8501905388bee67aa97c0bb9630ef3d30936d9e4a51dbf2aa3ec8a6ab44f8f41`. |
| `customer_entity` | Sample schema report row: 12 columns, signature `aebb36e123ffc8e48325944fabd30170e0c1a671b8c5284a44c2e056744ba56c`. |
| `customer_address_entity` | Sample schema report row: 8 columns, signature `823634a1c7d2833a838358ef139b69c22609ebcfb61ff03b89317945a18cace3`. |
| `sales_flat_order` | Sample schema report row: 201 columns, signature `8c1783375a130e949d291f724a4dc1ca5b611285e74f61c7c2137321a1df2f9f`. |
| `core_config_data` | Sample schema report row: 6 columns, signature `0cd01f571a70101d9a24755df01e0112df8f839d975b6c406481c045a7709737`. |
| `eav_attribute` | Sample schema report row: 17 columns, signature `8206be20c0769f22a3fd4b3d1e3e44b4074bbb650259349220c80b25b0adc6a4`. |

## EAV Table Coverage

The retained sample schema report includes these EAV value and metadata tables used by the current policy and tests:

- `catalog_product_entity_int`
- `catalog_product_entity_varchar`
- `catalog_product_entity_decimal`
- `catalog_product_entity_text`
- `catalog_product_entity_datetime`
- `catalog_category_entity_int`
- `catalog_category_entity_varchar`
- `catalog_category_entity_decimal`
- `catalog_category_entity_text`
- `catalog_category_entity_datetime`
- `customer_entity_int`
- `customer_entity_varchar`
- `customer_entity_decimal`
- `customer_entity_text`
- `customer_entity_datetime`
- `customer_address_entity_int`
- `customer_address_entity_varchar`
- `customer_address_entity_decimal`
- `customer_address_entity_text`
- `customer_address_entity_datetime`
- `eav_attribute`
- `eav_attribute_set`
- `eav_attribute_group`
- `eav_attribute_option`
- `eav_attribute_option_value`

## Verification

| Command | Result |
| --- | --- |
| `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 artisan test --compact tests/Feature/SchemaPreservationFoundationTest.php` | Passed with 5 tests and 26 assertions. |
| `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 dev/modernization/validate-schema-preservation-target.php --final` | Before this evidence file, failed only because schema-preservation evidence was not present; after this file was added, passed. |
| `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 dev/modernization/markdown-check.php` | Passed for 77 files. |
| `env PATH=/private/tmp/magento-lts-php85-bin:$PATH bash dev/modernization/gate.sh` | Passed in normal no-DB mode; fixture coverage/schema report skipped because `DB_DSN` is unset, and Docusaurus browser smoke skipped because the sandbox cannot bind the local port. |
| `env DB_DSN='mysql:host=127.0.0.1;port=3317;dbname=magento1945' DB_USER=magento DB_PASS=magento /Users/fabianwesner/Library/Application Support/Herd/bin/php85 dev/modernization/schema-report.php --format=json` | Sandbox run failed with `SQLSTATE[HY000] [2002] Operation not permitted`; escalation for the same read-only local schema report was rejected by the approval reviewer, so no new database query result was generated in this turn. |

## Current Boundaries

- This file records current foundation and sample-data schema evidence.
- DEF-001 remains open for the real project overlay.
- DEF-002 remains open for the canonical fixture manifest, restore proof, project database, and media package.
- DEF-008 remains open for hosted CI evidence.
- The final release checklist remains unchecked until every open P0/P1 defect is resolved or explicitly accepted according to the severity policy.
