<?php

require_once __DIR__ . '/../data/products-data.php';
require_once __DIR__ . '/../data/rentals-data.php';
require_once __DIR__ . '/../data/returns-data.php';

function staff_report_normalize_sections($raw_sections)
{
    $allowed = [
        'stock' => 'stock',
        'borrowings' => 'borrowings',
        'returns' => 'returns',
    ];

    $sections = [];
    foreach ((array) $raw_sections as $section) {
        $key = trim((string) $section);
        if (isset($allowed[$key])) {
            $sections[$key] = $allowed[$key];
        }
    }

    if ($sections === []) {
        return ['stock', 'borrowings', 'returns'];
    }

    return array_values($sections);
}

function staff_report_primary_section($sections)
{
    $sections = staff_report_normalize_sections($sections);

    foreach (['stock', 'borrowings', 'returns'] as $candidate) {
        if (in_array($candidate, $sections, true)) {
            return $candidate;
        }
    }

    return 'stock';
}

function staff_report_primary_legacy_type($sections)
{
    $map = [
        'stock' => 'inventory',
        'borrowings' => 'borrowings',
        'returns' => 'returns',
    ];

    return $map[staff_report_primary_section($sections)] ?? 'inventory';
}

function staff_report_export_range_config($range, $custom_from, $custom_to)
{
    $allowed_ranges = ['7', '30', '90', '365', 'custom'];
    $selected_range = in_array($range, $allowed_ranges, true) ? $range : '30';

    $end = new DateTimeImmutable('today 23:59:59');
    $start = $end->modify('-29 days')->setTime(0, 0, 0);
    $label = 'Last 30 days';

    if ($selected_range === 'custom') {
        $from = DateTimeImmutable::createFromFormat('Y-m-d', $custom_from ?: '');
        $to = DateTimeImmutable::createFromFormat('Y-m-d', $custom_to ?: '');

        if ($from instanceof DateTimeImmutable && $to instanceof DateTimeImmutable && $from <= $to) {
            $start = $from->setTime(0, 0, 0);
            $end = $to->setTime(23, 59, 59);
            $label = $from->format('M j, Y') . ' - ' . $to->format('M j, Y');
        } else {
            $selected_range = '30';
        }
    }

    if ($selected_range !== 'custom') {
        $days = (int) $selected_range;
        $start = $end->modify('-' . max(0, $days - 1) . ' days')->setTime(0, 0, 0);
        $label = 'Last ' . $days . ' days';
    }

    return [
        'selected_range' => $selected_range,
        'start' => $start,
        'end' => $end,
        'label' => $label,
        'custom_from' => $selected_range === 'custom' ? $custom_from : '',
        'custom_to' => $selected_range === 'custom' ? $custom_to : '',
    ];
}

function staff_report_export_filter_rows($rows, $preferred_keys, $start_timestamp, $end_timestamp)
{
    $filtered = [];

    foreach ($rows as $row) {
        foreach ($preferred_keys as $key) {
            if (empty($row[$key])) {
                continue;
            }

            $timestamp = strtotime((string) $row[$key]);
            if ($timestamp === false) {
                continue;
            }

            if ($timestamp >= $start_timestamp && $timestamp <= $end_timestamp) {
                $filtered[] = $row;
            }
            break;
        }
    }

    return $filtered;
}

