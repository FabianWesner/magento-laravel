# Visual Smoke Evidence

Generated At: 2026-05-19T08:38:10.394Z
Command: node dev/modernization/capture-visual-baseline.mjs --url=http://127.0.0.1:8090/admin --out=.localdev/visual-baseline/magento/admin/AD-LOGIN/login-default --runtime=magento --screen-id=AD-LOGIN --feature-ids=AD-001 --role=admin-anonymous --fixture-id=sample-data --state=login-default --parity-decision=preserve --evidence=specs/modernization/magento-admin-login-visual-smoke-evidence.md
URL: http://127.0.0.1:8090/admin
Runtime: magento
Screen ID: AD-LOGIN
Feature IDs: AD-001
Role: admin-anonymous
Fixture ID: sample-data
State: login-default
Parity Decision: preserve
Status: Local visual smoke only

| Viewport | Size | HTTP Status | Page Title | Artifact Path | Bytes |
| --- | --- | ---: | --- | --- | ---: |
| desktop | 1440x1000 | 200 | Log into Magento Admin Page | `.localdev/visual-baseline/magento/admin/AD-LOGIN/login-default/desktop.png` | 51812 |
| laptop | 1280x900 | 200 | Log into Magento Admin Page | `.localdev/visual-baseline/magento/admin/AD-LOGIN/login-default/laptop.png` | 49708 |
| tablet | 768x1024 | 200 | Log into Magento Admin Page | `.localdev/visual-baseline/magento/admin/AD-LOGIN/login-default/tablet.png` | 47997 |
| mobile | 390x844 | 200 | Log into Magento Admin Page | `.localdev/visual-baseline/magento/admin/AD-LOGIN/login-default/mobile.png` | 46035 |

## Notes

This evidence records a local screenshot smoke capture only. It is not a complete Magento/Laravel screenshot manifest, visual regression approval, accessibility evidence, manual acceptance, or release evidence.
