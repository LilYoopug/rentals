#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

tmp_dir="$(mktemp -d)"
trap 'rm -rf "${tmp_dir}"; agent-browser close --all >/dev/null 2>&1 || true' EXIT

login_snapshot="${tmp_dir}/login-snapshot.txt"
page_one_snapshot="${tmp_dir}/page-one-snapshot.txt"

agent-browser close --all >/dev/null 2>&1 || true

agent-browser --args "--no-sandbox" open "${TEST_BASE_URL}/login.php" >/dev/null
agent-browser wait --load networkidle >/dev/null
agent-browser snapshot -i > "${login_snapshot}"

username_ref="$(grep 'textbox "Username atau Email"' "${login_snapshot}" | grep -o 'ref=e[0-9]\+' | head -n 1 | cut -d= -f2)"
password_ref="$(grep 'textbox "Kata Sandi"' "${login_snapshot}" | grep -o 'ref=e[0-9]\+' | head -n 1 | cut -d= -f2)"
submit_ref="$(grep 'button "Masuk"' "${login_snapshot}" | grep -o 'ref=e[0-9]\+' | head -n 1 | cut -d= -f2)"

if [[ -z "${username_ref}" || -z "${password_ref}" || -z "${submit_ref}" ]]; then
  echo 'Expected login page snapshot to expose refs for username, password, and submit'
  exit 1
fi

agent-browser fill "@${username_ref}" "petugas" >/dev/null
agent-browser fill "@${password_ref}" "staff123" >/dev/null
agent-browser click "@${submit_ref}" >/dev/null
agent-browser wait --load networkidle >/dev/null

agent-browser open "${TEST_BASE_URL}/staff/stock-price.php" >/dev/null
agent-browser wait --load networkidle >/dev/null
agent-browser snapshot -i > "${page_one_snapshot}"

page_one_rows="$(grep -c 'unit sedang dipakai' "${page_one_snapshot}")"
if [[ "${page_one_rows}" != "5" ]]; then
  echo "Expected stock page 1 to show exactly 5 products on desktop, got ${page_one_rows:-<empty>}"
  exit 1
fi

page_two_ref="$(grep 'button "2"' "${page_one_snapshot}" | grep -o 'ref=e[0-9]\+' | head -n 1 | cut -d= -f2)"
if [[ -z "${page_two_ref}" ]]; then
  echo 'Expected stock pagination to expose a page 2 button'
  exit 1
fi

echo 'OK: staff stock page shows only the paginated first-page product slice on desktop'
