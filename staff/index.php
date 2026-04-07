<?php
require_once __DIR__ . '/../includes/staff-check.php';
require_once __DIR__ . '/../data/users/staff-data.php';
require_once __DIR__ . '/../data/users/admin-data.php';
require_once __DIR__ . '/../data/categories-data.php';
require_once __DIR__ . '/../data/products-data.php';
require_once __DIR__ . '/../data/rentals-data.php';
require_once __DIR__ . '/../data/returns-data.php';
require_once __DIR__ . '/../includes/flash.php';

function staff_relative_time($datetime)
{
    if (!$datetime) {
        return 'Baru saja';
    }

    $timestamp = strtotime((string) $datetime);
    if ($timestamp === false) {
        return 'Baru saja';
    }

    $diff = time() - $timestamp;
    if ($diff < 3600) {
        $minutes = max(1, (int) floor($diff / 60));
        return $minutes . ' menit lalu';
    }

    if ($diff < 86400) {
        $hours = max(1, (int) floor($diff / 3600));
        return $hours . ' jam lalu';
    }

    $days = max(1, (int) floor($diff / 86400));
    return $days . ' hari lalu';
}

function staff_change_meta($current, $previous)
{
    if ($previous <= 0) {
        if ($current > 0) {
            return ['text' => '+100%', 'class' => 'text-green-400 bg-green-900/30 border border-green-800/50'];
        }

        return ['text' => '0%', 'class' => 'text-neutral-400 bg-neutral-800/50 border border-neutral-700'];
    }

    $delta = (($current - $previous) / $previous) * 100;
    $rounded = (int) round($delta);
    if ($rounded > 0) {
        return ['text' => '+' . $rounded . '%', 'class' => 'text-green-400 bg-green-900/30 border border-green-800/50'];
    }
    if ($rounded < 0) {
        return ['text' => $rounded . '%', 'class' => 'text-red-400 bg-red-900/30 border border-red-800/50'];
    }

    return ['text' => '0%', 'class' => 'text-neutral-400 bg-neutral-800/50 border border-neutral-700'];
}

$staff_borrowing_rows = get_staff_borrowing_rows();
$staff_return_rows = get_staff_return_rows();
$all_borrowing_rows = get_all_borrowings();
$all_return_rows = get_all_returns();
$all_user_rows = get_admin_users();
$all_products = get_admin_products();
$all_category_rows = get_all_categories();

$staff_borrowings = [];
foreach ($staff_borrowing_rows as $row) {
    $status = (string) ($row['status'] ?? 'pending');
    $map = [
        'pending' => 'pending',
        'upcoming' => 'pending',
        'active' => 'approved',
        'completed' => 'approved',
        'cancelled' => 'rejected',
        'rejected' => 'rejected',
    ];

    $staff_borrowings[] = [
        'id' => (string) ($row['rental_code'] ?? ''),
        'customer' => (string) ($row['fullname'] ?? ''),
        'equipment' => (string) ($row['product_name'] ?? ''),
        'brand' => (string) ($row['brand'] ?? ''),
        'category' => (string) ($row['category_slug'] ?? ''),
        'image' => (string) ($row['image_path'] ?? '../images/gear-placeholder.svg'),
        'startDate' => (string) ($row['start_date'] ?? ''),
        'endDate' => (string) ($row['end_date'] ?? ''),
        'days' => (int) ($row['total_days'] ?? 0),
        'amount' => (float) ($row['total_price'] ?? 0),
        'status' => (string) ($map[$status] ?? 'pending'),
    ];
}

$staff_returns = [];
foreach ($staff_return_rows as $row) {
    $status = (string) ($row['status'] ?? 'pending');
    $map = [
        'completed' => 'returned',
        'pending' => 'pending',
        'overdue' => 'overdue',
    ];

    $staff_returns[] = [
        'id' => (string) ($row['return_code'] ?? ''),
        'customer' => (string) ($row['fullname'] ?? ''),
        'equipment' => (string) ($row['product_name'] ?? ''),
        'brand' => (string) ($row['brand'] ?? ''),
        'category' => (string) ($row['category'] ?? $row['category_slug'] ?? ''),
        'image' => (string) ($row['image_path'] ?? '../images/gear-placeholder.svg'),
        'returnDate' => !empty($row['returned_at']) ? date('Y-m-d', strtotime((string) $row['returned_at'])) : '',
        'notes' => (string) ($row['notes'] ?? ''),
        'status' => (string) ($map[$status] ?? 'pending'),
    ];
}

$revenue_by_day = [];
$transactions_by_day = [];
foreach ($all_borrowing_rows as $row) {
    $day = !empty($row['created_at']) ? date('Y-m-d', strtotime((string) $row['created_at'])) : date('Y-m-d');
    $revenue_by_day[$day] = ($revenue_by_day[$day] ?? 0) + (float) ($row['total_price'] ?? 0);
    $transactions_by_day[$day] = ($transactions_by_day[$day] ?? 0) + 1;
}
ksort($revenue_by_day);
ksort($transactions_by_day);
$revenue_labels = array_slice(array_keys($revenue_by_day), -7);
$revenue_values = [];
foreach ($revenue_labels as $label) {
    $revenue_values[] = (float) ($revenue_by_day[$label] ?? 0);
}
$transaction_labels = array_slice(array_keys($transactions_by_day), -7);
$transaction_values = [];
foreach ($transaction_labels as $label) {
    $transaction_values[] = (int) ($transactions_by_day[$label] ?? 0);
}

$user_counts = [];
foreach ($all_user_rows as $row) {
    $month = !empty($row['created_at']) ? date('Y-m', strtotime((string) $row['created_at'])) : date('Y-m');
    $user_counts[$month] = ($user_counts[$month] ?? 0) + 1;
}
ksort($user_counts);
$user_labels = array_slice(array_keys($user_counts), -6);
$user_values = [];
foreach ($user_labels as $label) {
    $user_values[] = (int) ($user_counts[$label] ?? 0);
}

$inventory_labels = [];
$inventory_values = [];
foreach ($all_category_rows as $row) {
    $inventory_labels[] = (string) ($row['name'] ?? 'Category');
    $count = 0;
    foreach ($all_products as $product_row) {
        if ((int) ($product_row['category_id'] ?? 0) === (int) ($row['id'] ?? 0)) {
            $count++;
        }
    }
    $inventory_values[] = $count;
}

$top_counts = [];
foreach ($all_borrowing_rows as $row) {
    $name = (string) ($row['product_name'] ?? 'Produk');
    $top_counts[$name] = ($top_counts[$name] ?? 0) + 1;
}
arsort($top_counts);
$top_counts = array_slice($top_counts, 0, 5, true);

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$pending_approval_count = 0;
$pending_yesterday_count = 0;
$active_rental_count = 0;
$revenue_today = 0.0;
$revenue_yesterday = 0.0;
$approved_count = 0;
foreach ($all_borrowing_rows as $row) {
    $status = (string) ($row['status'] ?? '');
    $created_day = !empty($row['created_at']) ? date('Y-m-d', strtotime((string) $row['created_at'])) : '';

    if (in_array($status, ['pending', 'upcoming'], true)) {
        $pending_approval_count++;
        if ($created_day === $yesterday) {
            $pending_yesterday_count++;
        }
    }

    if ($status === 'active') {
        $active_rental_count++;
    }

    if ($created_day === $today) {
        $revenue_today += (float) ($row['total_price'] ?? 0);
    }
    if ($created_day === $yesterday) {
        $revenue_yesterday += (float) ($row['total_price'] ?? 0);
    }

    if (in_array($status, ['active', 'completed'], true)) {
        $approved_count++;
    }
}

$returns_today = 0;
$returns_yesterday = 0;
$completed_return_count = 0;
$returns_by_day = [];
foreach ($all_return_rows as $row) {
    $returned_day = !empty($row['returnedAt']) ? date('Y-m-d', strtotime((string) $row['returnedAt'])) : '';
    if ($returned_day === $today) {
        $returns_today++;
    }
    if ($returned_day === $yesterday) {
        $returns_yesterday++;
    }
    if (($row['status'] ?? '') === 'completed') {
        $completed_return_count++;
    }
    if ($returned_day !== '') {
        $returns_by_day[$returned_day] = ($returns_by_day[$returned_day] ?? 0) + 1;
    }
}
ksort($returns_by_day);
$return_labels = array_slice(array_keys($returns_by_day), -7);
$return_values = [];
foreach ($return_labels as $label) {
    $return_values[] = (int) ($returns_by_day[$label] ?? 0);
}

$staff_summary = [
    'pending_approvals' => $pending_approval_count,
    'pending_change' => staff_change_meta($pending_approval_count, $pending_yesterday_count),
    'returns_today' => $returns_today,
    'returns_change' => staff_change_meta($returns_today, $returns_yesterday),
    'revenue_today' => format_currency($revenue_today),
    'revenue_change' => staff_change_meta($revenue_today, $revenue_yesterday),
    'active_rentals' => $active_rental_count,
    'active_badge_text' => $active_rental_count > 0 ? 'Aktif' : 'Kosong',
    'active_badge_class' => $active_rental_count > 0 ? 'text-amber-300 bg-amber-900/30 border border-amber-800/50' : 'text-neutral-400 bg-neutral-800/50 border border-neutral-700',
    'approvals_done_count' => $approved_count,
    'approvals_done_total' => max(1, count($all_borrowing_rows)),
    'approvals_done_percent' => count($all_borrowing_rows) > 0 ? (int) round(($approved_count / count($all_borrowing_rows)) * 100) : 0,
    'returns_done_count' => $completed_return_count,
    'returns_done_total' => max(1, count($all_return_rows)),
    'returns_done_percent' => count($all_return_rows) > 0 ? (int) round(($completed_return_count / count($all_return_rows)) * 100) : 0,
    'active_count' => $active_rental_count,
    'active_total' => max(1, count($all_borrowing_rows)),
    'active_percent' => count($all_borrowing_rows) > 0 ? (int) round(($active_rental_count / count($all_borrowing_rows)) * 100) : 0,
    'approval_rate' => count($all_borrowing_rows) > 0 ? (int) round(($approved_count / count($all_borrowing_rows)) * 100) : 0,
    'return_rate' => count($all_borrowing_rows) > 0 ? (int) round((count($all_return_rows) / count($all_borrowing_rows)) * 100) : 0,
];