function staff_report_export_payload($sections, $range)
{
    $sections = staff_report_normalize_sections($sections);
    $all_borrowings = get_all_borrowings();
    $all_returns = get_all_returns();
    $all_products = get_admin_products();

    $start_timestamp = $range['start']->getTimestamp();
    $end_timestamp = $range['end']->getTimestamp();

    $borrowings = staff_report_export_filter_rows($all_borrowings, ['created_at'], $start_timestamp, $end_timestamp);
    $returns = staff_report_export_filter_rows($all_returns, ['returnedAt', 'createdAt'], $start_timestamp, $end_timestamp);

    $section_map = [];

    if (in_array('stock', $sections, true)) {
        $rows = [];
        foreach ($all_products as $row) {
            $stock_total = (int) ($row['stock_total'] ?? 0);
            $stock_available = (int) ($row['stock_available'] ?? 0);
            $rows[] = [
                'Product ID' => (int) ($row['id'] ?? 0),
                'Name' => (string) ($row['name'] ?? ''),
                'Brand' => (string) ($row['brand'] ?? ''),
                'Category' => (string) ($row['category_name'] ?? $row['category_slug'] ?? ''),
                'Daily Rate' => number_format((float) ($row['price_per_day'] ?? 0), 2, '.', ''),
                'Discount %' => (int) ($row['discount_percentage'] ?? 0),
                'Stock Total' => $stock_total,
                'Stock Available' => $stock_available,
                'Reserved Units' => max(0, $stock_total - $stock_available),
                'Status' => (string) ($row['status'] ?? 'aktif'),
            ];
        }

        $section_map['stock'] = [
            'label' => 'Stock',
            'description' => 'Current stock snapshot as of export time.',
            'rows' => $rows,
        ];
    }

    if (in_array('borrowings', $sections, true)) {
        $rows = [];
        foreach ($borrowings as $row) {
            $rows[] = [
                'Request ID' => (string) ($row['rental_code'] ?? ''),
                'Customer' => (string) ($row['fullname'] ?? ''),
                'Equipment' => (string) ($row['product_name'] ?? ''),
                'Brand' => (string) ($row['brand'] ?? ''),
                'Start Date' => (string) ($row['start_date'] ?? ''),
                'End Date' => (string) ($row['end_date'] ?? ''),
                'Days' => (int) ($row['total_days'] ?? 0),
                'Total Price' => number_format((float) ($row['total_price'] ?? 0), 2, '.', ''),
                'Status' => (string) ($row['status'] ?? 'menunggu'),
                'Created At' => (string) ($row['created_at'] ?? ''),
            ];
        }

        $section_map['borrowings'] = [
            'label' => 'Peminjaman',
            'description' => 'Borrowing requests created within the selected date range.',
            'rows' => $rows,
        ];
    }

    if (in_array('returns', $sections, true)) {
        $rows = [];
        foreach ($returns as $row) {
            $rows[] = [
                'Return ID' => (string) ($row['id'] ?? ''),
                'Rental ID' => (string) ($row['rentalCode'] ?? ''),
                'Customer' => (string) ($row['fullname'] ?? ''),
                'Equipment' => (string) ($row['productName'] ?? ''),
                'Brand' => (string) ($row['brand'] ?? ''),
                'Return Date' => (string) ($row['returnedAt'] ?? ''),
                'Fine Amount' => number_format((float) ($row['fineAmount'] ?? 0), 2, '.', ''),
                'Status' => (string) ($row['status'] ?? 'menunggu'),
                'Notes' => (string) ($row['notes'] ?? ''),
                'Created At' => (string) ($row['createdAt'] ?? ''),
            ];
        }

        $section_map['returns'] = [
            'label' => 'Pengembalian',
            'description' => 'Return records created or completed within the selected date range.',
            'rows' => $rows,
        ];
    }

    return [
        'generated_at' => date('Y-m-d H:i:s'),
        'range_label' => (string) $range['label'],
        'sections' => $section_map,
    ];
}

function staff_report_export_filename($payload, $format)
{
    $parts = [];
    foreach (array_keys($payload['sections']) as $section) {
        $parts[] = $section;
    }

    if ($parts === []) {
        $parts[] = 'report';
    }

    return 'staff-report-' . implode('-', $parts) . '-' . date('Y-m-d') . '.' . $format;
}

function staff_report_export_send_csv($payload, $filename)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $handle = fopen('php://output', 'wb');
    if ($handle === false) {
        return;
    }

    foreach ($payload['sections'] as $section) {
        fputcsv($handle, [$section['label']]);
        fputcsv($handle, ['Date Range', $payload['range_label']]);
        fputcsv($handle, ['Generated At', $payload['generated_at']]);
        fputcsv($handle, [$section['description']]);

        $rows = $section['rows'];
        if ($rows === []) {
            fputcsv($handle, ['No data available']);
            fputcsv($handle, []);
            continue;
        }

        fputcsv($handle, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }
        fputcsv($handle, []);
    }

    fclose($handle);
}

function staff_report_export_column_letters($index)
{
    $letters = '';
    while ($index >= 0) {
        $letters = chr(($index % 26) + 65) . $letters;
        $index = intdiv($index, 26) - 1;
    }
    return $letters;
}

