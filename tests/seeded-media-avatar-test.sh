#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

avatar_column="$(mysql_test -N -B information_schema -e "SELECT COLUMN_NAME FROM COLUMNS WHERE TABLE_SCHEMA = 'lenscraft' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'avatar_path'")"
if [[ "${avatar_column}" != "avatar_path" ]]; then
  echo "Expected users.avatar_path column to exist"
  exit 1
fi

product_count="$(mysql_test -N -B lenscraft -e "SELECT COUNT(*) FROM products")"
user_count="$(mysql_test -N -B lenscraft -e "SELECT COUNT(*) FROM users")"

if [[ "${product_count}" -lt 11 ]]; then
  echo "Expected at least 11 seeded products, got ${product_count:-<empty>}"
  exit 1
fi

if [[ "${user_count}" -lt 7 ]]; then
  echo "Expected at least 7 seeded users, got ${user_count:-<empty>}"
  exit 1
fi

product_uploads="$(find uploads/products -maxdepth 1 -type f ! -name '.gitkeep' | wc -l | tr -d ' ')"
user_uploads="$(find uploads/users -maxdepth 1 -type f | wc -l | tr -d ' ')"

if [[ "${product_uploads}" -lt 6 ]]; then
  echo "Expected at least 6 downloaded product images in uploads/products, got ${product_uploads:-<empty>}"
  exit 1
fi

if [[ "${user_uploads}" -lt 4 ]]; then
  echo "Expected at least 4 downloaded user avatars in uploads/users, got ${user_uploads:-<empty>}"
  exit 1
fi

if ! grep -q 'name="avatar_file"' admin/users.php || ! grep -q 'enctype="multipart/form-data"' admin/users.php; then
  echo 'Expected admin user modal to support avatar uploads'
  exit 1
fi

if ! grep -q 'name="avatar_file"' user/profile.php || ! grep -q 'enctype="multipart/form-data"' user/profile.php; then
  echo 'Expected user profile form to support avatar uploads'
  exit 1
fi

echo 'OK: seeded media and avatar upload flow are wired'
