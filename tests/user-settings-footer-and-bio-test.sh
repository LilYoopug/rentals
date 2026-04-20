#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

cookie_file="$(mktemp)"
profile_body="$(mktemp)"
security_headers="$(mktemp)"
appearance_headers="$(mktemp)"
privacy_headers="$(mktemp)"
trap 'rm -f "${cookie_file}" "${profile_body}" "${security_headers}" "${appearance_headers}" "${privacy_headers}"' EXIT

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=pelanggan&password=user123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${cookie_file}" \
  "${TEST_BASE_URL}/user/profile.php" \
  -o "${profile_body}"

if grep -q 'id="bio"' "${profile_body}" || grep -q 'Bio (Optional)' "${profile_body}" || grep -q 'About You' "${profile_body}"; then
  echo 'Expected user/profile.php to remove the bio section from settings'
  exit 1
fi

if ! grep -q '<footer' "${profile_body}" || ! grep -q '© 2026 LensCraft. Sistem Rental Kamera.' "${profile_body}"; then
  echo 'Expected user/profile.php to include the shared settings footer'
  exit 1
fi

if grep -q 'bio-count' "${profile_body}" || grep -q "setValue('bio'" "${profile_body}"; then
  echo 'Expected user/profile.php to remove bio-related client bindings'
  exit 1
fi

curl -sS -b "${cookie_file}" -D "${security_headers}" -o /dev/null "${TEST_BASE_URL}/user/security.php"
curl -sS -b "${cookie_file}" -D "${appearance_headers}" -o /dev/null "${TEST_BASE_URL}/user/appearance.php"
curl -sS -b "${cookie_file}" -D "${privacy_headers}" -o /dev/null "${TEST_BASE_URL}/user/privacy.php"

security_location="$(awk 'tolower($1) == "location:" {print $2}' "${security_headers}" | tr -d '\r')"
appearance_location="$(awk 'tolower($1) == "location:" {print $2}' "${appearance_headers}" | tr -d '\r')"
privacy_location="$(awk 'tolower($1) == "location:" {print $2}' "${privacy_headers}" | tr -d '\r')"

if [[ "${security_location}" != "/user/profile.php" ]]; then
  echo "Expected user/security.php to redirect to /user/profile.php, got ${security_location:-<empty>}"
  exit 1
fi

if [[ "${appearance_location}" != "/user/profile.php" ]]; then
  echo "Expected user/appearance.php to redirect to /user/profile.php, got ${appearance_location:-<empty>}"
  exit 1
fi

if [[ "${privacy_location}" != "/user/profile.php" ]]; then
  echo "Expected user/privacy.php to redirect to /user/profile.php, got ${privacy_location:-<empty>}"
  exit 1
fi

echo 'OK: unified user settings remove bio, keep the footer, and redirect legacy settings routes'
