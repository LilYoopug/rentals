<?php

require_once __DIR__ . '/../includes/staff-check.php';
require_once __DIR__ . '/../includes/staff-report-export.php';

$format = trim((string) ($_GET['format'] ?? ''));
if (!in_array($format, ['pdf', 'xlsx', 'csv'], true)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Unsupported export format.';
    exit;
}

$sections = staff_report_normalize_sections($_GET['report_sections'] ?? []);
$range = staff_report_export_range_config(
    (string) ($_GET['report_date_range'] ?? '30'),
    (string) ($_GET['custom_date_from'] ?? ''),
    (string) ($_GET['custom_date_to'] ?? '')
);
$payload = staff_report_export_payload($sections, $range);
$filename = staff_report_export_filename($payload, $format);

if ($format === 'csv') {
    staff_report_export_send_csv($payload, $filename);
    exit;
}

try {
    if ($format === 'xlsx') {
        $bytes = staff_report_export_build_xlsx($payload);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    } else {
        $bytes = staff_report_export_build_pdf($payload);
        header('Content-Type: application/pdf');
    }
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Failed to generate export.';
    exit;
}

header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($bytes));
echo $bytes;
