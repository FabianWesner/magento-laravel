# Visual Smoke Evidence

Generated At: 2026-05-19T08:42:39.558Z
Command: node dev/modernization/capture-visual-baseline.mjs --url=http://127.0.0.1:8090/admin --out=.localdev/visual-baseline/magento/admin/AD-DASHBOARD/dashboard-default --runtime=magento --screen-id=AD-DASHBOARD --feature-ids=AD-001 --role=admin-full --fixture-id=sample-data --state=dashboard-default --parity-decision=preserve --magento-admin-login=1 --evidence=specs/modernization/magento-admin-dashboard-visual-smoke-evidence.md
URL: http://127.0.0.1:8090/admin
Runtime: magento
Screen ID: AD-DASHBOARD
Feature IDs: AD-001
Role: admin-full
Fixture ID: sample-data
State: dashboard-default
Parity Decision: preserve
Status: Local visual smoke only

| Viewport | Size | HTTP Status | Page Title | Artifact Path | Bytes |
| --- | --- | ---: | --- | --- | ---: |
| desktop | 1440x1000 | 200 | Dashboard / Magento Admin | `.localdev/visual-baseline/magento/admin/AD-DASHBOARD/dashboard-default/desktop.png` | 162786 |
| laptop | 1280x900 | 200 | Dashboard / Magento Admin | `.localdev/visual-baseline/magento/admin/AD-DASHBOARD/dashboard-default/laptop.png` | 159779 |
| tablet | 768x1024 | 200 | Dashboard / Magento Admin | `.localdev/visual-baseline/magento/admin/AD-DASHBOARD/dashboard-default/tablet.png` | 163749 |
| mobile | 390x844 | 200 | Dashboard / Magento Admin | `.localdev/visual-baseline/magento/admin/AD-DASHBOARD/dashboard-default/mobile.png` | 162446 |

## Notes

This evidence records a local screenshot smoke capture only. It is not a complete Magento/Laravel screenshot manifest, visual regression approval, accessibility evidence, manual acceptance, or release evidence.
