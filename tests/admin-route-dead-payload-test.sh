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

check_payload() {
  local route="$1"
  shift

  curl -sS \
    -b "${cookie_file}" \
    "${TEST_BASE_URL}/${route}" \
    -o "${page_body}"

  local forbidden
  for forbidden in "$@"; do
    if grep -q "${forbidden}" "${page_body}"; then
      echo "Expected ${route} to drop dead route payload marker: ${forbidden}"
      exit 1
    fi
  done
}

check_payload "admin/index.php" \
  "function openUserModal" \
  "function openCategoryModal" \
  "function openInventarisModal" \
  "function openTransactionModal" \
  "function openAdminDetailModal" \
  "function openAdminReturnModal"

check_payload "admin/users.php" \
  "const categories =" \
  "const inventory =" \
  "const borrowingsData =" \
  "const returnsData =" \
  "const activities =" \
  "function openCategoryModal" \
  "function openInventarisModal" \
  "function openTransactionModal" \
  "function openAdminDetailModal" \
  "function openAdminReturnModal"

check_payload "admin/categories.php" \
  "const users =" \
  "const inventory =" \
  "const borrowingsData =" \
  "const returnsData =" \
  "const activities =" \
  "function openUserModal" \
  "function openInventarisModal" \
  "function openTransactionModal" \
  "function openAdminDetailModal" \
  "function openAdminReturnModal"

check_payload "admin/products.php" \
  "const users =" \
  "const borrowingsData =" \
  "const returnsData =" \
  "const activities =" \
  "function openUserModal" \
  "function openCategoryModal" \
  "function openTransactionModal" \
  "function openAdminReturnModal"

check_payload "admin/borrowings.php" \
  "const users =" \
  "const categories =" \
  "const returnsData =" \
  "renderReturnsTable" \
  "filterPengembalian" \
  "function viewPengembalian" \
  "function editPengembalian"

check_payload "admin/returns.php" \
  "const users =" \
  "const categories =" \
  "const inventory =" \
  "const borrowingsData =" \
  "renderBorrowingsTable" \
  "function viewPeminjaman" \
  "function convertToPengembalian"

check_payload "admin/activity-log.php" \
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

echo "OK: admin routes no longer ship dead JS/data bundles from other pages"
