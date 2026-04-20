#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

cookie_file="$(mktemp)"
body_file="$(mktemp)"
trap 'rm -f "${cookie_file}" "${body_file}"' EXIT

mysql_test lenscraft <<'SQL'
UPDATE users
SET
  address_line1 = 'Jl. Braga No. 12',
  address_line2 = 'Unit 5B',
  city = 'Bandung',
  province = 'Jawa Barat',
  zip_code = '40111',
  country = 'Indonesia'
WHERE username = 'pelanggan';
SQL

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${cookie_file}" \
  "${TEST_BASE_URL}/product-detail.php?id=1" \
  -o "${body_file}"

if ! grep -q 'id="confirm-delivery-address-row"' "${body_file}"; then
  echo 'Expected confirmation modal to include a dedicated delivery address row'
  exit 1
fi

if ! grep -q 'id="confirm-delivery-address"' "${body_file}"; then
  echo 'Expected confirmation modal to include a delivery address target element'
  exit 1
fi

if ! grep -q 'function formatDeliveryAddress' "${body_file}"; then
  echo 'Expected product detail page to format the current user delivery address for the confirmation modal'
  exit 1
fi

if ! grep -q "deliveryMethodInput.value === 'diantar'" "${body_file}"; then
  echo 'Expected confirmation modal logic to show the address only for delivery requests'
  exit 1
fi

if ! grep -q 'Jl. Braga No. 12' "${body_file}"; then
  echo 'Expected logged-in user payload to include the saved delivery address'
  exit 1
fi

echo 'OK: product detail confirmation modal includes delivery-only address support'
