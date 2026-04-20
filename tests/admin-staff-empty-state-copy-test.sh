#!/usr/bin/env bash
set -euo pipefail

admin_routes=(
  "admin/index.php"
  "admin/users.php"
  "admin/categories.php"
  "admin/products.php"
  "admin/borrowings.php"
  "admin/returns.php"
  "admin/activity-log.php"
)

staff_routes=(
  "staff/index.php"
  "staff/borrowings.php"
  "staff/returns.php"
  "staff/reports.php"
)

for route in "${admin_routes[@]}" "${staff_routes[@]}"; do
  if ! rg -q 'function renderTableEmptyState' "${route}"; then
    echo "Expected ${route} to expose the shared table empty-state renderer"
    exit 1
  fi

  if ! rg -q 'Coba ubah filter atau tambah data baru\.' "${route}"; then
    echo "Expected ${route} to include the empty-state helper copy for no table data"
    exit 1
  fi
done

if ! rg -q 'Coba ubah filter atau tambah data baru\.' "staff/stock-price.php"; then
  echo "Expected staff/stock-price.php to include the empty-state helper copy for no table data"
  exit 1
fi

echo "OK: admin and staff route files include the shared table empty-state helper"
