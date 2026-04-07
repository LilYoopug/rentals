#!/usr/bin/env bash
set -euo pipefail

base_url="${1:-http://127.0.0.1:8000}"
cookie_file="$(mktemp)"
headers_file="$(mktemp)"
trap 'rm -f "$cookie_file" "$headers_file"' EXIT

curl -sS \
  -c "$cookie_file" \
  -X POST \
  -d 'username=admin&password=admin123' \
  "$base_url/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "$cookie_file" \
  -D "$headers_file" \
  -o /dev/null \
  "$base_url/admin/index.php"

status="$(awk 'NR==1 {print $2}' "$headers_file")"

if [[ "$status" != "200" ]]; then
  echo "Expected admin dashboard to return 200, got $status"
  exit 1
fi

echo "OK: admin dashboard returns 200 for admin session"
