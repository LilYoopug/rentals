#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "$0")/.." && pwd)"

php -r '
mysqli_report(MYSQLI_REPORT_OFF);
require $argv[1] . "/includes/functions.php";

$checks = [
    ["fn" => "normalize_rental_status_value", "args" => ["approved"], "expected" => "disetujui"],
    ["fn" => "normalize_rental_status_value", "args" => ["disetujui"], "expected" => "disetujui"],
    ["fn" => "present_borrowing_workflow_status", "args" => ["approved"], "expected" => "disetujui"],
    ["fn" => "present_borrowing_workflow_status", "args" => ["active"], "expected" => "aktif"],
    ["fn" => "present_borrowing_workflow_status", "args" => ["completed"], "expected" => "selesai"],
    ["fn" => "present_borrowing_workflow_status", "args" => ["cancelled"], "expected" => "dibatalkan"],
    ["fn" => "present_borrowing_workflow_status", "args" => ["pending"], "expected" => "menunggu"],
    ["fn" => "present_return_workflow_status", "args" => ["completed", false], "expected" => "returned"],
    ["fn" => "present_return_workflow_status", "args" => ["pending", false], "expected" => "menunggu"],
    ["fn" => "present_return_workflow_status", "args" => ["borrowed", true], "expected" => "borrowed"],
];

foreach ($checks as $check) {
    $fn = $check["fn"];
    if (!function_exists($fn)) {
        fwrite(STDERR, "Missing status presenter: {$fn}\n");
        exit(1);
    }

    $actual = $fn(...$check["args"]);
    if ($actual !== $check["expected"]) {
        fwrite(STDERR, "{$fn} returned {$actual}, expected {$check["expected"]}\n");
        exit(1);
    }
}
' "${project_root}"

echo "OK: workflow status helpers preserve approved-before-payment and active rental states distinctly"
