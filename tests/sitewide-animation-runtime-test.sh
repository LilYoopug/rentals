#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

admin_cookie="$(mktemp)"
public_body="$(mktemp)"
admin_body="$(mktemp)"
trap 'rm -f "${admin_cookie}" "${public_body}" "${admin_body}"' RETURN

curl -sS "${TEST_BASE_URL}/index.php" -o "${public_body}"

curl -sS \
  -c "${admin_cookie}" \
  -X POST \
  -d 'username=admin&password=admin123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${admin_cookie}" \
  "${TEST_BASE_URL}/admin/index.php" \
  -o "${admin_body}"

for page_body in "${public_body}" "${admin_body}"; do
  if ! grep -q 'window.__lenscraftMotionReady' "${page_body}"; then
    echo 'Expected shared page runtime bundle to expose the motion bootstrap marker'
    exit 1
  fi

  if ! grep -q 'prefers-reduced-motion: reduce' "${page_body}"; then
    echo 'Expected shared page runtime bundle to include reduced-motion safeguards'
    exit 1
  fi

  if ! grep -q 'data-lenscraft-motion' "${page_body}"; then
    echo 'Expected shared page runtime bundle to ship sitewide motion hooks'
    exit 1
  fi
done

echo 'OK: shared runtime bundle ships sitewide animation hooks'
