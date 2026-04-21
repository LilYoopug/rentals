<?php
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/users/admin-data.php';
require_once __DIR__ . '/../data/categories-data.php';
require_once __DIR__ . '/../data/products-data.php';
require_once __DIR__ . '/../data/rentals-data.php';
require_once __DIR__ . '/../data/returns-data.php';
require_once __DIR__ . '/../data/activity-data.php';
require_once __DIR__ . '/../includes/flash.php';

$admin_active_section = 'overview';
$admin_active_href = 'index.php';
$admin_active_section_selector = preg_replace('/[^a-z0-9_-]/i', '', $admin_active_section) ?: 'overview';

$admin_session_user = current_user();
$admin_avatar_url = !empty($admin_session_user['avatar_path']) ? admin_media_path((string) $admin_session_user['avatar_path']) : '';

function dashboard_percent_change($current, $previous)
{
    if ($previous <= 0) {
        if ($current > 0) {
            return [
                'text' => '+100%',
                'class' => 'text-green-400 bg-green-900/30 border border-green-800/50',
            ];
        }

        return [
            'text' => '0%',
            'class' => 'text-neutral-400 bg-neutral-800/50 border border-neutral-700',
        ];
    }

    $delta = (($current - $previous) / $previous) * 100;
    $rounded = (int) round($delta);

    if ($rounded > 0) {
        return [
            'text' => '+' . $rounded . '%',
            'class' => 'text-green-400 bg-green-900/30 border border-green-800/50',
        ];
    }

    if ($rounded < 0) {
        return [
            'text' => $rounded . '%',
            'class' => 'text-red-400 bg-red-900/30 border border-red-800/50',
        ];
    }

    return [
        'text' => '0%',
        'class' => 'text-neutral-400 bg-neutral-800/50 border border-neutral-700',
    ];
}

function dashboard_relative_time($datetime)
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

function admin_media_path($path)
{
    $image_path = trim((string) $path);
    if ($image_path === '') {
        return '../images/gear-placeholder.svg';
    }

    if (preg_match('/^(?:https?:)?\/\//', $image_path) === 1 || strpos($image_path, '../') === 0) {
        return $image_path;
    }

    return '../' . ltrim($image_path, '/');
}

function dashboard_transaction_badge($status)
{
    $status = normalize_rental_status_value($status);

    $map = [
        'menunggu' => ['label' => 'Menunggu', 'class' => 'badge-warning', 'amountClass' => 'text-white'],
        'disetujui' => ['label' => 'Disetujui', 'class' => 'badge-info', 'amountClass' => 'text-blue-400'],
        'mendatang' => ['label' => 'Menunggu', 'class' => 'badge-warning', 'amountClass' => 'text-white'],
        'aktif' => ['label' => 'Aktif', 'class' => 'badge-info', 'amountClass' => 'text-blue-400'],
        'selesai' => ['label' => 'Selesai', 'class' => 'badge-success', 'amountClass' => 'text-green-400'],
        'ditolak' => ['label' => 'Ditolak', 'class' => 'badge-danger', 'amountClass' => 'text-red-400'],
        'dibatalkan' => ['label' => 'Dibatalkan', 'class' => 'badge-danger', 'amountClass' => 'text-red-400'],
    ];

    return $map[$status] ?? ['label' => ucfirst((string) $status), 'class' => 'badge-info', 'amountClass' => 'text-white'];
}

$admin_user_rows = get_admin_users();
$admin_product_rows = get_admin_products();
$admin_borrowing_rows = get_all_borrowings();
$admin_return_rows = get_all_returns();
$admin_activity_log_rows = get_activity_logs();
$admin_category_rows = get_all_categories();