$staff_pending_approvals = [];
foreach ($all_borrowing_rows as $row) {
    if (!in_array((string) ($row['status'] ?? ''), ['pending', 'upcoming'], true)) {
        continue;
    }

    $staff_pending_approvals[] = [
        'code' => (string) ($row['rental_code'] ?? '-'),
        'product_name' => (string) ($row['product_name'] ?? 'Peralatan'),
        'customer' => (string) ($row['fullname'] ?? 'Customer'),
        'time_ago' => staff_relative_time($row['created_at'] ?? ''),
    ];
}
$staff_pending_approvals = array_slice($staff_pending_approvals, 0, 3);

$current_month = date('Y-m');
$previous_month = date('Y-m', strtotime('first day of last month'));
$current_month_user_count = 0;
$previous_month_user_count = 0;
foreach ($all_user_rows as $row) {
    $created_month = !empty($row['created_at']) ? date('Y-m', strtotime((string) $row['created_at'])) : '';
    if ($created_month === $current_month) {
        $current_month_user_count++;
    }
    if ($created_month === $previous_month) {
        $previous_month_user_count++;
    }
}

$current_month_inventory_count = 0;
$previous_month_inventory_count = 0;
foreach ($all_products as $row) {
    $created_month = !empty($row['created_at']) ? date('Y-m', strtotime((string) $row['created_at'])) : '';
    if ($created_month === $current_month) {
        $current_month_inventory_count++;
    }
    if ($created_month === $previous_month) {
        $previous_month_inventory_count++;
    }
}

$current_month_transaction_count = 0;
$previous_month_transaction_count = 0;
$current_month_revenue = 0.0;
$previous_month_revenue = 0.0;
foreach ($all_borrowing_rows as $row) {
    $created_month = !empty($row['created_at']) ? date('Y-m', strtotime((string) $row['created_at'])) : '';
    $amount = (float) ($row['total_price'] ?? 0);
    if ($created_month === $current_month) {
        $current_month_transaction_count++;
        $current_month_revenue += $amount;
    }
    if ($created_month === $previous_month) {
        $previous_month_transaction_count++;
        $previous_month_revenue += $amount;
    }
}

$staff_report_summary = [
    'borrowings' => [
        'total' => number_format(count($all_borrowing_rows)),
        'change' => staff_change_meta($current_month_transaction_count, $previous_month_transaction_count),
    ],
    'returns' => [
        'total' => number_format(count($all_return_rows)),
        'change' => staff_change_meta($returns_today, $returns_yesterday),
    ],
    'revenue' => [
        'total' => format_currency($current_month_revenue),
        'change' => staff_change_meta($current_month_revenue, $previous_month_revenue),
    ],
    'inventory' => [
        'total' => number_format(count($all_products)),
        'change' => staff_change_meta($current_month_inventory_count, $previous_month_inventory_count),
    ],
];

$staff_report_tables = [
    'borrowings' => [],
    'returns' => [],
    'revenue' => [],
    'inventory' => [],
];

for ($i = 0; $i < count($revenue_labels); $i++) {
    $previous_value = $i > 0 ? (float) ($revenue_values[$i - 1] ?? 0) : 0;
    $change = staff_change_meta((float) ($revenue_values[$i] ?? 0), $previous_value);
    $staff_report_tables['revenue'][] = [
        'date' => $revenue_labels[$i],
        'metric' => 'Daily Revenue',
        'value' => format_currency((float) ($revenue_values[$i] ?? 0)),
        'change' => $change['text'],
        'positive' => strpos($change['text'], '-') !== 0,
    ];
}

foreach ($all_borrowing_rows as $row) {
    $staff_report_tables['borrowings'][] = [
        'date' => !empty($row['created_at']) ? date('Y-m-d', strtotime((string) $row['created_at'])) : date('Y-m-d'),
        'metric' => (string) ($row['product_name'] ?? 'Borrowing Request'),
        'value' => (string) ($row['rental_code'] ?? '-'),
        'change' => ucfirst((string) ($row['status'] ?? 'pending')),
        'positive' => in_array((string) ($row['status'] ?? ''), ['active', 'completed', 'pending', 'upcoming'], true),
    ];
}
$staff_report_tables['borrowings'] = array_slice($staff_report_tables['borrowings'], 0, 8);

foreach ($all_return_rows as $row) {
    $staff_report_tables['returns'][] = [
        'date' => (string) ($row['returnedAt'] ?? $row['createdAt'] ?? date('Y-m-d')),
        'metric' => (string) ($row['productName'] ?? 'Return'),
        'value' => (string) ($row['id'] ?? '-'),
        'change' => ucfirst((string) ($row['status'] ?? 'pending')),
        'positive' => true,
    ];
}
 $staff_report_tables['returns'] = array_slice($staff_report_tables['returns'], 0, 8);

for ($i = 0; $i < count($inventory_labels); $i++) {
    $staff_report_tables['inventory'][] = [
        'date' => date('Y-m-d'),
        'metric' => $inventory_labels[$i],
        'value' => (string) ((int) ($inventory_values[$i] ?? 0)),
        'change' => 'Live',
        'positive' => true,
    ];
}

