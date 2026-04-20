#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

cookie_file="$(mktemp)"
page_body="$(mktemp)"
trap 'rm -f "${cookie_file}" "${page_body}"' RETURN

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=petugas&password=staff123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${cookie_file}" \
  "${TEST_BASE_URL}/staff/index.php" \
  -o "${page_body}"

if ! perl -0ne 'exit 0 if /text-3xl[^>]*>\s*1\s*<\/div>\s*<div class="text-sm text-neutral-400">Pengembalian Hari Ini<\/div>/s; exit 1' "${page_body}"; then
  echo 'Expected staff dashboard to show 1 return for today'
  exit 1
fi

if ! perl -0ne 'exit 0 if /text-3xl[^>]*>\s*Rp1\.850\.000,00\s*<\/div>\s*<div class="text-sm text-neutral-400">Pendapatan Hari Ini<\/div>/s; exit 1' "${page_body}"; then
  echo 'Expected staff dashboard to show Rp1.850.000,00 revenue for today'
  exit 1
fi

echo 'OK: staff dashboard today metrics reflect seeded data'
