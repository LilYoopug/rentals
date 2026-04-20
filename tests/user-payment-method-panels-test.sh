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
  'RENT-PAY-PANELS-001',
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
SELECT 'PAY-PANELS-001', id, total_price, 'transfer_bank', 'pending', NOW(), NOW()
FROM rentals WHERE rental_code = 'RENT-PAY-PANELS-001';
SQL

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS -b "${cookie_file}" "${TEST_BASE_URL}/user/payment.php?rental=RENT-PAY-PANELS-001" -o "${page_body}"

summary_line="$(grep -n 'Ringkasan Rental' "${page_body}" | head -n1 | cut -d: -f1)"
methods_line="$(grep -n 'Metode Pembayaran' "${page_body}" | head -n1 | cut -d: -f1)"

if [[ -z "${summary_line}" || -z "${methods_line}" ]]; then
  echo 'Expected payment page to contain both Ringkasan Rental and Metode Pembayaran sections'
  exit 1
fi

if (( summary_line >= methods_line )); then
  echo 'Expected product summary to appear above the payment methods section'
  exit 1
fi

for label in 'Transfer Bank' 'QRIS' 'Kartu Kredit'; do
  if ! grep -q "${label}" "${page_body}"; then
    echo "Expected payment page to render ${label} as a payment option"
    exit 1
  fi
done

for forbidden in 'E-Wallet' 'DANA'; do
  if grep -q "${forbidden}" "${page_body}"; then
    echo "Expected payment page to remove ${forbidden} from the payment options"
    exit 1
  fi
done

for panel_id in 'payment-panel-transfer-bank' 'payment-panel-qris' 'payment-panel-kartu-kredit'; do
  if ! grep -q "id=\"${panel_id}\"" "${page_body}"; then
    echo "Expected payment page to expose method-specific panel ${panel_id}"
    exit 1
  fi
done

for forbidden_label in 'Nama Pembayar' 'Email Konfirmasi' 'Nomor HP'; do
  if grep -q "${forbidden_label}" "${page_body}"; then
    echo "Expected payment page to remove shared identity/contact field ${forbidden_label}"
    exit 1
  fi
done

for required_marker in 'Nomor Virtual Account' 'QRIS Merchant' 'Nomor Kartu' 'Masa Berlaku' 'CVV'; do
  if ! grep -q "${required_marker}" "${page_body}"; then
    echo "Expected payment page to render method-specific detail ${required_marker}"
    exit 1
  fi
done

if ! grep -q 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQHhF-CpgeTS_MLS4zibhWbnO1K28aqI175euutV238WQ&s=10' "${page_body}"; then
  echo 'Expected QRIS panel to use the provided hosted QR image source'
  exit 1
fi

if grep -q 'svg viewBox=\"0 0 128 128\"' "${page_body}"; then
  echo 'Expected QRIS panel to stop using the inline SVG QR placeholder'
  exit 1
fi

if ! grep -q 'togglePaymentMethodPanels' "${page_body}"; then
  echo 'Expected payment page to include client-side logic for switching visible payment panels'
  exit 1
fi

echo 'OK: payment page shows top summary and method-specific payment panels'