$borrowings_json = json_encode($staff_borrowings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$returns_json = json_encode($staff_returns, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$revenue_json = json_encode(['labels' => $revenue_labels, 'values' => $revenue_values], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$user_growth_json = json_encode(['labels' => $return_labels, 'values' => $return_values], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$inventory_json = json_encode(['labels' => $inventory_labels, 'values' => $inventory_values], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$transaction_json = json_encode(['labels' => $transaction_labels, 'values' => $transaction_values], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$top_equipment_json = json_encode(['labels' => array_keys($top_counts), 'values' => array_values($top_counts)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$staff_report_summary_json = json_encode($staff_report_summary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$staff_report_tables_json = json_encode($staff_report_tables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$staff_default_report_rows = $staff_report_tables['borrowings'];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LensCraft - Dashboard Petugas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap"
      rel="stylesheet"
    />
    <style>
      :root {
        --accent-brass: #c7a65a;
        --accent-brass-soft: rgba(199, 166, 90, 0.18);
      }
      body {
        font-family: "Inter", sans-serif;
      }
      .font-serif {
        font-family: "Playfair Display", serif;
      }
      .hero-gradient {
        background: linear-gradient(
          135deg,
          #000000 0%,
          #0a0a0a 50%,
          #050505 100%
        );
      }
      .neutral-accent {
        color: #666;
      }
      .card-hover {
        transition:
          transform 0.3s ease,
          box-shadow 0.3s ease;
      }
      .card-hover:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(255, 255, 255, 0.08);
      }
      .nav-blur {
        background: rgba(5, 5, 5, 0.86);
        backdrop-filter: blur(18px);
      }
      .sidebar-blur {
        background: rgba(5, 5, 5, 0.94);
        backdrop-filter: blur(18px);
      }
      /* Animations */
      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(30px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      .animate-fade-in {
        opacity: 0;
        animation: fadeInUp 0.8s ease-out forwards;
      }
      .delay-100 {
        animation-delay: 0.1s !important;
      }
      .delay-200 {
        animation-delay: 0.2s !important;
      }
      .delay-300 {
        animation-delay: 0.3s !important;
      }
      .delay-400 {
        animation-delay: 0.4s !important;
      }
      .delay-500 {
        animation-delay: 0.5s !important;
      }

      /* Custom scrollbar for sidebar */
      .sidebar-scroll::-webkit-scrollbar {
        width: 6px;
      }
      .sidebar-scroll::-webkit-scrollbar-track {
        background: transparent;
      }
      .sidebar-scroll::-webkit-scrollbar-thumb {
        background-color: #333;
        border-radius: 3px;
      }
      .sidebar-scroll::-webkit-scrollbar-thumb:hover {
        background-color: #444;
      }

      /* Aktif nav item */
      .nav-item-active {
        background-color: var(--accent-brass-soft);
        border-left: 3px solid var(--accent-brass);
        color: #fff;
      }

      /* Table styles */
      .table-row-hover:hover {
        background-color: rgba(255, 255, 255, 0.03);
      }

      /* Status badges */
      .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        border: 1px solid;
      }
      .badge-success {
        background-color: rgba(34, 197, 94, 0.15);
        color: #4ade80;
        border-color: rgba(34, 197, 94, 0.3);
      }
      .badge-warning {
        background-color: rgba(251, 191, 36, 0.15);
        color: #fbbf24;
        border-color: rgba(251, 191, 36, 0.3);
      }
      .badge-danger {
        background-color: rgba(239, 68, 68, 0.15);
        color: #f87171;
        border-color: rgba(239, 68, 68, 0.3);
      }
      .modal-overlay {
        background:
          radial-gradient(circle at top, rgba(199, 166, 90, 0.14), transparent 28%),
          rgba(3, 3, 3, 0.86);
        backdrop-filter: blur(12px);
      }
      .modal-panel {
        background: linear-gradient(180deg, rgba(20, 20, 20, 0.98), rgba(9, 9, 9, 0.96));
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow:
          0 28px 90px rgba(0, 0, 0, 0.46),
          inset 0 1px 0 rgba(255, 255, 255, 0.05);
      }
      .modal-header {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0));
      }
      .modal-close {
        width: 2.5rem;
        height: 2.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.03);
      }
      .modal-close:hover {
        background: rgba(255, 255, 255, 0.08);
      }
      .modal-body-shell {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 1.1rem;
      }
      .modal-detail-list {
        display: grid;
        gap: 0.9rem;
      }
      .modal-detail-row {
        display: grid;
        grid-template-columns: minmax(0, 11rem) minmax(0, 1fr);
        gap: 1rem;
        align-items: start;
        padding-bottom: 0.9rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      }
      .modal-detail-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
      }
      .modal-detail-label {
        font-size: 0.73rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #737373;
      }
      .modal-detail-value {
        font-size: 0.95rem;
        color: #f5f5f5;
        line-height: 1.5;
        text-align: left;
      }
      .action-sheet {
        display: grid;
        gap: 1rem;
      }
      .action-sheet-icon {
        width: 4rem;
        height: 4rem;
        border-radius: 1.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: linear-gradient(180deg, rgba(199, 166, 90, 0.2), rgba(199, 166, 90, 0.08));
        color: #f1d08a;
      }
      .action-sheet-title {
        font-family: "Playfair Display", serif;
        font-size: 1.65rem;
        line-height: 1.1;
        color: #fff;
      }
      .action-sheet-copy {
        color: #b5b5b5;
        line-height: 1.65;
        font-size: 0.94rem;
      }
      .action-sheet-eyebrow {
        font-size: 0.72rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #8a8a8a;
      }
      .action-sheet-actions {
        display: flex;
        gap: 0.75rem;
        padding-top: 0.25rem;
      }
      .action-sheet-actions > * {
        flex: 1;
      }
      .detail-sheet {
        display: grid;
        gap: 1.25rem;
      }
      .detail-media-panel {
        position: relative;
      }
      .detail-media-frame {
        position: relative;
        overflow: hidden;
        border-radius: 1.35rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background:
          radial-gradient(circle at top left, rgba(199, 166, 90, 0.18), transparent 35%),
          linear-gradient(180deg, rgba(32, 32, 32, 0.96), rgba(13, 13, 13, 0.96));
        min-height: 22rem;
      }
      .detail-media-frame::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.14));
        pointer-events: none;
      }
      .detail-media-image {
        width: 100%;
        height: 100%;
        min-height: 22rem;
        object-fit: cover;
      }
      .detail-content {
        display: grid;
        gap: 1rem;
        align-content: start;
      }
      .detail-kicker {
        font-size: 0.72rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #a3a3a3;
      }
      .detail-title {
        font-family: "Playfair Display", serif;
        font-size: clamp(1.7rem, 2.8vw, 2.35rem);
        line-height: 1.05;
        color: #fff;
      }
      .detail-subtitle {
        color: #a3a3a3;
        font-size: 0.95rem;
        line-height: 1.55;
        max-width: 34rem;
      }
      .detail-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        padding-top: 0.2rem;
      }
      .detail-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.45rem 0.8rem;
        border-radius: 9999px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.03);
        color: #e5e5e5;
        font-size: 0.8rem;
      }
      @media (min-width: 900px) {
        .detail-sheet {
          grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.15fr);
          align-items: stretch;
        }
      }
      .badge-info {
        background-color: rgba(59, 130, 246, 0.15);
        color: #60a5fa;
        border-color: rgba(59, 130, 246, 0.3);
      }
    </style>
  </head>

  <body class="bg-neutral-950 text-neutral-100 min-h-screen">
    <!-- Top Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-50 nav-blur border-b border-neutral-800 h-16">
      <div class="flex items-center justify-between h-full px-6">
        <!-- Logo and Toggle -->
        <div class="flex items-center gap-4">
          <button id="sidebar-toggle" class="lg:hidden text-neutral-400 hover:text-white transition-colors p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <a href="index.php" class="text-2xl font-bold font-serif text-white tracking-tight">LensCraft</a>
          <span class="hidden md:inline-block text-sm text-neutral-500 border-l border-neutral-800 pl-4">Dashboard Petugas</span>
        </div>

        <!-- Right Side Actions -->
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-3 border-l border-neutral-800 pl-4">
            <div class="text-right hidden sm:block">
              <div class="text-sm font-medium text-white">Staff User</div>
              <div class="text-xs text-neutral-500">Staff Member</div>
            </div>
            <div class="w-10 h-10 bg-neutral-800 rounded-full flex items-center justify-center border border-neutral-700">
              <svg class="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <a href="../logout.php" class="text-sm text-neutral-400 hover:text-white transition-colors" title="Logout">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </a>
          </div>
        </div>
      </div>
    </nav>

    <!-- Sidebar Overlay (mobile) -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-16 bottom-0 w-64 sidebar-blur border-r border-neutral-800 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out overflow-y-auto sidebar-scroll">
      <div class="p-4 space-y-2">
        <!-- Navigation Items -->
        <a href="index.php" class="nav-item nav-item-active flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all hover:bg-white/5" data-section="overview">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          <span>Ringkasan</span>
        </a>

        <a href="borrowings.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white" data-section="approve-borrowings">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Menyetujui Peminjaman</span>
        </a>

        <a href="returns.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white" data-section="monitor-returns">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          <span>Memantau Pengembalian</span>
        </a>
        <a href="stock-price.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white" data-section="stock-price">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 .895-4 2s1.79 2 4 2 4 .895 4 2-1.79 2-4 2m0-10V6m0 12v2m8-8a8 8 0 11-16 0 8 8 0 0116 0z" />
          </svg>
          <span>Stock & Price</span>
        </a>
<a href="reports.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white" data-section="reports">
  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
  </svg>
  <span>Laporan & Analitik</span>
