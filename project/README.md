# Project Overlay

Place project-specific Magento 1 files here, using document-root-relative paths.

Examples:

- `app/code/local/...`
- `app/code/community/...` for project-owned community modules
- `app/design/...`
- `skin/...`
- `js/...`
- `media/...` fixtures that are safe to version
- deployment templates that are safe to commit

Do not commit secrets, production `app/etc/local.xml`, database dumps, generated cache, sessions, reports, or private media. Runtime composition should copy `core/magento-1.9.4.5/` first and then overlay this directory on top.
