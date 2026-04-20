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
  if ! rg -q "'actorName' =>" "${route}"; then
    echo "Expected ${route} to expose activity actorName data for admin activity filters"
    exit 1
  fi

  if ! rg -q "'actorRole' =>" "${route}"; then
    echo "Expected ${route} to expose activity actorRole data for admin activity filters"
    exit 1
  fi

  if rg -q 'activity\.user' "${route}"; then
    echo "Expected ${route} to stop referencing the non-existent activity.user property"
    exit 1
  fi

  if ! rg -q 'const weekAgo = new Date' "${route}"; then
    echo "Expected ${route} to use a past-week range in admin activity filters"
    exit 1
  fi
done

echo "OK: admin activity filter code uses the normalized activity contract"