</a>
       <!-- Divider -->
       <div class="border-t border-neutral-800 my-6"></div>

        <!-- Akses Cepat -->
        <div class="px-4 py-2">
          <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider mb-3">Akses Cepat</p>
          <a href="index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-neutral-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            <span>Lihat Situs</span>
          </a>
          <a href="../products.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-neutral-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <span>Inventaris</span>
          </a>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="lg:ml-64 pt-16 min-h-screen">
      <div class="p-6 md:p-8">
        <!-- Content Sections -->
        <div id="content-area">

          <!-- Ringkasan Section -->
          <section id="overview" class="content-section animate-fade-in">
            <div class="mb-8">
              <h1 class="text-3xl md:text-4xl font-serif text-white mb-2">Dashboard Petugas</h1>
              <p class="text-neutral-400">Pantau ringkasan tugas petugas dan aktivitas harian dari satu tempat.</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              <!-- Card 1 -->
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                  <div class="w-12 h-12 bg-amber-900/30 rounded-xl flex items-center justify-center border border-amber-800/50">
                    <svg class="w-6 h-6 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </div>
                  <span class="text-xs font-medium px-2 py-1 rounded-full <?= e($staff_summary['pending_change']['class']) ?>"><?= e($staff_summary['pending_change']['text']) ?></span>
                </div>
                <div class="text-3xl font-bold text-white mb-1"><?= e((string) $staff_summary['pending_approvals']) ?></div>
                <div class="text-sm text-neutral-400">Persetujuan Menunggu</div>
              </div>

              <!-- Card 2 -->
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                  <div class="w-12 h-12 bg-green-900/30 rounded-xl flex items-center justify-center border border-green-800/50">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                  </div>
                  <span class="text-xs font-medium px-2 py-1 rounded-full <?= e($staff_summary['returns_change']['class']) ?>"><?= e($staff_summary['returns_change']['text']) ?></span>
                </div>
                <div class="text-3xl font-bold text-white mb-1"><?= e((string) $staff_summary['returns_today']) ?></div>
                <div class="text-sm text-neutral-400">Pengembalian Hari Ini</div>
              </div>

              <!-- Card 3 -->
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                  <div class="w-12 h-12 bg-purple-900/30 rounded-xl flex items-center justify-center border border-purple-800/50">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <span class="text-xs font-medium px-2 py-1 rounded-full <?= e($staff_summary['revenue_change']['class']) ?>"><?= e($staff_summary['revenue_change']['text']) ?></span>
                </div>
                <div class="text-3xl font-bold text-white mb-1"><?= e($staff_summary['revenue_today']) ?></div>
                <div class="text-sm text-neutral-400">Pendapatan Hari Ini</div>
              </div>

              <!-- Card 4 -->
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                  <div class="w-12 h-12 bg-orange-900/30 rounded-xl flex items-center justify-center border border-orange-800/50">
                    <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <span class="text-xs font-medium px-2 py-1 rounded-full <?= e($staff_summary['active_badge_class']) ?>"><?= e($staff_summary['active_badge_text']) ?></span>
                </div>
                <div class="text-3xl font-bold text-white mb-1"><?= e((string) $staff_summary['active_rentals']) ?></div>
                <div class="text-sm text-neutral-400">Rental Aktif</div>
              </div>
            </div>

            <!-- Quick Actions & Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <!-- Persetujuan Menunggu -->
              <div class="lg:col-span-2 bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                  <h2 class="text-xl font-semibold text-white">Persetujuan Menunggu</h2>
                  <a href="borrowings.php" class="text-sm text-neutral-400 hover:text-white transition-colors">Lihat Semua</a>
                </div>
                <div class="space-y-4" id="pending-approvals-list">
                  <?php if (empty($staff_pending_approvals)): ?>
                    <div class="py-6 text-sm text-neutral-500">Tidak ada permintaan yang menunggu persetujuan.</div>
                  <?php else: ?>
                    <?php foreach ($staff_pending_approvals as $approval): ?>
                      <div class="flex items-center justify-between py-3 border-b border-neutral-800">
                        <div class="flex items-center gap-3">
                          <div class="w-10 h-10 bg-neutral-800 rounded-lg flex items-center justify-center">
                            <span class="text-xs font-medium text-neutral-300"><?= e($approval['code']) ?></span>
                          </div>
                          <div>
                            <p class="text-sm font-medium text-white"><?= e($approval['product_name']) ?></p>
                            <p class="text-xs text-neutral-500"><?= e($approval['customer']) ?> • <?= e($approval['time_ago']) ?></p>
                          </div>
                        </div>
                        <div class="flex gap-2">
                          <button onclick="approveBorrowing('<?= e($approval['code']) ?>')" class="px-3 py-1 bg-green-900/30 border border-green-800/50 text-green-400 text-xs rounded-lg hover:bg-green-900/50 transition-colors">Setujui</button>
                          <button onclick="rejectBorrowing('<?= e($approval['code']) ?>')" class="px-3 py-1 bg-red-900/30 border border-red-800/50 text-red-400 text-xs rounded-lg hover:bg-red-900/50 transition-colors">Tolak</button>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Tugas Cepat -->
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                <h2 class="text-xl font-semibold text-white mb-6">Tugas Cepat</h2>
                <div class="space-y-3">
                  <button onclick="window.location.href='borrowings.php'" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-left text-sm font-medium text-neutral-300 hover:bg-neutral-700 hover:text-white transition-colors flex items-center gap-3">
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Setujui Peminjaman
                  </button>
                  <button onclick="window.location.href='returns.php'" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-left text-sm font-medium text-neutral-300 hover:bg-neutral-700 hover:text-white transition-colors flex items-center gap-3">
                    <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Pantau Pengembalian
                  </button>
                  <button onclick="window.location.href='reports.php'" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-left text-sm font-medium text-neutral-300 hover:bg-neutral-700 hover:text-white transition-colors flex items-center gap-3">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak Laporan
                  </button>
                </div>

                <div class="mt-8">
                  <h3 class="text-sm font-semibold text-white mb-4">Progres Hari Ini</h3>
                  <div class="space-y-4">
                    <div>
                      <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-neutral-400">Persetujuan Selesai</span>
                        <span class="text-xs font-medium text-white"><?= e((string) $staff_summary['approvals_done_count']) ?>/<?= e((string) $staff_summary['approvals_done_total']) ?></span>
                      </div>
                      <div class="w-full bg-neutral-800 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: <?= e((string) $staff_summary['approvals_done_percent']) ?>%"></div>
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-neutral-400">Pengembalian Selesai</span>
                        <span class="text-xs font-medium text-white"><?= e((string) $staff_summary['returns_done_count']) ?>/<?= e((string) $staff_summary['returns_done_total']) ?></span>
                      </div>
                      <div class="w-full bg-neutral-800 rounded-full h-2">
                        <div class="bg-amber-400 h-2 rounded-full" style="width: <?= e((string) $staff_summary['returns_done_percent']) ?>%"></div>
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-neutral-400">Rental Aktif</span>
                        <span class="text-xs font-medium text-white"><?= e((string) $staff_summary['active_count']) ?>/<?= e((string) $staff_summary['active_total']) ?></span>
                      </div>
                      <div class="w-full bg-neutral-800 rounded-full h-2">
                        <div class="bg-purple-500 h-2 rounded-full" style="width: <?= e((string) $staff_summary['active_percent']) ?>%"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Menyetujui Peminjaman Section -->
          <section id="approve-borrowings" class="content-section hidden">
            <div class="mb-8">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                  <h1 class="text-3xl md:text-4xl font-serif text-white mb-2">Menyetujui Peminjaman</h1>
                  <p class="text-neutral-400">Review and approve equipment borrowing requests.</p>
                </div>
                <div class="flex gap-3">
                  <button class="px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-sm font-medium text-neutral-300 hover:bg-neutral-700 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export
                  </button>
                  <button class="px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-sm font-medium text-neutral-300 hover:bg-neutral-700 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                  </button>
                </div>
              </div>
            </div>

            <!-- Filters & Search -->
            <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 mb-6">
              <div class="flex flex-col md:flex-row gap-4 md:items-center">
                <!-- Filter Dropdown -->
                <div class="relative md:flex-shrink-0">
                  <button id="approve-filter-btn" class="flex items-center justify-center w-10 h-10 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-400 hover:bg-neutral-700 hover:text-white transition-colors" aria-label="Toggle filters">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                  </button>
                  <div id="approve-filter-dropdown" class="absolute left-0 top-full mt-2 w-80 bg-neutral-900 border border-neutral-800 rounded-xl shadow-xl z-20 hidden p-4">
                    <div class="space-y-4">
                      <div>
                        <label class="block text-xs font-medium text-neutral-300 mb-2">Status</label>
                        <select id="approve-status-filter" class="w-full px-3 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-700">
                          <option value="">All Status</option>
                          <option value="pending">Menunggu</option>
                          <option value="approved">Setujuid</option>
                          <option value="rejected">Tolaked</option>
                        </select>
                      </div>
                      <div>
                        <label class="block text-xs font-medium text-neutral-300 mb-2">Date Range</label>
                        <select id="approve-date-filter" class="w-full px-3 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-700">
                          <option value="">All Time</option>
                          <option value="today">Today</option>
                          <option value="week">This Week</option>
                          <option value="month">This Month</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Search Bar -->
                <div class="flex-1 relative">
                  <input type="text" id="approve-search" placeholder="Search by request ID, customer name, or equipment..." class="w-full pl-4 pr-12 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-700 focus:border-neutral-600 transition-all" />
                  <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-white transition-colors" aria-label="Search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Borrowings Table -->
            <div class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden">
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead>
                    <tr class="border-b border-neutral-800 bg-neutral-800/30">
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Request ID</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Customer</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Equipment</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Borrowing Period</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Amount</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Status</th>
                      <th class="px-6 py-4 text-right text-xs font-semibold text-neutral-400 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="borrowings-table-body" class="divide-y divide-neutral-800">
                    <!-- Borrowings will be populated by JavaScript -->
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-6 border-t border-neutral-800">
                <div class="text-sm text-neutral-400">
                  Showing <span id="borrowings-shown" class="font-medium text-white">0</span> of <span id="borrowings-total" class="font-medium text-white">0</span> requests
                </div>
                <div class="flex items-center gap-2">
                  <button id="borrowings-prev" class="px-4 py-2 text-sm font-medium bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-300 hover:bg-neutral-700 transition-colors disabled:opacity-50" disabled>Sebelumnya</button>
                  <div id="borrowings-page-numbers" class="flex items-center gap-1"></div>
                  <button id="borrowings-next" class="px-4 py-2 text-sm font-medium bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-300 hover:bg-neutral-700 transition-colors">Berikutnya</button>
                </div>
              </div>
            </div>
          </section>

          <!-- Memantau Pengembalian Section -->
          <section id="monitor-returns" class="content-section hidden">
            <div class="mb-8">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                  <h1 class="text-3xl md:text-4xl font-serif text-white mb-2">Memantau Pengembalian</h1>
                  <p class="text-neutral-400">Track and manage equipment returns and return records.</p>
                </div>
                <div class="flex gap-3">
                  <button class="px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-sm font-medium text-neutral-300 hover:bg-neutral-700 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export
                  </button>
                  <button class="px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-sm font-medium text-neutral-300 hover:bg-neutral-700 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                  </button>
                </div>
              </div>
            </div>

            <!-- Filters & Search -->
            <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 mb-6">
              <div class="flex flex-col md:flex-row gap-4 md:items-center">
                <!-- Filter Dropdown -->
                <div class="relative md:flex-shrink-0">
                  <button id="returns-filter-btn" class="flex items-center justify-center w-10 h-10 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-400 hover:bg-neutral-700 hover:text-white transition-colors" aria-label="Toggle filters">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                  </button>
                  <div id="returns-filter-dropdown" class="absolute left-0 top-full mt-2 w-80 bg-neutral-900 border border-neutral-800 rounded-xl shadow-xl z-20 hidden p-4">
                    <div class="space-y-4">
                      <div>
                        <label class="block text-xs font-medium text-neutral-300 mb-2">Return Status</label>
                        <select id="returns-status-filter" class="w-full px-3 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-700">
                          <option value="">All Status</option>
                          <option value="pending">Menunggu Return</option>
                          <option value="returned">Returned</option>
                          <option value="overdue">Overdue</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Search Bar -->
                <div class="flex-1 relative">
                  <input type="text" id="returns-search" placeholder="Search by return ID, customer name, or equipment..." class="w-full pl-4 pr-12 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-700 focus:border-neutral-600 transition-all" />
                  <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-white transition-colors" aria-label="Search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Returns Table -->
            <div class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden">
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead>
                    <tr class="border-b border-neutral-800 bg-neutral-800/30">
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Return ID</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Customer</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Equipment</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Return Date</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Status</th>
                      <th class="px-6 py-4 text-right text-xs font-semibold text-neutral-400 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="returns-table-body" class="divide-y divide-neutral-800">
                    <!-- Returns will be populated by JavaScript -->
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-6 border-t border-neutral-800">
                <div class="text-sm text-neutral-400">
                  Showing <span id="returns-shown" class="font-medium text-white">0</span> of <span id="returns-total" class="font-medium text-white">0</span> returns
                </div>
                <div class="flex items-center gap-2">
                  <button id="returns-prev" class="px-4 py-2 text-sm font-medium bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-300 hover:bg-neutral-700 transition-colors disabled:opacity-50" disabled>Sebelumnya</button>
                  <div id="returns-page-numbers" class="flex items-center gap-1"></div>
                  <button id="returns-next" class="px-4 py-2 text-sm font-medium bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-300 hover:bg-neutral-700 transition-colors">Berikutnya</button>
                </div>
              </div>
            </div>
          </section>

          <!-- Reports Section -->
          <section id="reports" class="content-section hidden">
            <div class="mb-8">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                  <h1 class="text-3xl md:text-4xl font-serif text-white mb-2">Laporan & Analitik</h1>
                  <p class="text-neutral-400">Generate insights and export data for decision making.</p>
                </div>
                <div class="flex gap-3">
                  <button onclick="exportReport('csv')" class="px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-sm font-medium text-neutral-300 hover:bg-neutral-700 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export CSV
                  </button>
                  <button onclick="exportReport('pdf')" class="px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-sm font-medium text-neutral-300 hover:bg-neutral-700 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export PDF
                  </button>
                </div>
              </div>
            </div>

            <!-- Report Controls -->
            <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 mb-6">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Report Type -->
                <div>
                  <label class="block text-sm font-medium text-neutral-400 mb-2">Report Type</label>
                  <select id="report-type" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-700">
                    <option value="borrowings">Borrowing Requests</option>
                    <option value="returns">Equipment Returns</option>
                    <option value="revenue">Revenue Summary</option>
                    <option value="inventory">Inventory Status</option>
                  </select>
                </div>
                <!-- Date Range -->
                <div>
                  <label class="block text-sm font-medium text-neutral-400 mb-2">Date Range</label>
                  <select id="report-date-range" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-700">
                    <option value="7">Last 7 days</option>
                    <option value="30" selected>Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="365">Last year</option>
                    <option value="custom">Custom range</option>
                  </select>
                </div>
                <!-- Custom Date Range (hidden by default) -->
                <div id="custom-date-range" class="hidden flex items-end gap-2">
                  <div class="flex-1">
                    <label class="block text-sm font-medium text-neutral-400 mb-2">From</label>
                    <input type="date" id="custom-date-from" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-700" />
                  </div>
                  <div class="flex-1">
                    <label class="block text-sm font-medium text-neutral-400 mb-2">To</label>
                    <input type="date" id="custom-date-to" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-700" />
                  </div>
                </div>
              </div>
              <div class="mt-4 flex justify-end">
                <button onclick="generateReport()" class="px-6 py-2 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-all transform hover:scale-105">
                  Generate Report
                </button>
              </div>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                <div class="text-sm text-neutral-400 mb-2">Total Requests</div>
                <div class="text-2xl font-bold text-white" id="report-total-transactions"><?= e($staff_report_summary['borrowings']['total']) ?></div>
                <div class="text-xs <?= strpos($staff_report_summary['borrowings']['change']['class'], 'text-red-400') !== false ? 'text-red-400' : (strpos($staff_report_summary['borrowings']['change']['class'], 'text-neutral-400') !== false ? 'text-neutral-400' : 'text-green-400') ?> mt-2"><?= e($staff_report_summary['borrowings']['change']['text']) ?> from last period</div>
              </div>
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                <div class="text-sm text-neutral-400 mb-2">Total Revenue</div>
                <div class="text-2xl font-bold text-white" id="report-total-revenue"><?= e($staff_report_summary['revenue']['total']) ?></div>
                <div class="text-xs <?= strpos($staff_report_summary['revenue']['change']['class'], 'text-red-400') !== false ? 'text-red-400' : (strpos($staff_report_summary['revenue']['change']['class'], 'text-neutral-400') !== false ? 'text-neutral-400' : 'text-green-400') ?> mt-2"><?= e($staff_report_summary['revenue']['change']['text']) ?> from last period</div>
              </div>
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                <div class="text-sm text-neutral-400 mb-2">Approval Rate</div>
                <div class="text-2xl font-bold text-white" id="report-approval-rate"><?= e((string) $staff_summary['approval_rate']) ?>%</div>
                <div class="text-xs text-neutral-400 mt-2">Based on all borrowing requests</div>
              </div>
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                <div class="text-sm text-neutral-400 mb-2">Return Rate</div>
                <div class="text-2xl font-bold text-white" id="report-return-rate"><?= e((string) $staff_summary['return_rate']) ?>%</div>
                <div class="text-xs text-neutral-400 mt-2">Compared to total borrowings</div>
              </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
              <!-- Main Chart -->
              <div class="lg:col-span-2 bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Revenue Trend</h3>
                <div class="h-80">
                  <canvas id="mainChart"></canvas>
                </div>
              </div>

              <!-- Pie Chart -->
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Category Distribution</h3>
                <div class="h-64">
                  <canvas id="categoryChart"></canvas>
                </div>
              </div>

              <!-- Bar Chart -->
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Top Equipment</h3>
                <div class="h-64">
                  <canvas id="topEquipmentChart"></canvas>
                </div>
              </div>
            </div>

            <!-- Data Table -->
            <div class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden">
              <div class="p-6 border-b border-neutral-800">
                <h3 class="text-lg font-semibold text-white">Report Data</h3>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead>
                    <tr class="border-b border-neutral-800 bg-neutral-800/30">
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Date</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Metric</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Value</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Change</th>
                    </tr>
                  </thead>
                  <tbody id="report-table-body" class="divide-y divide-neutral-800">
                    <?php foreach ($staff_default_report_rows as $row): ?>
                      <tr class="table-row-hover transition-colors">
                        <td class="px-6 py-4 text-sm text-neutral-300"><?= e($row['date']) ?></td>
                        <td class="px-6 py-4 text-sm text-neutral-300"><?= e($row['metric']) ?></td>
                        <td class="px-6 py-4 text-sm font-semibold text-white"><?= e($row['value']) ?></td>
                        <td class="px-6 py-4">
                          <span class="text-sm <?= !empty($row['positive']) ? 'text-green-400' : 'text-red-400' ?>">
                            <?= e($row['change']) ?>
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </section>

        </div>
      </div>
    </main>

            <div id="staff-detail-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-200">
      <div class="absolute inset-0 bg-neutral-950/80 backdrop-blur-sm" onclick="closeStaffDetailModal()"></div>
      <div class="relative bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl max-w-2xl w-full mx-4 overflow-hidden transform scale-95 opacity-0 transition-all duration-200" id="staff-detail-modal-content">
        <div class="p-8">
          <div class="flex items-start justify-between mb-6">
            <div>
              <h3 class="text-2xl font-serif text-white mb-1" id="staff-detail-modal-title">Detail</h3>
              <p class="text-sm text-neutral-400" id="staff-detail-modal-subtitle"></p>
            </div>
            <button onclick="closeStaffDetailModal()" class="text-neutral-400 hover:text-white transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div id="staff-detail-modal-body"></div>
          <div class="mt-6 pt-4 border-t border-neutral-800 flex justify-end gap-3">
            <button onclick="closeStaffDetailModal()" class="px-6 py-2.5 bg-neutral-800 border border-neutral-700 hover:bg-neutral-700 text-white text-sm font-medium rounded-xl transition-colors">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>



    <div id="staff-action-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 modal-overlay">
      <div class="modal-panel rounded-3xl w-full max-w-md">
        <div class="modal-header p-6 border-b border-neutral-800 flex items-center justify-between">
          <h3 class="text-xl font-semibold text-white" id="staff-action-modal-title">Konfirmasi</h3>
          <button type="button" onclick="closeStaffActionModal()" class="modal-close text-neutral-400 hover:text-white transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-6 modal-body-shell mx-6 mt-6">
          <div class="action-sheet">
            <div class="action-sheet-icon" id="staff-action-modal-icon">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="space-y-2">
              <div class="action-sheet-eyebrow">Please confirm</div>
              <p class="action-sheet-copy" id="staff-action-modal-message"></p>
            </div>
          </div>
        </div>
        <div class="px-6 pb-6 action-sheet-actions">
          <button type="button" onclick="closeStaffActionModal()" class="flex-1 px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-300 hover:bg-neutral-700 transition-colors">Batal</button>
          <button type="button" id="staff-action-confirm-btn" class="flex-1 px-4 py-3 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-colors">Lanjutkan</button>
        </div>
      </div>
    </div>

    <script>
      // Sidebar toggle for mobile
      const sidebar = document.getElementById('sidebar');
      const sidebarToggle = document.getElementById('sidebar-toggle');
      const sidebarOverlay = document.getElementById('sidebar-overlay');

      function toggleSidebar() {
        const isClosed = sidebar.classList.contains('-translate-x-full');
        if (isClosed) {
          sidebar.classList.remove('-translate-x-full');
          sidebarOverlay.classList.remove('hidden');
        } else {
          sidebar.classList.add('-translate-x-full');
          sidebarOverlay.classList.add('hidden');
        }
      }

      sidebarToggle.addEventListener('click', toggleSidebar);
      sidebarOverlay.addEventListener('click', toggleSidebar);

      // Navigation handling
      const navItems = document.querySelectorAll('.legacy-tab-nav');
      const sections = document.querySelectorAll('.content-section');

      function showSection(sectionId) {
        // Update nav items
        navItems.forEach(item => {
          if (item.dataset.section === sectionId) {
            item.classList.add('nav-item-active');
            item.classList.remove('text-neutral-400', 'hover:text-white');
          } else {
            item.classList.remove('nav-item-active');
            item.classList.add('text-neutral-400', 'hover:text-white');
          }
        });

        // Show selected section, hide others
        sections.forEach(section => {
          if (section.id === sectionId) {
            section.classList.remove('hidden');
            // Re-trigger animation
            section.style.opacity = '0';
            section.style.transform = 'translateY(30px)';
            setTimeout(() => {
              section.style.opacity = '1';
              section.style.transform = 'translateY(0)';
            }, 50);
          } else {
            section.classList.add('hidden');
          }
        });
// Render tables when sections are shown
if (sectionId === 'approve-borrowings') {
  renderBorrowingsTable();
} else if (sectionId === 'monitor-returns') {
  renderReturnsTable();
} else if (sectionId === 'reports') {
  // Initialize charts with default data
  generateReport();
}

// Close mobile sidebar after selection
        if (window.innerWidth < 1024 && !sidebar.classList.contains('-translate-x-full')) {
          toggleSidebar();
        }
      }

      // Add click listeners to nav items
      navItems.forEach(item => {
        item.addEventListener('click', (e) => {
          e.preventDefault();
          const sectionId = item.dataset.section;
          showSection(sectionId);

          // Update URL hash without scrolling
          history.pushState(null, null, `#${sectionId}`);
        });
      });

      // Handle initial hash or default to overview
      function initializeSection() {
        const hash = window.location.hash.substring(1);
        if (hash && document.getElementById(hash)) {
          showSection(hash);
        } else {
          showSection('overview');
        }
      }

      // Handle browser back/forward
      window.addEventListener('popstate', initializeSection);

      // Initialize on load
      initializeSection();

      // ============================================
      // SAMPLE DATA
      // ============================================

      // Sample borrowings data
      const borrowings = <?= $borrowings_json ?>;

      let filteredBorrowings = [...borrowings];
      let borrowingsCurrentPage = 1;
      const borrowingsPerPage = 5;

      // Sample returns data
      const returns = <?= $returns_json ?>;

      let filteredReturns = [...returns];
      let returnsCurrentPage = 1;
      const returnsPerPage = 5;

      // Helper functions
      function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      }

      function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
      }

      function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
      }

      function getStatusBadgeClass(status) {
        switch(status) {
          case 'pending': return 'badge-warning';
          case 'approved': return 'badge-success';
          case 'rejected': return 'badge-danger';
          case 'returned': return 'badge-success';
          case 'overdue': return 'badge-danger';
          default: return 'badge-info';
        }
      }

      // ============================================
      // BORROWINGS TABLE
      // ============================================

      function renderBorrowingsTable() {
        const tbody = document.getElementById('borrowings-table-body');
        if (!tbody) return;

        const start = (borrowingsCurrentPage - 1) * borrowingsPerPage;
        const end = start + borrowingsPerPage;
        const pageBorrowings = filteredBorrowings.slice(start, end);

        tbody.innerHTML = pageBorrowings.map(borrowing => `
          <tr class="table-row-hover transition-colors">
            <td class="px-6 py-4">
              <span class="text-sm font-medium text-white">${escapeHtml(borrowing.id)}</span>
            </td>
            <td class="px-6 py-4">
              <div class="space-y-1">
                <p class="text-sm font-medium text-white">${escapeHtml(borrowing.customer)}</p>
                <p class="text-xs text-neutral-500">Borrower</p>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-neutral-300">${escapeHtml(borrowing.equipment)}</td>
            <td class="px-6 py-4 text-sm text-neutral-400">${formatDate(borrowing.startDate)} - ${formatDate(borrowing.endDate)}</td>
            <td class="px-6 py-4 text-sm font-semibold text-white">$${borrowing.amount}</td>
            <td class="px-6 py-4">
              <span class="badge ${getStatusBadgeClass(borrowing.status)}">${capitalizeFirst(borrowing.status)}</span>
            </td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-2">
                ${borrowing.status === 'pending' ? `
                  <button onclick="approveBorrowing('${borrowing.id}')" class="p-2 text-neutral-400 hover:text-green-400 hover:bg-neutral-800 rounded transition-colors" title="Setujui">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>
                  <button onclick="rejectBorrowing('${borrowing.id}')" class="p-2 text-neutral-400 hover:text-red-400 hover:bg-neutral-800 rounded transition-colors" title="Tolak">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                ` : `
                  <button onclick="viewBorrowing('${borrowing.id}')" class="p-2 text-neutral-400 hover:text-white hover:bg-neutral-800 rounded transition-colors" title="View">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                `}
              </div>
            </td>
          </tr>
        `).join('');

        // Update pagination info
        document.getElementById('borrowings-shown').textContent = Math.min(filteredBorrowings.length, end - start);
        document.getElementById('borrowings-total').textContent = filteredBorrowings.length;

        // Render pagination buttons
        renderPagination('borrowings', filteredBorrowings.length, borrowingsPerPage, borrowingsCurrentPage);
      }

      function approveBorrowing(id) {
        const borrowing = borrowings.find(b => b.id === id);
        if (borrowing) {
          borrowing.status = 'approved';
          filterBorrowings();
          renderBorrowingsTable();
        }
      }

      function rejectBorrowing(id) {
        const borrowing = borrowings.find(b => b.id === id);
        if (borrowing) {
          borrowing.status = 'rejected';
          filterBorrowings();
          renderBorrowingsTable();
        }
      }

      // ============================================
      // RETURNS TABLE
      // ============================================

      function renderReturnsTable() {
        const tbody = document.getElementById('returns-table-body');
        if (!tbody) return;

        const start = (returnsCurrentPage - 1) * returnsPerPage;
        const end = start + returnsPerPage;
        const pageReturns = filteredReturns.slice(start, end);

        tbody.innerHTML = pageReturns.map(returnItem => `
          <tr class="table-row-hover transition-colors">
            <td class="px-6 py-4">
              <span class="text-sm font-medium text-white">${escapeHtml(returnItem.id)}</span>
            </td>
            <td class="px-6 py-4">
              <div class="space-y-1">
                <p class="text-sm font-medium text-white">${escapeHtml(returnItem.customer)}</p>
                <p class="text-xs text-neutral-500">Customer</p>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-neutral-300">${escapeHtml(returnItem.equipment)}</td>
            <td class="px-6 py-4 text-sm text-neutral-400">${formatDate(returnItem.returnDate)}</td>
            <td class="px-6 py-4">
              <span class="badge ${getStatusBadgeClass(returnItem.status)}">${capitalizeFirst(returnItem.status)}</span>
            </td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-2">
                ${returnItem.status === 'pending' ? `
                  <button onclick="markReturned('${returnItem.id}')" class="p-2 text-neutral-400 hover:text-green-400 hover:bg-neutral-800 rounded transition-colors" title="Mark as Returned">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>
                ` : `
                  <button onclick="viewReturn('${returnItem.id}')" class="p-2 text-neutral-400 hover:text-white hover:bg-neutral-800 rounded transition-colors" title="View Details">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                `}
              </div>
            </td>
          </tr>
        `).join('');

        // Update pagination info
        document.getElementById('returns-shown').textContent = Math.min(filteredReturns.length, end - start);
        document.getElementById('returns-total').textContent = filteredReturns.length;

        // Render pagination buttons
        renderPagination('returns', filteredReturns.length, returnsPerPage, returnsCurrentPage);
      }

      function markReturned(id) {
        const returnItem = returns.find(r => r.id === id);
        if (returnItem) {
          returnItem.status = 'returned';
          filterReturns();
          renderReturnsTable();
        }
      }
      // ============================================
      // PAGINATION
      // ============================================

      function renderPagination(type, totalItems, itemsPerPage, currentPage) {
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        const pageNumbersContainer = document.getElementById(`${type}-page-numbers`);
        const prevBtn = document.getElementById(`${type}-prev`);
        const nextBtn = document.getElementById(`${type}-next`);

        if (!pageNumbersContainer) return;

        pageNumbersContainer.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
          const btn = document.createElement('button');
          btn.textContent = i;
          btn.className = `px-3 py-1 text-sm font-medium rounded-lg transition-colors ${
            i === currentPage
              ? 'bg-white text-black'
              : 'bg-neutral-800 border border-neutral-700 text-neutral-300 hover:bg-neutral-700'
          }`;
          btn.addEventListener('click', () => {
            if (type === 'borrowings') {
              borrowingsCurrentPage = i;
              renderBorrowingsTable();
            } else if (type === 'returns') {
              returnsCurrentPage = i;
              renderReturnsTable();
            }
          });
          pageNumbersContainer.appendChild(btn);
        }

        if (prevBtn) {
          prevBtn.disabled = currentPage === 1;
          prevBtn.onclick = () => {
            if (currentPage > 1) {
              if (type === 'borrowings') {
                borrowingsCurrentPage--;
                renderBorrowingsTable();
              } else if (type === 'returns') {
                returnsCurrentPage--;
                renderReturnsTable();
              }
            }
          };
        }

        if (nextBtn) {
          nextBtn.disabled = currentPage === totalPages;
          nextBtn.onclick = () => {
            if (currentPage < totalPages) {
              if (type === 'borrowings') {
                borrowingsCurrentPage++;
                renderBorrowingsTable();
              } else if (type === 'returns') {
                returnsCurrentPage++;
                renderReturnsTable();
              }
            }
          };
        }
      }

      // ============================================
      // FILTER & SEARCH
      // ============================================

      function filterBorrowings() {
        const statusFilter = document.getElementById('approve-status-filter')?.value || '';
        const dateFilter = document.getElementById('approve-date-filter')?.value || '';
        const searchFilter = document.getElementById('approve-search')?.value.toLowerCase() || '';

        filteredBorrowings = borrowings.filter(borrowing => {
          let match = true;

          if (statusFilter && borrowing.status !== statusFilter) match = false;

          if (dateFilter) {
            const borrowingDate = new Date(borrowing.startDate);
            const now = new Date();
            if (dateFilter === 'today') {
              match = match && borrowingDate.toDateString() === now.toDateString();
            } else if (dateFilter === 'week') {
              const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
              match = match && borrowingDate >= weekAgo;
            } else if (dateFilter === 'month') {
              match = match && borrowingDate.getMonth() === now.getMonth() && borrowingDate.getFullYear() === now.getFullYear();
            }
          }

          if (searchFilter) {
            const searchMatch = borrowing.id.toLowerCase().includes(searchFilter) ||
                              borrowing.customer.toLowerCase().includes(searchFilter) ||
                              borrowing.equipment.toLowerCase().includes(searchFilter);
            match = match && searchMatch;
          }

          return match;
        });

        borrowingsCurrentPage = 1;
        renderBorrowingsTable();
      }

      function filterReturns() {
        const statusFilter = document.getElementById('returns-status-filter')?.value || '';
        const searchFilter = document.getElementById('returns-search')?.value.toLowerCase() || '';

        filteredReturns = returns.filter(returnItem => {
          let match = true;

          if (statusFilter && returnItem.status !== statusFilter) match = false;

          if (searchFilter) {
            const searchMatch = returnItem.id.toLowerCase().includes(searchFilter) ||
                              returnItem.customer.toLowerCase().includes(searchFilter) ||
                              returnItem.equipment.toLowerCase().includes(searchFilter);
            match = match && searchMatch;
          }

          return match;
        });

        returnsCurrentPage = 1;
        renderReturnsTable();
      }

      // Add event listeners for filters
      document.addEventListener('DOMContentLoaded', () => {
        // Borrowings filters
        const approveStatusFilter = document.getElementById('approve-status-filter');
        const approveDateFilter = document.getElementById('approve-date-filter');
        const approveSearch = document.getElementById('approve-search');
        const approveFilterBtn = document.getElementById('approve-filter-btn');
        const approveFilterDropdown = document.getElementById('approve-filter-dropdown');

        if (approveStatusFilter) approveStatusFilter.addEventListener('change', filterBorrowings);
        if (approveDateFilter) approveDateFilter.addEventListener('change', filterBorrowings);
        if (approveSearch) approveSearch.addEventListener('input', filterBorrowings);

        if (approveFilterBtn && approveFilterDropdown) {
          approveFilterBtn.addEventListener('click', () => {
            approveFilterDropdown.classList.toggle('hidden');
          });
        }

        // Returns filters
        const returnsStatusFilter = document.getElementById('returns-status-filter');
        const returnsSearch = document.getElementById('returns-search');
        const returnsFilterBtn = document.getElementById('returns-filter-btn');
        const returnsFilterDropdown = document.getElementById('returns-filter-dropdown');

        if (returnsStatusFilter) returnsStatusFilter.addEventListener('change', filterReturns);
        if (returnsSearch) returnsSearch.addEventListener('input', filterReturns);

        if (returnsFilterBtn && returnsFilterDropdown) {
          returnsFilterBtn.addEventListener('click', () => {
            returnsFilterDropdown.classList.toggle('hidden');
          });
        }

        // Close filter dropdowns when clicking outside
        document.addEventListener('click', (e) => {
          if (approveFilterDropdown && !approveFilterBtn.contains(e.target) && !approveFilterDropdown.contains(e.target)) {
            approveFilterDropdown.classList.add('hidden');
          }
          if (returnsFilterDropdown && !returnsFilterBtn.contains(e.target) && !returnsFilterDropdown.contains(e.target)) {
            returnsFilterDropdown.classList.add('hidden');
          }
        });
      });

      // ============================================
      // REPORTS & ANALYTICS
      // ============================================

      let mainChart = null;
      let categoryChart = null;
      let topEquipmentChart = null;

      // Sample report data (30 days)
      const revenueData = <?= $revenue_json ?>;

      const userGrowthData = <?= $user_growth_json ?>;

      const inventoryData = <?= $inventory_json ?>;

      const transactionVolumeData = <?= $transaction_json ?>;

      const topEquipmentData = <?= $top_equipment_json ?>;

      const reportSummaryData = <?= $staff_report_summary_json ?>;

      const reportTableData = <?= $staff_report_tables_json ?>;

      // Generate report based on type and date range
      function generateReport() {
        const reportType = document.getElementById('report-type').value;
        const dateRange = document.getElementById('report-date-range').value;
        
        // Update summary stats based on report type
        updateSummaryStats(reportType);
        
        // Update charts
        updateCharts(reportType);
        
        // Update data table
        updateReportTable(reportType);
        
        // Show success notification (could be enhanced with toast)
        console.log(`Report generated: ${reportType}, Date range: ${dateRange}`);
      }

      // Update summary statistics
      function updateSummaryStats(reportType) {
        const selectedStats = reportSummaryData[reportType] || reportSummaryData.borrowings;
        const changeClass = selectedStats.change.class.includes('text-red-400') ? 'text-red-400' : (selectedStats.change.class.includes('text-neutral-400') ? 'text-neutral-400' : 'text-green-400');
        const totalTransactionsLabel = document.getElementById('report-total-transactions')?.previousElementSibling;

        // Update based on report type
        if (reportType === 'revenue') {
          if (totalTransactionsLabel) totalTransactionsLabel.textContent = 'Total Requests';
          document.getElementById('report-total-revenue').textContent = selectedStats.total;
          document.getElementById('report-total-revenue').nextElementSibling.textContent = selectedStats.change.text;
          document.getElementById('report-total-revenue').nextElementSibling.className = `text-xs ${changeClass} mt-2`;
        } else if (reportType === 'borrowings') {
          if (totalTransactionsLabel) totalTransactionsLabel.textContent = 'Total Requests';
          document.getElementById('report-total-transactions').textContent = selectedStats.total;
          document.getElementById('report-total-transactions').nextElementSibling.textContent = selectedStats.change.text;
          document.getElementById('report-total-transactions').nextElementSibling.className = `text-xs ${changeClass} mt-2`;
        } else if (reportType === 'returns') {
          if (totalTransactionsLabel) totalTransactionsLabel.textContent = 'Total Returns';
          document.getElementById('report-total-transactions').textContent = selectedStats.total;
          document.getElementById('report-total-transactions').nextElementSibling.textContent = selectedStats.change.text;
          document.getElementById('report-total-transactions').nextElementSibling.className = `text-xs ${changeClass} mt-2`;
        } else if (reportType === 'inventory') {
          if (totalTransactionsLabel) totalTransactionsLabel.textContent = 'Inventory Items';
          document.getElementById('report-total-transactions').textContent = selectedStats.total;
          document.getElementById('report-total-transactions').nextElementSibling.textContent = selectedStats.change.text;
          document.getElementById('report-total-transactions').nextElementSibling.className = `text-xs ${changeClass} mt-2`;
        }

        document.getElementById('report-approval-rate').textContent = '<?= (int) $staff_summary['approval_rate'] ?>%';
        document.getElementById('report-return-rate').textContent = '<?= (int) $staff_summary['return_rate'] ?>%';
      }

      // Update charts based on report type
      function updateCharts(reportType) {
        // Destroy existing charts if they exist
        if (mainChart) mainChart.destroy();
        if (categoryChart) categoryChart.destroy();
        if (topEquipmentChart) topEquipmentChart.destroy();

        // Main chart (line chart)
        const mainCtx = document.getElementById('mainChart');
        if (mainCtx) {
          let mainData, mainLabel;

          switch(reportType) {
            case 'revenue':
              mainData = revenueData.values;
              mainLabel = 'Revenue ($)';
              break;
            case 'returns':
              mainData = userGrowthData.values;
              mainLabel = 'Returns';
              break;
            case 'borrowings':
              mainData = transactionVolumeData.values;
              mainLabel = 'Requests';
              break;
            case 'inventory':
              mainData = inventoryData.values;
              mainLabel = 'Items';
              break;
            default:
              mainData = transactionVolumeData.values;
              mainLabel = 'Requests';
          }

          mainChart = new Chart(mainCtx, {
            type: 'line',
            data: {
              labels: reportType === 'inventory'
                ? inventoryData.labels
                : (reportType === 'returns'
                    ? userGrowthData.labels
                    : (reportType === 'borrowings' ? transactionVolumeData.labels : revenueData.labels)),
              datasets: [{
                label: mainLabel,
                data: mainData,
                borderColor: '#ffffff',
                backgroundColor: 'rgba(255, 255, 255, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#000000',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  display: false
                }
              },
              scales: {
                x: {
                  grid: {
                    color: 'rgba(255, 255, 255, 0.1)'
                  },
                  ticks: {
                    color: '#9ca3af'
                  }
                },
                y: {
                  grid: {
                    color: 'rgba(255, 255, 255, 0.1)'
                  },
                  ticks: {
                    color: '#9ca3af'
                  }
                }
              }
            }
          });
        }

        // Category distribution chart (doughnut)
        const categoryCtx = document.getElementById('categoryChart');
        if (categoryCtx) {
          categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
              labels: inventoryData.labels,
              datasets: [{
                data: inventoryData.values,
                backgroundColor: [
                  'rgba(59, 130, 246, 0.8)',   // blue
                  'rgba(34, 197, 94, 0.8)',    // green
                  'rgba(168, 85, 247, 0.8)',   // purple
                  'rgba(251, 191, 36, 0.8)',   // yellow
                  'rgba(239, 68, 68, 0.8)',    // red
                  'rgba(99, 102, 241, 0.8)',   // indigo
                  'rgba(249, 115, 22, 0.8)',   // orange
                  'rgba(20, 184, 166, 0.8)'    // teal
                ],
                borderColor: '#000000',
                borderWidth: 2
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'right',
                  labels: {
                    color: '#9ca3af',
                    padding: 15,
                    font: {
                      size: 11
                    }
                  }
                }
              }
            }
          });
        }

        // Top equipment chart (horizontal bar)
        const topEquipmentCtx = document.getElementById('topEquipmentChart');
        if (topEquipmentCtx) {
          topEquipmentChart = new Chart(topEquipmentCtx, {
            type: 'bar',
            data: {
              labels: topEquipmentData.labels,
              datasets: [{
                label: 'Rentals',
                data: topEquipmentData.values,
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              indexAxis: 'y',
              plugins: {
                legend: {
                  display: false
                }
              },
              scales: {
                x: {
                  grid: {
                    color: 'rgba(255, 255, 255, 0.1)'
                  },
                  ticks: {
                    color: '#9ca3af'
                  }
                },
                y: {
                  grid: {
                    display: false
                  },
                  ticks: {
                    color: '#9ca3af'
                  }
                }
              }
            }
          });
        }
      }

      // Update report data table
      function updateReportTable(reportType) {
        const tbody = document.getElementById('report-table-body');
        if (!tbody) return;
        
        const tableData = reportTableData[reportType] || reportTableData.revenue || [];

        tbody.innerHTML = tableData.map(row => `
          <tr class="table-row-hover transition-colors">
            <td class="px-6 py-4 text-sm text-neutral-300">${row.date}</td>
            <td class="px-6 py-4 text-sm text-neutral-300">${row.metric}</td>
            <td class="px-6 py-4 text-sm font-semibold text-white">${row.value}</td>
            <td class="px-6 py-4">
              <span class="text-sm ${row.positive ? 'text-green-400' : 'text-red-400'}">
                ${row.change}
              </span>
            </td>
          </tr>
        `).join('');
      }

      // Export report functionality
      function exportReport(format) {
        const reportType = document.getElementById('report-type').value;
        const dateRange = document.getElementById('report-date-range').value || 'all';

        if (format === 'csv') {
          const rows = [
            ['Report Type', reportType],
            ['Date Range', dateRange],
            [],
            ['Borrowing ID', 'Customer', 'Equipment', 'Status', 'Start Date', 'End Date'],
            ...borrowings.map(item => [item.id, item.customer, item.equipment, item.status, item.startDate, item.endDate]),
            [],
            ['Return ID', 'Customer', 'Equipment', 'Status', 'Return Date'],
            ...returns.map(item => [item.id, item.customer, item.equipment, item.status, item.returnDate])
          ];
          const csvContent = 'data:text/csv;charset=utf-8,' + rows.map(row => row.map(value => `"${String(value ?? '').replace(/"/g, '""')}"`).join(',')).join('\n');
          const encodedUri = encodeURI(csvContent);
          const link = document.createElement("a");
          link.setAttribute("href", encodedUri);
          link.setAttribute("download", `report-${reportType}-${new Date().toISOString().split('T')[0]}.csv`);
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          return;
        }

        const printWindow = window.open('', '_blank', 'width=960,height=720');
        if (!printWindow) return;
        printWindow.document.write(`
          <html>
            <head><title>LensCraft Staff Report</title></head>
            <body style="font-family: Inter, Arial, sans-serif; padding: 24px;">
              <h1>LensCraft Staff Report</h1>
              <p><strong>Type:</strong> ${reportType}</p>
              <p><strong>Date Range:</strong> ${dateRange}</p>
              <h2>Borrowings</h2>
              <pre>${borrowings.map(item => `${item.id} | ${item.customer} | ${item.equipment} | ${item.status}`).join('\n')}</pre>
              <h2>Returns</h2>
              <pre>${returns.map(item => `${item.id} | ${item.customer} | ${item.equipment} | ${item.status}`).join('\n')}</pre>
            </body>
          </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
      }

      // Toggle custom date range visibility
      document.getElementById('report-date-range').addEventListener('change', function() {
        const customRange = document.getElementById('custom-date-range');
        if (this.value === 'custom') {
          customRange.classList.remove('hidden');
          customRange.classList.add('flex');
        } else {
          customRange.classList.add('hidden');
          customRange.classList.remove('flex');
        }
      });

      // Initialize reports when section becomes visible
      const originalShowSection8 = showSection;
      showSection = function(sectionId) {
        originalShowSection8(sectionId);
        if (sectionId === 'reports') {
          // Initialize charts with default data
          generateReport();
        }
      };
    </script>
  <script>
      function staffDetailRows(rows) {
        return `
          <div class="modal-detail-list">
            ${rows.map((row) => `
              <div class="modal-detail-row">
                <div class="modal-detail-label">${row.label}</div>
                <div class="modal-detail-value">${row.value}</div>
              </div>
            `).join('')}
          </div>
        `;
      }

                  function openStaffDetailModal(config) {
        const badges = (config.badges || []).map((badge) => `<span class="badge badge-info flex-shrink-0 text-xs">${badge}</span>`).join('');
        const noteHtml = config.note ? `
          <div class="bg-neutral-800/50 border border-neutral-700 rounded-xl p-3 sm:p-4">
            <h5 class="text-xs sm:text-sm font-semibold text-neutral-300 mb-2 sm:mb-3">Catatan</h5>
            <p class="text-xs sm:text-sm text-neutral-400 leading-relaxed">${config.note}</p>
          </div>
        ` : '';
        document.getElementById('staff-detail-modal-title').textContent = config.dialogTitle || 'Detail';
        document.getElementById('staff-detail-modal-subtitle').textContent = config.kicker || '';
        document.getElementById('staff-detail-modal-body').innerHTML = `
          <div class="space-y-4">
            <div class="flex gap-4">
              <div class="w-20 h-20 sm:w-24 sm:h-24 bg-neutral-800 rounded-lg overflow-hidden border border-neutral-700 flex-shrink-0">
                <img src="${config.image || '../images/gear-placeholder.svg'}" alt="${config.title || 'Detail'}" class="w-full h-full object-cover" onerror="this.src='../images/gear-placeholder.svg'">
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2 mb-1">
                  <div>
                    <h4 class="text-base sm:text-lg font-semibold text-white mb-1">${config.title || 'Item'}</h4>
                    ${config.subtitle ? `<p class="text-xs sm:text-sm text-neutral-400">${config.subtitle}</p>` : ''}
                  </div>
                  ${badges ? `<div class="flex flex-wrap justify-end gap-2">${badges}</div>` : ''}
                </div>
              </div>
            </div>
            <div class="bg-neutral-800/50 border border-neutral-700 rounded-xl p-3 sm:p-4">
              <div class="space-y-2 text-xs sm:text-sm">
                ${(config.rows || []).map((row) => `
                  <div class="flex justify-between gap-4">
                    <span class="text-neutral-400">${row.label}</span>
                    <span class="text-white text-right">${row.value}</span>
                  </div>
                `).join('')}
              </div>
            </div>
            ${noteHtml}
          </div>
        `;
        const modal = document.getElementById('staff-detail-modal');
        const modalContent = document.getElementById('staff-detail-modal-content');
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.remove('opacity-0');
        modalContent.classList.remove('opacity-0', 'scale-95');
        modalContent.classList.add('scale-100');
      }

      function closeStaffDetailModal() {
        const modal = document.getElementById('staff-detail-modal');
        const modalContent = document.getElementById('staff-detail-modal-content');
        modal.classList.add('opacity-0');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
          modal.classList.add('hidden');
        }, 200);
      }



      function openStaffActionModal(title, message, onConfirm) {
        const confirmBtn = document.getElementById('staff-action-confirm-btn');
        const iconWrap = document.getElementById('staff-action-modal-icon');
        const eyebrow = document.querySelector('.action-sheet-eyebrow');
        document.getElementById('staff-action-modal-title').textContent = title;
        document.getElementById('staff-action-modal-message').textContent = message;
        if (/tolak|rusak/i.test(title)) {
          if (eyebrow) eyebrow.textContent = 'High impact action';
          confirmBtn.textContent = 'Ya, lanjutkan';
          confirmBtn.className = 'flex-1 px-4 py-3 bg-red-500 text-white font-semibold rounded-lg hover:bg-red-400 transition-colors';
          iconWrap.innerHTML = `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-7.938 4h15.876c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>`;
        } else {
          if (eyebrow) eyebrow.textContent = 'Action confirmation';
          confirmBtn.textContent = 'Konfirmasi';
          confirmBtn.className = 'flex-1 px-4 py-3 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-colors';
          iconWrap.innerHTML = `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>`;
        }
        confirmBtn.onclick = function () {
          closeStaffActionModal();
          onConfirm();
        };
        document.getElementById('staff-action-modal').classList.remove('hidden');
      }

      function closeStaffActionModal() {
        document.getElementById('staff-action-modal').classList.add('hidden');
      }

      function viewBorrowing(id) {
        const borrowing = borrowings.find((entry) => entry.id === id);
        if (!borrowing) return;

        openStaffDetailModal({
          dialogTitle: 'Detail Peminjaman',
          kicker: 'Peminjaman',
          title: escapeHtml(borrowing.equipment),
          subtitle: `${escapeHtml(borrowing.brand || 'LensCraft')} • ${escapeHtml(capitalizeFirst(borrowing.category || 'equipment'))}`,
          image: borrowing.image,
          badges: [
            `Status: ${capitalizeFirst(borrowing.status)}`,
            `ID: ${escapeHtml(borrowing.id)}`
          ],
          rows: [
            { label: 'Customer', value: escapeHtml(borrowing.customer) },
            { label: 'Mulai', value: escapeHtml(formatDate(borrowing.startDate)) },
            { label: 'Selesai', value: escapeHtml(formatDate(borrowing.endDate)) },
            { label: 'Durasi', value: `${borrowing.days || 0} hari` },
            { label: 'Total', value: `$${borrowing.amount}` }
          ]
        });
      }

      function viewReturn(id) {
        const returnItem = returns.find((entry) => entry.id === id);
        if (!returnItem) return;

        openStaffDetailModal({
          dialogTitle: 'Detail Pengembalian',
          kicker: 'Pengembalian',
          title: escapeHtml(returnItem.equipment),
          subtitle: `${escapeHtml(returnItem.brand || 'LensCraft')} • ${escapeHtml(capitalizeFirst(returnItem.category || 'equipment'))}`,
          image: returnItem.image,
          badges: [
            `Status: ${capitalizeFirst(returnItem.status)}`,
            `ID: ${escapeHtml(returnItem.id)}`
          ],
          rows: [
            { label: 'Customer', value: escapeHtml(returnItem.customer) },
            { label: 'Tanggal', value: escapeHtml(formatDate(returnItem.returnDate)) }
          ],
          note: escapeHtml(returnItem.notes || 'Tidak ada catatan tambahan.')
        });
      }

      function approveBorrowing(id) {
        openStaffActionModal('Setujui Peminjaman', `Setujui peminjaman ${id}?`, function () {
          postStaffAction('../process/staff-peminjaman-approve.php', { rental_code: id });
        });
      }

      function rejectBorrowing(id) {
        openStaffActionModal('Tolak Peminjaman', `Tolak peminjaman ${id}?`, function () {
          postStaffAction('../process/staff-peminjaman-reject.php', { rental_code: id });
        });
      }

      function markReturned(id) {
        openStaffActionModal('Konfirmasi Pengembalian', `Tandai pengembalian ${id} sebagai selesai?`, function () {
          postStaffAction('../process/staff-pengembalian-konfirmasi.php', { return_code: id, status: 'completed' });
        });
      }
      function postStaffAction(action, payload) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = action;
        payload.csrf_token = <?= json_encode(csrf_token()) ?>;

        Object.keys(payload).forEach(function (key) {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = key;
          input.value = payload[key];
          form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
      }

      window.addEventListener('DOMContentLoaded', function () {
        if (window.location.hash === '#reports') {
          generateReport();
        }
      });
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof showSection === 'function') {
    showSection('overview');
  }
  document.querySelectorAll('a.nav-item[href]').forEach(function (link) {
    const active = link.getAttribute('href') === 'index.php';
    link.classList.toggle('nav-item-active', active);
    link.classList.toggle('text-neutral-400', !active);
    link.classList.toggle('text-white', active);
  });
});
</script>
    <?= page_runtime_bundle($flash_script) ?>
  </body>
</html>
