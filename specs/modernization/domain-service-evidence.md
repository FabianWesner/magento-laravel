# Domain Service Evidence

Evidence captured: 2026-05-19 09:40 CEST.

| Domain | Feature IDs | Service Contract | Fixture | Legacy Snapshot | Laravel Test | DB Delta | Media Artifact | Email Artifact | Import Export | Store Scope | Approved Difference | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Catalog, category, product, media, search, customer, CMS, wishlist, compare, reviews, tags, newsletter, contact, sitemap, RSS, import, export, dataflow, downloadable | SF-001, SF-002, SF-003, SF-004, SF-005, SF-006, SF-010, SF-011, SF-013, SF-014, SF-016, AD-002, AD-003, AD-004, AD-007, AD-009, AD-013, AD-015, AD-017, CB-012, CJ-019, CJ-022, CJ-025 | `DomainServiceContract` is implemented by `DomainQueryService`; `DomainCatalog` defines the domain registry. | `DomainFoundationTest` creates deterministic `domain_facts` rows and in-memory service payloads. | `test_db_snapshot_store_scope_store_view_and_legacy_comparison_side_effects_are_tracked` stores a Magento baseline row and Laravel snapshot row. | `DomainFoundationTest` covers catalog, category, product, media, search, customer, address, wishlist, compare, review, tag, CMS, widget, newsletter, contact, sitemap, URL rewrite, import, export, dataflow, permissions, and all domain feature IDs. | `assertDatabaseHas` verifies the Laravel domain snapshot row for store view `de`. | `MediaStorage` is exercised with `Storage::fake`, traversal rejection, downloadable files, and absent media inspection. | `CommunicationService` uses `Mail::fake` and `Notification::fake` artifacts for send-to-friend and product-alert planning. | `ImportExportDataflow` validates CSV rows and failure behavior. | Domain snapshots include store ID and store view, and `SeoUrlRewrite` resolves localized canonical URLs. | No approved difference is recorded for this Laravel foundation slice; domain parity labels remain explicit for later project baseline comparison. | Pass |

## Verification

```bash
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan test --compact tests/Feature/DomainFoundationTest.php
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 dev/modernization/validate-domain-target.php --final
```

