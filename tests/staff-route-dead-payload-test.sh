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

check_payload "staff/index.php" \
  "const borrowingsData =" \
  "const returnsData =" \
  "let mainChart = null" \
  "function openStaffDetailModal"

check_payload "staff/borrowings.php" \
  "const returnsData =" \
  "renderReturnsTable" \
  "function markReturned" \
  "let mainChart = null" \
  "function generateReport"

check_payload "staff/returns.php" \
  "const borrowingsData =" \
  "renderBorrowingsTable" \
  "function approveBorrowing" \
  "function rejectBorrowing" \
  "let mainChart = null" \
  "function generateReport"

check_payload "staff/reports.php" \
  "const borrowingsData =" \
  "const returnsData =" \
  "function openStaffDetailModal" \
  "function openStaffActionModal" \
  "function approveBorrowing" \
  "function markReturned"

echo "OK: staff routes no longer ship dead JS/data bundles from other pages"
