#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

user_cookie="$(mktemp)"
body_file="$(mktemp)"
trap 'rm -f "$user_cookie" "$body_file"' RETURN

curl -sS \
  -c "$user_cookie" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "$user_cookie" \
  "${TEST_BASE_URL}/product-detail.php?id=1" \
  -o "$body_file"

if ! grep -q '@media (max-width: 640px)' "$body_file"; then
  echo 'Expected product detail floating nav to include the mobile breakpoint block'
  exit 1
fi

if ! grep -q 'min-width: 3.1rem;' "$body_file" || ! grep -q 'height: 3.1rem;' "$body_file"; then
  echo 'Expected product detail floating nav buttons to use the same mobile size as the products page'
  exit 1
fi

if ! grep -q 'font-size: 0.49rem;' "$body_file"; then
  echo 'Expected product detail floating nav labels to use the same mobile font size as the products page'
  exit 1
fi

echo 'OK: product detail floating nav matches the shared mobile sizing rules'
