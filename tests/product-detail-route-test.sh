#!/usr/bin/env bash
set -euo pipefail

base_url="${1:-http://127.0.0.1:8000}"
cookie_file="$(mktemp)"
body_file="$(mktemp)"
trap 'rm -f "$cookie_file" "$body_file"' EXIT

curl -sS \
  -c "$cookie_file" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "$base_url/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "$cookie_file" \
  "$base_url/product-detail.php?id=1" \
  -o "$body_file"

if ! grep -q 'title="Logout"' "$body_file"; then
  echo 'Expected shared product detail page to show logout for logged-in users'
  exit 1
fi

if ! grep -q 'Sudah masuk' "$body_file"; then
  echo 'Expected shared product detail page to show logged-in account state'
  exit 1
fi

if ! grep -q '<nav class="floating-nav"' "$body_file"; then
  echo 'Expected shared product detail page to show floating nav for logged-in users'
  exit 1
fi

echo 'OK: shared product detail renders logged-in user state'
