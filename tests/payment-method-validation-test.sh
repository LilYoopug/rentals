#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

customer_cookie="$(mktemp)"
trap 'rm -f "${customer_cookie}"' RETURN

mysql_test lenscraft <<'SQL'
INSERT INTO rentals (
  rental_code, user_id, product_id, start_date, end_date, total_days,
  daily_rate, discount_percentage, delivery_method, delivery_fee, total_price,
  status, created_at, approved_at
) VALUES
(
  'RENT-PAY-METHOD-BANK',
  3,
  3,
  CURDATE(),
  DATE_ADD(CURDATE(), INTERVAL 1 DAY),
  2,
  59.00,
  0,
  'ambil_sendiri',
  0.00,
  118.00,
  'disetujui',
  NOW(),
  NOW()
),
(
  'RENT-PAY-METHOD-CC',
  3,
  4,
  CURDATE(),
  DATE_ADD(CURDATE(), INTERVAL 1 DAY),
  2,
  75.00,
  0,
  'ambil_sendiri',
  0.00,
  150.00,
  'disetujui',
  NOW(),
  NOW()
);

INSERT INTO payments (payment_code, rental_id, amount, method, status, created_at, updated_at)
SELECT CONCAT('PAY-', rental_code), id, total_price, 'transfer_bank', 'pending', NOW(), NOW()
FROM rentals WHERE rental_code IN ('RENT-PAY-METHOD-BANK', 'RENT-PAY-METHOD-CC');
SQL

curl -sS \
  -c "${customer_cookie}" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

customer_session_id="$(awk '$6 == "PHPSESSID" {print $7}' "${customer_cookie}")"
customer_csrf_token="$(php -r 'session_id($argv[1]); session_start(); echo $_SESSION["csrf_token"] ?? "";' "${customer_session_id}")"

bank_response="$(curl -sS \
  -b "${customer_cookie}" \
  -X POST \
  -d "csrf_token=${customer_csrf_token}&rental_code=RENT-PAY-METHOD-BANK&method=transfer_bank" \
  "${TEST_BASE_URL}/process/rental-payment-process.php")"

if [[ "${bank_response}" != *'"success":true'* ]]; then
  echo "Expected transfer bank payment to succeed without identity/contact fields, got: ${bank_response}"
  exit 1
fi

invalid_cc_response="$(curl -sS \
  -b "${customer_cookie}" \
  -X POST \
  -d "csrf_token=${customer_csrf_token}&rental_code=RENT-PAY-METHOD-CC&method=kartu_kredit" \
  "${TEST_BASE_URL}/process/rental-payment-process.php")"

if [[ "${invalid_cc_response}" != *'"success":false'* ]]; then
  echo "Expected kartu kredit payment without card fields to fail, got: ${invalid_cc_response}"
  exit 1
fi

short_cc_response="$(curl -sS \
  -b "${customer_cookie}" \
  -X POST \
  -d "csrf_token=${customer_csrf_token}&rental_code=RENT-PAY-METHOD-CC&method=kartu_kredit&card_number=4111 1111&card_expiry=12/30&card_cvv=123" \
  "${TEST_BASE_URL}/process/rental-payment-process.php")"

if [[ "${short_cc_response}" != *'"success":false'* ]]; then
  echo "Expected kartu kredit payment with short card number to fail, got: ${short_cc_response}"
  exit 1
fi

bad_expiry_response="$(curl -sS \
  -b "${customer_cookie}" \
  -X POST \
  -d "csrf_token=${customer_csrf_token}&rental_code=RENT-PAY-METHOD-CC&method=kartu_kredit&card_number=4111 1111 1111 1111&card_expiry=1a/30&card_cvv=123" \
  "${TEST_BASE_URL}/process/rental-payment-process.php")"

if [[ "${bad_expiry_response}" != *'"success":false'* ]]; then
  echo "Expected kartu kredit payment with invalid expiry to fail, got: ${bad_expiry_response}"
  exit 1
fi

bad_cvv_response="$(curl -sS \
  -b "${customer_cookie}" \
  -X POST \
  -d "csrf_token=${customer_csrf_token}&rental_code=RENT-PAY-METHOD-CC&method=kartu_kredit&card_number=4111 1111 1111 1111&card_expiry=12/30&card_cvv=1234" \
  "${TEST_BASE_URL}/process/rental-payment-process.php")"

if [[ "${bad_cvv_response}" != *'"success":false'* ]]; then
  echo "Expected kartu kredit payment with invalid CVV to fail, got: ${bad_cvv_response}"
  exit 1
fi

valid_cc_response="$(curl -sS \
  -b "${customer_cookie}" \
  -X POST \
  -d "csrf_token=${customer_csrf_token}&rental_code=RENT-PAY-METHOD-CC&method=kartu_kredit&card_number=4111111111111111&card_expiry=12/30&card_cvv=123" \
  "${TEST_BASE_URL}/process/rental-payment-process.php")"

if [[ "${valid_cc_response}" != *'"success":true'* ]]; then
  echo "Expected kartu kredit payment with card fields to succeed, got: ${valid_cc_response}"
  exit 1
fi

echo "OK: payment process uses method-specific validation rules"
