#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

staff_routes=(
  "staff/index.php"
  "staff/borrowings.php"
  "staff/returns.php"
  "staff/reports.php"
)

for route in "${staff_routes[@]}"; do
  section_count="$( (rg -o '<section id="' "${route}" 2>/dev/null || true) | wc -l | tr -d ' ' )"
  if [[ "${section_count}" -gt 1 ]]; then
    echo "Expected ${route} to stop embedding multiple staff sections, found ${section_count}"
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
  -d 'username=petugas&password=staff123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

check_route() {
  local route="$1"
  local title="$2"
  local section_id="$3"
  local active_href="$4"
  shift 3
  shift 1

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

  if ! grep -q "href=\"${active_href}\" class=\"nav-item nav-item-active" "${page_body}"; then
    echo "Expected ${route} to mark ${active_href} as the active sidebar item in the server-rendered HTML"
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

check_route "staff/index.php" "LensCraft - Dashboard Petugas" "overview" "index.php" \
  "staff-detail-modal"
check_route "staff/borrowings.php" "LensCraft - Peminjaman" "approve-borrowings" "borrowings.php"
check_route "staff/returns.php" "LensCraft - Pengembalian" "monitor-returns" "returns.php"
check_route "staff/reports.php" "LensCraft - Laporan &amp; Analitik" "reports" "reports.php" \
  "staff-detail-modal" "staff-action-modal"

echo "OK: staff route files keep only their route-specific rendered UI"
