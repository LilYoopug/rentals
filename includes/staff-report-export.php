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

function staff_report_export_pdf_text($x, $y, $text, $size = 10, $font = 'F1')
{
    $color = "0 0 0 rg\n"; // Default to black
    if ($size >= 18) $color = "0.05 0.20 0.40 rg\n"; // Navy for header
    if ($size == 12 && $font == 'F2') $color = "0.05 0.20 0.40 rg\n"; // Section Headers
    
    // If we're inside the blue header (checked via Y position or size context)
    // Actually simpler: if $size is 8.5 it's a table header, let's make it white
    if ($size == 8.5) $color = "1 1 1 rg\n";

    return "BT\n/{$font} " . number_format((float) $size, 2, '.', '') . " Tf\n" . $color . "1 0 0 1 "
        . number_format((float) $x, 2, '.', '') . ' ' . number_format((float) $y, 2, '.', '')
        . " Tm\n(" . staff_report_export_pdf_escape($text) . ") Tj\nET\n";
}

function staff_report_export_pdf_rect($x, $y, $width, $height, $fill_rgb = null, $stroke_rgb = null, $line_width = 1)
{
    $commands = '';

    // Use a blue-ish header theme for the report if it looks too dark
    if ($fill_rgb === [0.09, 0.09, 0.11]) $fill_rgb = [0.96, 0.97, 0.98]; // Summary boxes
    if ($fill_rgb === [0.15, 0.16, 0.18]) $fill_rgb = [0.12, 0.28, 0.45]; // Table headers
    if ($fill_rgb === [0.07, 0.07, 0.08]) $fill_rgb = [1.00, 1.00, 1.00]; // Even rows
    if ($fill_rgb === [0.10, 0.10, 0.12]) $fill_rgb = [0.98, 0.98, 0.99]; // Odd rows
    
    // Borders
    if ($stroke_rgb === [0.20, 0.22, 0.25]) $stroke_rgb = [0.85, 0.88, 0.91];
    if ($stroke_rgb === [0.22, 0.24, 0.27]) $stroke_rgb = [0.10, 0.24, 0.38];
    if ($stroke_rgb === [0.18, 0.20, 0.23]) $stroke_rgb = [0.92, 0.93, 0.95];

    if (is_array($fill_rgb)) {
        $commands .= sprintf("%.3F %.3F %.3F rg\n", $fill_rgb[0], $fill_rgb[1], $fill_rgb[2]);
    }

    if (is_array($stroke_rgb)) {
        $commands .= sprintf("%.3F %.3F %.3F RG\n", $stroke_rgb[0], $stroke_rgb[1], $stroke_rgb[2]);
        $commands .= sprintf("%.2F w\n", $line_width);
    }

    $paint = 'S';
    if (is_array($fill_rgb) && is_array($stroke_rgb)) {
        $paint = 'B';
    } elseif (is_array($fill_rgb)) {
        $paint = 'f';
    }

    $commands .= sprintf(
        "%.2F %.2F %.2F %.2F re %s\n",
        $x,
        $y,
        $width,
        $height,
        $paint
    );

    return $commands;
}

function staff_report_export_pdf_line($x1, $y1, $x2, $y2, $stroke_rgb = [0.26, 0.28, 0.31], $line_width = 1)
{
    return sprintf(
        "%.3F %.3F %.3F RG\n%.2F w\n%.2F %.2F m\n%.2F %.2F l\nS\n",
        $stroke_rgb[0],
        $stroke_rgb[1],
        $stroke_rgb[2],
        $line_width,
        $x1,
        $y1,
        $x2,
        $y2
    );
}

function staff_report_export_pdf_wrap_text($text, $max_chars)
{
    $plain = trim(preg_replace('/\s+/u', ' ', (string) $text));
    if ($plain === '') {
        return [''];
    }

    $max_chars = max(1, (int) $max_chars);
    $words = preg_split('/\s+/u', $plain) ?: [];
    $lines = [];
    $current = '';

    foreach ($words as $word) {
        $word = (string) $word;
        if ($current === '') {
            if (mb_strlen($word) <= $max_chars) {
                $current = $word;
                continue;
            }

            while (mb_strlen($word) > $max_chars) {
                $lines[] = mb_substr($word, 0, $max_chars);
                $word = mb_substr($word, $max_chars);
            }
            $current = $word;
            continue;
        }

        $candidate = $current . ' ' . $word;
        if (mb_strlen($candidate) <= $max_chars) {
            $current = $candidate;
            continue;
        }

        $lines[] = $current;
        if (mb_strlen($word) <= $max_chars) {
            $current = $word;
            continue;
        }

        while (mb_strlen($word) > $max_chars) {
            $lines[] = mb_substr($word, 0, $max_chars);
            $word = mb_substr($word, $max_chars);
        }
        $current = $word;
    }

    if ($current !== '') {
        $lines[] = $current;
    }

    return $lines === [] ? [''] : $lines;
}

