#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

body_file="$(mktemp)"
headers_file="$(mktemp)"
trap 'rm -f "${body_file}" "${headers_file}"' RETURN

curl -sS "${TEST_BASE_URL}/login.php" -o "${body_file}"

if grep -qi 'Ingat saya' "${body_file}" || grep -q 'name="remember"' "${body_file}"; then
  echo "Expected login page to omit remember-me UI"
  exit 1
fi

curl -sS \
  -D "${headers_file}" \
  -o /dev/null \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php"

location="$(awk 'tolower($1) == "location:" {print $2}' "${headers_file}" | tr -d '\r')"
if [[ "${location}" != "/products.php" ]]; then
  echo "Expected standard session login to keep working, got ${location:-<empty>}"
  exit 1
fi

echo "OK: remember me removed and session login still works"
