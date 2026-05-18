# Goal Prompt For Laravel Modernization Preparation

Use this prompt with the goal feature.

## Ready-To-Paste Prompt

```text
You are working in the Magento 1 modernization repository.

Objective:
Prepare the project for a long-running modernization from Magento 1 to Laravel. The legacy runtime baseline is Magento CE / Magento Open Source `1.9.4.5`, checked out from `https://github.com/OpenMage/magento-mirror` tag `1.9.4.5`. The target architecture replaces the Zend/Magento framework runtime with Laravel, uses Livewire for storefront/admin UI implementation, keeps the existing database schema including EAV and original seed/sample data, preserves the storefront and admin look and feel, and replaces XML-based extensibility with PHP-based modules, manifests, service providers, policies, events, and typed config.

Important context:
- The current root checkout may be OpenMage core-only. Do not assume it contains the real project overlay.
- If the checkout is core-only, identify what is missing and prepare for a two-repository workspace: project code plus Magento CE `1.9.4.5` core/mirror. Use existing local remotes/paths if available. If the project repository URL/path is not discoverable, document the blocker clearly and continue with all preparation work that can be done from core alone.
- Keep user changes safe. Do not revert unrelated work. Do not use destructive git commands.
- This is preparation work, not the Laravel rewrite itself.

Primary deliverables:
1. Split published documentation from migration execution instructions.
   - Public-facing docs should live under docs/content/modernization.
   - Migration instructions/specifications/checklists/templates should live under specs/.
   - Update mkdocs.yml so published docs link to the appropriate modernization docs.
   - Keep docs concise enough to be readable, but make specs comprehensive enough to execute.

2. Complete all preparation artifacts:
   - Actual project/core inventory plan and, where possible, generated inventory from the current checkout.
   - Compatibility policy.
   - Feature inventory template.
   - Architecture specs for Laravel bootstrap, no-XML module system, EAV/database access, config, storefront UI, admin UI, routing, events/jobs, APIs, auth/security, and operations.
   - Migration backlog with dependencies, risk, acceptance criteria, and verification.
   - Risk register.
   - Proof-of-concept plan for Laravel bootstrap, EAV repository, no-XML sample module, Livewire admin grid, Livewire storefront page, and route fallback.
   - Visual baseline plan.
   - Performance budget plan.
   - Data fixture strategy.
   - Release and rollback strategy.
   - Documentation structure and feature-guide plan.
   - Comprehensive test plan that defines when the modernization is done.

3. Add executable preparation tooling under dev/modernization/.
   Include scripts or commands for:
   - repository/core inventory report
   - schema checksum/report
   - no-new-XML validation for migrated code/spec areas
   - fixture restore check template
   - visual baseline capture template
   - performance baseline capture template
   - modernization gate runner that can call available checks and clearly mark unavailable checks
   - Markdown/docs/spec consistency checks

4. Install and verify the current Magento 1.9.4.5 app locally if feasible.
   - Use the repo's existing supported local setup where practical.
   - If installation requires unavailable credentials, missing project DB/media, unavailable services, or network approval, document the blocker and provide exact commands to run once available.
   - Verify the app in Chrome through Playwright or the available browser automation tool.
   - Capture at least a storefront/admin smoke result if the install succeeds.
   - If admin credentials are created during install, record them only in an ignored/local file, not committed docs.

5. Add missing spec templates and decision artifacts under specs/.
   Include:
   - specs/adr/ template and initial ADRs for Laravel, Livewire, no XML, preserving DB/EAV, strangler route fallback, and no direct Eloquent for EAV writes.
   - specs/security.md
   - specs/operations-runbook.md
   - specs/backlog.md
   - specs/inventory.md
   - specs/test-plan.md or link to the canonical test plan if already moved.
   - specs/release-strategy.md

Execution approach:
- First inspect the current repository structure, docs, specs, scripts, Composer files, test config, CI config, DDEV/Docker setup, and git status.
- If sub-agents are available, use them for independent read-only inventory, docs/nav inspection, and tooling/test/CI inspection. Review their findings before editing.
- Prefer rg/find/jq/php one-liners for inspection.
- Use apply_patch for manual edits.
- Keep generated scripts portable and safe. Scripts must not delete data by default.
- If a command fails because a tool is not installed, record that as verification status rather than hiding it.
- Do not install broad dependencies unless required and approved.

Acceptance criteria:
- docs/content/modernization exists and is linked from mkdocs.yml.
- specs/ exists and contains migration execution instructions, templates, ADRs, and runbooks.
- docs distinguish known facts from this core-only checkout versus project-specific work still requiring the project overlay.
- dev/modernization contains safe executable tooling with usage text.
- The test plan defines a concrete done gate.
- The compatibility policy explicitly covers database/EAV preservation, visual parity, APIs, URLs, modules, legacy XML bridge duration, and no-new-XML for new code.
- The backlog is phase-based and each task has dependencies, acceptance criteria, and verification.
- Playwright/Chrome verification of the current Magento app is attempted after install; if blocked, the blocker and exact next commands are documented.
- mkdocs build is attempted if available; if not, document the missing tool.
- All new Markdown has balanced code fences and no accidental non-ASCII.
- all md files that are relevant for the migration are moved to specs/*
- Magento CE `1.9.4.5` is up and running locally, seeded with sample data, and verified in Chrome using Playwright. You are allowed to delete or move existing non-doc/spec files in this folder and also to install missing dependencies when needed.

Final response:
Summarize changed files, what was verified, what could not be verified, and the next blocker if project code/core or install credentials are missing.
```

## Research Notes Used To Shape This Prompt

The prompt follows current OpenAI prompting guidance:

- Put instructions first and separate context clearly.
- Be specific about desired context, outcome, constraints, and output format.
- Provide enough project context to reduce ambiguity.
- For Codex-style coding tasks, use a well-scoped task with repository context and explicit verification expectations.

Sources:

- OpenAI Help Center, "Best practices for prompt engineering with the OpenAI API": https://help.openai.com/en/articles/6654000-how-to-prompt-the-models
- OpenAI Platform Docs, "Codex cloud": https://platform.openai.com/docs/codex
- OpenAI Academy, "Prompting fundamentals": https://openai.com/academy/prompting/
- OpenAI, "How OpenAI uses Codex": https://openai.com/business/guides-and-resources/how-openai-uses-codex/
