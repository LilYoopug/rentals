#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

staff_cookie="$(mktemp)"
borrowings_body="$(mktemp)"
returns_body="$(mktemp)"
trap 'rm -f "${staff_cookie}" "${borrowings_body}" "${returns_body}"' RETURN

curl -sS \
  -c "${staff_cookie}" \
  -X POST \
  -d 'username=petugas&password=staff123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${staff_cookie}" \
  "${TEST_BASE_URL}/staff/borrowings.php" \
  -o "${borrowings_body}"

curl -sS \
  -b "${staff_cookie}" \
  "${TEST_BASE_URL}/staff/returns.php" \
  -o "${returns_body}"

if ! grep -q '<title>LensCraft - Peminjaman</title>' "${borrowings_body}"; then
  echo 'Expected staff borrowings route to use the admin-style Peminjaman title'
  exit 1
fi

if ! grep -q '>Peminjaman<' "${borrowings_body}"; then
  echo 'Expected staff borrowings route to use the admin-style Peminjaman heading'
  exit 1
fi

if ! grep -q 'id="borrowings-search"' "${borrowings_body}"; then
  echo 'Expected staff borrowings route to use the admin-style borrowings search control'
  exit 1
fi

if ! grep -q 'id="borrowings-filter-btn"' "${borrowings_body}" || ! grep -q 'id="borrowings-status-filter"' "${borrowings_body}"; then
  echo 'Expected staff borrowings route to keep the admin-style borrowings filter controls'
  exit 1
fi

if ! grep -q "borrowing.status === statusFilter" "${borrowings_body}"; then
  echo 'Expected staff borrowings route to filter the table by the selected borrowing status'
  exit 1
fi

if grep -q 'id="approve-search"' "${borrowings_body}" || grep -q 'id="approve-filter-btn"' "${borrowings_body}"; then
  echo 'Expected staff borrowings route to drop the old approve-specific filter controls'
  exit 1
fi

if ! grep -q 'const borrowingsPerPage = 8;' "${borrowings_body}"; then
  echo 'Expected staff borrowings route to use the admin-style pagination density'
  exit 1
fi

if ! grep -q 'mobile-card-title-ellipsis' "${borrowings_body}"; then
  echo 'Expected staff borrowings route to include the admin-style responsive card rows'
  exit 1
fi

if ! grep -q '"image":"\/uploads\/products\/sony-a7-iii.jpg"' "${borrowings_body}"; then
  echo 'Expected staff borrowings payload to normalize DB product images to a site-root path'
  exit 1
fi

if grep -q 'Tambah Peminjaman' "${borrowings_body}"; then
  echo 'Expected staff borrowings route to exclude admin-only create controls'
  exit 1
fi

if ! grep -q '<title>LensCraft - Pengembalian</title>' "${returns_body}"; then
  echo 'Expected staff returns route to use the admin-style Pengembalian title'
  exit 1
fi

if ! grep -q '>Pengembalian<' "${returns_body}"; then
  echo 'Expected staff returns route to use the admin-style Pengembalian heading'
  exit 1
fi

if ! grep -q 'id="returns-filter-btn"' "${returns_body}" || ! grep -q 'id="returns-search"' "${returns_body}"; then
  echo 'Expected staff returns route to keep the admin-style returns filter and search controls'
  exit 1
fi

if ! grep -q 'const returnsPerPage = 8;' "${returns_body}"; then
  echo 'Expected staff returns route to use the admin-style pagination density'
  exit 1
fi

if ! grep -q 'mobile-card-title-ellipsis' "${returns_body}"; then
  echo 'Expected staff returns route to include the admin-style responsive card rows'
  exit 1
fi

if ! grep -q '"image":"\/uploads\/products\/sony-a7-iii.jpg"' "${returns_body}" && ! grep -q '"image":"\/uploads\/products\/sigma-18-35.jpg"' "${returns_body}"; then
  echo 'Expected staff returns payload to normalize DB product images to a site-root path'
  exit 1
fi

if grep -q 'id="admin-return-form"' "${returns_body}" || grep -q 'Save Return' "${returns_body}"; then
  echo 'Expected staff returns route to exclude admin-only return editing controls'
  exit 1
fi

echo 'OK: staff borrowings and returns routes use the admin transaction UI without admin-only controls'
