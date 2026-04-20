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
  "${TEST_BASE_URL}/admin/activity-log.php" \
  -o "${page_body}"

for forbidden in \
  "const users =" \
  "const categories =" \
  "const inventory =" \
  "const borrowingsData =" \
  "const returnsData =" \
  "renderPenggunaTable" \
  "renderCategoriesGrid" \
  "renderInventarisTable" \
  "renderBorrowingsTable" \
  "renderReturnsTable" \
  "function openAdminDetailModal" \
  "function openAdminReturnModal"
do
  if grep -q "${forbidden}" "${page_body}"; then
    echo "Expected admin/activity-log.php to drop dead runtime bundle marker: ${forbidden}"
    exit 1
  fi
done

if ! grep -q 'const activities = ' "${page_body}"; then
  echo 'Expected admin/activity-log.php to expose the activity log payload'
  exit 1
fi

if ! grep -q "sidebarToggle.addEventListener('click', toggleSidebar)" "${page_body}"; then
  echo 'Expected admin/activity-log.php to bind the mobile sidebar toggle'
  exit 1
fi

echo 'OK: admin activity log route ships only its activity runtime bundle'
