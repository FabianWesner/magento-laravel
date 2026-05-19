# Sample Fixture Coverage Evidence

Generated At: 2026-05-19T08:22:24+00:00
Command: php dev/modernization/fixture-coverage-report.php --format=markdown --fail-on-gaps --evidence specs/modernization/sample-fixture-coverage-evidence.md
Evidence Scope: Local Magento sample data smoke only; this is not canonical project fixture evidence.
Status: Not release-ready

# Fixture Coverage Report

- Database: `magento1945`
- Checks: 14
- Covered: 6
- Gaps: 8

| Area | Feature IDs | Required Coverage | Current Evidence | Status |
| --- | --- | --- | --- | --- |
| Product types | SF-005, CB-002, AD-002 | Simple, virtual, grouped, configurable, bundle, and downloadable products. | Observed bundle: 3; configurable: 81; downloadable: 10; grouped: 3; simple: 492; virtual: 4 | `covered` |
| Websites and stores | SF-012, AD-017 | At least 2 websites, 2 store groups, 3 store views, and 1 disabled store view. | websites: 2; store groups: 2; store views: 4; disabled store views: 0 | `gap` |
| Categories | SF-003, AD-003 | Nested categories at least 4 levels deep, disabled category, and empty category. | categories: 29; max level: 3; disabled: 0; empty: 1 | `gap` |
| Custom options | SF-005, CB-002 | Products with required and optional custom options. | required options: 1; optional options: 3 | `covered` |
| EAV attributes | CB-011, AD-004 | Every backend type and global, website, and store scope coverage. | backend types: datetime: 10; decimal: 12; int: 65; static: 16; text: 19; varchar: 61; scopes: 0: 54; 1: 64; 2: 23 | `covered` |
| Pricing and promotions | CB-003, CB-004, CB-005, AD-008 | Special price, tier price, group price, catalog rule, and cart rule data. | special-price products: 8; tier prices: 4; group prices: 0; catalog rules: 6; cart rules: 11 | `gap` |
| Inventory | CB-009, AD-002 | In-stock, out-of-stock, and backorder product coverage. | stock items: 593; out of stock: 14; backorder-enabled: 0 | `gap` |
| Customers | SF-010, AD-007 | Registered customers, customer groups, and customer addresses. | customers: 53; customer groups: 5; addresses: 53 | `covered` |
| Sales lifecycle | CB-010, AD-005, AD-006 | Orders, invoices, shipments, and credit memos. | orders: 46; invoices: 16; shipments: 12; credit memos: 3 | `covered` |
| Tax and shipping | CB-006, CB-007, AD-016, API-005 | Multiple tax rates/rules and table-rate shipping data. | tax rates: 23; tax rules: 5; table rates: 0 | `gap` |
| Payment and API users | CB-008, API-001, API-002, API-003, API-004, AD-012 | Active payment method config plus SOAP/XML-RPC API users and OAuth consumers. | active payment configs: 3; API users: 0; OAuth consumers: 0 | `gap` |
| CMS and media | SF-002, SF-005, AD-009 | CMS pages, blocks, widgets, and product media gallery references. | CMS pages: 10; CMS blocks: 26; widgets: 1; product media rows: 1253 | `covered` |
| Admin users and roles | AD-001, AD-012 | Full, partial, denied, and API/admin role fixtures. | admin users: 1; admin roles: 2 | `gap` |
| Cron and reports | CJ-001, CJ-002, CJ-003, CJ-004, CJ-005, CJ-006, CJ-007, CJ-008, CJ-009, CJ-010, CJ-011, CJ-012, CJ-013, CJ-014, CJ-015, CJ-016, CJ-017, CJ-018, CJ-019, CJ-020, CJ-021, CJ-022, CJ-023, CJ-024, CJ-025, AD-014 | Cron schedule rows and populated report aggregate tables. | cron rows: 0; selected report aggregate rows: 400 | `gap` |

## Notes

This evidence records the local sample database coverage signal only. It does not close fixture manifest, restore evidence, project overlay, sanitized project DB/media, CI restore, visual, or final release defects.
