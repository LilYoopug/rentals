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
  'RENT-PAY-CTA-001',
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
SELECT 'PAY-CTA-001', id, total_price, 'transfer_bank', 'pending', NOW(), NOW()
FROM rentals WHERE rental_code = 'RENT-PAY-CTA-001';
SQL

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS -b "${cookie_file}" "${TEST_BASE_URL}/user/rentals.php" -o "${page_body}"

if ! grep -q 'RENT-PAY-CTA-001' "${page_body}"; then
  echo 'Expected rentals page payload to include the approved unpaid rental'
  exit 1
fi

if ! grep -q 'Bayar Sekarang' "${page_body}"; then
  echo 'Expected rentals page to render a Bayar Sekarang CTA for approved unpaid rentals'
  exit 1
fi

if ! grep -q 'payment.php?rental=\${rental.id}' "${page_body}"; then
  echo 'Expected Bayar Sekarang CTA template to link to the payment page route'
  exit 1
fi

echo 'OK: user rentals page exposes a Bayar Sekarang CTA for approved unpaid rentals'
