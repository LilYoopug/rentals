<?php
require_once __DIR__ . '/../includes/staff-check.php';
require_once __DIR__ . '/../data/users/staff-data.php';
require_once __DIR__ . '/../data/users/admin-data.php';
require_once __DIR__ . '/../data/categories-data.php';
require_once __DIR__ . '/../data/products-data.php';
require_once __DIR__ . '/../data/rentals-data.php';
require_once __DIR__ . '/../data/returns-data.php';
require_once __DIR__ . '/../includes/flash.php';

$staff_active_section = 'overview';
$staff_active_href = 'index.php';
$staff_active_section_selector = preg_replace('/[^a-z0-9_-]/i', '', $staff_active_section) ?: 'overview';

$staff_user = current_user();
$staff_avatar_url = !empty($staff_user['avatar_path']) ? '../' . ltrim((string) $staff_user['avatar_path'], '/') : '';

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

function staff_compact_currency($amount)
{
  $amount = (float) $amount;

  if ($amount >= 1000000000) {
    $value = $amount / 1000000000;
    $suffix = ' M';
  } elseif ($amount >= 1000000) {
    $value = $amount / 1000000;
    $suffix = ' Jt';
  } elseif ($amount >= 1000) {
    $value = $amount / 1000;
    $suffix = ' Rb';
  } else {
    return 'Rp' . number_format($amount, 0, ',', '.');
  }

  $formatted = number_format($value, $value >= 100 ? 0 : 2, ',', '.');
  $formatted = rtrim(rtrim($formatted, '0'), ',');

  return 'Rp' . $formatted . $suffix;
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
    $status = present_borrowing_workflow_status((string) ($row['status'] ?? 'menunggu'));

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
        'status' => $status,
    ];
}

