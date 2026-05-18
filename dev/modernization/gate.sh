#!/usr/bin/env bash
set -u

status=0

run_optional() {
  name="$1"
  shift
  echo "==> $name"
  if "$@"; then
    echo "PASS: $name"
  else
    code=$?
    echo "FAIL: $name (exit $code)" >&2
    status=1
  fi
  echo
}

run_optional "markdown checks" php dev/modernization/markdown-check.php
run_optional "inventory report" php dev/modernization/inventory.php --format=markdown
run_optional "no-new-xml check for specs" php dev/modernization/validate-no-new-xml.php specs

if command -v composer >/dev/null 2>&1; then
  run_optional "composer validate" composer validate --no-check-publish
else
  echo "SKIP: composer validate (composer not found)"
fi

mkdocs_bin="${MKDOCS_BIN:-}"
if [ -z "$mkdocs_bin" ] && command -v mkdocs >/dev/null 2>&1; then
  mkdocs_bin="mkdocs"
fi
if [ -z "$mkdocs_bin" ] && [ -x ".venv-mkdocs/bin/mkdocs" ]; then
  mkdocs_bin=".venv-mkdocs/bin/mkdocs"
fi

if [ -n "$mkdocs_bin" ]; then
  run_optional "mkdocs strict build" env DISABLE_MKDOCS_2_WARNING=true "$mkdocs_bin" build --strict
else
  echo "SKIP: mkdocs strict build (mkdocs not found)"
fi

exit "$status"
