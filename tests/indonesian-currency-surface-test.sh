#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

cookie_file="$(mktemp)"
product_page="$(mktemp)"
rentals_page="$(mktemp)"
trap 'rm -f "${cookie_file}" "${product_page}" "${rentals_page}"' RETURN

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS "${TEST_BASE_URL}/product-detail.php?id=1" -o "${product_page}"
curl -sS -b "${cookie_file}" "${TEST_BASE_URL}/user/rentals.php" -o "${rentals_page}"

for page in "${product_page}" "${rentals_page}"; do
  if grep -q '\$\$' "${page}" || grep -q '\$0\.00' "${page}" || grep -q '\$/day' "${page}"; then
    echo "Expected Indonesian currency surfaces to omit raw dollar strings in ${page}"
    exit 1
  fi
done

if ! grep -q 'Rp' "${product_page}"; then
  echo 'Expected product detail page to render Rupiah labels'
  exit 1
fi

if ! grep -q '"price":500000' "${product_page}"; then
  echo 'Expected product detail page to expose realistic seeded Rupiah prices in the product payload'
  exit 1
fi

if ! grep -q 'formatCurrencyIDR(50000)' "${product_page}"; then
  echo 'Expected product detail page to use a realistic Rupiah delivery fee'
  exit 1
fi

if ! grep -q 'Rp' "${rentals_page}"; then
  echo 'Expected user rentals page to render Rupiah labels'
  exit 1
fi

if ! grep -q '"dailyRate":350000' "${rentals_page}" || ! grep -q '"deliveryFee":50000' "${rentals_page}" || ! grep -q '"total":1100000' "${rentals_page}"; then
  echo 'Expected user rentals payload to reflect realistic seeded Rupiah totals'
  exit 1
fi

echo 'OK: representative customer surfaces render realistic Rupiah pricing'
