#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

cookie_file="$(mktemp)"
owner_headers="$(mktemp)"
other_headers="$(mktemp)"
active_headers="$(mktemp)"
owner_body="$(mktemp)"
trap 'rm -f "${cookie_file}" "${owner_headers}" "${other_headers}" "${active_headers}" "${owner_body}"' RETURN

mysql_test lenscraft <<'SQL'
INSERT INTO rentals (
  rental_code, user_id, product_id, start_date, end_date, total_days,
  daily_rate, discount_percentage, delivery_method, delivery_fee, total_price,
  status, created_at, approved_at
) VALUES
(
  'RENT-PAY-ACCESS-OWN',
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
  'RENT-PAY-ACCESS-OTHER',
  4,
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
),
(
  'RENT-PAY-ACCESS-ACTIVE',
  3,
  5,
  CURDATE(),
  DATE_ADD(CURDATE(), INTERVAL 1 DAY),
  2,
  90.00,
  0,
  'ambil_sendiri',
  0.00,
  180.00,
  'aktif',
  NOW(),
  NOW()
);

INSERT INTO payments (payment_code, rental_id, amount, method, status, created_at, updated_at)
SELECT 'PAY-ACCESS-OWN', id, total_price, 'transfer_bank', 'pending', NOW(), NOW()
FROM rentals WHERE rental_code = 'RENT-PAY-ACCESS-OWN';

INSERT INTO payments (payment_code, rental_id, amount, method, status, created_at, updated_at)
SELECT 'PAY-ACCESS-OTHER', id, total_price, 'transfer_bank', 'pending', NOW(), NOW()
FROM rentals WHERE rental_code = 'RENT-PAY-ACCESS-OTHER';

INSERT INTO payments (payment_code, rental_id, amount, method, status, created_at, updated_at, paid_at)
SELECT 'PAY-ACCESS-ACTIVE', id, total_price, 'transfer_bank', 'paid', NOW(), NOW(), NOW()
FROM rentals WHERE rental_code = 'RENT-PAY-ACCESS-ACTIVE';
SQL

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

owner_code="$(curl -sS -b "${cookie_file}" -D "${owner_headers}" -o "${owner_body}" -w '%{http_code}' "${TEST_BASE_URL}/user/payment.php?rental=RENT-PAY-ACCESS-OWN")"
if [[ "${owner_code}" != "200" ]]; then
  echo "Expected owner to load payment page with HTTP 200, got: ${owner_code}"
  exit 1
fi

if ! grep -q 'RENT-PAY-ACCESS-OWN' "${owner_body}"; then
  echo 'Expected owner payment page to include the rental code'
  exit 1
fi

curl -sS -b "${cookie_file}" -D "${other_headers}" -o /dev/null "${TEST_BASE_URL}/user/payment.php?rental=RENT-PAY-ACCESS-OTHER"
other_location="$(awk 'tolower($1) == "location:" {print $2}' "${other_headers}" | tr -d '\r')"
if [[ "${other_location}" != "/user/rentals.php" ]]; then
  echo "Expected other user's rental payment page to redirect to /user/rentals.php, got: ${other_location:-<empty>}"
  exit 1
fi

curl -sS -b "${cookie_file}" -D "${active_headers}" -o /dev/null "${TEST_BASE_URL}/user/payment.php?rental=RENT-PAY-ACCESS-ACTIVE"
active_location="$(awk 'tolower($1) == "location:" {print $2}' "${active_headers}" | tr -d '\r')"
if [[ "${active_location}" != "/user/rentals.php" ]]; then
  echo "Expected active rental payment page to redirect to /user/rentals.php, got: ${active_location:-<empty>}"
  exit 1
fi

echo "OK: payment page is owner-only and blocks non-payable rental states"

