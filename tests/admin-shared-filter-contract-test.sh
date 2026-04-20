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

for route in "${admin_routes[@]}"; do
  if ! rg -q '<select id="inventory-category"' "${route}"; then
    echo "Expected ${route} to include the inventory category select"
    exit 1
  fi

  if ! rg -q 'foreach \(\$admin_categories as \$category\)' "${route}"; then
    echo "Expected ${route} to generate inventory category options from live admin categories"
    exit 1
  fi
done

if rg -q 'id="status-filter"' "admin/users.php"; then
  echo 'Expected admin/users.php to remove the status filter from user management'
  exit 1
fi

for route in "${admin_routes[@]}"; do
  if rg -q 'id="status-filter"|getElementById\('\''status-filter'\''\)|bindById\('\''status-filter'\''' "${route}"; then
    echo "Expected ${route} to stop referencing the removed user management status filter"
    exit 1
  fi
done

echo "OK: admin shared filters use live categories and user management no longer references status filter"
