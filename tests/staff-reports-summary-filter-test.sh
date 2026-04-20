#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

staff_cookie="$(mktemp)"
body_file="$(mktemp)"
trap 'rm -f "${staff_cookie}" "${body_file}"' RETURN

curl -sS \
  -c "${staff_cookie}" \
  -X POST \
  -d 'username=petugas&password=staff123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${staff_cookie}" \
  "${TEST_BASE_URL}/staff/reports.php?report_type=borrowings&report_date_range=custom&custom_date_from=2000-01-01&custom_date_to=2000-01-02" \
  -o "${body_file}"

for heading in 'Tren Pendapatan' 'Category Distribution' 'Top Equipment' 'Data Laporan'; do
  if ! grep -q "${heading}" "${body_file}"; then
    echo "Expected reports page to render ${heading}"
    exit 1
  fi
done

for chart_id in 'mainChart' 'categoryChart' 'topEquipmentChart'; do
  if ! grep -q "id=\"${chart_id}\"" "${body_file}"; then
    echo "Expected reports page to render chart canvas ${chart_id}"
    exit 1
  fi
done

if ! grep -q 'id="custom-date-range" class="mt-4 grid' "${body_file}"; then
  echo 'Expected reports page to show the custom date range row below the date select when custom range is active'
  exit 1
fi

if ! grep -q 'id="custom-date-from"' "${body_file}" || ! grep -q 'value="2000-01-01"' "${body_file}"; then
  echo 'Expected reports page to preserve the selected custom start date in the date inputs'
  exit 1
fi

if ! grep -q 'id="custom-date-to"' "${body_file}" || ! grep -q 'value="2000-01-02"' "${body_file}"; then
  echo 'Expected reports page to preserve the selected custom end date in the date inputs'
  exit 1
fi

for label in 'Total Requests' 'Approved Requests' 'Pending Requests' 'Total Revenue'; do
  if ! grep -q "${label}" "${body_file}"; then
    echo "Expected reports page to render summary label: ${label}"
    exit 1
  fi
done

if ! perl -0ne 'exit 0 if /Total Requests<\/div>\s*<div[^>]*>\s*0\s*<\/div>/s; exit 1' "${body_file}"; then
  echo 'Expected custom empty range to show zero total requests'
  exit 1
fi

if ! perl -0ne 'exit 0 if /Approved Requests<\/div>\s*<div[^>]*>\s*0\s*<\/div>/s; exit 1' "${body_file}"; then
  echo 'Expected custom empty range to show zero approved requests'
  exit 1
fi

if ! perl -0ne 'exit 0 if /Pending Requests<\/div>\s*<div[^>]*>\s*0\s*<\/div>/s; exit 1' "${body_file}"; then
  echo 'Expected custom empty range to show zero pending requests'
  exit 1
fi

if ! perl -0ne 'exit 0 if /Total Revenue<\/div>\s*<div[^>]*>\s*Rp0,00\s*<\/div>/s; exit 1' "${body_file}"; then
  echo 'Expected custom empty range to show zero revenue'
  exit 1
fi

echo 'OK: reports page keeps charts, preserves custom date inputs, and filters summaries by date range'
