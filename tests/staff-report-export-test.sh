#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

staff_cookie="$(mktemp)"
page_body="$(mktemp)"
index_body="$(mktemp)"
borrowings_body="$(mktemp)"
returns_body="$(mktemp)"
csv_headers="$(mktemp)"
csv_body="$(mktemp)"
pdf_headers="$(mktemp)"
pdf_body="$(mktemp)"
xlsx_headers="$(mktemp)"
xlsx_body="$(mktemp)"
trap 'rm -f "${staff_cookie}" "${page_body}" "${index_body}" "${borrowings_body}" "${returns_body}" "${csv_headers}" "${csv_body}" "${pdf_headers}" "${pdf_body}" "${xlsx_headers}" "${xlsx_body}"' RETURN

curl -sS \
  -c "${staff_cookie}" \
  -X POST \
  -d 'username=petugas&password=staff123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${staff_cookie}" \
  "${TEST_BASE_URL}/staff/reports.php" \
  -o "${page_body}"

curl -sS -b "${staff_cookie}" "${TEST_BASE_URL}/staff/index.php" -o "${index_body}"
curl -sS -b "${staff_cookie}" "${TEST_BASE_URL}/staff/borrowings.php" -o "${borrowings_body}"
curl -sS -b "${staff_cookie}" "${TEST_BASE_URL}/staff/returns.php" -o "${returns_body}"

for marker in \
  'id="report-section-stock"' \
  'id="report-section-borrowings"' \
  'id="report-section-returns"' \
  'value="stock" class="rounded' \
  'value="borrowings" class="rounded' \
  'value="returns" class="rounded' \
  '>PDF<' \
  '>XLSX<' \
  '>CSV<'
do
  if ! grep -q "${marker}" "${page_body}"; then
    echo "Expected staff reports page to contain ${marker}"
    exit 1
  fi
done

if [[ "$(grep -c 'name="report_sections\[\]"' "${page_body}")" -lt 3 ]]; then
  echo 'Expected three report section checkboxes'
  exit 1
fi

if [[ "$(grep -c 'checked' "${page_body}")" -lt 3 ]]; then
  echo 'Expected stock, peminjaman, and pengembalian to be checked by default'
  exit 1
fi

if grep -q '>Generate Report<' "${page_body}"; then
  echo 'Expected the old Generate Report button to be removed'
  exit 1
fi

for duplicate_page in "${index_body}" "${borrowings_body}" "${returns_body}"; do
  for marker in 'id="report-section-stock"' 'id="report-section-borrowings"' 'id="report-section-returns"' '>PDF<' '>XLSX<' '>CSV<'; do
    if grep -q "${marker}" "${duplicate_page}"; then
      echo "Expected non-report staff routes to drop duplicated report export UI marker ${marker}"
      exit 1
    fi
  done
done

query='report_sections[]=stock&report_sections[]=borrowings&report_sections[]=returns&report_date_range=30'

curl -sS -b "${staff_cookie}" -D "${csv_headers}" "${TEST_BASE_URL}/process/staff-report-export.php?format=csv&${query}" -o "${csv_body}"
curl -sS -b "${staff_cookie}" -D "${pdf_headers}" "${TEST_BASE_URL}/process/staff-report-export.php?format=pdf&${query}" -o "${pdf_body}"
curl -sS -b "${staff_cookie}" -D "${xlsx_headers}" "${TEST_BASE_URL}/process/staff-report-export.php?format=xlsx&${query}" -o "${xlsx_body}"

if ! grep -qi 'content-type: text/csv' "${csv_headers}"; then
  echo 'Expected CSV export content type'
  exit 1
fi

if ! grep -q 'Peminjaman' "${csv_body}" || ! grep -q 'Pengembalian' "${csv_body}" || ! grep -q 'Stock' "${csv_body}"; then
  echo 'Expected CSV export to include all selected sections'
  exit 1
fi

if ! grep -qi 'content-type: application/pdf' "${pdf_headers}"; then
  echo 'Expected PDF export content type'
  exit 1
fi

if [[ "$(head -c 5 "${pdf_body}")" != "%PDF-" ]]; then
  echo 'Expected a real PDF file'
  exit 1
fi

if ! grep -qi 'content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' "${xlsx_headers}"; then
  echo 'Expected XLSX export content type'
  exit 1
fi

if [[ "$(head -c 2 "${xlsx_body}")" != "PK" ]]; then
  echo 'Expected a real XLSX zip container'
  exit 1
fi

echo "OK: staff reports page exposes checkbox exports and real csv/pdf/xlsx downloads"
