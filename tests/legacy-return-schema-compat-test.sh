#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

password_hash="$(php -r 'echo password_hash("password", PASSWORD_DEFAULT);')"
mysql_test lenscraft <<SQL
UPDATE users SET password = '${password_hash}' WHERE username = 'pelanggan';
DELETE FROM returns;
ALTER TABLE returns MODIFY COLUMN status ENUM('pending','completed') NOT NULL DEFAULT 'pending';
SQL

cookie_file="$(mktemp)"
trap 'rm -f "${cookie_file}"' RETURN

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=pelanggan&password=password' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

csrf_token="$(php -r 'session_id($argv[1]); session_start(); echo $_SESSION["csrf_token"] ?? "";' "$(awk '$6 == "PHPSESSID" {print $7}' "${cookie_file}")")"

return_response="$(curl -sS -b "${cookie_file}" -X POST -d "csrf_token=${csrf_token}&rental_code=RENT-2026-A001" "${TEST_BASE_URL}/process/rental-return-process.php")"
if [[ "${return_response}" != *'"success":true'* ]]; then
  echo "Expected return request to succeed against legacy returns schema, got: ${return_response}"
  exit 1
fi

return_status="$(mysql_test -N -B lenscraft -e "SELECT rt.status FROM returns rt JOIN rentals r ON r.id = rt.rental_id WHERE r.rental_code = 'RENT-2026-A001' LIMIT 1")"
if [[ "${return_status}" != "pending" ]]; then
  echo "Expected legacy returns schema to store pending, got: ${return_status:-<empty>}"
  exit 1
fi

echo "OK: return requests stay compatible with legacy english returns status enums"
