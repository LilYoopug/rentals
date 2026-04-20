#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

user_cookie="$(mktemp)"
rentals_page="$(mktemp)"
trap 'rm -f "${user_cookie}" "${rentals_page}"' RETURN

password_hash="$(php -r 'echo password_hash("password", PASSWORD_DEFAULT);')"
mysql_test lenscraft <<SQL
UPDATE users SET password = '${password_hash}' WHERE username = 'pelanggan';
SQL

curl -sS \
  -c "${user_cookie}" \
  -X POST \
  -d 'username=pelanggan&password=password' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

csrf_token="$(php -r 'session_id($argv[1]); session_start(); echo $_SESSION["csrf_token"] ?? "";' "$(awk '$6 == "PHPSESSID" {print $7}' "${user_cookie}")")"

return_response="$(curl -sS -b "${user_cookie}" -X POST -d "csrf_token=${csrf_token}&rental_code=RENT-2026-A001" "${TEST_BASE_URL}/process/rental-return-process.php")"
if [[ "${return_response}" != *'"success":true'* ]]; then
  echo "Expected return request to succeed, got: ${return_response}"
  exit 1
fi

return_status="$(mysql_test -N -B lenscraft -e "SELECT rt.status FROM returns rt JOIN rentals r ON r.id = rt.rental_id WHERE r.rental_code = 'RENT-2026-A001' LIMIT 1")"
if [[ "${return_status}" != "menunggu" ]]; then
  echo "Expected return request to stay pending approval, got: ${return_status:-<empty>}"
  exit 1
fi

curl -sS \
  -b "${user_cookie}" \
  "${TEST_BASE_URL}/user/rentals.php" \
  -o "${rentals_page}"

php -r '
if (!preg_match("/const sampleRentals = (\\[[\\s\\S]*?\\]);/", file_get_contents($argv[1]), $matches)) {
    fwrite(STDERR, "Failed to locate rentals payload\n");
    exit(1);
}

$rentals = json_decode($matches[1], true);
if (!is_array($rentals)) {
    fwrite(STDERR, "Failed to decode rentals payload\n");
    exit(1);
}

$tracked = null;
foreach ($rentals as $rental) {
    if (($rental["id"] ?? "") === "RENT-2026-A001") {
        $tracked = $rental;
        break;
    }
}

if ($tracked === null) {
    fwrite(STDERR, "Missing rental payload for RENT-2026-A001\n");
    exit(1);
}

if (($tracked["status"] ?? "") !== "menunggu") {
    fwrite(STDERR, "Expected customer rental status to show menunggu while return approval is pending, got: " . ($tracked["status"] ?? "<empty>") . "\n");
    exit(1);
}
' "${rentals_page}"

echo "OK: customer return requests stay pending approval and render as menunggu in the user rentals UI"
