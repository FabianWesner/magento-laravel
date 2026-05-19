# Cron Job Evidence

Evidence captured: 2026-05-19 09:40 CEST.

| Feature IDs | Schedule List | Queue Policy | Locking | Retry | Failure Log | Idempotency | Report Snapshot | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| CJ-001, CJ-002, CJ-003, CJ-004, CJ-005 | `config/cron_jobs.php` maps backup, currency rate, customer token cleanup, PayPal reports, and log cleanup to legacy models and decisions. | `modernization:cron-status --dispatch` dispatches `CaptureCronParitySnapshot`. | `routes/console.php` schedule entry uses `withoutOverlapping` and `onOneServer`. | `CaptureCronParitySnapshot` uses `[60, 300, 900]` backoff and three tries. | `CaptureCronParitySnapshot::failed` writes structured log context. | Unique job ID `modernization-cron-parity-snapshot` covers repeat snapshot requests. | `CronJobSchedulerTest` asserts the snapshot job receives the CJ feature ID list. | Pass |
| CJ-006, CJ-007, CJ-008, CJ-009, CJ-010 | `config/cron_jobs.php` maps quote cleanup and sales aggregation schedules. | Same queue snapshot boundary. | Same scheduler lock controls. | Same retry controls. | Same failure log controls. | Same unique job controls. | Report-table snapshot expectation is asserted in `test_all_cj_feature_ids_have_scheduler_mapping_and_report_table_snapshot_expectation`. | Pass |
| CJ-011, CJ-012, CJ-013, CJ-014, CJ-015 | `config/cron_jobs.php` maps bestseller aggregation, persistent sessions, XmlConnect, catalog rules, and coupon reports. | Same queue snapshot boundary. | Same scheduler lock controls. | Same retry controls. | Same failure log controls. | Same unique job controls. | Feature list and legacy model keys are asserted by `CronJobSchedulerTest`. | Pass |
| CJ-016, CJ-017, CJ-018, CJ-019, CJ-020 | `config/cron_jobs.php` maps cache cleanup, email send, email cleanup, product alerts, and tax reports. | Same queue snapshot boundary. | Same scheduler lock controls. | Same retry controls. | Same failure log controls. | Same unique job controls. | Feature list and report snapshot coverage are asserted by `CronJobSchedulerTest`. | Pass |
| CJ-021, CJ-022, CJ-023, CJ-024, CJ-025 | `config/cron_jobs.php` maps reindex, newsletter, captcha cleanup, and sitemap generation. | Same queue snapshot boundary. | Same scheduler lock controls. | Same retry controls. | Same failure log controls. | Same unique job controls. | `modernization:cron-status` output is asserted for first and last tracked jobs. | Pass |

## Verification

```bash
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan test --compact tests/Feature/CronJobSchedulerTest.php
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 dev/modernization/validate-cron-job-target.php --final
```

