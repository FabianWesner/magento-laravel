#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
core_root="${MAGENTO_CORE_ROOT:-$repo_root/core/magento-1.9.4.5}"
project_root="${MAGENTO_PROJECT_ROOT:-$repo_root/project}"
runtime_root="${MAGENTO_RUNTIME_ROOT:-$repo_root/.localdev/magento-docroot}"

if [[ ! -f "$core_root/app/Mage.php" ]]; then
  echo "Magento core root not found: $core_root" >&2
  exit 1
fi

mkdir -p "$runtime_root"

rsync -a --delete \
  --exclude app/etc/local.xml \
  --exclude var/cache \
  --exclude var/session \
  --exclude var/report \
  "$core_root/" "$runtime_root/"

if [[ -d "$project_root" ]]; then
  rsync -a \
    --exclude README.md \
    "$project_root/" "$runtime_root/"
fi

if [[ -f "$core_root/app/etc/local.xml" && ! -f "$runtime_root/app/etc/local.xml" ]]; then
  cp "$core_root/app/etc/local.xml" "$runtime_root/app/etc/local.xml"
fi

echo "Built Magento docroot at $runtime_root"
