#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

cookie_file="$(mktemp)"
page_body="$(mktemp)"
trap 'rm -f "${cookie_file}" "${page_body}"' RETURN

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=admin&password=admin123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${cookie_file}" \
  "${TEST_BASE_URL}/admin/returns.php" \
  -o "${page_body}"

if ! grep -q 'Ekspor' "${page_body}"; then
  echo 'Expected admin returns page to keep the export button'
  exit 1
fi

if ! grep -q 'id="returns-filter-btn"' "${page_body}"; then
  echo 'Expected admin returns page to keep the inner filter dropdown trigger'
  exit 1
fi

if perl -0ne 'exit 0 if /<button class="px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-sm font-medium text-neutral-300 hover:bg-neutral-700 transition-colors flex items-center gap-2">\s*<svg[^>]*>[\s\S]*?<\/svg>\s*Filter\s*<\/button>/s; exit 1' "${page_body}"; then
  echo 'Expected admin returns page to remove the duplicated outer Filter button'
  exit 1
fi

echo 'OK: admin returns page keeps only the inner filter trigger'
