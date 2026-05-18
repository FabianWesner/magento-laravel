# Compatibility

The modernization preserves behavior unless an explicit decision record says otherwise.

| Contract | Policy |
| --- | --- |
| Database schema | Preserve existing tables, columns, indexes, and EAV data. |
| Storefront UI | Preserve look, layout, assets, and interaction behavior within agreed visual tolerance. |
| Admin UI | Preserve admin workflows, grids, forms, permissions, and operational affordances. |
| URLs | Preserve storefront, admin, and API URLs unless a deployment decision changes them. |
| APIs | Preserve required clients through contract tests. |
| Modules | Inventory each module and choose preserve, bridge, replace, or retire. |
| XML | Existing XML may be read by compatibility tooling during transition; new architecture code must not add XML registration. |
| PHP | Laravel target runs latest stable PHP; Magento baseline PHP `7.4` stays isolated to local verification. |

Each compatibility promise needs automated verification before a migrated route or workflow can be considered complete.
