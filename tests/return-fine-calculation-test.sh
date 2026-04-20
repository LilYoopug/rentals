#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

staff_cookie="$(mktemp)"
admin_cookie="$(mktemp)"
staff_returns_body="$(mktemp)"
admin_returns_body="$(mktemp)"
trap 'rm -f "${staff_cookie}" "${admin_cookie}" "${staff_returns_body}" "${admin_returns_body}"' EXIT

mysql_test lenscraft <<'SQL'
INSERT INTO rentals (
  rental_code, user_id, product_id, start_date, end_date, total_days,
  daily_rate, discount_percentage, delivery_method, delivery_fee, total_price,
  status, created_at, approved_at, completed_at, cancelled_at, cancel_reason
) VALUES
  (
    'RENT-FINE-LATE',
    3,
    3,
    DATE_SUB(CURDATE(), INTERVAL 4 DAY),
    DATE_SUB(CURDATE(), INTERVAL 2 DAY),
    3,
    250000.00,
    0,
    'ambil_sendiri',
    0.00,
    750000.00,
    'aktif',
    DATE_SUB(NOW(), INTERVAL 4 DAY),
    DATE_SUB(NOW(), INTERVAL 4 DAY),
    NULL,
    NULL,
    NULL
  ),
  (
    'RENT-FINE-ONTIME',
    4,
    4,
    DATE_SUB(CURDATE(), INTERVAL 2 DAY),
    CURDATE(),
    3,
    405000.00,
    10,
    'ambil_sendiri',
    0.00,
    1215000.00,
    'aktif',
    DATE_SUB(NOW(), INTERVAL 2 DAY),
    DATE_SUB(NOW(), INTERVAL 2 DAY),
    NULL,
    NULL,
    NULL
  );
SQL

late_rental_id="$(mysql_test -N -B lenscraft -e "SELECT id FROM rentals WHERE rental_code = 'RENT-FINE-LATE'")"
ontime_rental_id="$(mysql_test -N -B lenscraft -e "SELECT id FROM rentals WHERE rental_code = 'RENT-FINE-ONTIME'")"

mysql_test lenscraft <<SQL
INSERT INTO returns (
  return_code, rental_id, processed_by, notes, status, returned_at, created_at
) VALUES
  (
    'RET-FINE-LATE',
    ${late_rental_id},
    2,
    'Pending late return',
    'menunggu',
    NULL,
    NOW()
  ),
  (
    'RET-FINE-ONTIME',
    ${ontime_rental_id},
    2,
    'Pending on-time return',
    'menunggu',
    NULL,
    NOW()
  );
SQL

curl -sS \
  -c "${staff_cookie}" \
  -X POST \
  -d 'username=petugas&password=staff123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -c "${admin_cookie}" \
  -X POST \
  -d 'username=admin&password=admin123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

staff_csrf="$(php -r 'session_id($argv[1]); session_start(); echo $_SESSION["csrf_token"] ?? "";' "$(awk '$6 == "PHPSESSID" {print $7}' "${staff_cookie}")")"

for return_code in RET-FINE-LATE RET-FINE-ONTIME; do
  curl -sS \
    -b "${staff_cookie}" \
    -X POST \
    -d "csrf_token=${staff_csrf}&return_code=${return_code}&status=selesai" \
    "${TEST_BASE_URL}/process/staff-pengembalian-konfirmasi.php" \
    -o /dev/null
done

late_fine="$(mysql_test -N -B lenscraft -e "SELECT fine_amount FROM returns WHERE return_code = 'RET-FINE-LATE'")"
ontime_fine="$(mysql_test -N -B lenscraft -e "SELECT fine_amount FROM returns WHERE return_code = 'RET-FINE-ONTIME'")"

if [[ "${late_fine}" != "500000.00" ]]; then
  echo "Expected late return fine to equal 2 late days x 250000.00, got: ${late_fine:-<empty>}"
  exit 1
fi

if [[ "${ontime_fine}" != "0.00" ]]; then
  echo "Expected on-time return fine to stay zero, got: ${ontime_fine:-<empty>}"
  exit 1
fi

curl -sS \
  -b "${staff_cookie}" \
  "${TEST_BASE_URL}/staff/returns.php" \
  -o "${staff_returns_body}"

curl -sS \
  -b "${admin_cookie}" \
  "${TEST_BASE_URL}/admin/returns.php" \
  -o "${admin_returns_body}"

php -r '
function assertFine(string $file, string $pattern, array $expected): void {
    if (!preg_match($pattern, file_get_contents($file), $matches)) {
        fwrite(STDERR, "Failed to locate returns payload in {$file}\n");
        exit(1);
    }

    $rows = json_decode($matches[1], true);
    if (!is_array($rows)) {
        fwrite(STDERR, "Failed to decode returns payload in {$file}\n");
        exit(1);
    }

    $indexed = [];
    foreach ($rows as $row) {
        $indexed[(string) ($row["id"] ?? "")] = $row;
    }

    foreach ($expected as $id => $fine) {
        if (!isset($indexed[$id])) {
            fwrite(STDERR, "Missing return row {$id} in {$file}\n");
            exit(1);
        }

        $actual = (float) ($indexed[$id]["fineAmount"] ?? -1);
        if ($actual !== (float) $fine) {
            fwrite(STDERR, "Unexpected fine for {$id} in {$file}: {$actual}\n");
            exit(1);
        }
    }
}

assertFine($argv[1], "/const returns = (\\[[\\s\\S]*?\\]);/", [
    "RET-FINE-LATE" => 500000,
    "RET-FINE-ONTIME" => 0,
]);

assertFine($argv[2], "/const returnsData = (\\[[\\s\\S]*?\\]);/", [
    "RET-FINE-LATE" => 500000,
    "RET-FINE-ONTIME" => 0,
]);
' "${staff_returns_body}" "${admin_returns_body}"

echo "OK: late return fines are calculated and surfaced in staff/admin return payloads"
