#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

tmp_dir="$(mktemp -d)"
trap 'rm -rf "${tmp_dir}"; agent-browser close --all >/dev/null 2>&1 || true' EXIT

login_snapshot="${tmp_dir}/login-snapshot.txt"
reports_snapshot="${tmp_dir}/reports-snapshot.txt"
custom_snapshot="${tmp_dir}/custom-snapshot.txt"

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

agent-browser open "${TEST_BASE_URL}/staff/reports.php?report_date_range=custom&custom_date_from=2026-04-10&custom_date_to=2026-04-09" >/dev/null
agent-browser wait --load networkidle >/dev/null

for chart_id in mainChart categoryChart topEquipmentChart; do
  agent-browser wait --fn "(() => { const canvas = document.getElementById('${chart_id}'); if (!canvas || !canvas.getContext || !canvas.width || !canvas.height) return false; const data = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data; for (let i = 3; i < data.length; i += 4) { if (data[i] !== 0) return true; } return false; })()" >/dev/null
done

agent-browser snapshot -i > "${reports_snapshot}"
date_range_ref="$(grep 'combobox' "${reports_snapshot}" | grep -o 'ref=e[0-9]\+' | head -n 1 | cut -d= -f2)"

if [[ -z "${date_range_ref}" ]]; then
  echo 'Expected reports page snapshot to expose a date range select'
  exit 1
fi

agent-browser select "@${date_range_ref}" "Custom range" >/dev/null
agent-browser wait --fn "!document.getElementById('custom-date-range').classList.contains('hidden')" >/dev/null
agent-browser snapshot -i > "${custom_snapshot}"

spinbutton_count="$(grep -c 'spinbutton' "${custom_snapshot}")"
if [[ "${spinbutton_count}" -lt 6 ]]; then
  echo 'Expected custom date inputs to become interactive after choosing Custom range'
  exit 1
fi

agent-browser wait --fn "(() => { const from = document.getElementById('custom-date-from'); const to = document.getElementById('custom-date-to'); return from && to && to.min === from.value && to.value === from.value && from.value === '2026-04-10'; })()" >/dev/null

echo 'OK: reports page renders charts and enforces custom date controls'
