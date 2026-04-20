#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

mysql_test lenscraft <<'SQL'
UPDATE categories
SET name = 'Audio Pro',
    slug = 'audio-pro'
WHERE slug = 'lensa';

UPDATE products
SET category_slug = 'audio-pro'
WHERE category_slug = 'lensa';
SQL

cookie_file="$(mktemp)"
page_body="$(mktemp)"
trap 'rm -f "${cookie_file}" "${page_body}"' RETURN

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=admin&password=admin123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${cookie_file}" \
  "${TEST_BASE_URL}/admin/products.php" \
  -o "${page_body}"

filter_block="$(sed -n '/<select id="category-filter"/,/<\/select>/p' "${page_body}")"

if [[ -z "${filter_block}" ]]; then
  echo 'Expected admin products page to render the inventory category filter select'
  exit 1
fi

if ! grep -q 'option value="audio-pro"' <<<"${filter_block}"; then
  echo 'Expected admin inventory category filter options to be generated from live category data'
  exit 1
fi

if grep -q 'option value="lensa"' <<<"${filter_block}"; then
  echo 'Expected admin inventory category filter options to stop relying on stale hardcoded category slugs'
  exit 1
fi

echo 'OK: admin inventory category filter options follow live category data'