function staff_report_export_escape_xml($value)
{
    return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function staff_report_export_sheet_name($label)
{
    $name = preg_replace('/[\\\\\\/*\\?:\\[\\]]+/', '', (string) $label);
    $name = trim((string) $name);
    if ($name === '') {
        $name = 'Sheet';
    }
    return mb_substr($name, 0, 31);
}

function staff_report_export_sheet_xml($payload, $section)
{
    $rows = [];
    $rows[] = [$section['label']];
    $rows[] = ['Date Range', $payload['range_label']];
    $rows[] = ['Generated At', $payload['generated_at']];
    $rows[] = [$section['description']];
    $rows[] = [];

    if ($section['rows'] === []) {
        $rows[] = ['No data available'];
    } else {
        $rows[] = array_keys($section['rows'][0]);
        foreach ($section['rows'] as $row) {
            $rows[] = array_values($row);
        }
    }

    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $xml .= '<sheetData>';

    foreach ($rows as $row_index => $row) {
        $xml .= '<row r="' . ($row_index + 1) . '">';
        foreach ($row as $cell_index => $value) {
            $ref = staff_report_export_column_letters($cell_index) . ($row_index + 1);
            if (is_numeric($value) && (string) $value !== '') {
                $xml .= '<c r="' . $ref . '" t="n"><v>' . $value . '</v></c>';
                continue;
            }
            $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . staff_report_export_escape_xml($value) . '</t></is></c>';
        }
        $xml .= '</row>';
    }

    $xml .= '</sheetData></worksheet>';
    return $xml;
}

function staff_report_export_build_xlsx($payload)
{
    $temp_dir = sys_get_temp_dir() . '/staff-report-' . bin2hex(random_bytes(8));
    $zip_target = $temp_dir . '.xlsx';

    mkdir($temp_dir, 0777, true);
    mkdir($temp_dir . '/_rels', 0777, true);
    mkdir($temp_dir . '/docProps', 0777, true);
    mkdir($temp_dir . '/xl/_rels', 0777, true);
    mkdir($temp_dir . '/xl/worksheets', 0777, true);

    $sheet_entries = [];
    $workbook_rels = [];
    $content_overrides = [];
    $sheet_id = 1;

    foreach ($payload['sections'] as $section) {
        $sheet_name = staff_report_export_sheet_name($section['label']);
        $sheet_entries[] = '<sheet name="' . staff_report_export_escape_xml($sheet_name) . '" sheetId="' . $sheet_id . '" r:id="rId' . $sheet_id . '"/>';
        $workbook_rels[] = '<Relationship Id="rId' . $sheet_id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $sheet_id . '.xml"/>';
        $content_overrides[] = '<Override PartName="/xl/worksheets/sheet' . $sheet_id . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        file_put_contents($temp_dir . '/xl/worksheets/sheet' . $sheet_id . '.xml', staff_report_export_sheet_xml($payload, $section));
        $sheet_id++;
    }

    file_put_contents(
        $temp_dir . '/[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
        '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
        '<Default Extension="xml" ContentType="application/xml"/>' .
        '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
        '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
        '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>' .
        '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>' .
        implode('', $content_overrides) .
        '</Types>'
    );

    file_put_contents(
        $temp_dir . '/_rels/.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
        '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>' .
        '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>' .
        '</Relationships>'
    );

    file_put_contents(
        $temp_dir . '/xl/workbook.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
        '<sheets>' . implode('', $sheet_entries) . '</sheets>' .
        '</workbook>'
    );

    file_put_contents(
        $temp_dir . '/xl/_rels/workbook.xml.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        implode('', $workbook_rels) .
        '<Relationship Id="rId' . $sheet_id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
        '</Relationships>'
    );

    file_put_contents(
        $temp_dir . '/xl/styles.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
        '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>' .
        '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>' .
        '<borders count="1"><border/></borders>' .
        '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' .
        '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>' .
        '</styleSheet>'
    );

    file_put_contents(
        $temp_dir . '/docProps/core.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
        '<dc:title>LensCraft Staff Report</dc:title>' .
        '<dc:creator>Codex</dc:creator>' .
        '<cp:lastModifiedBy>Codex</cp:lastModifiedBy>' .
        '<dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created>' .
        '</cp:coreProperties>'
    );

    file_put_contents(
        $temp_dir . '/docProps/app.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">' .
        '<Application>LensCraft</Application>' .
        '</Properties>'
    );

    $cwd = getcwd();
    chdir($temp_dir);
    exec('zip -qr ' . escapeshellarg($zip_target) . ' .', $output, $status);
    chdir($cwd ?: __DIR__);

    if ($status !== 0 || !is_file($zip_target)) {
        staff_report_export_delete_dir($temp_dir);
        throw new RuntimeException('Failed to build XLSX export.');
    }

    $bytes = file_get_contents($zip_target);
    unlink($zip_target);
    staff_report_export_delete_dir($temp_dir);

    if ($bytes === false) {
        throw new RuntimeException('Failed to read XLSX export.');
    }

    return $bytes;
}

function staff_report_export_delete_dir($dir)
{
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            staff_report_export_delete_dir($path);
        } elseif (is_file($path)) {
            unlink($path);
        }
    }

    rmdir($dir);
}