$staff_returns = [];
foreach ($staff_return_rows as $row) {
    $status = present_return_workflow_status((string) ($row['status'] ?? 'menunggu'), true);

    $staff_returns[] = [
        'id' => (string) ($row['tracking_id'] ?? $row['return_code'] ?? $row['rental_code'] ?? ''),
        'customer' => (string) ($row['fullname'] ?? ''),
        'equipment' => (string) ($row['product_name'] ?? ''),
        'brand' => (string) ($row['brand'] ?? ''),
        'category' => (string) ($row['category'] ?? $row['category_slug'] ?? ''),
        'image' => (string) ($row['image_path'] ?? '../images/gear-placeholder.svg'),
        'returnDate' => !empty($row['returned_at']) ? date('Y-m-d', strtotime((string) $row['returned_at'])) : (string) ($row['end_date'] ?? ''),
        'notes' => (string) ($row['notes'] ?? ''),
        'status' => $status,
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

    if (in_array($status, ['menunggu', 'mendatang'], true)) {
        $pending_approval_count++;
        if ($created_day === $yesterday) {
            $pending_yesterday_count++;
        }
    }

    if ($status === 'aktif') {
        $active_rental_count++;
    }

    if ($created_day === $today) {
        $revenue_today += (float) ($row['total_price'] ?? 0);
    }
    if ($created_day === $yesterday) {
        $revenue_yesterday += (float) ($row['total_price'] ?? 0);
    }

    if (in_array($status, ['disetujui', 'aktif', 'selesai'], true)) {
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
    if (($row['status'] ?? '') === 'selesai') {
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
    'revenue_today_full' => format_currency($revenue_today),
    'revenue_today' => format_currency($revenue_today),
    'revenue_today_compact' => staff_compact_currency($revenue_today),
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
    if (!in_array((string) ($row['status'] ?? ''), ['menunggu', 'mendatang'], true)) {
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
        'metric' => 'Pendapatan Harian',
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
        'change' => ucfirst((string) ($row['status'] ?? 'menunggu')),
        'positive' => in_array((string) ($row['status'] ?? ''), ['aktif', 'selesai', 'menunggu', 'mendatang'], true),
    ];
}
$staff_report_tables['borrowings'] = array_slice($staff_report_tables['borrowings'], 0, 8);

foreach ($all_return_rows as $row) {
    $staff_report_tables['returns'][] = [
        'date' => (string) ($row['returnedAt'] ?? $row['createdAt'] ?? date('Y-m-d')),
        'metric' => (string) ($row['productName'] ?? 'Return'),
        'value' => (string) ($row['id'] ?? '-'),
        'change' => ucfirst((string) ($row['status'] ?? 'menunggu')),
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
    <script>
      document.documentElement.classList.add('staff-js', 'staff-page-loading');
    </script>
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
      @keyframes skeletonShimmer {
        0% {
          transform: translateX(-100%);
        }
        100% {
          transform: translateX(100%);
        }
      }
      .skeleton-shell {
        position: relative;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.06);
      }
      .skeleton-shell::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.14), transparent);
        animation: skeletonShimmer 1.35s ease-in-out infinite;
      }
      #dashboard-skeleton {
        display: none;
      }
      .staff-js.staff-page-loading #dashboard-skeleton {
        display: block;
      }
      .staff-js.staff-page-loading #overview {
        display: none !important;
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
      .content-section {
        display: none;
      }
      #<?= e($staff_active_section_selector) ?>.content-section {
        display: block;
      }

      /* Table styles */
      .table-row-hover:hover {
        background-color: rgba(255, 255, 255, 0.03);
      }
      @media (max-width: 639px) {
        .mobile-name-ellipsis {
          display: block;
          max-width: 100%;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
        .mobile-card-title-ellipsis {
          max-width: min(13rem, 100%);
        }
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
        margin-top: 0.75rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
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
              <div class="text-sm font-medium text-white"><?= e((string) ($staff_user['fullname'] ?? 'Staff User')) ?></div>
              <div class="text-xs text-neutral-500">Staff Member</div>
            </div>
            <div class="w-10 h-10 bg-neutral-800 rounded-full flex items-center justify-center border border-neutral-700 overflow-hidden">
              <?php if ($staff_avatar_url !== ''): ?>
                <img src="<?= e($staff_avatar_url) ?>" alt="Staff avatar" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
              <?php endif; ?>
              <svg class="w-5 h-5 text-neutral-400" style="<?= $staff_avatar_url !== '' ? 'display:none;' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <a href="index.php" class="nav-item <?= (isset($staff_active_section) && $staff_active_section === 'overview') ? 'nav-item-active' : 'text-neutral-400' ?> flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all hover:bg-white/5" data-section="overview">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          <span>Ringkasan</span>
        </a>

        <a href="borrowings.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white" data-section="approve-borrowings">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Peminjaman</span>
        </a>

        <a href="returns.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white" data-section="monitor-returns">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          <span>Pengembalian</span>
        </a>
        <a href="stock-price.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white" data-section="stock-price">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 .895-4 2s1.79 2 4 2 4 .895 4 2-1.79 2-4 2m0-10V6m0 12v2m8-8a8 8 0 11-16 0 8 8 0 0116 0z" />
          </svg>
          <span>Stok & Harga</span>
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

          <section id="dashboard-skeleton" aria-hidden="true">
            <div class="mb-8 space-y-3">
              <div class="skeleton-shell h-10 w-72 rounded-2xl"></div>
              <div class="skeleton-shell h-4 w-96 max-w-full rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              <?php for ($dashboard_card_skeleton = 0; $dashboard_card_skeleton < 4; $dashboard_card_skeleton++): ?>
                <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 space-y-4">
                  <div class="flex items-center justify-between">
                    <div class="skeleton-shell h-12 w-12 rounded-xl"></div>
                    <div class="skeleton-shell h-6 w-16 rounded-full"></div>
                  </div>
                  <div class="skeleton-shell h-8 w-24 rounded-xl"></div>
                  <div class="skeleton-shell h-4 w-36 rounded-full"></div>
                </div>
              <?php endfor; ?>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <div class="lg:col-span-2 bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                  <div class="skeleton-shell h-7 w-52 rounded-xl"></div>
                  <div class="skeleton-shell h-5 w-20 rounded-full"></div>
                </div>
                <div class="space-y-4">
                  <?php for ($dashboard_list_skeleton = 0; $dashboard_list_skeleton < 3; $dashboard_list_skeleton++): ?>
                    <div class="flex items-center justify-between gap-4 border-b border-neutral-800 pb-4 last:border-b-0 last:pb-0">
                      <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div class="skeleton-shell h-10 w-10 rounded-lg"></div>
                        <div class="flex-1 space-y-2 min-w-0">
                          <div class="skeleton-shell h-4 w-44 max-w-full rounded-full"></div>
                          <div class="skeleton-shell h-3 w-32 rounded-full"></div>
                        </div>
                      </div>
                      <div class="flex gap-2">
                        <div class="skeleton-shell h-8 w-16 rounded-lg"></div>
                        <div class="skeleton-shell h-8 w-16 rounded-lg"></div>
                      </div>
                    </div>
                  <?php endfor; ?>
                </div>
              </div>

              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                <div class="skeleton-shell h-7 w-32 rounded-xl mb-6"></div>
                <div class="space-y-3 mb-8">
                  <?php for ($dashboard_action_skeleton = 0; $dashboard_action_skeleton < 3; $dashboard_action_skeleton++): ?>
                    <div class="skeleton-shell h-12 w-full rounded-xl"></div>
                  <?php endfor; ?>
                </div>
                <div class="space-y-4">
                  <div class="skeleton-shell h-5 w-28 rounded-full mb-4"></div>
                  <?php for ($dashboard_progress_skeleton = 0; $dashboard_progress_skeleton < 3; $dashboard_progress_skeleton++): ?>
                    <div class="space-y-2">
                      <div class="flex items-center justify-between gap-3">
                        <div class="skeleton-shell h-3 w-28 rounded-full"></div>
                        <div class="skeleton-shell h-3 w-14 rounded-full"></div>
                      </div>
                      <div class="skeleton-shell h-2 w-full rounded-full"></div>
                    </div>
                  <?php endfor; ?>
                </div>
              </div>
            </div>
          </section>

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
                <div class="min-w-0 text-2xl font-bold leading-tight text-white md:text-3xl break-words" title="<?= e($staff_summary['revenue_today_full']) ?>"><?= e($staff_summary['revenue_today_compact']) ?></div>
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
              <div class="lg:col-span-2 bg-neutral-900 border border-neutral-800 rounded-2xl p-6 flex flex-col">
                <div class="flex items-center justify-between mb-6">
                  <h2 class="text-xl font-semibold text-white">Persetujuan Menunggu</h2>
                  <a href="borrowings.php" class="text-sm text-neutral-400 hover:text-white transition-colors">Lihat Semua</a>
                </div>
                <div class="space-y-4 flex-1" id="pending-approvals-list">
                  <?php if (empty($staff_pending_approvals)): ?>
                    <div class="flex h-full flex-col items-center justify-center rounded-2xl border border-dashed border-neutral-800 bg-neutral-950/40 px-6 py-12 text-center">
                      <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-neutral-800 bg-neutral-900 text-neutral-400">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <p class="text-sm font-medium text-white">Tidak ada permintaan menunggu</p>
                      <p class="mt-2 max-w-sm text-sm text-neutral-500">Tidak ada permintaan yang menunggu persetujuan.</p>
                    </div>
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
    </main>



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
              <div class="action-sheet-eyebrow">Mohon konfirmasi</div>
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

      if (sidebarToggle && sidebarOverlay) {
        sidebarToggle.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);
      }

      function showSection(sectionId) {
        return sectionId;
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
          iconWrap.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-7.938 4h15.876c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>';
        } else {
          if (eyebrow) eyebrow.textContent = 'Action confirmation';
          confirmBtn.textContent = 'Konfirmasi';
          confirmBtn.className = 'flex-1 px-4 py-3 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-colors';
          iconWrap.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';
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

      function approveBorrowing(id) {
        openStaffActionModal('Setujui Peminjaman', 'Setujui peminjaman ' + id + '?', function () {
          postStaffAction('../process/staff-peminjaman-approve.php', { rental_code: id });
        });
      }

      function rejectBorrowing(id) {
        openStaffActionModal('Tolak Peminjaman', 'Tolak peminjaman ' + id + '?', function () {
          postStaffAction('../process/staff-peminjaman-reject.php', { rental_code: id });
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
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof showSection === 'function') {
    showSection(<?= json_encode($staff_active_section) ?>);
  }
  document.querySelectorAll('a.nav-item[href]').forEach(function (link) {
    const active = link.getAttribute('href') === <?= json_encode($staff_active_href) ?>;
    link.classList.toggle('nav-item-active', active);
    link.classList.toggle('text-neutral-400', !active);
    link.classList.toggle('text-white', active);
  });
});

window.addEventListener('load', function () {
  document.documentElement.classList.remove('staff-page-loading');
});
</script>
    <?= page_runtime_bundle($flash_script) ?>
  </body>
</html>
