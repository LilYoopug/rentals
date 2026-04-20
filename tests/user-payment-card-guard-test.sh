#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

cookie_file="$(mktemp)"
page_body="$(mktemp)"
trap 'rm -f "${cookie_file}" "${page_body}"' RETURN

mysql_test lenscraft <<'SQL'
INSERT INTO rentals (
  rental_code, user_id, product_id, start_date, end_date, total_days,
  daily_rate, discount_percentage, delivery_method, delivery_fee, total_price,
  status, created_at, approved_at
) VALUES (
  'RENT-PAY-GUARD-001',
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
);

INSERT INTO payments (payment_code, rental_id, amount, method, status, created_at, updated_at)
SELECT 'PAY-GUARD-001', id, total_price, 'transfer_bank', 'pending', NOW(), NOW()
FROM rentals WHERE rental_code = 'RENT-PAY-GUARD-001';
SQL

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS -b "${cookie_file}" "${TEST_BASE_URL}/user/payment.php?rental=RENT-PAY-GUARD-001" -o "${page_body}"

if ! grep -q 'id="credit-card-number"' "${page_body}" || ! grep -q 'maxlength="19"' "${page_body}"; then
  echo 'Expected card number input to cap displayed length at 19 characters'
  exit 1
fi

if ! grep -q 'id="credit-card-expiry"' "${page_body}" || ! grep -q 'maxlength="5"' "${page_body}"; then
  echo 'Expected expiry input to cap displayed length at 5 characters'
  exit 1
fi

if ! grep -q 'id="credit-card-cvv"' "${page_body}" || ! grep -q 'maxlength="3"' "${page_body}"; then
  echo 'Expected CVV input to cap displayed length at 3 characters'
  exit 1
fi

for js_marker in 'formatCardNumberValue' 'formatCardExpiryValue' 'digitsOnly' 'creditCardNumberInput?.addEventListener' 'creditCardExpiryInput?.addEventListener' 'creditCardCvvInput?.addEventListener'; do
  if ! grep -q "${js_marker}" "${page_body}"; then
    echo "Expected payment page to include card input guard marker ${js_marker}"
    exit 1
  fi
done

echo 'OK: payment page exposes strict frontend guards for card inputs'
