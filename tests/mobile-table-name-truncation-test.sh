#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

admin_cookie="$(mktemp)"
staff_cookie="$(mktemp)"
admin_page="$(mktemp)"
staff_page="$(mktemp)"
stock_page="$(mktemp)"
trap 'rm -f "${admin_cookie}" "${staff_cookie}" "${admin_page}" "${staff_page}" "${stock_page}"' RETURN

curl -sS \
  -c "${admin_cookie}" \
  -X POST \
  -d 'username=admin&password=admin123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -c "${staff_cookie}" \
  -X POST \
  -d 'username=petugas&password=staff123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${admin_cookie}" \
  "${TEST_BASE_URL}/admin/users.php" \
  -o "${admin_page}"

curl -sS \
  -b "${staff_cookie}" \
  "${TEST_BASE_URL}/staff/borrowings.php" \
  -o "${staff_page}"

curl -sS \
  -b "${staff_cookie}" \
  "${TEST_BASE_URL}/staff/stock-price.php" \
  -o "${stock_page}"

if ! grep -q 'mobile-name-ellipsis' "${admin_page}"; then
  echo 'Expected admin mobile table rows to include mobile-name-ellipsis for long names'
  exit 1
fi

if ! grep -q 'class="min-w-0 flex-1"' "${admin_page}"; then
  echo 'Expected admin mobile inventory cards to give the text wrapper flex-1 so long names can truncate'
  exit 1
fi

if ! grep -q 'mobile-card-title-ellipsis' "${admin_page}"; then
  echo 'Expected admin mobile inventory cards to use a dedicated capped title ellipsis helper'
  exit 1
fi

if ! grep -q 'mobile-name-ellipsis' "${staff_page}"; then
  echo 'Expected staff mobile borrowing and return rows to include mobile-name-ellipsis for long names'
  exit 1
fi

if ! grep -q 'mobile-name-ellipsis' "${stock_page}"; then
  echo 'Expected staff stock and price rows to include mobile-name-ellipsis for product names on mobile'
  exit 1
fi

if grep -q 'min-w-\[16rem\]' "${stock_page}"; then
  echo 'Expected staff stock and price mobile product rows to avoid a hard 16rem minimum width'
  exit 1
fi

if ! grep -q 'stock-price-table-wrapper' "${stock_page}" || ! grep -q 'stock-price-table' "${stock_page}"; then
  echo 'Expected staff stock and price page to mark the mobile table wrapper and table for dedicated overflow handling'
  exit 1
fi

if ! grep -q 'overflow-x: clip;' "${stock_page}"; then
  echo 'Expected staff stock and price mobile CSS to disable horizontal scrolling in the table wrapper'
  exit 1
fi

echo "OK: mobile table name truncation helper is present in representative pages"
