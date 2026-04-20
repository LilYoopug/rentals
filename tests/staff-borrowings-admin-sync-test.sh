#!/usr/bin/env bash
set -euo pipefail

source "$(cd "$(dirname "$0")/.." && pwd)/tests/helpers/test-env.sh"

start_test_stack

admin_cookie="$(mktemp)"
staff_cookie="$(mktemp)"
admin_body="$(mktemp)"
staff_body="$(mktemp)"
trap 'rm -f "${admin_cookie}" "${staff_cookie}" "${admin_body}" "${staff_body}"' RETURN

mysql_test lenscraft <<'SQL'
INSERT INTO rentals (
  rental_code, user_id, product_id, start_date, end_date, total_days,
  daily_rate, discount_percentage, delivery_method, delivery_fee, total_price,
  status, created_at, approved_at, completed_at, cancelled_at, cancel_reason
) VALUES
  (
    'RENT-SYNC-APPROVED-UNPAID',
    3,
    5,
    '2026-03-08',
    '2026-03-10',
    3,
    80.00,
    0,
    'ambil_sendiri',
    0.00,
    240.00,
    'disetujui',
    '2026-03-07 08:00:00',
    '2026-03-07 09:00:00',
    NULL,
    NULL,
    NULL
  ),
  (
    'RENT-SYNC-COMPLETED',
    3,
    3,
    '2026-03-10',
    '2026-03-12',
    3,
    59.00,
    0,
    'ambil_sendiri',
    0.00,
    177.00,
    'selesai',
    '2026-03-09 08:00:00',
    '2026-03-09 09:00:00',
    '2026-03-12 10:00:00',
    NULL,
    NULL
  ),
  (
    'RENT-SYNC-REJECTED',
    4,
    4,
    '2026-03-11',
    '2026-03-13',
    3,
    75.00,
    0,
    'ambil_sendiri',
    0.00,
    225.00,
    'ditolak',
    '2026-03-10 08:00:00',
    NULL,
    NULL,
    '2026-03-10 09:00:00',
    'Stock unavailable'
  );
SQL

mysql_test lenscraft <<'SQL'
INSERT INTO payments (payment_code, rental_id, amount, method, status, created_at, updated_at)
SELECT 'PAY-SYNC-APPROVED-UNPAID', id, total_price, 'transfer_bank', 'pending', NOW(), NOW()
FROM rentals WHERE rental_code = 'RENT-SYNC-APPROVED-UNPAID';
SQL

curl -sS \
  -c "${admin_cookie}" \
  -X POST \
  -d 'username=admin&password=admin123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -c "${staff_cookie}" \
  -X POST \
  -d 'username=petugas&password=staff123' \
  "${TEST_BASE_URL}/process/login-process.php" \
  -o /dev/null

curl -sS \
  -b "${admin_cookie}" \
  "${TEST_BASE_URL}/admin/borrowings.php" \
  -o "${admin_body}"

curl -sS \
  -b "${staff_cookie}" \
  "${TEST_BASE_URL}/staff/borrowings.php" \
  -o "${staff_body}"

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

function tracked_statuses(array $rows): array {
    $tracked = [];
    foreach ($rows as $row) {
        $id = (string) ($row["id"] ?? "");
        if (str_starts_with($id, "RENT-SYNC-")) {
            $tracked[$id] = (string) ($row["status"] ?? "");
        }
    }

    ksort($tracked);
    return $tracked;
}

$admin = tracked_statuses(extract_array(file_get_contents($argv[1]), "borrowingsData"));
$staff = tracked_statuses(extract_array(file_get_contents($argv[2]), "borrowings"));

$expected = [
    "RENT-SYNC-APPROVED-UNPAID" => "disetujui",
    "RENT-SYNC-COMPLETED" => "selesai",
    "RENT-SYNC-REJECTED" => "ditolak",
];

if ($admin !== $expected) {
    fwrite(STDERR, "Unexpected admin tracked borrowings: " . json_encode($admin) . "\n");
    exit(1);
}

if ($staff !== $expected) {
    fwrite(STDERR, "Staff borrowings not synced with admin: " . json_encode($staff) . "\n");
    exit(1);
}
' "${admin_body}" "${staff_body}"

echo "OK: staff borrowings route stays in sync with admin borrowing records across statuses"
