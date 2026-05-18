---
tags:
- Development
---

# Preparation Checklist

This checklist covers the 13 preparation areas required before replacing the Magento 1 framework runtime with Laravel.

| # | Area | Status | Required Work |
| --- | --- | --- | --- |
| 1 | MkDocs integration | Prepared | Modernization docs now live under `docs/content/modernization` and need to remain in `mkdocs.yml` navigation. |
| 2 | Actual custom-code inventory | Partially known | Magento CE `1.9.4.5` source is under `core/magento-1.9.4.5/`, and `project/` is reserved for the real project overlay. Run the inventory against the real project overlay, including local/community modules, themes, config, database, media, and integrations. |
| 3 | Compatibility policy | Drafted | Approve which legacy contracts remain compatible and which are intentionally retired. |
| 4 | Feature inventory | Drafted | Complete the feature matrix with project-specific storefront, admin, API, cron, integration, and reporting behavior. |
| 5 | Architecture specs | Drafted structure | Write and approve individual specs before implementation. |
| 6 | Migration backlog | Drafted structure | Convert specs into executable tasks with dependencies, acceptance criteria, and verification commands. |
| 7 | Risk register | Drafted | Assign owners, severity, mitigation, fallback, and decision status. |
| 8 | Proof-of-concept spikes | Drafted | Build Laravel bootstrap, EAV repository, no-XML module, Livewire admin grid, Livewire storefront page, and route fallback spikes. |
| 9 | Visual baseline | Drafted | Capture storefront/admin screenshots across supported breakpoints before UI migration. |
| 10 | Performance budgets | Drafted | Capture current p50/p95, query counts, memory, cache behavior, and set budgets per journey. |
| 11 | Data fixture strategy | Drafted | Version fixtures or sanitized dumps for minimal, seed, multistore, EAV, sales, permissions, and scale data. |
| 12 | Release strategy | Drafted | Choose route-by-route, admin-first, storefront-first, API-first, or parallel cutover model. |
| 13 | Documentation structure | Prepared | Add architecture reasoning, feature guide, module guide, migration status, and operations docs under this section. |

## Completion Gate

Preparation is complete when:

- The real project code and Magento CE `1.9.4.5` source are both available locally.
- The compatibility policy is approved.
- The feature inventory is complete.
- The modernization specs are written and reviewed.
- The test plan is accepted as the definition of done.
- The migration backlog is executable.
- POC results either validate the approach or force a revision.
- Visual and performance baselines exist.
- Fixture data is reproducible.
- Release and rollback strategy has been rehearsed in staging.
