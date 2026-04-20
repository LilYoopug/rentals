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

if ! grep -q 'Dashboard Petugas' "${page_body}"; then
  echo 'Expected staff dashboard to render the dashboard heading'
  exit 1
fi

if grep -q 'Dashboard Petugas Ringkasan' "${page_body}"; then
  echo 'Expected staff dashboard heading to remove the duplicated Ringkasan wording'
  exit 1
fi

echo 'OK: staff dashboard copy renders the corrected heading'
