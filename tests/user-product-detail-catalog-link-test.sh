#!/usr/bin/env bash
set -euo pipefail

base_url="${1:-http://127.0.0.1:8000}"
user_headers="$(mktemp)"
detail_headers="$(mktemp)"
trap 'rm -f "$user_headers" "$detail_headers"' EXIT

curl -sS -D "$user_headers" -o /dev/null -I "$base_url/user/index.php"
curl -sS -D "$detail_headers" -o /dev/null -I "$base_url/user/product-detail.php?id=1"

user_location="$(awk 'tolower($1) == "location:" {print $2}' "$user_headers" | tr -d '\r')"
detail_location="$(awk 'tolower($1) == "location:" {print $2}' "$detail_headers" | tr -d '\r')"

if [[ "$user_location" != "/products.php" ]]; then
  echo "Expected /user/index.php to redirect to /products.php, got ${user_location:-<empty>}"
  exit 1
fi

if [[ "$detail_location" != "/product-detail.php?id=1" ]]; then
  echo "Expected /user/product-detail.php?id=1 to redirect to /product-detail.php?id=1, got ${detail_location:-<empty>}"
  exit 1
fi

echo 'OK: legacy user catalog/detail routes redirect to shared routes'
