#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/helpers/test-env.sh"

start_test_stack

cookie_file="$(mktemp)"
guest_products_body="$(mktemp)"
guest_detail_body="$(mktemp)"
user_products_body="$(mktemp)"
user_detail_body="$(mktemp)"
trap 'rm -f "$cookie_file" "$guest_products_body" "$guest_detail_body" "$user_products_body" "$user_detail_body"' RETURN

curl -sS "$TEST_BASE_URL/products.php" -o "$guest_products_body"
curl -sS "$TEST_BASE_URL/product-detail.php?id=1" -o "$guest_detail_body"

if grep -q '<nav class="floating-nav"' "$guest_products_body"; then
  echo 'Expected shared catalog page to hide the floating nav for guests'
  exit 1
fi

if grep -q '<nav class="floating-nav"' "$guest_detail_body"; then
  echo 'Expected shared product detail page to hide the floating nav for guests'
  exit 1
fi

curl -sS \
  -c "$cookie_file" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "$TEST_BASE_URL/process/login-process.php" \
  -o /dev/null

curl -sS -b "$cookie_file" "$TEST_BASE_URL/products.php" -o "$user_products_body"
curl -sS -b "$cookie_file" "$TEST_BASE_URL/product-detail.php?id=1" -o "$user_detail_body"

if ! grep -q '<nav class="floating-nav"' "$user_products_body"; then
  echo 'Expected shared catalog page to show the floating nav for logged-in customers'
  exit 1
fi

if ! grep -q '<nav class="floating-nav"' "$user_detail_body"; then
  echo 'Expected shared product detail page to show the floating nav for logged-in customers'
  exit 1
fi

echo 'OK: shared catalog and product detail floating nav only renders for logged-in customers'
