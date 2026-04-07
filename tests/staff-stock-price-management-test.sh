#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

staff_cookie="$(mktemp)"
page_body="$(mktemp)"
update_headers="$(mktemp)"
trap 'rm -f "${staff_cookie}" "${page_body}" "${update_headers}"' RETURN

curl -sS \
  -c "${staff_cookie}" \
  -X POST \
  -d 'username=staff&password=staff123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${staff_cookie}" \
  "${TEST_BASE_URL}/staff/stock-price.php" \
  -o "${page_body}"

if ! grep -q 'Memantau Pengembalian' "${page_body}"; then
  echo 'Expected staff stock and price page to render the requested replacement heading'
  exit 1
fi

if ! grep -q '<table class="w-full">' "${page_body}" || ! grep -q 'Edit Mode' "${page_body}" || ! grep -q 'floating-nav' "${page_body}" || ! grep -q 'name="discount_enabled' "${page_body}" || ! grep -q 'data-stock-adjust' "${page_body}" || ! grep -q 'stock-price-page-numbers' "${page_body}" || ! grep -q '© 2026 LensCraft. Sistem Rental Kamera.' "${page_body}"; then
  echo 'Expected staff stock and price page to render edit mode controls, pagination, and footer'
  exit 1
fi

if grep -q 'name="stock_available\[' "${page_body}"; then
  echo 'Expected stock tersedia to be derived automatically instead of editable'
  exit 1
fi

if grep -q 'Diskon aktif' "${page_body}"; then
  echo 'Expected staff stock and price page to remove the old discount badge text'
  exit 1
fi

if grep -q '>Status<' "${page_body}" || grep -q '>Actions<' "${page_body}"; then
  echo 'Expected staff stock and price table to remove Status and Actions columns'
  exit 1
fi

csrf_token="$(
  grep -o 'name="csrf_token" value="[^"]*"' "${page_body}" \
    | head -n 1 \
    | sed 's/^name="csrf_token" value="//; s/"$//'
)"

if [[ -z "${csrf_token}" ]]; then
  echo 'Expected staff stock and price page to include a CSRF token'
  exit 1
fi

product_id="$(mysql_test -N -B lenscraft -e "SELECT id FROM products ORDER BY id ASC LIMIT 1")"

curl -sS \
  -b "${staff_cookie}" \
  -D "${update_headers}" \
  -o /dev/null \
  -X POST \
  --data-urlencode "csrf_token=${csrf_token}" \
  --data-urlencode "product_ids[]=${product_id}" \
  --data-urlencode "price_per_day[${product_id}]=275" \
  --data-urlencode "discount_enabled[${product_id}]=1" \
  --data-urlencode "discount_percentage[${product_id}]=15" \
  --data-urlencode "stock_total[${product_id}]=7" \
  "${TEST_BASE_URL}/process/staff-stock-price-bulk-update.php"

update_location="$(awk 'tolower($1) == "location:" {print $2}' "${update_headers}" | tr -d '\r')"

if [[ "${update_location}" != "/staff/stock-price.php" ]]; then
  echo "Expected stock/price update to redirect to /staff/stock-price.php, got ${update_location:-<empty>}"
  exit 1
fi

updated_row="$(mysql_test -N -B lenscraft -e "SELECT CONCAT(price_per_day, ',', discount_percentage, ',', stock_total, ',', stock_available) FROM products WHERE id = ${product_id}")"

if [[ "${updated_row}" != "275.00,15,7,6" ]]; then
  echo "Expected product ${product_id} stock/price update to persist, got ${updated_row:-<empty>}"
  exit 1
fi

echo "OK: staff stock and price page updates product pricing and stock"