function staff_report_export_pdf_column_widths($headers, $rows, $available_width, $gap)
{
    $column_count = count($headers);
    if ($column_count === 0) {
        return [];
    }

    $gap_total = max(0, $column_count - 1) * $gap;
    $usable_width = max(120, $available_width - $gap_total);
    $minimum_width = 52;

    if (($minimum_width * $column_count) >= $usable_width) {
        return array_fill(0, $column_count, $usable_width / $column_count);
    }

    $sample_rows = array_slice($rows, 0, 12);
    $weights = [];
    foreach ($headers as $header) {
        $max_length = mb_strlen((string) $header);
        foreach ($sample_rows as $row) {
            $value = (string) ($row[$header] ?? '');
            $max_length = max($max_length, min(32, mb_strlen($value)));
        }
        $weights[] = max(8, min(32, $max_length));
    }

    $weight_sum = array_sum($weights) ?: $column_count;
    $widths = [];
    foreach ($weights as $weight) {
        $widths[] = max($minimum_width, ($usable_width * $weight) / $weight_sum);
    }

    $difference = $usable_width - array_sum($widths);
    $widths[$column_count - 1] += $difference;

    return $widths;
}

function staff_report_export_build_pdf($payload)
{
    $page_width = 842;
    $page_height = 595;
    $margin = 32;
    $bottom_margin = 30;
    $content_width = $page_width - ($margin * 2);
    $table_gap = 8;
    $pages = [];
    $page_number = 0;
    $content = '';
    $y = $page_height - $margin;

    $start_page = function ($show_summary = false) use (&$content, &$y, &$page_number, $payload, $page_width, $page_height, $margin, $content_width) {
        $page_number++;
        $content = '';
        $y = $page_height - 38;

        $content .= staff_report_export_pdf_text($margin, $y, 'LensCraft Staff Report', 18, 'F2');
        $content .= staff_report_export_pdf_text($page_width - 96, $y + 2, 'Page ' . $page_number, 9, 'F1');
        $content .= staff_report_export_pdf_line($margin, $y - 10, $page_width - $margin, $y - 10, [0.28, 0.30, 0.34], 1);
        $y -= 24;

        if ($show_summary) {
            $box_height = 44;
            $content .= staff_report_export_pdf_rect($margin, $y - $box_height, $content_width, $box_height, [0.09, 0.09, 0.11], [0.20, 0.22, 0.25], 0.8);
            $content .= staff_report_export_pdf_text($margin + 14, $y - 16, 'Date Range: ' . $payload['range_label'], 10, 'F2');
            $content .= staff_report_export_pdf_text($margin + 14, $y - 31, 'Generated At: ' . $payload['generated_at'], 9, 'F1');
            $y -= ($box_height + 18);
        } else {
            $y -= 10;
        }
    };

    $flush_page = function () use (&$pages, &$content) {
        if ($content !== '') {
            $pages[] = $content;
        }
    };

    $start_page(true);

    foreach ($payload['sections'] as $section) {
        $section_title = strtoupper((string) ($section['label'] ?? 'Section'));
        $section_description = (string) ($section['description'] ?? '');
        $description_lines = staff_report_export_pdf_wrap_text($section_description, 120);
        $heading_height = 16 + (count($description_lines) * 11) + 12;

        if (($y - $heading_height) < $bottom_margin) {
            $flush_page();
            $start_page(false);
        }

        $content .= staff_report_export_pdf_text($margin, $y, $section_title, 12, 'F2');
        $y -= 16;
        foreach ($description_lines as $line) {
            $content .= staff_report_export_pdf_text($margin, $y, $line, 9, 'F1');
            $y -= 11;
        }
        $y -= 8;

        if ($section['rows'] === []) {
            $empty_height = 40;
            if (($y - $empty_height) < $bottom_margin) {
                $flush_page();
                $start_page(false);
            }

            $content .= staff_report_export_pdf_rect($margin, $y - $empty_height, $content_width, $empty_height, [0.09, 0.09, 0.11], [0.20, 0.22, 0.25], 0.8);
            $content .= staff_report_export_pdf_text($margin + 14, $y - 23, 'No data available for this section.', 10, 'F1');
            $y -= ($empty_height + 18);
            continue;
        }

        $headers = array_keys($section['rows'][0]);
        $column_widths = staff_report_export_pdf_column_widths($headers, $section['rows'], $content_width, $table_gap);
        $header_height = 24;

        $draw_table_header = function () use (&$content, &$y, $margin, $content_width, $header_height, $headers, $column_widths, $table_gap) {
            $content .= staff_report_export_pdf_rect($margin, $y - $header_height, $content_width, $header_height, [0.15, 0.16, 0.18], [0.22, 0.24, 0.27], 0.8);
            $x = $margin + 8;
            foreach ($headers as $index => $header) {
                $content .= staff_report_export_pdf_text($x, $y - 16, (string) $header, 8.5, 'F2');
                $x += $column_widths[$index] + $table_gap;
            }
            $y -= $header_height;
        };

        if (($y - $header_height) < $bottom_margin) {
            $flush_page();
            $start_page(false);
        }

        $draw_table_header();

        foreach ($section['rows'] as $row_index => $row) {
            $wrapped_cells = [];
            $max_lines = 1;

            foreach ($headers as $index => $header) {
                $char_capacity = max(6, (int) floor($column_widths[$index] / 4.7));
                $lines = staff_report_export_pdf_wrap_text((string) ($row[$header] ?? ''), $char_capacity);
                $wrapped_cells[$index] = $lines;
                $max_lines = max($max_lines, count($lines));
            }

            $row_height = max(24, 10 + ($max_lines * 10));
            if (($y - $row_height) < $bottom_margin) {
                $flush_page();
                $start_page(false);

                $continued_lines = staff_report_export_pdf_wrap_text($section_description . ' (continued)', 120);
                $content .= staff_report_export_pdf_text($margin, $y, $section_title . ' (CONTINUED)', 12, 'F2');
                $y -= 16;
                foreach ($continued_lines as $line) {
                    $content .= staff_report_export_pdf_text($margin, $y, $line, 9, 'F1');
                    $y -= 11;
                }
                $y -= 8;
                $draw_table_header();
            }

            $fill = $row_index % 2 === 0 ? [0.07, 0.07, 0.08] : [0.10, 0.10, 0.12];
            $content .= staff_report_export_pdf_rect($margin, $y - $row_height, $content_width, $row_height, $fill, [0.18, 0.20, 0.23], 0.5);

            $x = $margin + 8;
            foreach ($headers as $index => $header) {
                $line_y = $y - 14;
                foreach ($wrapped_cells[$index] as $line) {
                    $content .= staff_report_export_pdf_text($x, $line_y, $line, 8.3, 'F1');
                    $line_y -= 10;
                }
                $x += $column_widths[$index] + $table_gap;
            }

            $y -= $row_height;
        }

        $y -= 18;
    }

    $flush_page();

    if ($pages === []) {
        $start_page(true);
        $content .= staff_report_export_pdf_text($margin, $y, 'No data available.', 11, 'F1');
        $flush_page();
    }

    $objects = [];
    $page_object_ids = [];
    $content_object_ids = [];
    $font_object_id = 3;
    $bold_font_object_id = 4;
    $next_object_id = 5;

    foreach ($pages as $page_content) {
        $content = $page_content;

        $content_object_id = $next_object_id++;
        $page_object_id = $next_object_id++;
        $content_object_ids[] = $content_object_id;
        $page_object_ids[] = $page_object_id;

        $objects[$content_object_id] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        $objects[$page_object_id] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . $page_width . ' ' . $page_height . "] /Resources << /Font << /F1 " . $font_object_id . " 0 R /F2 " . $bold_font_object_id . " 0 R >> >> /Contents " . $content_object_id . " 0 R >>";
    }

    $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $kids = implode(' ', array_map(static function ($id) {
        return $id . ' 0 R';
    }, $page_object_ids));
    $objects[2] = "<< /Type /Pages /Count " . count($page_object_ids) . " /Kids [ " . $kids . " ] >>";
    $objects[$font_object_id] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[$bold_font_object_id] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

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
