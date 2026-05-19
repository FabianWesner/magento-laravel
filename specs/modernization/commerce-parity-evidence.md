# Commerce Parity Evidence

Evidence captured: 2026-05-19 09:40 CEST.

| Feature IDs | Legacy Characterization | Laravel Test | DB Delta | Fixture | Edge Case | Failure Path | Concurrency | Rollback | Approved Difference | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| CB-001, CB-002, CB-003, CB-004, CB-005, CB-006, CB-007, CB-008, CB-009, CB-010, CB-011, CB-012, CB-013, CB-014 | `CommerceCalculator::snapshot` stores legacy result labels for quote, cart, totals, pricing, promotion, tax, shipping, and payment collector comparison. | `CommerceFoundationTest` covers all commerce feature IDs, quote/cart/totals, product types, pricing, rules, tax, shipping, payment, order, invoice, shipment, credit memo, refund, inventory, index, cache, session, and email queue contexts. | `CommerceSnapshotRepository` records DB snapshot rows into the `commerce_facts` fixture table and the test asserts `assertDatabaseHas`. | The test creates a deterministic `commerce_facts` fixture table and item payloads for totals and quote snapshots. | Invalid quantity, permission denial, duplicate concurrency, stale cache, stale index, failed payment, unavailable shipping, and recovery states are asserted. | Payment outage, shipping unavailable state, and side-effect replay are covered with `Http::fake` and `Queue::fake`. | `CommerceTransactionPolicy::runIdempotent` uses a cache lock and duplicate policy for quote snapshots. | Snapshot payloads use `legacy_runtime_fallback`, and `ReplayCommerceSideEffects` carries rollback metadata. | No approved difference is recorded for this Laravel foundation slice; parity labels remain explicit for later project baseline comparison. | Pass |

## Verification

```bash
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan test --compact tests/Feature/CommerceFoundationTest.php
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 dev/modernization/validate-commerce-target.php --final
```

