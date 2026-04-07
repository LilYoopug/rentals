#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

password_hash="$(php -r 'echo password_hash("password", PASSWORD_DEFAULT);')"
mysql_test lenscraft <<SQL
UPDATE users SET password = '${password_hash}' WHERE username = 'user';
INSERT INTO users (fullname, email, username, password, role, status, created_at)
VALUES ('Other User', 'other@example.com', 'other', '${password_hash}', 'user', 'active', NOW());
SET @other_id = LAST_INSERT_ID();
INSERT INTO rentals (rental_code, user_id, product_id, start_date, end_date, total_days, daily_rate, discount_percentage, delivery_method, delivery_fee, total_price, status, created_at, approved_at)
VALUES ('RENT-2026-OTHER', @other_id, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 2, 89.00, 0, 'pickup', 0.00, 178.00, 'active', NOW(), NOW());
SQL

cookie_file="$(mktemp)"
trap 'rm -f "${cookie_file}"' RETURN

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=user&password=password' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

csrf_token="$(php -r 'session_id($argv[1]); session_start(); echo $_SESSION["csrf_token"] ?? "";' "$(awk '$6 == "PHPSESSID" {print $7}' "${cookie_file}")")"

other_return_response="$(curl -sS -b "${cookie_file}" -X POST -d "csrf_token=${csrf_token}&rental_code=RENT-2026-OTHER" "${TEST_BASE_URL}/process/rental-return-process.php")"
if [[ "${other_return_response}" != *'"success":false'* ]]; then
  echo "Expected returning another user's rental to fail, got: ${other_return_response}"
  exit 1
fi

first_return_response="$(curl -sS -b "${cookie_file}" -X POST -d "csrf_token=${csrf_token}&rental_code=RENT-2026-A001" "${TEST_BASE_URL}/process/rental-return-process.php")"
if [[ "${first_return_response}" != *'"success":true'* ]]; then
  echo "Expected first return attempt to succeed, got: ${first_return_response}"
  exit 1
fi

second_return_response="$(curl -sS -b "${cookie_file}" -X POST -d "csrf_token=${csrf_token}&rental_code=RENT-2026-A001" "${TEST_BASE_URL}/process/rental-return-process.php")"
if [[ "${second_return_response}" != *'"success":false'* ]]; then
  echo "Expected duplicate return attempt to fail, got: ${second_return_response}"
  exit 1
fi

mysql_test lenscraft <<'SQL'
UPDATE products SET stock_available = 0, in_stock = 0 WHERE id = 1;
SQL

rental_response="$(curl -sS -b "${cookie_file}" -X POST -d "csrf_token=${csrf_token}&product_id=1&start_date=2026-04-10&end_date=2026-04-11&delivery_method=pickup" "${TEST_BASE_URL}/process/rental-create-process.php")"
if [[ "${rental_response}" != *'"success":false'* ]]; then
  echo "Expected rental creation for out-of-stock product to fail, got: ${rental_response}"
  exit 1
fi

echo "OK: rental and return integrity checks hold"
