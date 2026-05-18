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
| API | Required REST/JSON-RPC/SOAP/API2 endpoints. |
| EAV | Single entity read, batch product listing, scoped attribute fallback, option labels. |
| Cron/jobs | Indexers, email queue, sitemap, report aggregation, cleanup jobs. |

## Initial Budget Policy

- Migrated p95 latency must not exceed legacy p95 by more than the approved tolerance.
- Migrated query count must not exceed legacy query count without documented approval.
- Memory usage must not exceed legacy usage without documented approval.
- Cached pages must preserve or improve cache behavior.
- Checkout and order placement require dedicated approval because external services can distort numbers.

## Verification

- Automated smoke performance checks in CI for selected fast paths.
- Full staging performance run before release.
- Profiling report for every path that misses budget.
- Performance budget table updated after baseline capture.

