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

run_maybe_unavailable() {
  name="$1"
  shift
  echo "==> $name"
  if "$@"; then
    echo "PASS: $name"
  else
    code=$?
    if [ "$code" -eq 2 ]; then
      if [ "${MODERNIZATION_FINAL:-0}" = "1" ]; then
        echo "FAIL: $name unavailable in final mode (exit $code)" >&2
        status=1
      else
        echo "SKIP: $name (unavailable in this environment)"
      fi
    else
      echo "FAIL: $name (exit $code)" >&2
      status=1
    fi
  fi
  echo
}

run_php_syntax() {
  local file
  for file in dev/modernization/*.php; do
    php -l "$file" >/dev/null || return 1
  done
}

run_fixture_coverage() {
  if [ -z "${DB_DSN:-}" ]; then
    echo "DB_DSN is not set."
    return 2
  fi

  if [ "${FIXTURE_COVERAGE_STRICT:-0}" = "1" ] || [ "${MODERNIZATION_FINAL:-0}" = "1" ]; then
    php dev/modernization/fixture-coverage-report.php --format=markdown --fail-on-gaps
  else
    php dev/modernization/fixture-coverage-report.php --format=markdown
  fi
}

run_schema_report() {
  if [ -z "${DB_DSN:-}" ]; then
    echo "DB_DSN is not set."
    return 2
  fi

  php dev/modernization/schema-report.php --format=markdown
}

run_laravel_boost_smoke() {
  if [ ! -x artisan ]; then
    echo "Repository root artisan proxy is missing or not executable."
    return 1
  fi

  php artisan --version >/dev/null || return 1
  php artisan list --raw | grep -q '^boost:mcp[[:space:]]' || return 1
  php artisan boost:execute-tool 'Laravel\Boost\Mcp\Tools\ApplicationInfo' W10= | grep -q '"isError":false' || return 1
}

run_magento_docroot_verification() {
  if [ "${MODERNIZATION_FINAL:-0}" = "1" ]; then
    php dev/modernization/verify-magento-docroot.php --final
  else
    php dev/modernization/verify-magento-docroot.php
  fi
}

run_visual_baseline_capture_syntax() {
  if ! command -v node >/dev/null 2>&1; then
    echo "node is not available."
    return 2
  fi

  node --check dev/modernization/capture-visual-baseline.mjs
}

run_optional "modernization PHP syntax" run_php_syntax
run_maybe_unavailable "visual baseline capture syntax" run_visual_baseline_capture_syntax
run_optional "markdown checks" php dev/modernization/markdown-check.php
if [ "${MODERNIZATION_FINAL:-0}" = "1" ]; then
  run_optional "documentation content final check" php dev/modernization/validate-docs-content.php --final
else
  run_optional "documentation content template check" php dev/modernization/validate-docs-content.php
fi
run_optional "inventory report" php dev/modernization/inventory.php --format=markdown
if [ "${MODERNIZATION_FINAL:-0}" = "1" ]; then
  run_optional "UI screen inventory final check" php dev/modernization/validate-ui-screen-inventory.php --final
  run_optional "visual tolerances final check" php dev/modernization/validate-visual-tolerances.php --final
  run_optional "release readiness final check" php dev/modernization/validate-release-readiness.php --final
  run_optional "performance budgets final check" php dev/modernization/validate-performance-budgets.php --final
  run_optional "operations readiness final check" php dev/modernization/validate-operations-readiness.php --final
  run_optional "security/accessibility final check" php dev/modernization/validate-security-accessibility.php --final
  run_optional "production readiness final check" php dev/modernization/validate-production-readiness.php --final
  run_optional "runtime tooling final check" php dev/modernization/validate-runtime-tooling.php --final
else
  run_optional "UI screen inventory template check" php dev/modernization/validate-ui-screen-inventory.php
  run_optional "visual tolerances template check" php dev/modernization/validate-visual-tolerances.php
  run_optional "release readiness template check" php dev/modernization/validate-release-readiness.php
  run_optional "performance budgets template check" php dev/modernization/validate-performance-budgets.php
  run_optional "operations readiness template check" php dev/modernization/validate-operations-readiness.php
  run_optional "security/accessibility template check" php dev/modernization/validate-security-accessibility.php
  run_optional "production readiness template check" php dev/modernization/validate-production-readiness.php
  run_optional "runtime tooling template check" php dev/modernization/validate-runtime-tooling.php
fi
run_maybe_unavailable "Magento docroot verification" run_magento_docroot_verification
run_optional "Laravel Boost application smoke" run_laravel_boost_smoke
run_maybe_unavailable "fixture coverage report" run_fixture_coverage
run_maybe_unavailable "schema report" run_schema_report
run_optional "no-new-xml check for migrated paths" php dev/modernization/validate-no-new-xml.php specs laravel/app laravel/config laravel/routes laravel/resources laravel/database laravel/modules laravel/packages docs/content/modernization docusaurus/docs
run_optional "removed-technology check for Laravel target" php dev/modernization/validate-removed-technologies.php
if php dev/modernization/validate-removed-technologies.php dev/modernization/fixtures/removed-tech/bad >/dev/null 2>&1; then
  echo "FAIL: removed-technology fixture should fail but passed" >&2
  status=1
else
  echo "PASS: removed-technology fixture rejects banned references"
fi
echo
run_optional "feature traceability template check" php dev/modernization/validate-feature-traceability.php --strict
if [ "${MODERNIZATION_FINAL:-0}" = "1" ]; then
  run_optional "feature traceability final check" php dev/modernization/validate-feature-traceability.php --final
else
  echo "SKIP: feature traceability final check (set MODERNIZATION_FINAL=1)"
  echo
fi

if [ -f composer.json ] && command -v composer >/dev/null 2>&1; then
  run_optional "composer validate" composer validate --no-check-publish
elif [ -f composer.json ]; then
  echo "SKIP: composer validate (composer not found)"
else
  echo "SKIP: composer validate (composer.json not found at repository root)"
fi

if [ -f laravel/composer.json ] && command -v composer >/dev/null 2>&1; then
  run_optional "Laravel composer validate" composer --working-dir=laravel validate --no-check-publish
elif [ -f laravel/composer.json ]; then
  echo "SKIP: Laravel composer validate (composer not found)"
else
  echo "SKIP: Laravel composer validate (laravel/composer.json not found)"
fi

if [ -f laravel/phpunit.xml ]; then
  run_optional "Laravel tests" php artisan test --compact
else
  echo "SKIP: Laravel tests (laravel/phpunit.xml not found)"
fi

mkdocs_bin="${MKDOCS_BIN:-}"
if [ -z "$mkdocs_bin" ] && command -v mkdocs >/dev/null 2>&1; then
  mkdocs_bin="mkdocs"
fi
if [ -z "$mkdocs_bin" ] && [ -x ".venv-mkdocs/bin/mkdocs" ]; then
  mkdocs_bin=".venv-mkdocs/bin/mkdocs"
fi

if [ -n "$mkdocs_bin" ] && [ -f mkdocs.yml ]; then
  run_optional "mkdocs strict build" env DISABLE_MKDOCS_2_WARNING=true "$mkdocs_bin" build --strict
elif [ -f mkdocs.yml ]; then
  echo "SKIP: mkdocs strict build (mkdocs not found)"
else
  echo "SKIP: mkdocs strict build (mkdocs.yml not found)"
fi

if [ -f docusaurus/package.json ] && command -v npm >/dev/null 2>&1; then
  run_optional "docusaurus build" npm --prefix docusaurus run build
  run_maybe_unavailable "docusaurus browser smoke" node dev/modernization/smoke-docusaurus.mjs
elif [ -f docusaurus/package.json ]; then
  echo "SKIP: docusaurus build (npm not found)"
else
  echo "SKIP: docusaurus build (docusaurus/package.json not found)"
fi

exit "$status"
