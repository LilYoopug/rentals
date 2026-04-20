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
  'RENT-PAY-STYLE-001',
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
SELECT 'PAY-STYLE-001', id, total_price, 'transfer_bank', 'pending', NOW(), NOW()
FROM rentals WHERE rental_code = 'RENT-PAY-STYLE-001';
SQL

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS -b "${cookie_file}" "${TEST_BASE_URL}/user/payment.php?rental=RENT-PAY-STYLE-001" -o "${page_body}"

for marker in \
  'max-w-7xl mx-auto animate-fade-in' \
  'text-3xl md:text-4xl font-serif text-white mb-2' \
  'text-neutral-400' \
  'filter-shell rounded-\[1.6rem\] p-4' \
  'bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden' \
  'bg-white text-black hover:bg-neutral-200'; do
  if ! grep -q "${marker}" "${page_body}"; then
    echo "Expected payment page to reuse shared customer-page style marker: ${marker}"
    exit 1
  fi
done

for forbidden in \
  'uppercase tracking-\[0.3em\]' \
  'rounded-\[1.8rem\]' \
  'rounded-\[1.6rem\] overflow-hidden bg-neutral-900 border border-neutral-800'; do
  if grep -q "${forbidden}" "${page_body}"; then
    echo "Expected payment page to remove drifting custom style marker: ${forbidden}"
    exit 1
  fi
done

echo 'OK: payment page aligns with the shared customer-page styling language'
