#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

admin_routes=(
  "admin/index.php"
  "admin/users.php"
  "admin/categories.php"
  "admin/products.php"
  "admin/borrowings.php"
  "admin/returns.php"
  "admin/activity-log.php"
)

for route in "${admin_routes[@]}"; do
  section_count="$( (rg -o '<section id="' "${route}" 2>/dev/null || true) | wc -l | tr -d ' ' )"
  if [[ "${section_count}" -gt 1 ]]; then
    echo "Expected ${route} to stop embedding multiple admin sections, found ${section_count}"
    exit 1
  fi
done

start_test_stack

cookie_file="$(mktemp)"
page_body="$(mktemp)"
trap 'rm -f "${cookie_file}" "${page_body}"' RETURN

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=admin&password=admin123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

check_route() {
  local route="$1"
  local title="$2"
  local section_id="$3"
  shift 3

  curl -sS \
    -b "${cookie_file}" \
    "${TEST_BASE_URL}/${route}" \
    -o "${page_body}"

  if ! grep -q "<title>${title}</title>" "${page_body}"; then
    echo "Expected ${route} to render title ${title}"
    exit 1
  fi

  if ! grep -q "#${section_id}\\.content-section" "${page_body}"; then
    echo "Expected ${route} to activate only #${section_id}.content-section"
    exit 1
  fi

  local forbidden_id
  for forbidden_id in "$@"; do
    if grep -q "id=\"${forbidden_id}\"" "${page_body}"; then
      echo "Expected ${route} to drop unrelated UI block ${forbidden_id}"
      exit 1
    fi
  done
}

check_route "admin/index.php" "LensCraft - Dashboard Admin" "overview" \
  "user-modal" "category-modal" "inventory-modal" "transaction-modal" "admin-detail-modal" "admin-return-modal"
check_route "admin/users.php" "LensCraft - Pengguna" "users" \
  "category-modal" "inventory-modal" "transaction-modal" "admin-detail-modal" "admin-return-modal"
check_route "admin/categories.php" "LensCraft - Kategori" "categories" \
  "user-modal" "inventory-modal" "transaction-modal" "admin-detail-modal" "admin-return-modal"
check_route "admin/products.php" "LensCraft - Peralatan &amp; Stok" "tools-stock" \
  "user-modal" "category-modal" "transaction-modal" "admin-return-modal"
check_route "admin/borrowings.php" "LensCraft - Peminjaman" "borrowings" \
  "user-modal" "category-modal" "inventory-modal"
check_route "admin/returns.php" "LensCraft - Pengembalian" "returns" \
  "user-modal" "category-modal" "inventory-modal" "transaction-modal"
check_route "admin/activity-log.php" "LensCraft - Aktivitas" "activity-log" \
  "user-modal" "category-modal" "inventory-modal" "transaction-modal" "admin-detail-modal" "admin-return-modal"

echo "OK: admin route files keep only their route-specific rendered UI"
