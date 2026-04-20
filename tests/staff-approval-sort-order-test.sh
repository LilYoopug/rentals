#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

staff_cookie="$(mktemp)"
borrowings_body="$(mktemp)"
returns_body="$(mktemp)"
trap 'rm -f "${staff_cookie}" "${borrowings_body}" "${returns_body}"' RETURN

mysql_test lenscraft <<'SQL'
INSERT INTO rentals (
  rental_code, user_id, product_id, start_date, end_date, total_days,
  daily_rate, discount_percentage, delivery_method, delivery_fee, total_price,
  status, created_at, approved_at, completed_at, cancelled_at, cancel_reason
) VALUES
  (
    'RENT-SORT-PENDING-OLD',
    3,
    3,
    '2026-01-02',
    '2026-01-03',
    2,
    59.00,
    0,
    'ambil_sendiri',
    0.00,
    118.00,
    'menunggu',
    '2026-01-01 08:00:00',
    NULL,
    NULL,
    NULL,
    NULL
  ),
  (
    'RENT-SORT-PENDING-NEW',
    4,
    4,
    '2026-01-03',
    '2026-01-04',
    2,
    75.00,
    0,
    'ambil_sendiri',
    0.00,
    150.00,
    'menunggu',
    '2026-01-02 08:00:00',
    NULL,
    NULL,
    NULL,
    NULL
  ),
  (
    'RENT-SORT-APPROVED-UNPAID',
    5,
    5,
    '2026-01-04',
    '2026-01-05',
    2,
    80.00,
    0,
    'ambil_sendiri',
    0.00,
    160.00,
    'disetujui',
    '2026-01-03 08:00:00',
    '2026-01-03 09:00:00',
    NULL,
    NULL,
    NULL
  ),
  (
    'RENT-SORT-ACTIVE',
    5,
    6,
    '2026-01-04',
    '2026-01-05',
    2,
    95.00,
    0,
    'ambil_sendiri',
    0.00,
    190.00,
    'aktif',
    '2026-01-03 08:00:00',
    '2026-01-03 09:00:00',
    NULL,
    NULL,
    NULL
  ),
  (
    'RENT-RETURN-PENDING-OLD',
    3,
    6,
    '2026-01-02',
    '2026-01-03',
    2,
    70.00,
    0,
    'ambil_sendiri',
    0.00,
    140.00,
    'aktif',
    '2026-01-01 10:00:00',
    '2026-01-01 11:00:00',
    NULL,
    NULL,
    NULL
  ),
  (
    'RENT-RETURN-PENDING-NEW',
    4,
    7,
    '2026-01-03',
    '2026-01-04',
    2,
    90.00,
    0,
    'ambil_sendiri',
    0.00,
    180.00,
    'aktif',
    '2026-01-02 10:00:00',
    '2026-01-02 11:00:00',
    NULL,
    NULL,
    NULL
  ),
  (
    'RENT-RETURN-COMPLETED',
    5,
    8,
    '2026-01-04',
    '2026-01-05',
    2,
    95.00,
    0,
    'ambil_sendiri',
    0.00,
    190.00,
    'selesai',
    '2026-01-03 10:00:00',
    '2026-01-03 11:00:00',
    '2026-01-05 12:00:00',
    NULL,
    NULL
  );

SQL

mysql_test lenscraft <<'SQL'
INSERT INTO payments (payment_code, rental_id, amount, method, status, created_at, updated_at)
SELECT 'PAY-SORT-APPROVED-UNPAID', id, total_price, 'transfer_bank', 'pending', NOW(), NOW()
FROM rentals WHERE rental_code = 'RENT-SORT-APPROVED-UNPAID';
SQL

return_pending_old_id="$(mysql_test -N -B lenscraft -e "SELECT id FROM rentals WHERE rental_code = 'RENT-RETURN-PENDING-OLD'")"
return_pending_new_id="$(mysql_test -N -B lenscraft -e "SELECT id FROM rentals WHERE rental_code = 'RENT-RETURN-PENDING-NEW'")"
return_completed_id="$(mysql_test -N -B lenscraft -e "SELECT id FROM rentals WHERE rental_code = 'RENT-RETURN-COMPLETED'")"

mysql_test lenscraft <<SQL
INSERT INTO returns (
  return_code, rental_id, processed_by, notes, status, returned_at, created_at
) VALUES
  (
    'RET-SORT-PENDING-OLD',
    ${return_pending_old_id},
    2,
    'Oldest pending return',
    'menunggu',
    NULL,
    '2026-01-01 12:00:00'
  ),
  (
    'RET-SORT-PENDING-NEW',
    ${return_pending_new_id},
    2,
    'Newer pending return',
    'menunggu',
    NULL,
    '2026-01-02 12:00:00'
  ),
  (
    'RET-SORT-COMPLETED',
    ${return_completed_id},
    2,
    'Completed return',
    'selesai',
    '2026-01-05 13:00:00',
    '2026-01-03 12:00:00'
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
  "${TEST_BASE_URL}/staff/borrowings.php" \
  -o "${borrowings_body}"

curl -sS \
  -b "${staff_cookie}" \
  "${TEST_BASE_URL}/staff/returns.php" \
  -o "${returns_body}"

php -r '
function extract_array(string $html, string $variable): array {
    if (!preg_match("/const " . preg_quote($variable, "/") . " = (\\[[\\s\\S]*?\\]);/", $html, $matches)) {
        fwrite(STDERR, "Failed to locate JSON payload for {$variable}\n");
        exit(1);
    }

    $decoded = json_decode($matches[1], true);
    if (!is_array($decoded)) {
        fwrite(STDERR, "Failed to decode JSON payload for {$variable}\n");
        exit(1);
    }

    return $decoded;
}

function filter_ids(array $rows, string $prefix): array {
    $ids = [];
    foreach ($rows as $row) {
        $id = (string) ($row["id"] ?? "");
        if (str_starts_with($id, $prefix)) {
            $ids[] = $id;
        }
    }

    return $ids;
}

$borrowings = extract_array(file_get_contents($argv[1]), "borrowings");
$returns = extract_array(file_get_contents($argv[2]), "returns");

$borrowing_ids = filter_ids($borrowings, "RENT-SORT-");
$return_ids = filter_ids($returns, "RET-SORT-");

$expected_borrowings = [
    "RENT-SORT-PENDING-OLD",
    "RENT-SORT-PENDING-NEW",
    "RENT-SORT-APPROVED-UNPAID",
    "RENT-SORT-ACTIVE",
];
$expected_returns = [
    "RET-SORT-PENDING-OLD",
    "RET-SORT-PENDING-NEW",
    "RET-SORT-COMPLETED",
];

if ($borrowing_ids !== $expected_borrowings) {
    fwrite(STDERR, "Unexpected borrowing order: " . implode(", ", $borrowing_ids) . "\n");
    exit(1);
}

if ($return_ids !== $expected_returns) {
    fwrite(STDERR, "Unexpected return order: " . implode(", ", $return_ids) . "\n");
    exit(1);
}
' "${borrowings_body}" "${returns_body}"

echo "OK: staff pages prioritize pending work and sort pending items from oldest to newest"
