<?php
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/users/admin-data.php';
require_once __DIR__ . '/../data/categories-data.php';
require_once __DIR__ . '/../data/products-data.php';
require_once __DIR__ . '/../data/rentals-data.php';
require_once __DIR__ . '/../data/returns-data.php';
require_once __DIR__ . '/../data/activity-data.php';
require_once __DIR__ . '/../includes/flash.php';

$admin_active_section = 'categories';
$admin_active_href = 'categories.php';
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
    <title>LensCraft - Kategori</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
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
          <span class="hidden md:inline-block text-sm text-neutral-500 border-l border-neutral-800 pl-4">Kategori</span>
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
        <a href="index.php" class="nav-item nav-item-active flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all hover:bg-white/5" data-section="overview">
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
          
          <!-- User Modal -->
          <div id="category-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 modal-overlay">
            <div class="modal-panel rounded-3xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
              <div class="modal-header p-6 border-b border-neutral-800 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-white" id="category-modal-title">Tambah Kategori</h3>
                <button onclick="closeCategoryModal()" class="modal-close text-neutral-400 hover:text-white transition-colors">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <form id="category-form" class="p-6 space-y-4 modal-body-shell">
                <input type="hidden" id="category-id">
                <div>
                  <label class="block text-sm font-medium text-neutral-400 mb-2">Nama Kategori</label>
                  <input type="text" id="category-name" required maxlength="100" placeholder="Masukkan nama kategori" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-700">
                </div>
                <div>
                  <label class="block text-sm font-medium text-neutral-400 mb-2">Deskripsi</label>
                  <textarea id="category-description" rows="3" maxlength="500" placeholder="Masukkan deskripsi kategori" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-700"></textarea>
                </div>
                <div>
                  <label class="block text-sm font-medium text-neutral-400 mb-2">Ikon</label>
                  <select id="category-icon" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-700">
                    <option value="camera">Kamera</option>
                    <option value="lensa">Lensa</option>
                    <option value="video">Video</option>
                    <option value="audio">Audio</option>
                    <option value="tripod">Tripod</option>
                    <option value="light">Pencahayaan</option>
                    <option value="accessory">Aksesoris</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-neutral-400 mb-2">Warna</label>
                  <select id="category-color" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-700">
                    <option value="blue">Biru</option>
                    <option value="green">Hijau</option>
                    <option value="purple">Ungu</option>
                    <option value="yellow">Kuning</option>
                    <option value="red">Merah</option>
                    <option value="indigo">Indigo</option>
                    <option value="orange">Oranye</option>
                    <option value="teal">Teal</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-neutral-400 mb-2">Status</label>
                  <select id="category-status" required class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-700">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                  </select>
                </div>
                <div class="modal-actions">
                  <button type="button" onclick="closeCategoryModal()" class="flex-1 px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-300 hover:bg-neutral-700 transition-colors">
                    Batal
                  </button>
                  <button type="submit" class="flex-1 px-4 py-3 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-colors">
                    Simpan Kategori
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Inventaris Modal -->

          <!-- Content Sections -->
          <!-- Ringkasan Section -->
          <section id="categories" class="content-section">
            <div class="mb-8">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                  <h1 class="text-3xl md:text-4xl font-serif text-white mb-2">Kategori</h1>
                  <p class="text-neutral-400">Manage equipment categories and classifications.</p>
                </div>
                <button onclick="openCategoryModal()" class="px-6 py-3 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-all transform hover:scale-105 flex items-center gap-2 w-fit">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  Add Category
                </button>
              </div>
            </div>

            <!-- Kategori Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="categories-grid">
              <!-- Kategori will be populated by JavaScript -->
            </div>
          </section>
    </main>

    <script>
      // Sidebar toggle for mobile
      const sidebar = document.getElementById('sidebar');
      const sidebarToggle = document.getElementById('sidebar-toggle');
      const sidebarOverlay = document.getElementById('sidebar-overlay');
      const bindById = (id, event, handler) => {
        const element = document.getElementById(id);
        if (element) {
          element.addEventListener(event, handler);
        }
        return element;
      };

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

      function renderTableEmptyState(tbody, columnCount, options = {}) {
        const {
          title = 'Belum ada data',
          emptyMessage = 'Coba ubah filter atau tambah data baru.',
          filteredTitle = 'Data tidak ditemukan',
          filteredMessage = 'Coba ubah kata kunci pencarian atau reset filter.',
          isFiltered = false
        } = options;
        const heading = isFiltered ? filteredTitle : title;
        const copy = isFiltered ? filteredMessage : emptyMessage;

        tbody.innerHTML = `
          <tr>
            <td colspan="${columnCount}" class="px-6 py-10">
              <div class="flex flex-col items-center justify-center gap-4 rounded-2xl border border-dashed border-neutral-700 bg-neutral-950/60 px-6 py-10 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-neutral-700 bg-neutral-900 text-neutral-300">
                  <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16m-2 0v10a2 2 0 01-2 2H8a2 2 0 01-2-2V7m3-3h6a2 2 0 012 2v1H7V6a2 2 0 012-2z" />
                  </svg>
                </div>
                <div class="space-y-2">
                  <p class="text-base font-semibold text-white">${heading}</p>
                  <p class="mx-auto max-w-md text-sm leading-6 text-neutral-400">${copy}</p>
                </div>
              </div>
            </td>
          </tr>
        `;
      }

      // ============================================
      // USERS MANAGEMENT
      // ============================================

      // Sample user data (30 users)
      const users = <?= $admin_users_json ?>;

      let filteredPengguna = [...users];
      let usersCurrentPage = 1;
      const usersPerPage = 5;

      // Render user table
      function renderPenggunaTable() {
        const tbody = document.getElementById('users-table-body');
        const start = (usersCurrentPage - 1) * usersPerPage;
        const end = start + usersPerPage;
        const pagePengguna = filteredPengguna.slice(start, end);

        if (pagePengguna.length === 0) {
          renderTableEmptyState(tbody, 5, {
            title: 'Belum ada pengguna',
            filteredTitle: 'Pengguna tidak ditemukan',
            filteredMessage: 'Coba ubah kata kunci pencarian atau reset filter pengguna.',
            isFiltered: users.length > 0
          });
          document.getElementById('users-shown').textContent = '0';
          document.getElementById('users-total').textContent = filteredPengguna.length;
          renderPenggunaPagination();
          return;
        }

        tbody.innerHTML = pagePengguna.map(user => {
          const roleBadge = `<span class="badge ${getRoleBadgeClass(user.role)}">${capitalizeFirst(user.role)}</span>`;
          const actionButtons = `
            <button onclick="editUser(${user.id})" class="p-2 text-neutral-400 hover:text-white hover:bg-neutral-800 rounded transition-colors" title="Edit">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button onclick="deleteUser(${user.id})" class="p-2 text-neutral-400 hover:text-red-400 hover:bg-neutral-800 rounded transition-colors" title="Hapus">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          `;

          return `
            <tr class="sm:hidden">
              <td colspan="5" class="p-4">
                <div class="card-hover group">
                  <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                      <div class="w-12 h-12 bg-neutral-800 rounded-full flex items-center justify-center border border-neutral-700 flex-shrink-0">
                        <span class="text-sm font-medium text-neutral-300">${escapeHtml(user.name.charAt(0))}</span>
                      </div>
                      <div class="min-w-0">
                        <h4 class="text-sm font-semibold text-white truncate mobile-name-ellipsis mobile-card-title-ellipsis">${escapeHtml(user.name)}</h4>
                        <p class="text-xs text-neutral-400 truncate">${escapeHtml(user.email)}</p>
                      </div>
                    </div>
                    <div class="flex-shrink-0">${roleBadge}</div>
                  </div>

                  <div class="space-y-2 text-xs mb-3">
                    <div class="flex justify-between gap-3">
                      <span class="text-neutral-500">Joined</span>
                      <span class="text-neutral-200 text-right">${formatDate(user.joined)}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                      <span class="text-neutral-500">Last Aktif</span>
                      <span class="text-neutral-200 text-right">${user.lastAktif === 'Never' ? 'Never' : formatDateTime(user.lastAktif)}</span>
                    </div>
                  </div>

                  <div class="flex justify-end gap-2 pt-2 border-t border-neutral-800">
                    ${actionButtons}
                  </div>
                </div>
              </td>
            </tr>
            <tr class="hidden sm:table-row table-row-hover transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-neutral-800 rounded-full flex items-center justify-center border border-neutral-700">
                    <span class="text-sm font-medium text-neutral-300">${escapeHtml(user.name.charAt(0))}</span>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-white">${escapeHtml(user.name)}</p>
                    <p class="text-xs text-neutral-500">${escapeHtml(user.email)}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                ${roleBadge}
              </td>
              <td class="px-6 py-4 text-sm text-neutral-400">${formatDate(user.joined)}</td>
              <td class="px-6 py-4 text-sm text-neutral-400">${user.lastAktif === 'Never' ? 'Never' : formatDateTime(user.lastAktif)}</td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  ${actionButtons}
                </div>
              </td>
            </tr>
          `;
        }).join('');

        // Update pagination info
        document.getElementById('users-shown').textContent = pagePengguna.length;
        document.getElementById('users-total').textContent = filteredPengguna.length;

        renderPenggunaPagination();
      }

      // Get role badge class
      function getRoleBadgeClass(role) {
        switch(role) {
          case 'admin': return 'badge-danger';
          case 'petugas': return 'badge-info';
          case 'pelanggan': return 'badge-success';
          default: return 'badge-info';
        }
      }

      // Get status badge class
      function getStatusBadgeClass(status) {
        switch(status) {
          case 'aktif': return 'badge-success';
          case 'nonaktif': return 'badge-danger';
          case 'menunggu': return 'badge-warning';
          default: return 'badge-info';
        }
      }

      // Format date
      function formatDate(dateStr) {
        if (!dateStr || dateStr === 'Never') return dateStr || 'N/A';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
      }

      // Format date time
      function formatDateTime(dateStr) {
        if (!dateStr || dateStr === 'Never') return dateStr || 'N/A';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
      }

      // Capitalize first letter
      function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
      }

      // Escape HTML to prevent XSS
      function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      }

      // Render pagination
      function renderPenggunaPagination() {
        const totalPages = Math.ceil(filteredPengguna.length / usersPerPage);
        const container = document.getElementById('users-page-numbers');
        const prevBtn = document.getElementById('users-prev');
        const nextBtn = document.getElementById('users-next');

        container.innerHTML = '';

        // Simple pagination (show all page numbers for now)
        for (let i = 1; i <= totalPages; i++) {
          const btn = document.createElement('button');
          btn.className = `px-3 py-2 text-sm font-medium border rounded-lg transition-colors ${i === usersCurrentPage ? 'bg-white text-black border-neutral-700' : 'bg-neutral-800 border-neutral-700 text-neutral-300 hover:bg-neutral-700'}`;
          btn.textContent = i;
          btn.addEventListener('click', () => goToPenggunaPage(i));
          container.appendChild(btn);
        }

        prevBtn.disabled = usersCurrentPage === 1;
        nextBtn.disabled = usersCurrentPage === totalPages || totalPages === 0;
      }

      // Go to users page
      function goToPenggunaPage(page) {
        const totalPages = Math.ceil(filteredPengguna.length / usersPerPage);
        if (page < 1 || page > totalPages) return;
        usersCurrentPage = page;
        renderPenggunaTable();
      }

      // Filter users
      function filterPengguna() {
        const searchTerm = document.getElementById('user-search').value.toLowerCase();
        const roleFilter = document.getElementById('role-filter').value;

        filteredPengguna = users.filter(user => {
          const matchesCari = user.name.toLowerCase().includes(searchTerm) || user.email.toLowerCase().includes(searchTerm);
          const matchesRole = roleFilter === '' || user.role === roleFilter;
          return matchesCari && matchesRole;
        });

        usersCurrentPage = 1;
        renderPenggunaTable();
      }

      // Event listeners for filters
      bindById('user-search', 'input', filterPengguna);
      bindById('role-filter', 'change', filterPengguna);

      bindById('users-prev', 'click', () => goToPenggunaPage(usersCurrentPage - 1));
      bindById('users-next', 'click', () => goToPenggunaPage(usersCurrentPage + 1));

      // User modal (placeholder)
      function openUserModal(userId = null) {
        if (userId) {
          alert('Fitur edit pengguna akan dibuka di sini (belum diterapkan pada demo)');
        } else {
          alert('Add user functionality would open here (not implemented in demo)');
        }
      }

      function editUser(userId) {
        openUserModal(userId);
      }

      function deleteUser(userId) {
        if (confirm('Yakin ingin menghapus pengguna ini?')) {
          alert(`User ${userId} would be deleted (not implemented in demo)`);
        }
      }

      // Initialize users table when users section becomes visible
      const originalShowSection = showSection;
      showSection = function(sectionId) {
        originalShowSection(sectionId);
        if (sectionId === 'users') {
          renderPenggunaTable();
        }
      };

      // ============================================
      // CATEGORIES MANAGEMENT
      // ============================================

      // Sample categories data
      const categories = <?= $admin_categories_json ?>;

      // Icon SVGs
      const categoryIcons = {
        camera: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
        lens: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" /></svg>',
        video: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>',
        audio: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>',
        tripod: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>',
        light: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>',
        accessory: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>'
      };

      // Color classes
      const colorClasses = {
        blue: 'bg-amber-900/30 border-amber-800/50 text-amber-300',
        green: 'bg-green-900/30 border-green-800/50 text-green-400',
        purple: 'bg-purple-900/30 border-purple-800/50 text-purple-400',
        yellow: 'bg-yellow-900/30 border-yellow-800/50 text-yellow-400',
        red: 'bg-red-900/30 border-red-800/50 text-red-400',
        indigo: 'bg-indigo-900/30 border-indigo-800/50 text-indigo-400',
        orange: 'bg-orange-900/30 border-orange-800/50 text-orange-400',
        teal: 'bg-teal-900/30 border-teal-800/50 text-teal-400'
      };

      // Render categories grid
      function renderKategori() {
        const grid = document.getElementById('categories-grid');
        grid.innerHTML = categories.map(cat => `
          <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 card-hover animate-fade-in">
            <div class="flex items-start justify-between mb-4">
              <div class="w-14 h-14 ${colorClasses[cat.color]} rounded-xl flex items-center justify-center border">
                ${categoryIcons[cat.icon] || categoryIcons.accessory}
              </div>
              <div class="flex gap-2">
                <button onclick="editCategory(${cat.id})" class="p-2 text-neutral-400 hover:text-white hover:bg-neutral-800 rounded transition-colors" title="Edit">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button onclick="deleteCategory(${cat.id})" class="p-2 text-neutral-400 hover:text-red-400 hover:bg-neutral-800 rounded transition-colors" title="Hapus">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
            <h3 class="text-lg font-semibold text-white mb-2">${cat.name}</h3>
            <p class="text-sm text-neutral-400 mb-4 line-clamp-2">${cat.description}</p>
            <div class="flex items-center justify-between pt-4 border-t border-neutral-800">
              <div class="text-sm">
                <span class="text-neutral-500">Items: </span>
                <span class="font-medium text-white">${cat.itemCount}</span>
              </div>
              <span class="badge ${cat.status === 'aktif' ? 'badge-success' : 'badge-danger'}">${capitalizeFirst(cat.status)}</span>
            </div>
          </div>
        `).join('');
      }

      function openCategoryModal(categoryId = null) {
        if (categoryId) {
          alert(`Edit kategori ${categoryId} would open here (not implemented in demo)`);
        } else {
          alert('Add category functionality would open here (not implemented in demo)');
        }
      }

      function editCategory(categoryId) {
        openCategoryModal(categoryId);
      }

      function deleteCategory(categoryId) {
        if (confirm('Are you sure you want to delete this category?')) {
          alert(`Category ${categoryId} would be deleted (not implemented in demo)`);
        }
      }

      // Chain for categories section
      const originalShowSection2 = showSection;

      // Initialize categories when section becomes visible
      const originalShowSection3 = originalShowSection2;
      showSection = function(sectionId) {
        originalShowSection3(sectionId);
        if (sectionId === 'categories') {
          renderKategori();
        }
      };

      // ============================================
      // TOOLS & STOCK (INVENTORY) MANAGEMENT
      // ============================================

      // Sample inventory data (40 items)
      const inventory = <?= $admin_inventory_json ?>;

      let filteredInventaris = [...inventory];
      let inventoryCurrentPage = 1;
      const inventoryPerPage = 8;

      // Render inventory table
      function renderInventarisTable() {
        const tbody = document.getElementById('inventory-table-body');
        const start = (inventoryCurrentPage - 1) * inventoryPerPage;
        const end = start + inventoryPerPage;
        const pageItems = filteredInventaris.slice(start, end);

        if (pageItems.length === 0) {
          renderTableEmptyState(tbody, 5, {
            title: 'Belum ada produk',
            filteredTitle: 'Produk tidak ditemukan',
            filteredMessage: 'Coba ubah kata kunci pencarian atau reset filter produk.',
            isFiltered: inventory.length > 0
          });
          document.getElementById('inventory-shown').textContent = '0';
          document.getElementById('inventory-total').textContent = filteredInventaris.length;
          renderInventarisPagination();
          return;
        }

        tbody.innerHTML = pageItems.map(item => {
          const stockPercent = item.totalStock > 0 ? (item.stock / item.totalStock) * 100 : 0;
          const stockBarClass = item.stock === 0 ? 'bg-red-500' : item.stock <= item.totalStock * 0.3 ? 'bg-yellow-500' : 'bg-green-500';
          const categoryBadge = `<span class="badge badge-info">${capitalizeFirst(item.category)}</span>`;
          const actionButtons = `
            <button onclick="editInventaris(${item.id})" class="p-2 text-neutral-400 hover:text-white hover:bg-neutral-800 rounded transition-colors" title="Edit">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button onclick="viewInventaris(${item.id})" class="p-2 text-neutral-400 hover:text-white hover:bg-neutral-800 rounded transition-colors" title="View">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
          `;

          return `
            <tr class="sm:hidden">
              <td colspan="5" class="p-4">
                <div class="card-hover group">
                  <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                      <div class="w-12 h-12 bg-neutral-800 rounded-lg overflow-hidden border border-neutral-700 flex-shrink-0">
                        <img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" class="w-full h-full object-cover" onerror="this.src='../images/gear-placeholder.svg'">
                      </div>
                      <div class="min-w-0 flex-1">
                        <h4 class="text-sm font-semibold text-white truncate mobile-name-ellipsis mobile-card-title-ellipsis">${escapeHtml(item.name)}</h4>
                        <p class="text-xs text-neutral-400">${escapeHtml(item.brand)}</p>
                      </div>
                    </div>
                    <div class="flex-shrink-0">${categoryBadge}</div>
                  </div>

                  <div class="space-y-2 text-xs mb-3">
                    <div class="flex justify-between gap-3">
                      <span class="text-neutral-500">Category</span>
                      <span class="text-neutral-200 text-right">${capitalizeFirst(item.category)}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                      <span class="text-neutral-500">Daily Rate</span>
                      <span class="text-white font-semibold text-right">${window.formatCurrencyIDR(item.price)}/hari</span>
                    </div>
                    <div class="flex justify-between gap-3">
                      <span class="text-neutral-500">Stock</span>
                      <span class="text-neutral-200 text-right">${item.stock}/${item.totalStock}</span>
                    </div>
                    <div class="w-full bg-neutral-800 rounded-full h-2 mt-2">
                      <div class="h-2 rounded-full ${stockBarClass}" style="width: ${stockPercent}%"></div>
                    </div>
                  </div>

                  <div class="flex justify-end gap-2 pt-2 border-t border-neutral-800">
                    ${actionButtons}
                  </div>
                </div>
              </td>
            </tr>
            <tr class="hidden sm:table-row table-row-hover transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-16 h-16 bg-neutral-800 rounded-lg overflow-hidden border border-neutral-700 flex-shrink-0">
                    <img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" class="w-full h-full object-cover" onerror="this.src='../images/gear-placeholder.svg'">
                  </div>
                  <div>
                    <p class="text-sm font-medium text-white">${escapeHtml(item.name)}</p>
                    <p class="text-xs text-neutral-500">${escapeHtml(item.brand)}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                ${categoryBadge}
              </td>
              <td class="px-6 py-4 text-sm font-medium text-white">${window.formatCurrencyIDR(item.price)}<span class="text-xs text-neutral-500">/hari</span></td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div class="text-sm text-right w-16">
                    <span class="font-medium text-white">${item.stock}</span>
                    <span class="text-neutral-500">/</span>
                    <span class="text-neutral-400">${item.totalStock}</span>
                  </div>
                  <div class="flex-1 bg-neutral-800 rounded-full h-2">
                    <div class="h-2 rounded-full ${stockBarClass}" style="width: ${stockPercent}%"></div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  ${actionButtons}
                </div>
              </td>
            </tr>
          `;
        }).join('');

        document.getElementById('inventory-shown').textContent = pageItems.length;
        document.getElementById('inventory-total').textContent = filteredInventaris.length;

        renderInventarisPagination();
      }

      // Render pagination
      function renderInventarisPagination() {
        const totalPages = Math.ceil(filteredInventaris.length / inventoryPerPage);
        const container = document.getElementById('inventory-page-numbers');
        const prevBtn = document.getElementById('inventory-prev');
        const nextBtn = document.getElementById('inventory-next');

        container.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
          const btn = document.createElement('button');
          btn.className = `px-3 py-2 text-sm font-medium border rounded-lg transition-colors ${i === inventoryCurrentPage ? 'bg-white text-black border-neutral-700' : 'bg-neutral-800 border-neutral-700 text-neutral-300 hover:bg-neutral-700'}`;
          btn.textContent = i;
          btn.addEventListener('click', () => goToInventarisPage(i));
          container.appendChild(btn);
        }

        prevBtn.disabled = inventoryCurrentPage === 1;
        nextBtn.disabled = inventoryCurrentPage === totalPages || totalPages === 0;
      }

      // Go to inventory page
      function goToInventarisPage(page) {
        const totalPages = Math.ceil(filteredInventaris.length / inventoryPerPage);
        if (page < 1 || page > totalPages) return;
        inventoryCurrentPage = page;
        renderInventarisTable();
      }

      // Filter inventory
      function filterInventaris() {
        const searchTerm = document.getElementById('inventory-search').value.toLowerCase();
        const categoryFilter = document.getElementById('category-filter').value;
        filteredInventaris = inventory.filter(item => {
          const matchesCari = item.name.toLowerCase().includes(searchTerm) || item.brand.toLowerCase().includes(searchTerm);
          const matchesCategory = categoryFilter === '' || item.category === categoryFilter;
          return matchesCari && matchesCategory;
        });

        inventoryCurrentPage = 1;
        renderInventarisTable();
      }

      // Event listeners for filters
      bindById('inventory-search', 'input', filterInventaris);
      bindById('category-filter', 'change', filterInventaris);

      bindById('inventory-prev', 'click', () => goToInventarisPage(inventoryCurrentPage - 1));
      bindById('inventory-next', 'click', () => goToInventarisPage(inventoryCurrentPage + 1));

      // Inventaris modal functions (placeholder)
      function openInventarisModal() {
        alert('Add equipment functionality would open here (not implemented in demo)');
      }

      function editInventaris(itemId) {
        alert(`Edit peralatan ${itemId} would open here (not implemented in demo)`);
      }

      function viewInventaris(itemId) {
        alert(`View equipment ${itemId} details would open here (not implemented in demo)`);
      }

      // Initialize inventory when section becomes visible
      const originalShowSection4 = showSection;
      showSection = function(sectionId) {
        originalShowSection4(sectionId);
        if (sectionId === 'tools-stock') {
          renderInventarisTable();
        }
      };

      // ============================================
      // BORROWINGS MANAGEMENT
      // ============================================

      // Sample borrowings data (borrowing requests)
      const borrowingsData = <?= $admin_borrowings_json ?>;

      let filteredPeminjaman = [...borrowingsData];
      let borrowingsCurrentPage = 1;
      const borrowingsPerPage = 8;

      // Render borrowings table
      function renderPeminjamanTable() {
        const tbody = document.getElementById('borrowings-table-body');
        const start = (borrowingsCurrentPage - 1) * borrowingsPerPage;
        const end = start + borrowingsPerPage;
        const pageItems = filteredPeminjaman.slice(start, end);

        if (pageItems.length === 0) {
          renderTableEmptyState(tbody, 7, {
            title: 'Belum ada peminjaman',
            filteredTitle: 'Peminjaman tidak ditemukan',
            filteredMessage: 'Coba ubah kata kunci pencarian atau reset filter peminjaman.',
            isFiltered: borrowingsData.length > 0
          });
          document.getElementById('borrowings-shown').textContent = '0';
          document.getElementById('borrowings-total').textContent = filteredPeminjaman.length;
          renderPeminjamanPagination();
          return;
        }

        tbody.innerHTML = pageItems.map(trx => {
          const statusBadge = `<span class="badge ${getPeminjamanStatusBadgeClass(trx.status)}">${capitalizeFirst(trx.status)}</span>`;
          const actionButtons = `
            <button onclick="viewPeminjaman('${trx.id}')" class="p-2 text-neutral-400 hover:text-white hover:bg-neutral-800 rounded transition-colors" title="Lihat Detail">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
            <button onclick="editPeminjaman('${trx.id}')" class="p-2 text-neutral-400 hover:text-white hover:bg-neutral-800 rounded transition-colors" title="Edit">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button onclick="convertToPengembalian('${trx.id}')" class="p-2 text-neutral-400 hover:text-green-400 hover:bg-neutral-800 rounded transition-colors" title="Convert to Return">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </button>
          `;

          return `
            <tr class="sm:hidden">
              <td colspan="7" class="p-4">
                <div class="card-hover group">
                  <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                      <div class="w-12 h-12 bg-neutral-800 rounded-lg overflow-hidden flex-shrink-0">
                        <img src="${escapeHtml(trx.image || '../images/gear-placeholder.svg')}" alt="${escapeHtml(trx.equipment)}" class="w-full h-full object-cover">
                      </div>
                      <div class="min-w-0">
                        <h4 class="text-sm font-semibold text-white truncate mobile-name-ellipsis mobile-card-title-ellipsis">${escapeHtml(trx.equipment)}</h4>
                        <p class="text-xs text-neutral-400 mobile-name-ellipsis">${escapeHtml(trx.customer)}</p>
                        <p class="text-xs text-neutral-500 mt-0.5">${trx.id}</p>
                      </div>
                    </div>
                    <div class="flex-shrink-0">${statusBadge}</div>
                  </div>

                  <div class="space-y-2 text-xs mb-3">
                    <div class="flex justify-between gap-3">
                      <span class="text-neutral-500">Customer</span>
                      <span class="text-neutral-200 text-right mobile-name-ellipsis">${escapeHtml(trx.customer)}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                      <span class="text-neutral-500">Periode</span>
                      <span class="text-neutral-200 text-right">${formatDate(trx.startDate)} - ${formatDate(trx.endDate)}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                      <span class="text-neutral-500">Durasi</span>
                      <span class="text-neutral-200 text-right">${trx.days} day${trx.days > 1 ? 's' : ''}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                      <span class="text-neutral-500">Total</span>
                      <span class="text-white font-semibold text-right">${window.formatCurrencyIDR(trx.amount)}</span>
                    </div>
                  </div>

                  <div class="flex justify-end gap-2 pt-2 border-t border-neutral-800">
                    ${actionButtons}
                  </div>
                </div>
              </td>
            </tr>
            <tr class="hidden sm:table-row table-row-hover transition-colors">
              <td class="px-6 py-4">
                <span class="text-sm font-mono font-medium text-white">${trx.id}</span>
              </td>
              <td class="px-6 py-4">
                <div>
                  <p class="text-sm font-medium text-white">${escapeHtml(trx.customer)}</p>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-10 h-10 bg-neutral-800 rounded overflow-hidden flex-shrink-0">
                    <img src="${escapeHtml(trx.image || '../images/gear-placeholder.svg')}" alt="${escapeHtml(trx.equipment)}" class="w-full h-full object-cover">
                  </div>
                  <span class="text-sm text-neutral-300 truncate max-w-[150px]">${escapeHtml(trx.equipment)}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm">
                  <p class="text-neutral-300">${formatDate(trx.startDate)} - ${formatDate(trx.endDate)}</p>
                  <p class="text-xs text-neutral-500">${trx.days} day${trx.days > 1 ? 's' : ''}</p>
                </div>
              </td>
              <td class="px-6 py-4 text-sm font-semibold text-white">${window.formatCurrencyIDR(trx.amount)}</td>
              <td class="px-6 py-4">
                ${statusBadge}
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  ${actionButtons}
                </div>
              </td>
            </tr>
          `;
        }).join('');

        document.getElementById('borrowings-shown').textContent = pageItems.length;
        document.getElementById('borrowings-total').textContent = filteredPeminjaman.length;

        renderPeminjamanPagination();
      }


      // Get borrowings status badge class
      function getPeminjamanStatusBadgeClass(status) {
        switch(status) {
          case 'disetujui': return 'badge-info';
          case 'aktif': return 'badge-success';
          case 'selesai': return 'badge-success';
          case 'menunggu': return 'badge-warning';
          case 'dibatalkan': return 'badge-danger';
          case 'ditolak': return 'badge-danger';
          default: return 'badge-info';
        }
      }

      // Render pagination
      function renderPeminjamanPagination() {
        const totalPages = Math.ceil(filteredPeminjaman.length / borrowingsPerPage);
        const container = document.getElementById('borrowings-page-numbers');
        const prevBtn = document.getElementById('borrowings-prev');
        const nextBtn = document.getElementById('borrowings-next');

        container.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
          const btn = document.createElement('button');
          btn.className = `px-3 py-2 text-sm font-medium border rounded-lg transition-colors ${i === borrowingsCurrentPage ? 'bg-white text-black border-neutral-700' : 'bg-neutral-800 border-neutral-700 text-neutral-300 hover:bg-neutral-700'}`;
          btn.textContent = i;
          btn.addEventListener('click', () => goToPeminjamanPage(i));
          container.appendChild(btn);
        }

        prevBtn.disabled = borrowingsCurrentPage === 1;
        nextBtn.disabled = borrowingsCurrentPage === totalPages || totalPages === 0;
      }

      // Go to borrowings page
      function goToPeminjamanPage(page) {
        const totalPages = Math.ceil(filteredPeminjaman.length / borrowingsPerPage);
        if (page < 1 || page > totalPages) return;
        borrowingsCurrentPage = page;
        renderPeminjamanTable();
      }

      // Filter borrowings
      function filterPeminjaman() {
        const searchTerm = document.getElementById('borrowings-search').value.toLowerCase();

        filteredPeminjaman = borrowingsData.filter(trx => {
          const matchesCari = trx.id.toLowerCase().includes(searchTerm) ||
                               trx.customer.toLowerCase().includes(searchTerm) ||
                               trx.equipment.toLowerCase().includes(searchTerm);

          return matchesCari;
        });

        borrowingsCurrentPage = 1;
        renderPeminjamanTable();
      }

      // Event listeners for borrowings filters
      bindById('borrowings-search', 'input', filterPeminjaman);

      bindById('borrowings-prev', 'click', () => goToPeminjamanPage(borrowingsCurrentPage - 1));
      bindById('borrowings-next', 'click', () => goToPeminjamanPage(borrowingsCurrentPage + 1));

      // Peminjaman actions
      function viewPeminjaman(trxId) {
        const trx = borrowingsData.find(t => t.id === trxId);
        if (trx) {
          alert(`Borrowing Details:\n\nID: ${trx.id}\nCustomer: ${trx.customer}\nEquipment: ${trx.equipment}\nBorrowing Period: ${formatDate(trx.startDate)} - ${formatDate(trx.endDate)}\nDays: ${trx.days}\nAmount: ${window.formatCurrencyIDR(trx.amount)}\nStatus: ${capitalizeFirst(trx.status)}`);
        }
      }

      function editPeminjaman(trxId) {
        openTransactionModal(trxId);
      }

      function convertToPengembalian(trxId) {
        const trx = borrowingsData.find(t => t.id === trxId);
        if (trx && confirm(`Convert this borrowing to return for ${trx.customer}?`)) {
          // In a real app, this would create a return record
          alert(`Borrowing ${trxId} converted to return. Return ID would be generated.`);
        }
      }

      // ============================================
      // RETURNS MANAGEMENT
      // ============================================

      // Sample returns data
      const returnsData = <?= $admin_returns_json ?>;

      let filteredPengembalian = [...returnsData];
      let returnsCurrentPage = 1;
      const returnsPerPage = 8;

      // Render returns table
      function renderPengembalianTable() {
        const tbody = document.getElementById('returns-table-body');
        const start = (returnsCurrentPage - 1) * returnsPerPage;
        const end = start + returnsPerPage;
        const pageItems = filteredPengembalian.slice(start, end);

        if (pageItems.length === 0) {
          renderTableEmptyState(tbody, 6, {
            title: 'Belum ada pengembalian',
            filteredTitle: 'Pengembalian tidak ditemukan',
            filteredMessage: 'Coba ubah kata kunci pencarian atau reset filter pengembalian.',
            isFiltered: returnsData.length > 0
          });
          document.getElementById('returns-shown').textContent = '0';
          document.getElementById('returns-total').textContent = filteredPengembalian.length;
          renderPengembalianPagination();
          return;
        }

        tbody.innerHTML = pageItems.map(trx => {
          const statusBadge = `<span class="badge ${getPengembalianStatusBadgeClass(trx.status)}">${capitalizeFirst(trx.status)}</span>`;
          const actionButtons = `
            <button onclick="viewPengembalian('${trx.id}')" class="p-2 text-neutral-400 hover:text-white hover:bg-neutral-800 rounded transition-colors" title="Lihat Detail">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
            <button onclick="editPengembalian('${trx.id}')" class="p-2 text-neutral-400 hover:text-white hover:bg-neutral-800 rounded transition-colors" title="Edit">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
          `;

          return `
            <tr class="sm:hidden">
              <td colspan="6" class="p-4">
                <div class="card-hover group">
                  <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                      <div class="w-12 h-12 bg-neutral-800 rounded-lg overflow-hidden flex-shrink-0">
                        <img src="${escapeHtml(trx.image || '../images/gear-placeholder.svg')}" alt="${escapeHtml(trx.equipment)}" class="w-full h-full object-cover">
                      </div>
                      <div class="min-w-0">
                        <h4 class="text-sm font-semibold text-white truncate mobile-name-ellipsis mobile-card-title-ellipsis">${escapeHtml(trx.equipment)}</h4>
                        <p class="text-xs text-neutral-400 mobile-name-ellipsis">${escapeHtml(trx.customer)}</p>
                        <p class="text-xs text-neutral-500 mt-0.5">${trx.id}</p>
                      </div>
                    </div>
                    <div class="flex-shrink-0">${statusBadge}</div>
                  </div>

                  <div class="space-y-2 text-xs mb-3">
                    <div class="flex justify-between gap-3">
                      <span class="text-neutral-500">Customer</span>
                      <span class="text-neutral-200 text-right mobile-name-ellipsis">${escapeHtml(trx.customer)}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                      <span class="text-neutral-500">Tanggal Return</span>
                      <span class="text-neutral-200 text-right">${formatDate(trx.returnDate)}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                      <span class="text-neutral-500">Equipment</span>
                      <span class="text-neutral-200 text-right mobile-name-ellipsis">${escapeHtml(trx.equipment)}</span>
                    </div>
                  </div>

                  <div class="flex justify-end gap-2 pt-2 border-t border-neutral-800">
                    ${actionButtons}
                  </div>
                </div>
              </td>
            </tr>
            <tr class="hidden sm:table-row table-row-hover transition-colors">
              <td class="px-6 py-4">
                <span class="text-sm font-mono font-medium text-white">${trx.id}</span>
              </td>
              <td class="px-6 py-4">
                <div>
                  <p class="text-sm font-medium text-white">${escapeHtml(trx.customer)}</p>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-10 h-10 bg-neutral-800 rounded overflow-hidden flex-shrink-0">
                    <img src="${escapeHtml(trx.image || '../images/gear-placeholder.svg')}" alt="${escapeHtml(trx.equipment)}" class="w-full h-full object-cover">
                  </div>
                  <span class="text-sm text-neutral-300 truncate max-w-[150px]">${escapeHtml(trx.equipment)}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-neutral-300">${formatDate(trx.returnDate)}</td>
              <td class="px-6 py-4">
                ${statusBadge}
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  ${actionButtons}
                </div>
              </td>
            </tr>
          `;
        }).join('');

        document.getElementById('returns-shown').textContent = pageItems.length;
        document.getElementById('returns-total').textContent = filteredPengembalian.length;

        renderPengembalianPagination();
      }


      // Get returns status badge class
      function getPengembalianStatusBadgeClass(status) {
        switch(status) {
          case 'returned': return 'badge-success';
          case 'menunggu': return 'badge-warning';
          case 'overdue': return 'badge-danger';
          default: return 'badge-info';
        }
      }

      // Render pagination
      function renderPengembalianPagination() {
        const totalPages = Math.ceil(filteredPengembalian.length / returnsPerPage);
        const container = document.getElementById('returns-page-numbers');
        const prevBtn = document.getElementById('returns-prev');
        const nextBtn = document.getElementById('returns-next');

        container.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
          const btn = document.createElement('button');
          btn.className = `px-3 py-2 text-sm font-medium border rounded-lg transition-colors ${i === returnsCurrentPage ? 'bg-white text-black border-neutral-700' : 'bg-neutral-800 border-neutral-700 text-neutral-300 hover:bg-neutral-700'}`;
          btn.textContent = i;
          btn.addEventListener('click', () => goToPengembalianPage(i));
          container.appendChild(btn);
        }

        prevBtn.disabled = returnsCurrentPage === 1;
        nextBtn.disabled = returnsCurrentPage === totalPages || totalPages === 0;
      }

      // Go to returns page
      function goToPengembalianPage(page) {
        const totalPages = Math.ceil(filteredPengembalian.length / returnsPerPage);
        if (page < 1 || page > totalPages) return;
        returnsCurrentPage = page;
        renderPengembalianTable();
      }

      // Filter returns
      function filterPengembalian() {
        const searchTerm = document.getElementById('returns-search').value.toLowerCase();
        const statusFilter = document.getElementById('returns-status-filter').value;

        filteredPengembalian = returnsData.filter(trx => {
          const matchesCari = trx.id.toLowerCase().includes(searchTerm) ||
                               trx.customer.toLowerCase().includes(searchTerm) ||
                               trx.equipment.toLowerCase().includes(searchTerm);
          const matchesStatus = statusFilter === '' || trx.status === statusFilter;
          return matchesCari && matchesStatus;
        });

        returnsCurrentPage = 1;
        renderPengembalianTable();
      }

      // Event listeners for returns filters
      bindById('returns-search', 'input', filterPengembalian);
      bindById('returns-status-filter', 'change', filterPengembalian);

      bindById('returns-prev', 'click', () => goToPengembalianPage(returnsCurrentPage - 1));
      bindById('returns-next', 'click', () => goToPengembalianPage(returnsCurrentPage + 1));

      // Pengembalian actions
      function viewPengembalian(trxId) {
        const trx = returnsData.find(t => t.id === trxId);
        if (trx) {
          alert(`Return Details:\n\nID: ${trx.id}\nCustomer: ${trx.customer}\nEquipment: ${trx.equipment}\nReturn Date: ${formatDate(trx.returnDate)}\nStatus: ${capitalizeFirst(trx.status)}\nNotes: ${trx.notes}`);
        }
      }

      function editPengembalian(trxId) {
        alert(`Edit pengembalian ${trxId} would open here (not implemented in demo)`);
      }

      // Initialize borrowings and returns when sections become visible
      const originalShowSection5 = showSection;
      showSection = function(sectionId) {
        originalShowSection5(sectionId);
        if (sectionId === 'borrowings') {
          renderPeminjamanTable();
        } else if (sectionId === 'returns') {
          renderPengembalianTable();
        }
      };

      // ============================================
      // ACTIVITY LOG
      // ============================================

      // Sample activity data (50 activities)
      const activities = <?= $admin_activities_json ?>;

      let filteredActivities = [...activities];
      let activitiesCurrentPage = 1;
      const activitiesPerPage = 10;

      // Activity icons
      const activityIcons = {
        'shopping-cart': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
        'user-plus': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>',
        'package': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>',
        'check-circle': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        'shield': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>',
        'wrench': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
        'pelanggan': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>',
        'x-circle': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>',
        'database': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" /></svg>',
        'alert': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>',
        'user-check': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>'
      };

      // Color classes for icons
      const activityColorClasses = {
        blue: 'bg-amber-900/30 text-amber-300 border-amber-800/50',
        green: 'bg-green-900/30 text-green-400 border-green-800/50',
        purple: 'bg-purple-900/30 text-purple-400 border-purple-800/50',
        yellow: 'bg-yellow-900/30 text-yellow-400 border-yellow-800/50',
        orange: 'bg-orange-900/30 text-orange-400 border-orange-800/50',
        red: 'bg-red-900/30 text-red-400 border-red-800/50',
        indigo: 'bg-indigo-900/30 text-indigo-400 border-indigo-800/50'
      };

      // Render activity timeline
      function renderActivityTimeline() {
        const timeline = document.getElementById('activity-timeline');
        const start = (activitiesCurrentPage - 1) * activitiesPerPage;
        const end = start + activitiesPerPage;
        const pageActivities = filteredActivities.slice(start, end);

        timeline.innerHTML = pageActivities.map(activity => `
          <div class="flex gap-4 pb-6 border-b border-neutral-800 last:border-b-0 animate-fade-in">
            <!-- Icon -->
            <div class="flex-shrink-0">
              <div class="w-10 h-10 rounded-full ${activityColorClasses[activity.color]} flex items-center justify-center border">
                ${activityIcons[activity.icon] || activityIcons.alert}
              </div>
            </div>
            
            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2 mb-1">
                <div>
                  <p class="text-sm font-medium text-white">${activity.action}</p>
                  <p class="text-xs text-neutral-500">
                    by ${activity.actorName} • ${formatDateTime(activity.timestamp)}
                  </p>
                </div>
                <span class="badge badge-info text-xs capitalize">${activity.type}</span>
              </div>
              <div class="bg-neutral-800/50 rounded-lg p-3 mt-2">
                <p class="text-sm text-neutral-300">
                  <span class="font-medium">Target:</span> ${activity.target}
                </p>
                <p class="text-sm text-neutral-400 mt-1">
                  ${activity.details}
                </p>
              </div>
            </div>
          </div>
        `).join('');

        document.getElementById('activity-shown').textContent = pageActivities.length;
        document.getElementById('activity-total').textContent = filteredActivities.length;

        renderActivityPagination();
      }

      // Render pagination
      function renderActivityPagination() {
        const totalPages = Math.ceil(filteredActivities.length / activitiesPerPage);
        const container = document.getElementById('activity-page-numbers');
        const prevBtn = document.getElementById('activity-prev');
        const nextBtn = document.getElementById('activity-next');

        container.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
          const btn = document.createElement('button');
          btn.className = `px-3 py-2 text-sm font-medium border rounded-lg transition-colors ${i === activitiesCurrentPage ? 'bg-white text-black border-neutral-700' : 'bg-neutral-800 border-neutral-700 text-neutral-300 hover:bg-neutral-700'}`;
          btn.textContent = i;
          btn.addEventListener('click', () => goToActivityPage(i));
          container.appendChild(btn);
        }

        prevBtn.disabled = activitiesCurrentPage === 1;
        nextBtn.disabled = activitiesCurrentPage === totalPages || totalPages === 0;
      }

      // Go to activity page
      function goToActivityPage(page) {
        const totalPages = Math.ceil(filteredActivities.length / activitiesPerPage);
        if (page < 1 || page > totalPages) return;
        activitiesCurrentPage = page;
        renderActivityTimeline();
      }

      // Filter activities
      function filterActivities() {
        const searchTerm = document.getElementById('activity-search').value.toLowerCase();
        const typeFilter = document.getElementById('activity-type-filter').value;
        const dateFilter = document.getElementById('activity-date-filter').value;

        filteredActivities = activities.filter(activity => {
          const matchesCari = activity.action.toLowerCase().includes(searchTerm) ||
                               activity.actorName.toLowerCase().includes(searchTerm) ||
                               activity.target.toLowerCase().includes(searchTerm);
          const matchesType = typeFilter === '' || activity.type === typeFilter;
          
          // Date filtering
          let matchesDate = true;
          if (dateFilter) {
            const activityDate = new Date(activity.timestamp);
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            
            switch(dateFilter) {
              case 'today':
                matchesDate = activityDate >= today && activityDate < new Date(today.getTime() + 24 * 60 * 60 * 1000);
                break;
              case 'week':
                const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                matchesDate = activityDate >= weekAgo && activityDate < new Date(today.getTime() + 24 * 60 * 60 * 1000);
                break;
              case 'month':
                matchesDate = activityDate.getMonth() === now.getMonth() && activityDate.getFullYear() === now.getFullYear();
                break;
            }
          }
          
          return matchesCari && matchesType && matchesDate;
        });

        activitiesCurrentPage = 1;
        renderActivityTimeline();
      }

      // Event listeners for filters
      bindById('activity-search', 'input', filterActivities);
      bindById('activity-type-filter', 'change', filterActivities);
      bindById('activity-date-filter', 'change', filterActivities);

      bindById('activity-prev', 'click', () => goToActivityPage(activitiesCurrentPage - 1));
      bindById('activity-next', 'click', () => goToActivityPage(activitiesCurrentPage + 1));

      // Initialize activity log when section becomes visible
      const originalShowSection6 = showSection;
      showSection = function(sectionId) {
        originalShowSection6(sectionId);
        if (sectionId === 'activity-log') {
          renderActivityTimeline();
        }
      };

      // ============================================
      // CRUD OPERATIONS & DATA PERSISTENCE
      // ============================================

      // Generate unique ID
      function generateId(array) {
        return array.length > 0 ? Math.max(...array.map(item => item.id)) + 1 : 1;
      }

      // Activity logging
      function logActivity(type, user, action, target, details) {
        const iconMap = {
          'transaction': 'shopping-cart',
          'pelanggan': 'pelanggan',
          'inventory': 'package',
          'system': 'database',
          'security': 'shield'
        };
        const colorMap = {
          'transaction': 'blue',
          'pelanggan': 'purple',
          'inventory': 'green',
          'system': 'indigo',
          'security': 'red'
        };

        activities.unshift({
          id: generateId(activities),
          type: type,
          user: user,
          action: action,
          target: target,
          details: details,
          timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19),
          icon: iconMap[type] || 'alert',
          color: colorMap[type] || 'yellow'
        });

        // Keep only last 100 activities
        if (activities.length > 100) {
          activities.pop();
        }

        // Refresh activity log if visible
        if (document.getElementById('activity-log').classList.contains('hidden') === false) {
          renderActivityTimeline();
        }
      }

      // ============================================
      // USERS CRUD
      // ============================================

      // Open user modal
      function openUserModal(userId = null) {
        const modal = document.getElementById('user-modal');
        const form = document.getElementById('user-form');
        const title = document.getElementById('user-modal-title');
        
        form.reset();
        document.getElementById('user-id').value = '';

        if (userId) {
          const user = users.find(u => u.id === userId);
          if (user) {
            title.textContent = 'Edit Pengguna';
            document.getElementById('user-id').value = user.id;
            document.getElementById('user-name').value = user.name;
            document.getElementById('user-email').value = user.email;
            document.getElementById('user-role').value = user.role;
            document.getElementById('user-status').value = user.status;
          }
        } else {
          title.textContent = 'Tambah Pengguna';
        }

        modal.classList.remove('hidden');
      }

      // Close user modal
      function closeUserModal() {
        document.getElementById('user-modal').classList.add('hidden');
      }

      // Save user
      bindById('user-form', 'submit', function(e) {
        e.preventDefault();
        
        const id = document.getElementById('user-id').value;
        const name = document.getElementById('user-name').value;
        const email = document.getElementById('user-email').value;
        const role = document.getElementById('user-role').value;
        const status = document.getElementById('user-status').value;

        if (id) {
          // Edit existing user
          const user = users.find(u => u.id === parseInt(id));
          if (user) {
            user.name = name;
            user.email = email;
            user.role = role;
            user.status = status;
            logActivity('pelanggan', 'Admin', 'Pengguna diperbarui', name, `Role: ${role}, Status: ${status}`);
          }
        } else {
          // Add new user
          const newUser = {
            id: generateId(users),
            name: name,
            email: email,
            role: role,
            status: status,
            joined: new Date().toISOString().split('T')[0],
            lastAktif: 'Never'
          };
          users.push(newUser);
          logActivity('pelanggan', 'Admin', 'Pengguna dibuat', name, `Role: ${role}, Status: ${status}`);
        }

        closeUserModal();
        renderPenggunaTable();
      });

      function editUser(userId) {
        openUserModal(userId);
      }

      function deleteUser(userId) {
        if (confirm('Yakin ingin menghapus pengguna ini?')) {
          const user = users.find(u => u.id === userId);
          if (user) {
            const userIndex = users.findIndex(u => u.id === userId);
            if (userIndex !== -1) {
              users.splice(userIndex, 1);
              logActivity('pelanggan', 'Admin', 'Pengguna dihapus', user.name, 'Pengguna dihapus dari sistem');
              renderPenggunaTable();
            }
          }
        }
      }

      // ============================================
      // CATEGORIES CRUD
      // ============================================

      // Open category modal
      function openCategoryModal(categoryId = null) {
        const modal = document.getElementById('category-modal');
        const form = document.getElementById('category-form');
        const title = document.getElementById('category-modal-title');
        
        form.reset();
        document.getElementById('category-id').value = '';

        if (categoryId) {
          const category = categories.find(c => c.id === categoryId);
          if (category) {
            title.textContent = 'Edit Kategori';
            document.getElementById('category-id').value = category.id;
            document.getElementById('category-name').value = category.name;
            document.getElementById('category-description').value = category.description;
            document.getElementById('category-icon').value = category.icon;
            document.getElementById('category-color').value = category.color;
            document.getElementById('category-status').value = category.status;
          }
        } else {
          title.textContent = 'Add Category';
        }

        modal.classList.remove('hidden');
      }

      // Close category modal
      function closeCategoryModal() {
        document.getElementById('category-modal').classList.add('hidden');
      }

      // Save category
      bindById('category-form', 'submit', function(e) {
        e.preventDefault();
        
        const id = document.getElementById('category-id').value;
        const name = document.getElementById('category-name').value;
        const description = document.getElementById('category-description').value;
        const icon = document.getElementById('category-icon').value;
        const color = document.getElementById('category-color').value;
        const status = document.getElementById('category-status').value;

        if (id) {
          // Edit existing category
          const category = categories.find(c => c.id === parseInt(id));
          if (category) {
            category.name = name;
            category.description = description;
            category.icon = icon;
            category.color = color;
            category.status = status;
            logActivity('inventory', 'Admin', 'Updated category', name, `Status: ${status}`);
          }
        } else {
          // Add new category
          const newCategory = {
            id: generateId(categories),
            name: name,
            description: description,
            icon: icon,
            color: color,
            itemCount: 0,
            status: status
          };
          categories.push(newCategory);
          logActivity('inventory', 'Admin', 'Created category', name, `Status: ${status}`);
        }

        closeCategoryModal();
        renderKategori();
      });

      function editCategory(categoryId) {
        openCategoryModal(categoryId);
      }

      function deleteCategory(categoryId) {
        if (confirm('Are you sure you want to delete this category?')) {
          const category = categories.find(c => c.id === categoryId);
          if (category) {
            const categoryIndex = categories.findIndex(c => c.id === categoryId);
            if (categoryIndex !== -1) {
              categories.splice(categoryIndex, 1);
              logActivity('inventory', 'Admin', 'Hapusd category', category.name, 'Kategori dihapus');
              renderKategori();
            }
          }
        }
      }

      // ============================================
      // INVENTORY CRUD
      // ============================================

      // Populate equipment dropdown for peminjaman
      function populateEquipmentDropdown() {
        const select = document.getElementById('transaction-equipment');
        if (!select) return;
        select.innerHTML = '<option value="">Pilih Peralatan</option>' +
          inventory.map(item => `<option value="${item.id}">${item.name} (${item.brand})</option>`).join('');
      }

      // Open inventory modal
      function openInventarisModal(itemId = null) {
        const modal = document.getElementById('inventory-modal');
        const form = document.getElementById('inventory-form');
        const title = document.getElementById('inventory-modal-title');
        const previewImage = document.getElementById('inventory-preview-image');
        const existingImageInput = document.getElementById('inventory-existing-image');
        const imageFileInput = document.getElementById('inventory-image-file');
        
        form.reset();
        document.getElementById('inventory-id').value = '';
        existingImageInput.value = '../images/gear-placeholder.svg';
        previewImage.src = '../images/gear-placeholder.svg';
        imageFileInput.value = '';

        if (itemId) {
          const item = inventory.find(i => i.id === itemId);
          if (item) {
            title.textContent = 'Edit Peralatan';
            document.getElementById('inventory-id').value = item.id;
            document.getElementById('inventory-name').value = item.name;
            document.getElementById('inventory-brand').value = item.brand;
            document.getElementById('inventory-category').value = item.category;
            document.getElementById('inventory-status').value = item.status === 'maintenance' ? 'nonaktif' : 'aktif';
            document.getElementById('inventory-price').value = item.price;
            document.getElementById('inventory-total-stock').value = item.totalStock;
            document.getElementById('inventory-stock').value = item.stock;
            document.getElementById('inventory-discount').value = item.discount || 0;
            document.getElementById('inventory-description').value = item.description || '';
            existingImageInput.value = item.image || '../images/gear-placeholder.svg';
            previewImage.src = item.image || '../images/gear-placeholder.svg';
            form.action = '../process/admin-produk-edit.php';
          }
        } else {
          title.textContent = 'Add Equipment';
          document.getElementById('inventory-description').value = '';
          form.action = '../process/admin-produk-tambah.php';
        }

        modal.classList.remove('hidden');
      }

      // Close inventory modal
      function closeInventarisModal() {
        document.getElementById('inventory-modal').classList.add('hidden');
      }

      const inventoryImageInput = document.getElementById('inventory-image-file');
      if (inventoryImageInput) {
        inventoryImageInput.addEventListener('change', function (event) {
          const file = event.target.files && event.target.files[0];
          const previewImage = document.getElementById('inventory-preview-image');
          if (!file || !previewImage) return;

          const reader = new FileReader();
          reader.onload = function (readerEvent) {
            previewImage.src = readerEvent.target && readerEvent.target.result ? readerEvent.target.result : '../images/gear-placeholder.svg';
          };
          reader.readAsDataURL(file);
        });
      }

      function editInventaris(itemId) {
        openInventarisModal(itemId);
      }

      function viewInventaris(itemId) {
        const item = inventory.find(i => i.id === itemId);
        if (item) {
          alert(`Equipment Details:\n\nName: ${item.name}\nBrand: ${item.brand}\nCategory: ${capitalizeFirst(item.category)}\nStatus: ${capitalizeFirst(item.status)}\nDaily Rate: ${window.formatCurrencyIDR(item.price)}\nStock: ${item.stock}/${item.totalStock}`);
        }
      }

      // ============================================
      // TRANSACTIONS CRUD
      // ============================================

      // Open transaction modal
      function openTransactionModal(trxId = null) {
        const modal = document.getElementById('transaction-modal');
        const form = document.getElementById('transaction-form');
        const title = document.getElementById('transaction-modal-title');
        
        form.reset();
        document.getElementById('transaction-id').value = '';
        populateEquipmentDropdown();

        if (trxId) {
          const trx = borrowingsData.find(t => t.id === trxId);
          if (trx) {
            title.textContent = 'Edit Peminjaman';
            document.getElementById('transaction-id').value = trx.id;
            document.getElementById('transaction-customer').value = trx.customer;
            document.getElementById('transaction-equipment').value = trx.productId || inventory.find(i => i.name === trx.equipment)?.id || '';
            document.getElementById('transaction-start-date').value = trx.startDate;
            document.getElementById('transaction-end-date').value = trx.endDate;
            document.getElementById('transaction-status').value = trx.rawStatus || 'menunggu';
          }
        } else {
          title.textContent = 'Add Peminjaman';
        }

        modal.classList.remove('hidden');
      }

      // Close transaction modal
      function closeTransactionModal() {
        document.getElementById('transaction-modal').classList.add('hidden');
      }

      // Save transaction
      bindById('transaction-form', 'submit', function(e) {
        e.preventDefault();
        
        const id = document.getElementById('transaction-id').value;
        const customer = document.getElementById('transaction-customer').value;
        const equipmentId = parseInt(document.getElementById('transaction-equipment').value);
        const startDate = document.getElementById('transaction-start-date').value;
        const endDate = document.getElementById('transaction-end-date').value;
        const status = document.getElementById('transaction-status').value;

        const equipmentItem = inventory.find(i => i.id === equipmentId);
        if (!equipmentItem) {
          alert('Please select valid equipment');
          return;
        }

        // Calculate days and amount
        const start = new Date(startDate);
        const end = new Date(endDate);
        const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        const amount = days * equipmentItem.price;

        if (id) {
          // Edit existing peminjaman
          // Edit existing borrowing
          const trx = borrowingsData.find(t => t.id === id);
          if (trx) {
            trx.customer = customer;
            trx.equipment = equipmentItem.name;
            trx.startDate = startDate;
            trx.endDate = endDate;
            trx.days = days;
            trx.amount = amount;
            trx.status = status;
            logActivity('transaction', 'Admin', 'Updated borrowing', trx.id, `Customer: ${customer}, Status: ${status}`);
          }
        } else {
          // Add new borrowing
          const newTrx = {
            id: 'REQ' + String(borrowingsData.length + 1).padStart(3, '0'),
            customer: customer,
            equipment: equipmentItem.name,
            startDate: startDate,
            endDate: endDate,
            days: days,
            amount: amount,
            status: status
          };
          borrowingsData.push(newTrx);
          logActivity('transaction', 'Admin', 'Peminjaman dibuat', newTrx.id, `Customer: ${customer}, Amount: ${window.formatCurrencyIDR(amount)}`);
        }
        closeTransactionModal();
        renderPeminjamanTable();
      });

      function viewTransaction(trxId) {
        const trx = borrowingsData.find(t => t.id === trxId);
        if (trx) {
          alert(`Borrowing Details:\n\nID: ${trx.id}\nCustomer: ${trx.customer}\nEquipment: ${trx.equipment}\nBorrowing Period: ${formatDate(trx.startDate)} - ${formatDate(trx.endDate)}\nDays: ${trx.days}\nAmount: ${window.formatCurrencyIDR(trx.amount)}\nStatus: ${capitalizeFirst(trx.status)}`);
        }
      }

      function editTransaction(trxId) {
        openTransactionModal(trxId);
      }
      // Update "Add" buttons to open modals
      const originalOpenUserModal = openUserModal;
      const originalOpenCategoryModal = openCategoryModal;
      const originalOpenInventarisModal = openInventarisModal;

      // Override the alert-based modals with actual CRUD
      // (Already implemented above)

      // Initialize equipment dropdown when page loads
      document.addEventListener('DOMContentLoaded', function() {
        populateEquipmentDropdown();
      });

      // User filter dropdown toggle
      const userFilterBtn = document.getElementById('user-filter-btn');
      const userFilterDropdown = document.getElementById('user-filter-dropdown');
      if (userFilterBtn && userFilterDropdown) {
        userFilterBtn.addEventListener('click', () => {
          userFilterDropdown.classList.toggle('hidden');
        });
        document.addEventListener('click', (e) => {
          if (!userFilterDropdown.contains(e.target) && !userFilterBtn.contains(e.target)) {
            userFilterDropdown.classList.add('hidden');
          }
        });
      }

      // Inventaris filter dropdown toggle
      const inventoryFilterBtn = document.getElementById('inventory-filter-btn');
      const inventoryFilterDropdown = document.getElementById('inventory-filter-dropdown');
      if (inventoryFilterBtn && inventoryFilterDropdown) {
        inventoryFilterBtn.addEventListener('click', () => {
          inventoryFilterDropdown.classList.toggle('hidden');
        });
        document.addEventListener('click', (e) => {
          if (!inventoryFilterDropdown.contains(e.target) && !inventoryFilterBtn.contains(e.target)) {
            inventoryFilterDropdown.classList.add('hidden');
          }
        });
      }
      // Pengembalian filter dropdown toggle
      const returnsFilterBtn = document.getElementById('returns-filter-btn');
      const returnsFilterDropdown = document.getElementById('returns-filter-dropdown');
      if (returnsFilterBtn && returnsFilterDropdown) {
        returnsFilterBtn.addEventListener('click', () => {
          returnsFilterDropdown.classList.toggle('hidden');
        });
        document.addEventListener('click', (e) => {
          if (!returnsFilterDropdown.contains(e.target) && !returnsFilterBtn.contains(e.target)) {
            returnsFilterDropdown.classList.add('hidden');
          }
        });
      }
      // Activity filter dropdown toggle
      const activityFilterBtn = document.getElementById('activity-filter-btn');
      const activityFilterDropdown = document.getElementById('activity-filter-dropdown');
      if (activityFilterBtn && activityFilterDropdown) {
        activityFilterBtn.addEventListener('click', () => {
          activityFilterDropdown.classList.toggle('hidden');
        });
        document.addEventListener('click', (e) => {
          if (!activityFilterDropdown.contains(e.target) && !activityFilterBtn.contains(e.target)) {
            activityFilterDropdown.classList.add('hidden');
          }
        });
      }

    </script>
  <script>
      function postAdminForm(action, payload) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = action;
        payload.csrf_token = <?= json_encode(csrf_token()) ?>;

        Object.keys(payload).forEach(function (key) {
          if (payload[key] === undefined || payload[key] === null) return;
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
        const userForm = document.getElementById('user-form');
        if (userForm) {
          userForm.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();

            const id = document.getElementById('user-id').value;
            const email = document.getElementById('user-email').value;

            postAdminForm(id ? '../process/admin-user-edit.php' : '../process/admin-user-tambah.php', {
              id: id,
              fullname: document.getElementById('user-name').value,
              email: email,
              username: email.split('@')[0] || 'pelanggan',
              role: document.getElementById('user-role').value,
              status: document.getElementById('user-status').value
            });
          }, true);
        }

        const categoryForm = document.getElementById('category-form');
        if (categoryForm) {
          categoryForm.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();

            const id = document.getElementById('category-id').value;

            postAdminForm(id ? '../process/admin-kategori-edit.php' : '../process/admin-kategori-tambah.php', {
              id: id,
              name: document.getElementById('category-name').value,
              description: document.getElementById('category-description').value,
              icon: document.getElementById('category-icon').value,
              color: document.getElementById('category-color').value,
              status: document.getElementById('category-status').value
            });
          }, true);
        }

        const inventoryForm = document.getElementById('inventory-form');
        if (inventoryForm) {
          inventoryForm.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
            inventoryForm.submit();
          }, true);
        }

        const transactionForm = document.getElementById('transaction-form');
        if (transactionForm) {
          transactionForm.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();

            const rentalCode = document.getElementById('transaction-id').value;
            const startDate = document.getElementById('transaction-start-date').value;
            const endDate = document.getElementById('transaction-end-date').value;
            const start = new Date(startDate);
            const end = new Date(endDate);
            const totalDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
            const payload = {
              customer: document.getElementById('transaction-customer').value,
              product_id: document.getElementById('transaction-equipment').value,
              start_date: startDate,
              end_date: endDate,
              total_days: totalDays > 0 ? totalDays : 1,
              delivery_method: 'ambil_sendiri',
              status: document.getElementById('transaction-status').value
            };

            if (rentalCode) {
              payload.rental_code = rentalCode;
              postAdminForm('../process/admin-peminjaman-edit.php', payload);
              return;
            }

            postAdminForm('../process/admin-peminjaman-tambah.php', payload);
          }, true);
        }
      });

      function deleteUser(userId) {
        postAdminForm('../process/admin-user-hapus.php', { id: userId });
      }

      function deleteCategory(categoryId) {
        postAdminForm('../process/admin-kategori-hapus.php', { id: categoryId });
      }

      function deleteItem(itemId) {
        postAdminForm('../process/admin-produk-hapus.php', { id: itemId });
      }

      function adminDetailRows(rows) {
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

                  function openAdminDetailModal(config) {
        const badges = (config.badges || []).map((badge) => `<span class="badge badge-info flex-shrink-0 text-xs">${badge}</span>`).join('');
        const noteHtml = config.note ? `
          <div class="bg-neutral-800/50 border border-neutral-700 rounded-xl p-3 sm:p-4">
            <h5 class="text-xs sm:text-sm font-semibold text-neutral-300 mb-2 sm:mb-3">Catatan</h5>
            <p class="text-xs sm:text-sm text-neutral-400 leading-relaxed">${config.note}</p>
          </div>
        ` : '';

        document.getElementById('admin-detail-modal-title').textContent = config.dialogTitle || 'Detail';
        document.getElementById('admin-detail-modal-subtitle').textContent = config.kicker || '';
        document.getElementById('admin-detail-modal-body').innerHTML = `
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
        const modal = document.getElementById('admin-detail-modal');
        const modalContent = document.getElementById('admin-detail-modal-content');
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.remove('opacity-0');
        modalContent.classList.remove('opacity-0', 'scale-95');
        modalContent.classList.add('scale-100');
      }

      function closeAdminDetailModal() {
        const modal = document.getElementById('admin-detail-modal');
        const modalContent = document.getElementById('admin-detail-modal-content');
        modal.classList.add('opacity-0');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
          modal.classList.add('hidden');
        }, 200);
      }



      function openAdminReturnModal(config = {}) {
        document.getElementById('admin-return-modal-title').textContent = config.title || 'Edit Pengembalian';
        document.getElementById('admin-return-code').value = config.returnCode || '';
        document.getElementById('admin-return-rental-code').value = config.rentalCode || '';
        document.getElementById('admin-return-status').value = config.status || 'selesai';
        document.getElementById('admin-return-notes').value = config.notes || '';
        document.getElementById('admin-return-modal').classList.remove('hidden');
      }

      function closeAdminReturnModal() {
        document.getElementById('admin-return-modal').classList.add('hidden');
      }

      function viewInventaris(itemId) {
        const item = inventory.find((entry) => entry.id === itemId);
        if (!item) return;

        openAdminDetailModal({
          dialogTitle: 'Detail Peralatan',
          kicker: capitalizeFirst(item.category),
          title: escapeHtml(item.name),
          subtitle: escapeHtml(item.brand),
          image: item.image,
          badges: [
            `Status: ${capitalizeFirst(item.status)}`,
            `Stok ${item.stock}/${item.totalStock}`
          ],
          rows: [
            { label: 'Harga Harian', value: `${window.formatCurrencyIDR(item.price)}` },
            { label: 'Diskon', value: `${item.discount || 0}%` },
            { label: 'Kategori', value: escapeHtml(capitalizeFirst(item.category)) }
          ]
        });
      }

      function viewPeminjaman(trxId) {
        const trx = borrowingsData.find((entry) => entry.id === trxId);
        if (!trx) return;

        openAdminDetailModal({
          dialogTitle: 'Detail Peminjaman',
          kicker: 'Peminjaman',
          title: escapeHtml(trx.equipment),
          subtitle: `Peminjam: ${escapeHtml(trx.customer)}`,
          image: trx.image,
          badges: [
            `Status: ${capitalizeFirst(trx.status)}`,
            `ID: ${escapeHtml(trx.id)}`
          ],
          rows: [
            { label: 'Mulai', value: escapeHtml(formatDate(trx.startDate)) },
            { label: 'Selesai', value: escapeHtml(formatDate(trx.endDate)) },
            { label: 'Durasi', value: `${trx.days} hari` },
            { label: 'Total', value: `${window.formatCurrencyIDR(trx.amount)}` }
          ]
        });
      }

      function viewPengembalian(trxId) {
        const trx = returnsData.find((entry) => entry.id === trxId);
        if (!trx) return;

        openAdminDetailModal({
          dialogTitle: 'Detail Pengembalian',
          kicker: 'Pengembalian',
          title: escapeHtml(trx.equipment),
          subtitle: `Customer: ${escapeHtml(trx.customer)}`,
          image: trx.image,
          badges: [
            `Status: ${capitalizeFirst(trx.status)}`,
            `ID: ${escapeHtml(trx.id)}`
          ],
          rows: [
            { label: 'Tanggal', value: escapeHtml(formatDate(trx.returnDate)) }
          ],
          note: escapeHtml(trx.notes || 'Tidak ada catatan tambahan.')
        });
      }

      function editPengembalian(trxId) {
        const trx = returnsData.find((entry) => entry.id === trxId);
        if (!trx) return;

        openAdminReturnModal({
          title: 'Edit Pengembalian',
          returnCode: trx.id,
          rentalCode: '',
          status: trx.status === 'returned' ? 'selesai' : trx.status,
          notes: trx.notes || ''
        });
      }

      function convertToPengembalian(trxId) {
        const trx = borrowingsData.find((entry) => entry.id === trxId);
        if (!trx) return;

        openAdminReturnModal({
          title: 'Buat Pengembalian dari Peminjaman',
          returnCode: '',
          rentalCode: trx.id,
          status: 'selesai',
          notes: `Generated from borrowing ${trx.id}`
        });
      }

      const adminReturnForm = document.getElementById('admin-return-form');
      if (adminReturnForm) {
        adminReturnForm.addEventListener('submit', function (event) {
          event.preventDefault();
          postAdminForm('../process/admin-pengembalian-edit.php', {
            return_code: document.getElementById('admin-return-code').value,
            rental_code: document.getElementById('admin-return-rental-code').value,
            status: document.getElementById('admin-return-status').value,
            notes: document.getElementById('admin-return-notes').value
          });
        });
      }
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof renderKategori === 'function') {
    renderKategori();
  }
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
    <?= page_runtime_bundle($flash_script) ?>
  </body>
</html>
