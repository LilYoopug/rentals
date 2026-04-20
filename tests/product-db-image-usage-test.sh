#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

user_cookie="$(mktemp)"
index_body="$(mktemp)"
rentals_body="$(mktemp)"
trap 'rm -f "${user_cookie}" "${index_body}" "${rentals_body}"' EXIT

curl -sS "${TEST_BASE_URL}/index.php" -o "${index_body}"

if ! grep -q '/uploads/products/sony-a7-iii.jpg' "${index_body}"; then
  echo 'Expected landing page popular gear to use product images from the database'
  exit 1
fi

if grep -q 'Sony Alpha A7 IV' "${index_body}"; then
  echo 'Expected landing page to stop using the old hardcoded popular gear cards'
  exit 1
fi

curl -sS \
  -c "${user_cookie}" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${user_cookie}" \
  "${TEST_BASE_URL}/user/rentals.php" \
  -o "${rentals_body}"

if ! grep -q '"image":"\/uploads\/products\/sony-a7-iii.jpg"' "${rentals_body}"; then
  echo 'Expected user rentals payload to normalize DB product images to a site-root path'
  exit 1
fi

for admin_file in \
  "${PROJECT_ROOT:-$(cd "$(dirname "$0")/.." && pwd)}/admin/index.php" \
  "${PROJECT_ROOT:-$(cd "$(dirname "$0")/.." && pwd)}/admin/products.php" \
  "${PROJECT_ROOT:-$(cd "$(dirname "$0")/.." && pwd)}/admin/borrowings.php" \
  "${PROJECT_ROOT:-$(cd "$(dirname "$0")/.." && pwd)}/admin/returns.php" \
  "${PROJECT_ROOT:-$(cd "$(dirname "$0")/.." && pwd)}/admin/categories.php" \
  "${PROJECT_ROOT:-$(cd "$(dirname "$0")/.." && pwd)}/admin/activity-log.php" \
  "${PROJECT_ROOT:-$(cd "$(dirname "$0")/.." && pwd)}/admin/users.php"
do
  if ! grep -q 'trx.image ||' "${admin_file}"; then
    echo "Expected $(basename "${admin_file}") to use trx.image in transaction product thumbnails"
    exit 1
  fi
done

echo 'OK: landing, rentals, and admin transaction views use DB-backed product images'
