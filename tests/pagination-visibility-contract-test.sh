#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "$0")/.." && pwd)"

while IFS= read -r file; do
  if rg -q "classList\\.toggle\\('hidden', index < start \\|\\| index >= end\\)" "${file}" && rg -q "sm:table-row" "${file}"; then
    echo "Expected ${file#${project_root}/} to avoid class-based hidden pagination on responsive table rows"
    exit 1
  fi
done < <(find "${project_root}/staff" "${project_root}/admin" -name '*.php' | sort)

echo 'OK: responsive table pages avoid the hidden-class pagination pattern that breaks desktop row visibility'
