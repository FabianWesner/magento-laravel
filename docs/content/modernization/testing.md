# Testing

The modernization is complete only when the test plan passes with evidence. Compilation alone is not a done signal.

## Done Gate

- Legacy characterization tests pass.
- Laravel implementation tests pass.
- Storefront and admin visual regression pass.
- Database and EAV parity tests pass.
- API contract tests pass for preserved endpoints.
- Security, performance, accessibility, and operations gates pass.
- No-new-XML checks pass for migrated code.
- Laravel target runs on the latest stable PHP and Composer platform checks pass.
- Laravel Boost remains installable in the target Laravel workspace.
- Documentation and runbooks are buildable and reviewed.

Execution details and checklists live in `specs/modernization/test-plan.md`.
