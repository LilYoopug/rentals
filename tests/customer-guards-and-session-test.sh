#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

password_hash="$(php -r 'echo password_hash("password", PASSWORD_DEFAULT);')"
mysql_test lenscraft <<SQL
UPDATE users SET password = '${password_hash}' WHERE username IN ('admin', 'staff');
UPDATE users SET password = '${password_hash}' WHERE username = 'user';
SQL

staff_cookie="$(mktemp)"
user_cookie="$(mktemp)"
logout_headers="$(mktemp)"
trap 'rm -f "${staff_cookie}" "${user_cookie}" "${logout_headers}"' RETURN

curl -sS \
  -c "${staff_cookie}" \
  -X POST \
  -d 'username=staff&password=password' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

staff_headers="$(mktemp)"
curl -sS -b "${staff_cookie}" -D "${staff_headers}" -o /dev/null "${TEST_BASE_URL}/user/profile.php"
staff_location="$(awk 'tolower($1) == "location:" {print $2}' "${staff_headers}" | tr -d '\r')"
rm -f "${staff_headers}"

if [[ "${staff_location}" != "/products.php" ]]; then
  echo "Expected staff access to /user/profile.php to redirect to /products.php, got ${staff_location:-<empty>}"
  exit 1
fi

mysql_test lenscraft <<'SQL'
UPDATE users SET status = 'inactive' WHERE username = 'user';
SQL

inactive_headers="$(mktemp)"
curl -sS \
  -D "${inactive_headers}" \
  -o /dev/null \
  -X POST \
  -d 'username=user&password=password' \
  "${TEST_BASE_URL}/process/login-process.php"
inactive_location="$(awk 'tolower($1) == "location:" {print $2}' "${inactive_headers}" | tr -d '\r')"
rm -f "${inactive_headers}"

if [[ "${inactive_location}" != "/login.php" ]]; then
  echo "Expected inactive user login to redirect back to /login.php, got ${inactive_location:-<empty>}"
  exit 1
fi

mysql_test lenscraft <<'SQL'
UPDATE users SET status = 'active' WHERE username = 'user';
SQL

curl -sS \
  -c "${user_cookie}" \
  -X POST \
  -d 'username=user&password=password' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS -b "${user_cookie}" -D "${logout_headers}" -o /dev/null "${TEST_BASE_URL}/logout.php"
post_logout_headers="$(mktemp)"
curl -sS -b "${user_cookie}" -D "${post_logout_headers}" -o /dev/null "${TEST_BASE_URL}/user/rentals.php"
post_logout_location="$(awk 'tolower($1) == "location:" {print $2}' "${post_logout_headers}" | tr -d '\r')"
rm -f "${post_logout_headers}"

if [[ "${post_logout_location}" != "/login.php" ]]; then
  echo "Expected logged-out access to /user/rentals.php to redirect to /login.php, got ${post_logout_location:-<empty>}"
  exit 1
fi

echo "OK: customer-only guards and session lifecycle behave correctly"
