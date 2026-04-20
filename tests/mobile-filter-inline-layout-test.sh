#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "$0")/.." && pwd)"

check_inline_filter_row() {
  local file="$1"

  if grep -q 'flex flex-col md:flex-row gap-4 md:items-center' "${project_root}/${file}"; then
    echo "Expected ${file} to stop stacking the filter icon above the search bar on mobile"
    exit 1
  fi

  if ! grep -q 'flex flex-row items-center gap-4' "${project_root}/${file}"; then
    echo "Expected ${file} to keep the filter icon and search bar on one row"
    exit 1
  fi

  if ! grep -q 'relative flex-shrink-0' "${project_root}/${file}"; then
    echo "Expected ${file} to keep the filter trigger from shrinking on mobile"
    exit 1
  fi

  if ! grep -q 'min-w-0 flex-1 relative' "${project_root}/${file}"; then
    echo "Expected ${file} to let the search field fill the remaining row width on mobile"
    exit 1
  fi
}

check_inline_filter_row "admin/users.php"
check_inline_filter_row "admin/products.php"
check_inline_filter_row "admin/returns.php"
check_inline_filter_row "admin/activity-log.php"
check_inline_filter_row "staff/borrowings.php"
check_inline_filter_row "staff/returns.php"
check_inline_filter_row "staff/stock-price.php"

echo "OK: mobile filter icon rows stay inline with the search bar across table pages"
