#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

cookie_file="$(mktemp)"
products_body="$(mktemp)"
rentals_body="$(mktemp)"
profile_body="$(mktemp)"
trap 'rm -f "${cookie_file}" "${products_body}" "${rentals_body}" "${profile_body}"' RETURN

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS -b "${cookie_file}" "${TEST_BASE_URL}/products.php" -o "${products_body}"
curl -sS -b "${cookie_file}" "${TEST_BASE_URL}/user/rentals.php" -o "${rentals_body}"
curl -sS -b "${cookie_file}" "${TEST_BASE_URL}/user/profile.php" -o "${profile_body}"

for page in "${products_body}" "${rentals_body}" "${profile_body}"; do
  if ! grep -q 'linear-gradient(135deg, #c7a65a 0%, #8f6421 100%)' "${page}"; then
    echo "Expected floating nav active state to use the shared brass gradient in ${page}"
    exit 1
  fi
done

if ! grep -q "btn.classList.add('active')" "${products_body}"; then
  echo 'Expected products floating nav logic to add the active class for the current page'
  exit 1
fi

if ! grep -q "btn.classList.add('active')" "${rentals_body}"; then
  echo 'Expected user rentals floating nav logic to add the active class for the current page'
  exit 1
fi

if ! grep -q "this.classList.add('active')" "${profile_body}"; then
  echo 'Expected user profile floating nav interactions to use the active class consistently'
  exit 1
fi

if grep -q "classList.add('aktif')" "${products_body}" || grep -q "classList.add('aktif')" "${rentals_body}" || grep -q "classList.add('aktif')" "${profile_body}"; then
  echo 'Expected floating nav logic to stop toggling the non-existent aktif class'
  exit 1
fi

echo 'OK: user floating nav pages consistently use the active class for the selected tab'
