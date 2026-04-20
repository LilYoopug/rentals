#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

staff_cookie="$(mktemp)"
body_file="$(mktemp)"
trap 'rm -f "${staff_cookie}" "${body_file}"' RETURN

mysql_test lenscraft <<'SQL'
INSERT INTO rentals (
  rental_code, user_id, product_id, start_date, end_date, total_days,
  daily_rate, discount_percentage, delivery_method, delivery_fee, total_price,
  status, created_at, approved_at
) VALUES
(
  'RENT-REPORT-APPROVED-UNPAID',
  3,
  3,
  '2026-02-01',
  '2026-02-02',
  2,
  59.00,
  0,
  'ambil_sendiri',
  0.00,
  118.00,
  'disetujui',
  '2026-02-01 08:00:00',
  '2026-02-01 09:00:00'
),
(
  'RENT-REPORT-PENDING',
  4,
  4,
  '2026-02-01',
  '2026-02-02',
  2,
  75.00,
  0,
  'ambil_sendiri',
  0.00,
  150.00,
  'menunggu',
  '2026-02-01 10:00:00',
  NULL
);
SQL

mysql_test lenscraft <<'SQL'
INSERT INTO payments (payment_code, rental_id, amount, method, status, created_at, updated_at)
SELECT 'PAY-REPORT-APPROVED-UNPAID', id, total_price, 'transfer_bank', 'pending', NOW(), NOW()
FROM rentals WHERE rental_code = 'RENT-REPORT-APPROVED-UNPAID';
SQL

curl -sS \
  -c "${staff_cookie}" \
  -X POST \
  -d 'username=petugas&password=staff123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${staff_cookie}" \
  "${TEST_BASE_URL}/staff/reports.php?report_type=borrowings&report_date_range=custom&custom_date_from=2026-02-01&custom_date_to=2026-02-02" \
  -o "${body_file}"

for heading in 'Tren Pendapatan' 'Category Distribution' 'Top Equipment' 'Data Laporan'; do
  if ! grep -q "${heading}" "${body_file}"; then
    echo "Expected reports page to render ${heading}"
    exit 1
  fi
done

for chart_id in 'mainChart' 'categoryChart' 'topEquipmentChart'; do
  if ! grep -q "id=\\\"${chart_id}\\\"" "${body_file}"; then
    echo "Expected reports page to render chart canvas ${chart_id}"
    exit 1
  fi
done

if ! grep -q 'id=\"custom-date-range\" class=\"mt-4 grid' "${body_file}"; then
  echo 'Expected reports page to show the custom date range row below the date select when custom range is active'
  exit 1
fi

if ! grep -q 'id=\"custom-date-from\"' "${body_file}" || ! grep -q 'value=\"2026-02-01\"' "${body_file}"; then
  echo 'Expected reports page to preserve the selected custom start date in the date inputs'
  exit 1
fi

if ! grep -q 'id=\"custom-date-to\"' "${body_file}" || ! grep -q 'value=\"2026-02-02\"' "${body_file}"; then
  echo 'Expected reports page to preserve the selected custom end date in the date inputs'
  exit 1
fi

for label in 'Total Requests' 'Approved Requests' 'Pending Requests' 'Total Revenue'; do
  if ! grep -q "${label}" "${body_file}"; then
    echo "Expected reports page to render summary label: ${label}"
    exit 1
  fi
done

if ! perl -0ne 'exit 0 if /Total Requests<\/div>\s*<div[^>]*>\s*2\s*<\/div>/s; exit 1' "${body_file}"; then
  echo 'Expected custom filtered range to show two total requests'
  exit 1
fi

if ! perl -0ne 'exit 0 if /Approved Requests<\/div>\s*<div[^>]*>\s*1\s*<\/div>/s; exit 1' "${body_file}"; then
  echo 'Expected disetujui rental to count as one approved request'
  exit 1
fi

if ! perl -0ne 'exit 0 if /Pending Requests<\/div>\s*<div[^>]*>\s*1\s*<\/div>/s; exit 1' "${body_file}"; then
  echo 'Expected menunggu rental to remain in pending requests'
  exit 1
fi

if ! perl -0ne 'exit 0 if /Total Revenue<\/div>\s*<div[^>]*>\s*Rp268,00\s*<\/div>/s; exit 1' "${body_file}"; then
  echo 'Expected custom filtered range to show the combined revenue for approved and pending requests'
  exit 1
fi

echo 'OK: reports page keeps charts, preserves custom date inputs, and counts disetujui separately from pending'