$admin_users = [];
foreach ($admin_user_rows as $row) {
    $admin_users[] = [
        'id' => (int) ($row['id'] ?? 0),
        'name' => (string) ($row['fullname'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'role' => (string) ($row['role'] ?? 'pelanggan'),
        'status' => (string) ($row['status'] ?? 'aktif'),
        'joined' => !empty($row['created_at']) ? date('Y-m-d', strtotime((string) $row['created_at'])) : '',
        'lastAktif' => !empty($row['last_active']) ? date('Y-m-d H:i', strtotime((string) $row['last_active'])) : 'Never',
    ];
}

$category_counts = [];
foreach ($admin_product_rows as $product_row) {
    $slug = (string) ($product_row['category_slug'] ?? '');
    $category_counts[$slug] = ($category_counts[$slug] ?? 0) + 1;
}

$admin_categories = [];
foreach ($admin_category_rows as $row) {
    $slug = (string) ($row['slug'] ?? '');
    $admin_categories[] = [
        'id' => (int) ($row['id'] ?? 0),
        'name' => (string) ($row['name'] ?? ''),
        'description' => (string) ($row['description'] ?? ''),
        'icon' => (string) ($row['icon'] ?? 'camera'),
        'color' => (string) ($row['color'] ?? 'blue'),
        'status' => (string) ($row['status'] ?? 'aktif'),
        'slug' => $slug,
        'itemCount' => (int) ($category_counts[$slug] ?? 0),
    ];
}

$admin_inventory = [];
foreach ($admin_product_rows as $row) {
    $available = (int) ($row['stock_available'] ?? 0);
    $status = 'available';
    if ($available <= 0) {
        $status = 'rented';
    } elseif (($row['status'] ?? 'aktif') !== 'aktif') {
        $status = 'maintenance';
    }

    $admin_inventory[] = [
        'id' => (int) ($row['id'] ?? 0),
        'name' => (string) ($row['name'] ?? ''),
        'brand' => (string) ($row['brand'] ?? ''),
        'category' => (string) ($row['category_slug'] ?? ''),
        'description' => (string) ($row['description'] ?? ''),
        'price' => (float) ($row['price_per_day'] ?? 0),
        'status' => $status,
        'stock' => $available,
        'totalStock' => (int) ($row['stock_total'] ?? 0),
        'image' => admin_media_path((string) ($row['image_path'] ?? '')),
        'discount' => (int) ($row['discount_percentage'] ?? 0),
    ];
}

$admin_borrowings = [];
foreach ($admin_borrowing_rows as $row) {
    $raw_status = normalize_rental_status_value((string) ($row['status'] ?? 'menunggu'));
    $admin_borrowings[] = [
        'id' => (string) ($row['rental_code'] ?? ''),
        'rawStatus' => $raw_status,
        'customer' => (string) ($row['fullname'] ?? ''),
        'equipment' => (string) ($row['product_name'] ?? ''),
        'image' => admin_media_path((string) ($row['image_path'] ?? '')),
        'productId' => (int) ($row['product_id'] ?? 0),
        'startDate' => (string) ($row['start_date'] ?? ''),
        'endDate' => (string) ($row['end_date'] ?? ''),
        'days' => (int) ($row['total_days'] ?? 0),
        'amount' => (float) ($row['total_price'] ?? 0),
        'status' => present_borrowing_workflow_status($raw_status),
    ];
}

$return_status_map = [
    'selesai' => 'returned',
    'menunggu' => 'menunggu',
    'overdue' => 'overdue',
];

$admin_returns = [];
foreach ($admin_return_rows as $row) {
    $admin_returns[] = [
        'id' => (string) ($row['id'] ?? ''),
        'customer' => (string) ($row['fullname'] ?? ''),
        'equipment' => (string) ($row['productName'] ?? ''),
        'image' => (string) ($row['image'] ?? '../images/gear-placeholder.svg'),
        'returnDate' => (string) ($row['returnedAt'] ?? $row['createdAt'] ?? ''),
        'status' => (string) ($return_status_map[$row['status'] ?? 'menunggu'] ?? 'menunggu'),
        'notes' => (string) ($row['notes'] ?? ''),
    ];
}

$admin_activity_type_map = [
    'system' => 'system',
    'rental' => 'transaction',
    'return' => 'transaction',
    'payment' => 'transaction',
    'inventory' => 'inventory',
    'product' => 'inventory',
    'category' => 'inventory',
    'profile' => 'pelanggan',
    'user' => 'pelanggan',
    'auth' => 'security',
    'security' => 'security',
];

$admin_activities = [];
foreach ($admin_activity_log_rows as $row) {
    $activity_type = (string) ($row['activity_type'] ?? 'system');
    $admin_activities[] = [
        'id' => (int) ($row['id'] ?? 0),
        'type' => (string) ($admin_activity_type_map[$activity_type] ?? 'system'),
        'rawType' => $activity_type,
        'actorName' => (string) ($row['actor_name'] ?? 'System'),
        'actorRole' => (string) ($row['actor_role'] ?? 'system'),
        'action' => ucfirst(str_replace('-', ' ', $activity_type)),
        'target' => 'LensCraft',
        'details' => (string) ($row['message'] ?? ''),
        'timestamp' => (string) ($row['created_at'] ?? date('Y-m-d H:i:s')),
        'icon' => 'database',
        'color' => 'indigo',
    ];
}

$current_month = date('Y-m');
$previous_month = date('Y-m', strtotime('first day of last month'));

$current_month_users = 0;
$previous_month_users = 0;
$active_users = 0;
foreach ($admin_user_rows as $row) {
    $created_month = !empty($row['created_at']) ? date('Y-m', strtotime((string) $row['created_at'])) : '';
    if ($created_month === $current_month) {
        $current_month_users++;
    }
    if ($created_month === $previous_month) {
        $previous_month_users++;
    }
    if (($row['status'] ?? '') === 'aktif') {
        $active_users++;
    }
}

$current_month_products = 0;
$previous_month_products = 0;
$total_stock = 0;
$available_stock = 0;
foreach ($admin_product_rows as $row) {
    $created_month = !empty($row['created_at']) ? date('Y-m', strtotime((string) $row['created_at'])) : '';
    if ($created_month === $current_month) {
        $current_month_products++;
    }
    if ($created_month === $previous_month) {
        $previous_month_products++;
    }
    $total_stock += (int) ($row['stock_total'] ?? 0);
    $available_stock += (int) ($row['stock_available'] ?? 0);
}

$current_month_transactions = 0;
$previous_month_transactions = 0;
$current_month_revenue = 0.0;
$previous_month_revenue = 0.0;
$pending_approvals = 0;
$recent_transaction_rows = array_slice($admin_borrowing_rows, 0, 3);
foreach ($admin_borrowing_rows as $row) {
    $created_month = !empty($row['created_at']) ? date('Y-m', strtotime((string) $row['created_at'])) : '';
    $amount = (float) ($row['total_price'] ?? 0);
    if ($created_month === $current_month) {
        $current_month_transactions++;
        $current_month_revenue += $amount;
    }
    if ($created_month === $previous_month) {
        $previous_month_transactions++;
        $previous_month_revenue += $amount;
    }
    if (in_array((string) ($row['status'] ?? ''), ['menunggu', 'mendatang'], true)) {
        $pending_approvals++;
    }
}

$utilization_percent = $total_stock > 0 ? (int) round((($total_stock - $available_stock) / $total_stock) * 100) : 0;
$available_stock_percent = $total_stock > 0 ? (int) round(($available_stock / $total_stock) * 100) : 0;
$active_user_percent = count($admin_user_rows) > 0 ? (int) round(($active_users / count($admin_user_rows)) * 100) : 0;
$pending_width = count($admin_borrowing_rows) > 0 ? (int) round(($pending_approvals / count($admin_borrowing_rows)) * 100) : 0;

$admin_summary = [
    'total_users' => number_format(count($admin_user_rows)),
    'total_equipment' => number_format(count($admin_product_rows)),
    'total_transactions' => number_format(count($admin_borrowing_rows)),
    'revenue_mtd' => format_currency($current_month_revenue),
    'users_change' => dashboard_percent_change($current_month_users, $previous_month_users),
    'equipment_change' => dashboard_percent_change($current_month_products, $previous_month_products),
    'transactions_change' => dashboard_percent_change($current_month_transactions, $previous_month_transactions),
    'revenue_change' => dashboard_percent_change($current_month_revenue, $previous_month_revenue),
    'utilization_percent' => $utilization_percent,
    'active_users' => $active_users,
    'active_users_percent' => $active_user_percent,
    'pending_approvals' => $pending_approvals,
    'pending_width' => $pending_width,
    'available_stock' => $available_stock,
    'available_stock_percent' => $available_stock_percent,
];

$admin_recent_transactions = [];
foreach ($recent_transaction_rows as $row) {
    $badge = dashboard_transaction_badge((string) ($row['status'] ?? 'menunggu'));
    $admin_recent_transactions[] = [
        'code' => (string) ($row['rental_code'] ?? '-'),
        'product_name' => (string) ($row['product_name'] ?? 'Peralatan'),
        'image' => admin_media_path((string) ($row['image_path'] ?? '')),
        'customer' => (string) ($row['fullname'] ?? 'Customer'),
        'time_ago' => dashboard_relative_time($row['created_at'] ?? ''),
        'amount' => format_currency((float) ($row['total_price'] ?? 0)),
        'status_label' => $badge['label'],
        'status_class' => $badge['class'],
        'amount_class' => $badge['amountClass'],
    ];
}

$admin_users_json = json_encode($admin_users, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$admin_categories_json = json_encode($admin_categories, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$admin_inventory_json = json_encode($admin_inventory, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$admin_borrowings_json = json_encode($admin_borrowings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$admin_returns_json = json_encode($admin_returns, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$admin_activities_json = json_encode($admin_activities, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LensCraft - Dashboard Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap"
      rel="stylesheet"
    />
    <script>
      document.documentElement.classList.add('admin-js', 'admin-page-loading');
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
      #admin-page-skeleton {
        display: none;
      }
      .admin-js.admin-page-loading #admin-page-skeleton {
        display: block;
      }
      .admin-js.admin-page-loading #overview {
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
      #<?= e($admin_active_section_selector) ?>.content-section {
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
      .detail-note-card {
        padding: 1rem 1.1rem;
        border-radius: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.06);
        background: rgba(255, 255, 255, 0.025);
      }
      .detail-note-label {
        font-size: 0.72rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #737373;
        margin-bottom: 0.45rem;
      }
      .detail-note-text {
        color: #d4d4d4;
        line-height: 1.65;
        font-size: 0.92rem;
      }
      .modal-actions {
        display: flex;
        gap: 0.75rem;
        padding-top: 0.5rem;
      }
      .modal-actions > * {
        flex: 1;
      }
      .inventory-editor {
        display: grid;
        gap: 1.25rem;
      }
      .inventory-preview-card {
        display: grid;
        gap: 0.9rem;
        align-content: start;
      }
      .inventory-preview-frame {
        position: relative;
        overflow: hidden;
        min-height: 16rem;
        border-radius: 1.25rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background:
          radial-gradient(circle at top left, rgba(199, 166, 90, 0.18), transparent 35%),
          linear-gradient(180deg, rgba(32, 32, 32, 0.96), rgba(13, 13, 13, 0.96));
      }
      .inventory-preview-image {
        width: 100%;
        height: 100%;
        min-height: 16rem;
        object-fit: cover;
      }
      .inventory-upload-label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        width: 100%;
        padding: 0.9rem 1rem;
        border-radius: 1rem;
        border: 1px dashed rgba(255, 255, 255, 0.14);
        background: rgba(255, 255, 255, 0.02);
        color: #f5f5f5;
        font-size: 0.88rem;
        cursor: pointer;
      }
      .inventory-upload-label:hover {
        background: rgba(255, 255, 255, 0.05);
      }
      .inventory-upload-meta {
        font-size: 0.78rem;
        color: #8a8a8a;
        line-height: 1.55;
      }
      .inventory-form-shell {
        display: grid;
        gap: 1rem;
      }
      .inventory-form-shell label {
        font-size: 0.78rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #a3a3a3;
      }
      @media (min-width: 900px) {
        .detail-sheet {
          grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.15fr);
          align-items: stretch;
        }
        .detail-content {
          padding-top: 0.25rem;
        }
        .inventory-editor {
          grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.15fr);
          align-items: start;
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
          <span class="hidden md:inline-block text-sm text-neutral-500 border-l border-neutral-800 pl-4">Dashboard Admin</span>
        </div>

        <!-- Right Side Actions -->
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-3 border-l border-neutral-800 pl-4">
            <div class="text-right hidden sm:block">
              <div class="text-sm font-medium text-white"><?= e((string) ($admin_session_user['fullname'] ?? 'Admin User')) ?></div>
              <div class="text-xs text-neutral-500">Super Admin</div>
            </div>
            <div class="w-10 h-10 bg-neutral-800 rounded-full flex items-center justify-center border border-neutral-700 overflow-hidden">
              <?php if ($admin_avatar_url !== ''): ?>
                <img src="<?= e($admin_avatar_url) ?>" alt="Admin avatar" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
              <?php endif; ?>
              <svg class="w-5 h-5 text-neutral-400" style="<?= $admin_avatar_url !== '' ? 'display:none;' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <a href="index.php" class="nav-item <?= (isset($admin_active_section) && $admin_active_section === 'overview') ? 'nav-item-active' : 'text-neutral-400' ?> flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all hover:bg-white/5" data-section="overview">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          <span>Ringkasan</span>
        </a>

        <a href="users.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white" data-section="users">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <span>Pengguna</span>
        </a>

        <a href="categories.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white" data-section="categories">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          <span>Kategori</span>
        </a>

        <a href="products.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white" data-section="tools-stock">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
          <span>Peralatan & Stok</span>
        </a>

        <a href="borrowings.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white" data-section="borrowings">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H8" />
          </svg>
          <span>Peminjaman</span>
        </a>

        <a href="returns.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white" data-section="returns">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Pengembalian</span>
        </a>

        <a href="activity-log.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white" data-section="activity-log">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Aktivitas</span>
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
          <a href="products.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-neutral-400 hover:text-white transition-colors">
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
        <!-- Content Sections (all hidden by default, shown via JS) -->
        <div id="content-area">
          <section id="admin-page-skeleton" aria-hidden="true">
            <div class="mb-8 space-y-3">
              <div class="skeleton-shell h-10 w-72 rounded-2xl"></div>
              <div class="skeleton-shell h-4 w-96 max-w-full rounded-full"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              <?php for ($admin_dashboard_skeleton = 0; $admin_dashboard_skeleton < 4; $admin_dashboard_skeleton++): ?>
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
                  <div class="skeleton-shell h-7 w-48 rounded-xl"></div>
                  <div class="skeleton-shell h-5 w-20 rounded-full"></div>
                </div>
                <div class="space-y-4">
                  <?php for ($admin_tx_skeleton = 0; $admin_tx_skeleton < 4; $admin_tx_skeleton++): ?>
                    <div class="flex items-center justify-between gap-4 border-b border-neutral-800 pb-4 last:border-b-0 last:pb-0">
                      <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="skeleton-shell h-10 w-10 rounded-lg"></div>
                        <div class="flex-1 min-w-0 space-y-2">
                          <div class="skeleton-shell h-3 w-24 rounded-full"></div>
                          <div class="skeleton-shell h-4 w-44 max-w-full rounded-full"></div>
                          <div class="skeleton-shell h-3 w-36 rounded-full"></div>
                        </div>
                      </div>
                      <div class="space-y-2 text-right">
                        <div class="skeleton-shell h-4 w-20 rounded-full"></div>
                        <div class="skeleton-shell h-5 w-16 rounded-full"></div>
                      </div>
                    </div>
                  <?php endfor; ?>
                </div>
              </div>
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                <div class="skeleton-shell h-7 w-36 rounded-xl mb-6"></div>
                <div class="space-y-6">
                  <?php for ($admin_stat_skeleton = 0; $admin_stat_skeleton < 4; $admin_stat_skeleton++): ?>
                    <div class="space-y-2">
                      <div class="flex items-center justify-between gap-3">
                        <div class="skeleton-shell h-4 w-32 rounded-full"></div>
                        <div class="skeleton-shell h-4 w-12 rounded-full"></div>
                      </div>
                      <div class="skeleton-shell h-2 w-full rounded-full"></div>
                    </div>
                  <?php endfor; ?>
                </div>
              </div>
            </div>
          </section>
          <!-- Content Sections -->
          <!-- Ringkasan Section -->
          <section id="overview" class="content-section animate-fade-in">
            <div class="mb-8">
              <h1 class="text-3xl md:text-4xl font-serif text-white mb-2">Ringkasan Dashboard</h1>
              <p class="text-neutral-400">Pantau aktivitas utama admin dan ringkasan operasional hari ini.</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              <!-- Card 1 -->
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                  <div class="w-12 h-12 bg-amber-900/30 rounded-xl flex items-center justify-center border border-amber-800/50">
                    <svg class="w-6 h-6 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                  </div>
                  <span class="text-xs font-medium px-2 py-1 rounded-full <?= e($admin_summary['users_change']['class']) ?>"><?= e($admin_summary['users_change']['text']) ?></span>
                </div>
                <div class="text-3xl font-bold text-white mb-1"><?= e($admin_summary['total_users']) ?></div>
                <div class="text-sm text-neutral-400">Total Pengguna</div>
              </div>

              <!-- Card 2 -->
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                  <div class="w-12 h-12 bg-green-900/30 rounded-xl flex items-center justify-center border border-green-800/50">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                  </div>
                  <span class="text-xs font-medium px-2 py-1 rounded-full <?= e($admin_summary['equipment_change']['class']) ?>"><?= e($admin_summary['equipment_change']['text']) ?></span>
                </div>
                <div class="text-3xl font-bold text-white mb-1"><?= e($admin_summary['total_equipment']) ?></div>
                <div class="text-sm text-neutral-400">Total Peralatan</div>
              </div>

              <!-- Card 3 -->
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                  <div class="w-12 h-12 bg-yellow-900/30 rounded-xl flex items-center justify-center border border-yellow-800/50">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </div>
                  <span class="text-xs font-medium px-2 py-1 rounded-full <?= e($admin_summary['transactions_change']['class']) ?>"><?= e($admin_summary['transactions_change']['text']) ?></span>
                </div>
                <div class="text-3xl font-bold text-white mb-1"><?= e($admin_summary['total_transactions']) ?></div>
                <div class="text-sm text-neutral-400">Total Transaksi</div>
              </div>

              <!-- Card 4 -->
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                  <div class="w-12 h-12 bg-purple-900/30 rounded-xl flex items-center justify-center border border-purple-800/50">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <span class="text-xs font-medium px-2 py-1 rounded-full <?= e($admin_summary['revenue_change']['class']) ?>"><?= e($admin_summary['revenue_change']['text']) ?></span>
                </div>
                <div class="text-3xl font-bold text-white mb-1"><?= e($admin_summary['revenue_mtd']) ?></div>
                <div class="text-sm text-neutral-400">Pendapatan (Bulan Ini)</div>
              </div>
            </div>

            <!-- Recent Activity & Statistik Singkat -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <!-- Transaksi Terbaru -->
              <div class="lg:col-span-2 bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                  <h2 class="text-xl font-semibold text-white">Transaksi Terbaru</h2>
                  <a href="borrowings.php" class="text-sm text-neutral-400 hover:text-white transition-colors">Lihat Semua</a>
                </div>
                <div class="space-y-4">
                  <?php if (empty($admin_recent_transactions)): ?>
                    <div class="py-6 text-sm text-neutral-500">Belum ada transaksi terbaru.</div>
                  <?php else: ?>
                    <?php foreach ($admin_recent_transactions as $transaction): ?>
                      <div class="flex items-center justify-between py-3 border-b border-neutral-800">
                        <div class="flex items-center gap-3">
                          <div class="w-10 h-10 bg-neutral-800 rounded-lg overflow-hidden border border-neutral-700 flex-shrink-0">
                            <img src="<?= e($transaction['image']) ?>" alt="<?= e($transaction['product_name']) ?>" class="w-full h-full object-cover" onerror="this.src='../images/gear-placeholder.svg'">
                          </div>
                          <div>
                            <p class="text-xs text-neutral-500"><?= e($transaction['code']) ?></p>
                            <p class="text-sm font-medium text-white"><?= e($transaction['product_name']) ?></p>
                            <p class="text-xs text-neutral-500"><?= e($transaction['customer']) ?> • <?= e($transaction['time_ago']) ?></p>
                          </div>
                        </div>
                        <div class="text-right">
                          <p class="text-sm font-semibold <?= e($transaction['amount_class']) ?>"><?= e($transaction['amount']) ?></p>
                          <span class="badge <?= e($transaction['status_class']) ?> text-xs"><?= e($transaction['status_label']) ?></span>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Statistik Singkat -->
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                <h2 class="text-xl font-semibold text-white mb-6">Statistik Singkat</h2>
                <div class="space-y-6">
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm text-neutral-400">Utilisasi Peralatan</span>
                      <span class="text-sm font-medium text-white"><?= e((string) $admin_summary['utilization_percent']) ?>%</span>
                    </div>
                    <div class="w-full bg-neutral-800 rounded-full h-2">
                      <div class="bg-amber-400 h-2 rounded-full" style="width: <?= e((string) $admin_summary['utilization_percent']) ?>%"></div>
                    </div>
                  </div>
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm text-neutral-400">Persetujuan Menunggu</span>
                      <span class="text-sm font-medium text-white"><?= e((string) $admin_summary['pending_approvals']) ?></span>
                    </div>
                    <div class="w-full bg-neutral-800 rounded-full h-2">
                      <div class="bg-yellow-500 h-2 rounded-full" style="width: <?= e((string) $admin_summary['pending_width']) ?>%"></div>
                    </div>
                  </div>
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm text-neutral-400">User Aktif</span>
                      <span class="text-sm font-medium text-white"><?= e((string) $admin_summary['active_users']) ?></span>
                    </div>
                    <div class="w-full bg-neutral-800 rounded-full h-2">
                      <div class="bg-green-500 h-2 rounded-full" style="width: <?= e((string) $admin_summary['active_users_percent']) ?>%"></div>
                    </div>
                  </div>
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm text-neutral-400">Stok Tersedia</span>
                      <span class="text-sm font-medium text-white"><?= e((string) $admin_summary['available_stock']) ?></span>
                    </div>
                    <div class="w-full bg-neutral-800 rounded-full h-2">
                      <div class="bg-purple-500 h-2 rounded-full" style="width: <?= e((string) $admin_summary['available_stock_percent']) ?>%"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
    </main>

    <script>
      const sidebar = document.getElementById("sidebar");
      const sidebarToggle = document.getElementById("sidebar-toggle");
      const sidebarOverlay = document.getElementById("sidebar-overlay");

      function toggleSidebar() {
        const isClosed = sidebar.classList.contains("-translate-x-full");
        if (isClosed) {
          sidebar.classList.remove("-translate-x-full");
          sidebarOverlay.classList.remove("hidden");
        } else {
          sidebar.classList.add("-translate-x-full");
          sidebarOverlay.classList.add("hidden");
        }
      }

      if (sidebarToggle && sidebarOverlay) {
        sidebarToggle.addEventListener("click", toggleSidebar);
        sidebarOverlay.addEventListener("click", toggleSidebar);
      }

      function showSection(sectionId) {
        return sectionId;
      }
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof showSection === 'function') {
    showSection(<?= json_encode($admin_active_section) ?>);
  }
  document.querySelectorAll('a.nav-item[href]').forEach(function (link) {
    const active = link.getAttribute('href') === <?= json_encode($admin_active_href) ?>;
    link.classList.toggle('nav-item-active', active);
    link.classList.toggle('text-neutral-400', !active);
    link.classList.toggle('text-white', active);
  });
});
</script>
    <script>
window.addEventListener('load', function () {
  document.documentElement.classList.remove('admin-page-loading');
});
</script>
    <?= page_runtime_bundle($flash_script) ?>
  </body>
</html>
