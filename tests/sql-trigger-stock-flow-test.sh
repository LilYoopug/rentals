#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

column_type="$(mysql_test -N -B information_schema -e "SELECT COLUMN_TYPE FROM COLUMNS WHERE TABLE_SCHEMA = 'lenscraft' AND TABLE_NAME = 'returns' AND COLUMN_NAME = 'status'")"
if [[ "${column_type}" != "enum('pending','completed')" ]]; then
  echo "Expected returns.status enum to remove flagged, got: ${column_type:-<empty>}"
  exit 1
fi

initial_stock="$(mysql_test -N -B lenscraft -e "SELECT stock_available FROM products WHERE id = 3")"

mysql_test lenscraft <<'SQL'
INSERT INTO rentals (
  rental_code, user_id, product_id, start_date, end_date, total_days,
  daily_rate, discount_percentage, delivery_method, delivery_fee, total_price, status, created_at
) VALUES (
  'RENT-TRIGGER-PENDING', 3, 3, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 2,
  59.00, 0, 'pickup', 0.00, 118.00, 'pending', NOW()
);
SQL

after_pending_stock="$(mysql_test -N -B lenscraft -e "SELECT stock_available FROM products WHERE id = 3")"
if [[ "${after_pending_stock}" != "$((initial_stock - 1))" ]]; then
  echo "Expected pending rental insert to reserve 1 stock, got ${after_pending_stock:-<empty>} from ${initial_stock:-<empty>}"
  exit 1
fi

mysql_test lenscraft <<'SQL'
UPDATE rentals
SET status = 'cancelled', cancelled_at = NOW(), cancel_reason = 'trigger test'
WHERE rental_code = 'RENT-TRIGGER-PENDING';
SQL

after_cancel_stock="$(mysql_test -N -B lenscraft -e "SELECT stock_available FROM products WHERE id = 3")"
if [[ "${after_cancel_stock}" != "${initial_stock}" ]]; then
  echo "Expected cancelled rental to release stock, got ${after_cancel_stock:-<empty>} from ${initial_stock:-<empty>}"
  exit 1
fi

mysql_test lenscraft <<'SQL'
INSERT INTO rentals (
  rental_code, user_id, product_id, start_date, end_date, total_days,
  daily_rate, discount_percentage, delivery_method, delivery_fee, total_price, status, created_at, approved_at
) VALUES (
  'RENT-TRIGGER-RETURN', 3, 3, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 2,
  59.00, 0, 'pickup', 0.00, 118.00, 'active', NOW(), NOW()
);
SQL

after_active_stock="$(mysql_test -N -B lenscraft -e "SELECT stock_available FROM products WHERE id = 3")"
if [[ "${after_active_stock}" != "$((initial_stock - 1))" ]]; then
  echo "Expected active rental insert to reserve 1 stock, got ${after_active_stock:-<empty>} from ${initial_stock:-<empty>}"
  exit 1
fi

rental_id="$(mysql_test -N -B lenscraft -e "SELECT id FROM rentals WHERE rental_code = 'RENT-TRIGGER-RETURN'")"

mysql_test lenscraft <<SQL
INSERT INTO returns (return_code, rental_id, processed_by, notes, status, returned_at, created_at)
VALUES ('RET-TRIGGER-RETURN', ${rental_id}, 2, 'Trigger completed return', 'completed', NOW(), NOW());
SQL

after_return_stock="$(mysql_test -N -B lenscraft -e "SELECT stock_available FROM products WHERE id = 3")"
if [[ "${after_return_stock}" != "${initial_stock}" ]]; then
  echo "Expected completed return to release stock, got ${after_return_stock:-<empty>} from ${initial_stock:-<empty>}"
  exit 1
fi

rental_status="$(mysql_test -N -B lenscraft -e "SELECT status FROM rentals WHERE rental_code = 'RENT-TRIGGER-RETURN'")"
if [[ "${rental_status}" != "completed" ]]; then
  echo "Expected completed return trigger to mark rental completed, got: ${rental_status:-<empty>}"
  exit 1
fi

mysql_test lenscraft <<'SQL'
INSERT INTO rentals (
  rental_code, user_id, product_id, start_date, end_date, total_days,
  daily_rate, discount_percentage, delivery_method, delivery_fee, total_price, status, created_at
) VALUES (
  'RENT-TRIGGER-DELETE', 3, 3, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 2,
  59.00, 0, 'pickup', 0.00, 118.00, 'pending', NOW()
);
DELETE FROM rentals WHERE rental_code = 'RENT-TRIGGER-DELETE';
SQL

after_delete_stock="$(mysql_test -N -B lenscraft -e "SELECT stock_available FROM products WHERE id = 3")"
if [[ "${after_delete_stock}" != "${initial_stock}" ]]; then
  echo "Expected deleting reserved rental to release stock, got ${after_delete_stock:-<empty>} from ${initial_stock:-<empty>}"
  exit 1
fi

echo "OK: SQL triggers manage rental and return stock flow"
