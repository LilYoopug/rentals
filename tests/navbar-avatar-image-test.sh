#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

admin_cookie="$(mktemp)"
staff_cookie="$(mktemp)"
user_cookie="$(mktemp)"
admin_page="$(mktemp)"
admin_users_page="$(mktemp)"
staff_page="$(mktemp)"
products_page="$(mktemp)"
detail_page="$(mktemp)"
rentals_page="$(mktemp)"
trap 'rm -f "${admin_cookie}" "${staff_cookie}" "${user_cookie}" "${admin_page}" "${admin_users_page}" "${staff_page}" "${products_page}" "${detail_page}" "${rentals_page}"' RETURN

curl -sS -c "${admin_cookie}" -X POST -d 'username=admin&password=admin123' "${TEST_BASE_URL}/process/login-process.php" -o /dev/null
curl -sS -c "${staff_cookie}" -X POST -d 'username=petugas&password=staff123' "${TEST_BASE_URL}/process/login-process.php" -o /dev/null
curl -sS -c "${user_cookie}" -X POST -d 'username=pelanggan&password=user123' "${TEST_BASE_URL}/process/login-process.php" -o /dev/null

curl -sS -b "${admin_cookie}" "${TEST_BASE_URL}/admin/products.php" -o "${admin_page}"
curl -sS -b "${admin_cookie}" "${TEST_BASE_URL}/admin/users.php" -o "${admin_users_page}"
curl -sS -b "${staff_cookie}" "${TEST_BASE_URL}/staff/index.php" -o "${staff_page}"
curl -sS -b "${user_cookie}" "${TEST_BASE_URL}/products.php" -o "${products_page}"
curl -sS -b "${user_cookie}" "${TEST_BASE_URL}/product-detail.php?id=1" -o "${detail_page}"
curl -sS -b "${user_cookie}" "${TEST_BASE_URL}/user/rentals.php" -o "${rentals_page}"

if ! grep -q 'uploads/users/admin-lenscraft.jpg' "${admin_page}" || ! grep -q 'Admin avatar' "${admin_page}"; then
  echo 'Expected admin products navbar to render the admin avatar image'
  exit 1
fi

if ! grep -q 'uploads/users/admin-lenscraft.jpg' "${admin_users_page}" || ! grep -q 'uploads/users/staff-lenscraft.jpg' "${admin_users_page}" || ! grep -q 'uploads/users/raka-pratama.jpg' "${admin_users_page}"; then
  echo 'Expected admin user management table to render stored avatar images'
  exit 1
fi

if ! grep -q 'uploads/users/staff-lenscraft.jpg' "${staff_page}" || ! grep -q 'Staff avatar' "${staff_page}"; then
  echo 'Expected staff dashboard navbar to render the staff avatar image'
  exit 1
fi

if ! grep -q 'uploads/users/raka-pratama.jpg' "${products_page}" || ! grep -q 'Profile avatar' "${products_page}"; then
  echo 'Expected products navbar to render the logged-in user avatar image'
  exit 1
fi

if ! grep -q 'uploads/users/raka-pratama.jpg' "${detail_page}" || ! grep -q 'Profile avatar' "${detail_page}"; then
  echo 'Expected product detail navbar to render the logged-in user avatar image'
  exit 1
fi

if ! grep -q 'uploads/users/raka-pratama.jpg' "${rentals_page}" || ! grep -q 'Profile avatar' "${rentals_page}"; then
  echo 'Expected user rentals navbar to render the logged-in user avatar image'
  exit 1
fi

echo "OK: navbar avatar images render across admin, staff, and user routes"
