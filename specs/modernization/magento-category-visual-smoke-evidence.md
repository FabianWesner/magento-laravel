# Visual Smoke Evidence

Generated At: 2026-05-19T08:45:00.420Z
Command: node dev/modernization/capture-visual-baseline.mjs --url=http://127.0.0.1:8090/women/new-arrivals.html --out=.localdev/visual-baseline/magento/storefront/SF-CATEGORY/women-new-arrivals-default --runtime=magento --screen-id=SF-CATEGORY --feature-ids=SF-003,SF-004 --role=guest --fixture-id=sample-data --state=category-default --parity-decision=preserve --evidence=specs/modernization/magento-category-visual-smoke-evidence.md
URL: http://127.0.0.1:8090/women/new-arrivals.html
Runtime: magento
Screen ID: SF-CATEGORY
Feature IDs: SF-003,SF-004
Role: guest
Fixture ID: sample-data
State: category-default
Parity Decision: preserve
Status: Local visual smoke only

| Viewport | Size | HTTP Status | Page Title | Artifact Path | Bytes |
| --- | --- | ---: | --- | --- | ---: |
| desktop | 1440x1000 | 200 | New Arrivals - Women | `.localdev/visual-baseline/magento/storefront/SF-CATEGORY/women-new-arrivals-default/desktop.png` | 305573 |
| laptop | 1280x900 | 200 | New Arrivals - Women | `.localdev/visual-baseline/magento/storefront/SF-CATEGORY/women-new-arrivals-default/laptop.png` | 297700 |
| tablet | 768x1024 | 200 | New Arrivals - Women | `.localdev/visual-baseline/magento/storefront/SF-CATEGORY/women-new-arrivals-default/tablet.png` | 269525 |
| mobile | 390x844 | 200 | New Arrivals - Women | `.localdev/visual-baseline/magento/storefront/SF-CATEGORY/women-new-arrivals-default/mobile.png` | 181557 |

## Notes

This evidence records a local screenshot smoke capture only. It is not a complete Magento/Laravel screenshot manifest, visual regression approval, accessibility evidence, manual acceptance, or release evidence.
