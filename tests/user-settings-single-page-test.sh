#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

password_hash="$(php -r 'echo password_hash("password", PASSWORD_DEFAULT);')"
mysql_test lenscraft <<SQL
UPDATE users SET password = '${password_hash}' WHERE username = 'pelanggan';
SQL

cookie_file="$(mktemp)"
profile_page="$(mktemp)"
security_headers="$(mktemp)"
appearance_headers="$(mktemp)"
privacy_headers="$(mktemp)"
trap 'rm -f "${cookie_file}" "${profile_page}" "${security_headers}" "${appearance_headers}" "${privacy_headers}"' RETURN

curl -sS \
  -c "${cookie_file}" \
  -X POST \
  -d 'username=pelanggan&password=password' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${cookie_file}" \
  "${TEST_BASE_URL}/user/profile.php" \
  -o "${profile_page}"

if grep -q 'id="sidebar"' "${profile_page}"; then
  echo 'Expected unified user settings page to omit the settings sidebar'
  exit 1
fi

if grep -q 'class="max-w-3xl"' "${profile_page}"; then
  echo 'Expected unified user settings page to avoid fixed-width max-w-3xl wrappers'
  exit 1
fi

for section_heading in 'Profil' 'Keamanan' 'Tampilan'; do
  if ! grep -q "${section_heading}" "${profile_page}"; then
    echo "Expected unified user settings page to include section heading: ${section_heading}"
    exit 1
  fi
done

for removed_copy in 'Data' 'Your Data' 'Ekspor Data Saya' 'Simpan Pengaturan Privasi'; do
  if grep -q "${removed_copy}" "${profile_page}"; then
    echo "Expected unified user settings page to omit removed data/privacy copy: ${removed_copy}"
    exit 1
  fi
done

curl -sS -b "${cookie_file}" -D "${security_headers}" -o /dev/null "${TEST_BASE_URL}/user/security.php"
curl -sS -b "${cookie_file}" -D "${appearance_headers}" -o /dev/null "${TEST_BASE_URL}/user/appearance.php"
curl -sS -b "${cookie_file}" -D "${privacy_headers}" -o /dev/null "${TEST_BASE_URL}/user/privacy.php"

security_location="$(awk 'tolower($1) == "location:" {print $2}' "${security_headers}" | tr -d '\r')"
appearance_location="$(awk 'tolower($1) == "location:" {print $2}' "${appearance_headers}" | tr -d '\r')"
privacy_location="$(awk 'tolower($1) == "location:" {print $2}' "${privacy_headers}" | tr -d '\r')"

if [[ "${security_location}" != "/user/profile.php" ]]; then
  echo "Expected /user/security.php to redirect to /user/profile.php, got ${security_location:-<empty>}"
  exit 1
fi

if [[ "${appearance_location}" != "/user/profile.php" ]]; then
  echo "Expected /user/appearance.php to redirect to /user/profile.php, got ${appearance_location:-<empty>}"
  exit 1
fi

if [[ "${privacy_location}" != "/user/profile.php" ]]; then
  echo "Expected /user/privacy.php to redirect to /user/profile.php, got ${privacy_location:-<empty>}"
  exit 1
fi

echo 'OK: user settings are unified on one page and legacy settings routes redirect there'