function staff_report_export_pdf_escape($value)
{
    $text = mb_convert_encoding((string) $value, 'ISO-8859-1', 'UTF-8');
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function staff_report_export_build_pdf($payload)
{
    $lines = [
        'LensCraft Staff Report',
        'Date Range: ' . $payload['range_label'],
        'Generated At: ' . $payload['generated_at'],
        '',
    ];

    foreach ($payload['sections'] as $section) {
        $lines[] = strtoupper($section['label']);
        $lines[] = $section['description'];
        if ($section['rows'] === []) {
            $lines[] = 'No data available';
            $lines[] = '';
            continue;
        }

        $headers = array_keys($section['rows'][0]);
        $lines[] = implode(' | ', $headers);
        foreach ($section['rows'] as $row) {
            $values = [];
            foreach ($headers as $header) {
                $values[] = (string) ($row[$header] ?? '');
            }
            $chunk = implode(' | ', $values);
            while (mb_strlen($chunk) > 110) {
                $lines[] = mb_substr($chunk, 0, 110);
                $chunk = mb_substr($chunk, 110);
            }
            $lines[] = $chunk;
        }
        $lines[] = '';
    }

    $pages = [];
    $page_lines = [];
    $line_limit = 48;
    foreach ($lines as $line) {
        $page_lines[] = $line;
        if (count($page_lines) >= $line_limit) {
            $pages[] = $page_lines;
            $page_lines = [];
        }
    }
    if ($page_lines !== []) {
        $pages[] = $page_lines;
    }

    $objects = [];
    $page_object_ids = [];
    $content_object_ids = [];
    $font_object_id = 3;
    $next_object_id = 4;

    foreach ($pages as $page_index => $page_lines_set) {
        $content = "BT\n/F1 10 Tf\n36 806 Td\n";
        foreach ($page_lines_set as $line_index => $line) {
            if ($line_index > 0) {
                $content .= "0 -15 Td\n";
            }
            $content .= '(' . staff_report_export_pdf_escape($line) . ") Tj\n";
        }
        $content .= "ET";

        $content_object_id = $next_object_id++;
        $page_object_id = $next_object_id++;
        $content_object_ids[] = $content_object_id;
        $page_object_ids[] = $page_object_id;

        $objects[$content_object_id] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        $objects[$page_object_id] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 " . $font_object_id . " 0 R >> >> /Contents " . $content_object_id . " 0 R >>";
    }

    $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $kids = implode(' ', array_map(static function ($id) {
        return $id . ' 0 R';
    }, $page_object_ids));
    $objects[2] = "<< /Type /Pages /Count " . count($page_object_ids) . " /Kids [ " . $kids . " ] >>";
    $objects[$font_object_id] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

    ksort($objects);
    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $object_id => $body) {
        $offsets[$object_id] = strlen($pdf);
        $pdf .= $object_id . " 0 obj\n" . $body . "\nendobj\n";
    }

    $xref_offset = strlen($pdf);
    $max_object_id = max(array_keys($objects));
    $pdf .= "xref\n0 " . ($max_object_id + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= $max_object_id; $i++) {
        $offset = $offsets[$i] ?? 0;
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }
    $pdf .= "trailer\n<< /Size " . ($max_object_id + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref_offset . "\n%%EOF";

    return $pdf;
}
