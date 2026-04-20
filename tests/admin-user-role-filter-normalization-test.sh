#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

admin_cookie="$(mktemp)"
page_body="$(mktemp)"
trap 'rm -f "${admin_cookie}" "${page_body}"' RETURN

mysql_test lenscraft <<'SQL'
ALTER TABLE users MODIFY COLUMN role ENUM('admin','staff','user','petugas','pelanggan') NOT NULL DEFAULT 'pelanggan';
UPDATE users
SET role = CASE role
  WHEN 'petugas' THEN 'staff'
  WHEN 'pelanggan' THEN 'user'
  ELSE role
END
WHERE username IN ('petugas', 'pelanggan', 'ayu', 'bimo', 'salsa', 'dion');
SQL

curl -sS \
  -c "${admin_cookie}" \
  -X POST \
  -d 'username=admin&password=admin123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${admin_cookie}" \
  "${TEST_BASE_URL}/admin/users.php" \
  -o "${page_body}"

if ! grep -q '"role":"petugas"' "${page_body}"; then
  echo 'Expected admin users payload to normalize legacy staff role values for filtering'
  exit 1
fi

if ! grep -q '"role":"pelanggan"' "${page_body}"; then
  echo 'Expected admin users payload to normalize legacy user role values for filtering'
  exit 1
fi

if grep -q '"role":"staff"' "${page_body}" || grep -q '"role":"user"' "${page_body}"; then
  echo 'Expected admin users payload to stop exposing legacy english role values'
  exit 1
fi

echo 'OK: admin users payload normalizes role values so staff and user filters work'
