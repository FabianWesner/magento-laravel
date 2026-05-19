# Visual Smoke Evidence

Generated At: 2026-05-19T08:34:40.156Z
Command: node dev/modernization/capture-visual-baseline.mjs --url=http://127.0.0.1:8090/ --out=.localdev/visual-baseline/magento/storefront/SF-HOME/home-default --runtime=magento --screen-id=SF-HOME --feature-ids=SF-001,SF-002 --role=guest --fixture-id=sample-data --state=home-default --parity-decision=preserve --evidence=specs/modernization/magento-home-visual-smoke-evidence.md
URL: http://127.0.0.1:8090/
Runtime: magento
Screen ID: SF-HOME
Feature IDs: SF-001,SF-002
Role: guest
Fixture ID: sample-data
State: home-default
Parity Decision: preserve
Status: Local visual smoke only

| Viewport | Size | HTTP Status | Page Title | Artifact Path | Bytes |
| --- | --- | ---: | --- | --- | ---: |
| desktop | 1440x1000 | 200 | Madison Island | `.localdev/visual-baseline/magento/storefront/SF-HOME/home-default/desktop.png` | 832461 |
| laptop | 1280x900 | 200 | Madison Island | `.localdev/visual-baseline/magento/storefront/SF-HOME/home-default/laptop.png` | 821873 |
| tablet | 768x1024 | 200 | Madison Island | `.localdev/visual-baseline/magento/storefront/SF-HOME/home-default/tablet.png` | 490539 |
| mobile | 390x844 | 200 | Madison Island | `.localdev/visual-baseline/magento/storefront/SF-HOME/home-default/mobile.png` | 241288 |

## Notes

This evidence records a local screenshot smoke capture only. It is not a complete Magento/Laravel screenshot manifest, visual regression approval, accessibility evidence, manual acceptance, or release evidence.
