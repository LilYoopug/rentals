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
  -d 'username=petugas&password=staff123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

check_route() {
  local route="$1"
  local inner_button_id="$2"
  local should_have_filter="$3"

  curl -sS \
    -b "${cookie_file}" \
    "${TEST_BASE_URL}/${route}" \
    -o "${page_body}"

  if ! grep -q 'Ekspor' "${page_body}"; then
    echo "Expected ${route} to keep the export button"
    exit 1
  fi

  if [[ "${should_have_filter}" == "yes" ]]; then
    if ! grep -q "id=\"${inner_button_id}\"" "${page_body}"; then
      echo "Expected ${route} to keep the inner filter dropdown trigger"
      exit 1
    fi
  else
    if grep -q "id=\"${inner_button_id}\"" "${page_body}"; then
      echo "Expected ${route} to drop the inner filter dropdown trigger"
      exit 1
    fi
  fi

  if perl -0ne 'exit 0 if /<button class="px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-sm font-medium text-neutral-300 hover:bg-neutral-700 transition-colors flex items-center gap-2">\s*<svg[^>]*>[\s\S]*?<\/svg>\s*Filter\s*<\/button>/s; exit 1' "${page_body}"; then
    echo "Expected ${route} to remove the duplicated outer Filter button"
    exit 1
  fi
}

check_route "staff/borrowings.php" "borrowings-filter-btn" "yes"
check_route "staff/returns.php" "returns-filter-btn" "yes"
check_route "staff/stock-price.php" "stock-filter-btn" "yes"

echo 'OK: staff stock and transaction pages remove duplicated outer filter buttons and keep the inner filter trigger'
