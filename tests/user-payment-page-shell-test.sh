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
  'RENT-PAY-SHELL-001',
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
SELECT 'PAY-SHELL-001', id, total_price, 'transfer_bank', 'pending', NOW(), NOW()
FROM rentals WHERE rental_code = 'RENT-PAY-SHELL-001';
SQL

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS -b "${cookie_file}" "${TEST_BASE_URL}/user/payment.php?rental=RENT-PAY-SHELL-001" -o "${page_body}"

if ! grep -q 'nav-blur border-b border-neutral-800 h-16' "${page_body}"; then
  echo 'Expected payment page to reuse the customer navbar shell'
  exit 1
fi

if ! grep -q '© 2026 LensCraft. Sistem Rental Kamera.' "${page_body}"; then
  echo 'Expected payment page to reuse the shared footer copy'
  exit 1
fi

if grep -q 'class="floating-nav"' "${page_body}" || grep -q 'floating-nav-btn' "${page_body}"; then
  echo 'Expected payment page to omit the floating nav'
  exit 1
fi

if ! grep -q 'max-w-7xl mx-auto' "${page_body}"; then
  echo 'Expected payment page main content to use the shared container width'
  exit 1
fi

if ! grep -q 'Bayar Sekarang' "${page_body}"; then
  echo 'Expected payment page to render the pay CTA'
  exit 1
fi

echo 'OK: payment page reuses the shared shell and omits the floating nav'
