# Boost MCP Evidence

Evidence captured: 2026-05-19 09:10 CEST.

| Field | Evidence |
| --- | --- |
| MCP Server | Laravel Boost MCP is exposed by `php artisan boost:mcp` through the repository root artisan proxy. |
| Laravel Boost | `laravel/boost` `2.4.7` is installed in the Laravel target. |
| tools/list | `dev/modernization/validate-boost-mcp-readiness.php` initializes the MCP server and validates the JSON-RPC `tools/list` response. |
| application-info | `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 artisan boost:execute-tool 'Laravel\Boost\Mcp\Tools\ApplicationInfo' W10=` returned `isError:false` with PHP `8.5`, Laravel `13.9.0`, Livewire `4.3.0`, Boost `2.4.7`, MCP `0.7.0`, Pail `1.2.6`, Pint `1.29.1`, and PHPUnit `12.5.25`. |
| search-docs | Laravel Boost `SearchDocs` was used for Laravel implementation work; network access to `boost.laravel.com` needs normal registry/DNS access when run from the sandbox. |
| database-schema | The MCP `tools/list` response includes `database-schema` and the validator confirms its read-only annotation. |
| database-query | The MCP `tools/list` response includes `database-query` and the validator confirms its read-only annotation. |
| read-only | `application-info`, `database-query`, `database-schema`, and `get-absolute-url` advertise `readOnlyHint` in the MCP tool list. |
| Reload Steps | If a Codex or editor client does not show Boost tools, restart the client MCP session, confirm the repository root artisan proxy is executable, and rerun `php artisan boost:mcp` or the modernization Boost validator. |
| Run URL/Log | Local log source: `/Users/fabianwesner/Library/Application Support/Herd/bin/php85 dev/modernization/validate-boost-mcp-readiness.php`; normal modernization gate output also includes `Laravel Boost application smoke` and `Boost MCP readiness template check`. |
| Status | Boost MCP tool discovery and application-info execution are verified locally. |

## Commands

```bash
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 artisan boost:execute-tool 'Laravel\Boost\Mcp\Tools\ApplicationInfo' W10=
/Users/fabianwesner/Library/Application\ Support/Herd/bin/php85 dev/modernization/validate-boost-mcp-readiness.php
```
