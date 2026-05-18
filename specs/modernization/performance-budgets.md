---
tags:
- Development
---

# Performance Budgets

Performance budgets must be based on current baseline measurements. Until baseline capture is complete, no migrated path may be accepted without measuring p50, p95, query count, memory, and cache behavior against the legacy path.

## Baseline Metrics

Capture these for each critical journey:

- p50 latency.
- p95 latency.
- DB query count.
- Slowest queries.
- Peak memory.
- Response size.
- Cache hit/miss behavior.
- External service time.

## Critical Journeys

| Area | Journeys |
| --- | --- |
| Storefront | Home, category, product, search, cart, checkout, customer account, CMS. |
| Admin | Login, dashboard, product grid/edit, category edit, customer grid/edit, order view, config, cache, indexer. |
| API | Required REST/XML-RPC/SOAP/API2 endpoints. |
| EAV | Single entity read, batch product listing, scoped attribute fallback, option labels. |
| Cron/jobs | Indexers, email queue, sitemap, report aggregation, cleanup jobs. |

## Initial Budget Policy

- Migrated p95 latency must not exceed the numeric budget set from the legacy baseline.
- Migrated query count must not exceed the numeric budget set from the legacy baseline.
- Memory usage must not exceed the numeric budget set from the legacy baseline.
- Cached pages must preserve or improve cache behavior.
- Checkout and order placement require dedicated approval because external services can distort numbers.
- No migrated route, job, API, or admin workflow may be accepted until its budget row has an owner, baseline date, numeric target, and exception policy.
- Any exception must include owner, approval date, expiry/revisit date, risk, and compensating control.

## Required Numeric Budgets

| Area | Required Numeric Budget |
| --- | --- |
| HTTP journeys | p50, p95, p99, query count, peak memory, response size, cache hit ratio. |
| APIs | p50, p95, throughput, error rate, response size. |
| Admin grids | p50, p95, query count, peak memory, export runtime, maximum tested row count. |
| EAV reads/writes | single-entity time, batch time, query count, memory, lock wait. |
| Cron/jobs | maximum runtime, peak memory, retry count, duplicate-lock timeout, failure alert threshold. |
| Operations | RTO, RPO, health-check timeout, rollback duration, backup restore duration. |

## Verification

- Automated smoke performance checks in CI for selected fast paths.
- Full staging performance run before release.
- Profiling report for every path that misses budget.
- Performance budget table updated after baseline capture.
