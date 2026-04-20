#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

users_role_type="$(mysql_test -N -B information_schema -e "SELECT COLUMN_TYPE FROM COLUMNS WHERE TABLE_SCHEMA = 'lenscraft' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'")"
users_status_type="$(mysql_test -N -B information_schema -e "SELECT COLUMN_TYPE FROM COLUMNS WHERE TABLE_SCHEMA = 'lenscraft' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'status'")"
rentals_status_type="$(mysql_test -N -B information_schema -e "SELECT COLUMN_TYPE FROM COLUMNS WHERE TABLE_SCHEMA = 'lenscraft' AND TABLE_NAME = 'rentals' AND COLUMN_NAME = 'status'")"
rentals_delivery_type="$(mysql_test -N -B information_schema -e "SELECT COLUMN_TYPE FROM COLUMNS WHERE TABLE_SCHEMA = 'lenscraft' AND TABLE_NAME = 'rentals' AND COLUMN_NAME = 'delivery_method'")"
returns_status_type="$(mysql_test -N -B information_schema -e "SELECT COLUMN_TYPE FROM COLUMNS WHERE TABLE_SCHEMA = 'lenscraft' AND TABLE_NAME = 'returns' AND COLUMN_NAME = 'status'")"
returns_fine_type="$(mysql_test -N -B information_schema -e "SELECT COLUMN_TYPE FROM COLUMNS WHERE TABLE_SCHEMA = 'lenscraft' AND TABLE_NAME = 'returns' AND COLUMN_NAME = 'fine_amount'")"

if [[ "${users_role_type}" != "enum('admin','petugas','pelanggan')" ]]; then
  echo "Expected users.role enum to use Indonesian values, got: ${users_role_type:-<empty>}"
  exit 1
fi

if [[ "${users_status_type}" != "enum('aktif','nonaktif','menunggu')" ]]; then
  echo "Expected users.status enum to use Indonesian values, got: ${users_status_type:-<empty>}"
  exit 1
fi

if [[ "${rentals_status_type}" != "enum('menunggu','mendatang','aktif','selesai','dibatalkan','ditolak')" ]]; then
  echo "Expected rentals.status enum to use Indonesian values, got: ${rentals_status_type:-<empty>}"
  exit 1
fi

if [[ "${rentals_delivery_type}" != "enum('ambil_sendiri','diantar')" ]]; then
  echo "Expected rentals.delivery_method enum to use Indonesian values, got: ${rentals_delivery_type:-<empty>}"
  exit 1
fi

if [[ "${returns_status_type}" != "enum('menunggu','selesai')" ]]; then
  echo "Expected returns.status enum to use Indonesian values, got: ${returns_status_type:-<empty>}"
  exit 1
fi

if [[ "${returns_fine_type}" != "decimal(10,2)" ]]; then
  echo "Expected returns.fine_amount column to exist with decimal(10,2), got: ${returns_fine_type:-<empty>}"
  exit 1
fi

seeded_usernames="$(mysql_test -N -B lenscraft -e "SELECT GROUP_CONCAT(username ORDER BY id SEPARATOR ',') FROM users LIMIT 1")"
if [[ "${seeded_usernames}" != admin,petugas,pelanggan,* ]]; then
  echo "Expected seeded usernames to start with admin,petugas,pelanggan, got: ${seeded_usernames:-<empty>}"
  exit 1
fi

category_slugs="$(mysql_test -N -B lenscraft -e "SELECT GROUP_CONCAT(slug ORDER BY id SEPARATOR ',') FROM categories")"
if [[ "${category_slugs}" != "kamera-mirrorless,lensa,video" ]]; then
  echo "Expected category slugs to use Indonesian values, got: ${category_slugs:-<empty>}"
  exit 1
fi

echo "OK: schema enums and seed data use Indonesian internal values"
