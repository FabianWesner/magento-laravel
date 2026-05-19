# Report Parity Evidence

Evidence captured: 2026-05-19 09:40 CEST.

| Report Name | Feature IDs | Legacy Snapshot | Laravel Snapshot | DB Delta | Aggregation Job | Fixture | Filter Coverage | Permission | Export | Performance | Approved Difference | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Sales, tax, shipping, invoiced, refunded, coupon, product, customer, search terms, cart, review, tag, bestseller, low stock | AD-014, AD-008, AD-005, AD-006, CJ-004, CJ-007, CJ-008, CJ-009, CJ-010, CJ-011, CJ-015, CJ-020 | `ReportFoundationTest::test_dual_runtime_legacy_report_comparison_and_all_report_feature_ids_are_tracked` asserts each configured report feature ID for legacy comparison coverage. | `ReportQuery::run` returns deterministic report snapshots from `report_facts`. | `test_report_query_creates_db_snapshot_table_parity_with_date_store_currency_filters` asserts `assertDatabaseHas` after seeded report rows. | `AggregateReportTables` is executed in `test_before_and_after_aggregation_job_behavior_uses_report_table_snapshots`. | The test creates and seeds `report_facts` rows for sales and coupon report paths. | Date range, store ID, and currency filters are asserted against the report query. | `ReportPolicy` allows read-only report users and denies forbidden roles. | `ReportQuery::exportCsv` emits CSV header and values for empty/normal states. | Empty low-stock state covers the large catalog timeout/performance path. | No approved difference is recorded for this Laravel foundation slice; report parity labels remain explicit for later project baseline comparison. | Pass |

## Verification

```bash
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan test --compact tests/Feature/ReportFoundationTest.php
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 dev/modernization/validate-report-target.php --final
```

