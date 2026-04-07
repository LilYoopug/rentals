#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

before_count="$(mysql_test -N -B lenscraft -e "SELECT COUNT(*) FROM password_resets")"

curl -sS \
  -X POST \
  -d 'email=user@example.com' \
  "${TEST_BASE_URL}/process/forgot-password-process.php" \
  -o /dev/null

after_count="$(mysql_test -N -B lenscraft -e "SELECT COUNT(*) FROM password_resets")"
if [[ "${before_count}" != "${after_count}" ]]; then
  echo "Expected forgot-password flow to avoid creating password reset tokens"
  exit 1
fi

headers_file="$(mktemp)"
trap 'rm -f "${headers_file}"' RETURN

curl -sS -D "${headers_file}" -o /dev/null "${TEST_BASE_URL}/reset-password.php?token=fake-token"
location="$(awk 'tolower($1) == "location:" {print $2}' "${headers_file}" | tr -d '\r')"
if [[ "${location}" != "/login.php" ]]; then
  echo "Expected token reset page to redirect to /login.php, got ${location:-<empty>}"
  exit 1
fi

curl -sS \
  -D "${headers_file}" \
  -o /dev/null \
  -X POST \
  -d 'token=fake-token&new_password=newpass123&confirm_password=newpass123' \
  "${TEST_BASE_URL}/process/reset-password-process.php"

location="$(awk 'tolower($1) == "location:" {print $2}' "${headers_file}" | tr -d '\r')"
if [[ "${location}" != "/login.php" ]]; then
  echo "Expected reset-password process to redirect to /login.php, got ${location:-<empty>}"
  exit 1
fi

curl -sS \
  -D "${headers_file}" \
  -o /dev/null \
  -X POST \
  -d 'username=user&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php"

location="$(awk 'tolower($1) == "location:" {print $2}' "${headers_file}" | tr -d '\r')"
if [[ "${location}" != "/products.php" ]]; then
  echo "Expected standard session login to keep working, got ${location:-<empty>}"
  exit 1
fi

echo "OK: forgot-password is fake-only and real login remains session-based"
