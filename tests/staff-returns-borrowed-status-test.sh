#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

staff_cookie="$(mktemp)"
returns_body="$(mktemp)"
trap 'rm -f "${staff_cookie}" "${returns_body}"' EXIT

mysql_test lenscraft <<'SQL'
INSERT INTO rentals (
  rental_code, user_id, product_id, start_date, end_date, total_days,
  daily_rate, discount_percentage, delivery_method, delivery_fee, total_price,
  status, created_at, approved_at, completed_at, cancelled_at, cancel_reason
) VALUES
  (
    'RENT-TRACK-BORROWED',
    3,
    3,
    '2026-02-10',
    '2026-02-12',
    3,
    59.00,
    0,
    'ambil_sendiri',
    0.00,
    177.00,
    'aktif',
    '2026-02-09 08:00:00',
    '2026-02-09 09:00:00',
    NULL,
    NULL,
    NULL
  ),
  (
    'RENT-TRACK-PENDING',
    4,
    4,
    '2026-02-11',
    '2026-02-13',
    3,
    75.00,
    0,
    'ambil_sendiri',
    0.00,
    225.00,
    'aktif',
    '2026-02-10 08:00:00',
    '2026-02-10 09:00:00',
    NULL,
    NULL,
    NULL
  ),
  (
    'RENT-TRACK-RETURNED',
    5,
    5,
    '2026-02-12',
    '2026-02-14',
    3,
    80.00,
    0,
    'ambil_sendiri',
    0.00,
    240.00,
    'selesai',
    '2026-02-11 08:00:00',
    '2026-02-11 09:00:00',
    '2026-02-14 11:00:00',
    NULL,
    NULL
  );
SQL

pending_rental_id="$(mysql_test -N -B lenscraft -e "SELECT id FROM rentals WHERE rental_code = 'RENT-TRACK-PENDING'")"
returned_rental_id="$(mysql_test -N -B lenscraft -e "SELECT id FROM rentals WHERE rental_code = 'RENT-TRACK-RETURNED'")"

mysql_test lenscraft <<SQL
INSERT INTO returns (
  return_code, rental_id, processed_by, notes, status, returned_at, created_at
) VALUES
  (
    'RET-TRACK-PENDING',
    ${pending_rental_id},
    2,
    'Pending handoff',
    'menunggu',
    NULL,
    '2026-02-13 09:00:00'
  ),
  (
    'RET-TRACK-RETURNED',
    ${returned_rental_id},
    2,
    'Completed return',
    'selesai',
    '2026-02-14 12:00:00',
    '2026-02-14 10:00:00'
  );
SQL

curl -sS \
  -c "${staff_cookie}" \
  -X POST \
  -d 'username=petugas&password=staff123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${staff_cookie}" \
  "${TEST_BASE_URL}/staff/returns.php" \
  -o "${returns_body}"

php -r '
if (!preg_match("/const returns = (\\[[\\s\\S]*?\\]);/", file_get_contents($argv[1]), $matches)) {
    fwrite(STDERR, "Failed to locate returns payload\n");
    exit(1);
}

$rows = json_decode($matches[1], true);
if (!is_array($rows)) {
    fwrite(STDERR, "Failed to decode returns payload\n");
    exit(1);
}

$tracked = [];
foreach ($rows as $row) {
    $id = (string) ($row["id"] ?? "");
    if (str_starts_with($id, "RENT-TRACK-") || str_starts_with($id, "RET-TRACK-")) {
        $tracked[$id] = [
            "status" => (string) ($row["status"] ?? ""),
            "returnDate" => (string) ($row["returnDate"] ?? "")
        ];
    }
}

$expected = [
    "RENT-TRACK-BORROWED" => ["status" => "borrowed", "returnDate" => "2026-02-12"],
    "RET-TRACK-PENDING" => ["status" => "menunggu", "returnDate" => "2026-02-13"],
    "RET-TRACK-RETURNED" => ["status" => "returned", "returnDate" => "2026-02-14"],
];

foreach ($expected as $id => $expectation) {
    if (!isset($tracked[$id])) {
        fwrite(STDERR, "Missing tracked return row: {$id}\n");
        exit(1);
    }
    if ($tracked[$id]["status"] !== $expectation["status"]) {
        fwrite(STDERR, "Unexpected status for {$id}: {$tracked[$id]["status"]}\n");
        exit(1);
    }
    if ($tracked[$id]["returnDate"] !== $expectation["returnDate"]) {
        fwrite(STDERR, "Unexpected return date for {$id}: {$tracked[$id]["returnDate"]}\n");
        exit(1);
    }
}
' "${returns_body}"

echo "OK: staff returns page includes borrowed, pending, and returned tracking states"
