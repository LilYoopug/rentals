#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

check_login_redirect() {
  local username="$1"
  local password="$2"
  local expected="$3"
  local headers_file
  headers_file="$(mktemp)"

  curl -sS \
    -D "${headers_file}" \
    -o /dev/null \
    -X POST \
    -d "username=${username}&password=${password}" \
    "${TEST_BASE_URL}/process/login-process.php"

  local location
  location="$(awk 'tolower($1) == "location:" {print $2}' "${headers_file}" | tr -d '\r')"

  rm -f "${headers_file}"

  if [[ "${location}" != "${expected}" ]]; then
    echo "Expected ${username} login to redirect to ${expected}, got ${location:-<empty>}"
    exit 1
  fi
}

check_login_redirect admin admin123 /admin/index.php
check_login_redirect staff staff123 /staff/index.php
check_login_redirect user user123 /products.php

echo "OK: advertised demo credentials log in successfully"
