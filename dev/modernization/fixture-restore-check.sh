#!/usr/bin/env bash
set -u

usage() {
  cat <<'USAGE'
Usage:
  dev/modernization/fixture-restore-check.sh --fixture=/path/to/db.sql[.gz] --media=/path/to/media

This is a non-destructive fixture check. It verifies that fixture files exist and
prints the restore commands that should be run in an isolated database/media root.
It does not import or delete data.
USAGE
}

fixture=""
media=""
for arg in "$@"; do
  case "$arg" in
    --fixture=*) fixture="${arg#--fixture=}" ;;
    --media=*) media="${arg#--media=}" ;;
    --help|-h) usage; exit 0 ;;
  esac
done

if [[ -z "$fixture" ]]; then
  echo "Missing --fixture" >&2
  usage
  exit 2
fi

if [[ ! -f "$fixture" ]]; then
  echo "Fixture not found: $fixture" >&2
  exit 1
fi

if [[ -n "$media" && ! -d "$media" ]]; then
  echo "Media directory not found: $media" >&2
  exit 1
fi

echo "Fixture exists: $fixture"
if [[ -n "$media" ]]; then
  echo "Media directory exists: $media"
fi
echo
echo "Restore in an isolated environment with commands like:"
echo "  mysql -h <host> -u <user> -p <database> < $fixture"
echo "  rsync -a $media/ <magento-root>/media/"

