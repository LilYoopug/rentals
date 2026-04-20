#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

staff_cookie="$(mktemp)"
customer_cookie="$(mktemp)"
trap 'rm -f "${staff_cookie}" "${customer_cookie}"' RETURN

mysql_test lenscraft <<'SQL'
INSERT INTO rentals (
  rental_code, user_id, product_id, start_date, end_date, total_days,
  daily_rate, discount_percentage, delivery_method, delivery_fee, total_price,
  status, created_at
) VALUES (
  'RENT-PAY-GATE-001',
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
  'menunggu',
  NOW()
);
SQL

curl -sS \
  -c "${staff_cookie}" \
  -X POST \
  -d 'username=petugas&password=staff123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

staff_session_id="$(awk '$6 == "PHPSESSID" {print $7}' "${staff_cookie}")"
staff_csrf_token="$(php -r 'session_id($argv[1]); session_start(); echo $_SESSION["csrf_token"] ?? "";' "${staff_session_id}")"

curl -sS \
  -b "${staff_cookie}" \
  -X POST \
  -d "csrf_token=${staff_csrf_token}&rental_code=RENT-PAY-GATE-001" \
  "${TEST_BASE_URL}/process/staff-peminjaman-approve.php" \
  -o /dev/null

approved_status="$(mysql_test -N -B lenscraft -e "SELECT status FROM rentals WHERE rental_code = 'RENT-PAY-GATE-001'")"
if [[ "${approved_status}" != "disetujui" ]]; then
  echo "Expected staff approval to move rental into disetujui, got: ${approved_status:-<empty>}"
  exit 1
fi

payment_count="$(mysql_test -N -B lenscraft -e "SELECT COUNT(*) FROM payments p JOIN rentals r ON r.id = p.rental_id WHERE r.rental_code = 'RENT-PAY-GATE-001'")"
if [[ "${payment_count}" != "1" ]]; then
  echo "Expected one pending payment row after approval, got: ${payment_count:-<empty>}"
  exit 1
fi

pending_payment_status="$(mysql_test -N -B lenscraft -e "SELECT p.status FROM payments p JOIN rentals r ON r.id = p.rental_id WHERE r.rental_code = 'RENT-PAY-GATE-001' LIMIT 1")"
if [[ "${pending_payment_status}" != "pending" ]]; then
  echo "Expected payment status pending after approval, got: ${pending_payment_status:-<empty>}"
  exit 1
fi

curl -sS \
  -c "${customer_cookie}" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

customer_session_id="$(awk '$6 == "PHPSESSID" {print $7}' "${customer_cookie}")"
customer_csrf_token="$(php -r 'session_id($argv[1]); session_start(); echo $_SESSION["csrf_token"] ?? "";' "${customer_session_id}")"

payment_response="$(curl -sS \
  -b "${customer_cookie}" \
  -X POST \
  -d "csrf_token=${customer_csrf_token}&rental_code=RENT-PAY-GATE-001&method=transfer_bank" \
  "${TEST_BASE_URL}/process/rental-payment-process.php")"

if [[ "${payment_response}" != *'"success":true'* ]]; then
  echo "Expected fake payment submit to succeed, got: ${payment_response}"
  exit 1
fi

paid_payment_status="$(mysql_test -N -B lenscraft -e "SELECT p.status FROM payments p JOIN rentals r ON r.id = p.rental_id WHERE r.rental_code = 'RENT-PAY-GATE-001' LIMIT 1")"
if [[ "${paid_payment_status}" != "paid" ]]; then
  echo "Expected payment row to become paid, got: ${paid_payment_status:-<empty>}"
  exit 1
fi

active_rental_status="$(mysql_test -N -B lenscraft -e "SELECT status FROM rentals WHERE rental_code = 'RENT-PAY-GATE-001'")"
if [[ "${active_rental_status}" != "aktif" ]]; then
  echo "Expected paid rental to become aktif, got: ${active_rental_status:-<empty>}"
  exit 1
fi

echo "OK: staff approval creates pending payment and fake payment activation moves rental to aktif"

