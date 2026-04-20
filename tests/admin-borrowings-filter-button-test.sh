#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

cookie_file="$(mktemp)"
page_file="$(mktemp)"
trap 'rm -f "${cookie_file}" "${page_file}"' RETURN

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=admin&password=admin123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${cookie_file}" \
  "${TEST_BASE_URL}/admin/borrowings.php" \
  -o "${page_file}"

if grep -q 'borrowings-filter-btn' "${page_file}"; then
  echo 'Expected admin borrowings page to omit the borrowings filter button toggle'
  exit 1
fi

if grep -q 'borrowings-filter-dropdown' "${page_file}"; then
  echo 'Expected admin borrowings page to omit the borrowings filter dropdown panel'
  exit 1
fi

echo 'OK: admin borrowings page no longer renders the borrowings filter toggle'
