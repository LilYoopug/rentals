<?php
require_once __DIR__ . '/../includes/staff-check.php';
require_once __DIR__ . '/../data/categories-data.php';
require_once __DIR__ . '/../data/products-data.php';
require_once __DIR__ . '/../includes/flash.php';

$staff_active_section = 'stock-price';
$staff_active_href = 'stock-price.php';
$staff_active_section_selector = preg_replace('/[^a-z0-9_-]/i', '', $staff_active_section) ?: 'overview';

function staff_inventory_image_path($path)
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

function staff_inventory_badge($row)
{
    $available = (int) ($row['stock_available'] ?? 0);
    $status = (string) ($row['status'] ?? 'aktif');

    if ($status !== 'aktif') {
        return [
            'label' => 'Perawatan',
            'class' => 'badge badge-danger',
        ];
    }

    if ($available <= 0) {
        return [
            'label' => 'Habis',
            'class' => 'badge badge-danger',
        ];
    }

    if ($available <= 1) {
        return [
            'label' => 'Menipis',
            'class' => 'badge badge-warning',
        ];
    }

    return [
        'label' => 'Siap Disewa',
        'class' => 'badge badge-success',
    ];
}

$staff_user = current_user();
$staff_avatar_url = !empty($staff_user['avatar_path']) ? '../' . ltrim((string) $staff_user['avatar_path'], '/') : '';
$product_rows = get_admin_products();
$stock_filter_categories = get_all_categories();
$inventory_items = [];
$available_units = 0;
$reserved_units = 0;
$low_stock_items = 0;

foreach ($product_rows as $row) {
    $available = (int) ($row['stock_available'] ?? 0);
    $total = (int) ($row['stock_total'] ?? 0);
    $reserved = max(0, $total - $available);
    $badge = staff_inventory_badge($row);

    if ($available <= 1) {
        $low_stock_items++;
    }

    $available_units += $available;
    $reserved_units += $reserved;

    $inventory_items[] = [
        'id' => (int) ($row['id'] ?? 0),
        'name' => (string) ($row['name'] ?? ''),
        'brand' => (string) ($row['brand'] ?? ''),
        'category' => (string) ($row['category_name'] ?? $row['category_slug'] ?? ''),
        'category_slug' => (string) ($row['category_slug'] ?? ''),
        'description' => (string) ($row['description'] ?? ''),
        'price' => (float) ($row['price_per_day'] ?? 0),
        'discount' => (int) ($row['discount_percentage'] ?? 0),
        'available' => $available,
        'total' => $total,
        'reserved' => $reserved,
        'image' => staff_inventory_image_path($row['image_path'] ?? ''),
        'status_label' => $badge['label'],
        'status_class' => $badge['class'],
    ];
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LensCraft - Stok & Harga</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap"
      rel="stylesheet"
    />
    <script>
      document.documentElement.classList.add('staff-js');
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
      .nav-blur {
        background: rgba(5, 5, 5, 0.86);
        backdrop-filter: blur(18px);
      }
      .sidebar-blur {
        background: rgba(5, 5, 5, 0.94);
        backdrop-filter: blur(18px);
      }
      .card-hover {
        transition:
          transform 0.3s ease,
          box-shadow 0.3s ease;
      }
      .card-hover:hover {
        transform: translateY(-6px);
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
      .skeleton-shimmer {
        position: relative;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.06);
      }
      .skeleton-shimmer::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.14), transparent);
        animation: skeletonShimmer 1.35s ease-in-out infinite;
      }
      .table-row-hover:hover {
        background-color: rgba(255, 255, 255, 0.03);
      }
      .stock-loading-shell {
        display: none;
      }
      .stock-loading-grid {
        display: grid;
        gap: 1rem;
        padding: 1.5rem;
      }
      .stock-loading-row {
        display: grid;
        grid-template-columns: minmax(0, 2.3fr) repeat(4, minmax(0, 1fr));
        gap: 1rem;
        align-items: center;
        width: 100%;
        padding: 1rem 1.25rem;
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 1.25rem;
        background: rgba(10, 10, 10, 0.7);
      }
      .stock-loading-product {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
      }
      .stock-loading-meta {
        min-width: 0;
        flex: 1 1 auto;
      }
      .stock-loading-stack {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
        min-width: 0;
      }
      .stock-loading-pill {
        height: 2.75rem;
        border-radius: 1rem;
      }
      .staff-js [data-stock-loading="true"] #stock-price-loading {
        display: block;
      }
      .staff-js [data-stock-loading="true"] #stock-price-form {
        height: 0;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
      }
      .staff-js [data-stock-loading="false"] #stock-price-loading {
        display: none;
      }
      .floating-nav {
        position: fixed;
        bottom: 1.5rem;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(17, 17, 17, 0.6);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 1.6rem;
        padding: 0.8rem 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        z-index: 60;
        transition:
          background 0.2s ease,
          opacity 0.2s ease,
          border-color 0.2s ease,
          box-shadow 0.2s ease;
      }
      .floating-nav.hidden {
        display: none;
      }
      .floating-nav:hover {
        background: rgba(17, 17, 17, 0.75);
      }
      .floating-nav.footer-near:not(:hover) {
        background: rgba(17, 17, 17, 0.2);
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.22);
        opacity: 0.2;
      }
      .floating-nav-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.2rem;
        min-width: 4.75rem;
        width: auto;
        height: 3.75rem;
        border-radius: 1.05rem;
        padding: 0 0.75rem;
        background: transparent;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        transition: all 0.2s ease;
      }
      .floating-nav-btn:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #f3f4f6;
      }
      .floating-nav-btn.primary {
        background: linear-gradient(135deg, #b78a37 0%, #8f6421 100%);
        color: #fff;
      }
      .floating-nav-btn.secondary {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.08);
      }
      .floating-nav-btn svg {
        width: 1.35rem;
        height: 1.35rem;
      }
      .floating-nav-btn span {
        font-size: 0.58rem;
        font-weight: 500;
        letter-spacing: 0.02em;
        white-space: nowrap;
      }
      .mode-toggle-shell {
        display: inline-flex;
        align-items: center;
        gap: 0.8rem;
        padding: 0.45rem 0.9rem;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
      }
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
      .nav-item-active {
        background-color: var(--accent-brass-soft);
        border-left: 3px solid var(--accent-brass);
        color: #fff;
      }
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
      .field-shell {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.08);
      }
      .field-shell:focus-within {
        border-color: rgba(199, 166, 90, 0.55);
        box-shadow: 0 0 0 3px rgba(199, 166, 90, 0.12);
      }
      .field-input {
        width: 100%;
        background: transparent;
        color: #fafafa;
        outline: none;
      }
      .field-input::-webkit-outer-spin-button,
      .field-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
      }
      .stock-stepper-btn {
        width: 2rem;
        height: 2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: #d4d4d4;
        transition:
          background-color 0.2s ease,
          color 0.2s ease,
          border-color 0.2s ease;
      }
      .stock-stepper-btn:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        border-color: rgba(255, 255, 255, 0.16);
      }
      @media (min-width: 640px) {
        .stock-stepper-btn {
          width: 1.6rem;
          height: 1.6rem;
        }
        input[data-stock-total] {
          width: 4ch;
          min-width: 3ch;
          max-width: 6ch;
        }
        input[data-stock-total]::-webkit-outer-spin-button,
        input[data-stock-total]::-webkit-inner-spin-button {
          -webkit-appearance: none;
          margin: 0;
        }
      }
      .discount-switch {
        position: relative;
        width: 3.25rem;
        height: 1.9rem;
        border-radius: 9999px;
        background: #262626;
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: background-color 0.2s ease, border-color 0.2s ease;
      }
      .discount-switch::after {
        content: "";
        position: absolute;
        top: 0.18rem;
        left: 0.2rem;
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 9999px;
        background: #fafafa;
        transition: transform 0.2s ease;
      }
      .discount-toggle:checked + .discount-switch {
        background: rgba(199, 166, 90, 0.28);
        border-color: rgba(199, 166, 90, 0.5);
      }
      .discount-toggle:checked + .discount-switch::after {
        transform: translateX(1.32rem);
      }
      .discount-panel.is-disabled {
        opacity: 0.45;
      }
      .mobile-value {
        text-align: left;
      }
      .mobile-control {
        width: 100%;
        margin-left: 0;
      }
      @media (max-width: 639px) {
        .floating-nav {
          bottom: 1rem;
          width: calc(100% - 1.5rem);
          max-width: 22rem;
          padding: 0.6rem 0.85rem;
          gap: 0.55rem;
        }
        .floating-nav-btn {
          flex: 1 1 0;
          min-width: 0;
          height: 3.1rem;
          padding: 0 0.45rem;
        }
        .floating-nav-btn svg {
          width: 1.15rem;
          height: 1.15rem;
        }
        .floating-nav-btn span {
          font-size: 0.49rem;
        }
        .mobile-value {
          text-align: right;
          min-width: 8.5rem;
        }
        .mobile-control {
          width: min(11rem, 100%);
          margin-left: auto;
        }
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
        .stock-price-table-wrapper {
          overflow-x: clip;
        }
        .stock-price-table,
        .stock-price-table tbody {
          display: block;
          width: 100%;
        }
        .stock-price-table tr {
          display: block;
          width: 100%;
        }
        .stock-price-table td {
          display: block;
          width: 100%;
          min-width: 0;
        }
        .stock-price-table .field-shell {
          width: 100%;
        }
        .stock-loading-grid {
          padding: 1rem;
        }
        .stock-loading-row {
          grid-template-columns: minmax(0, 1fr);
          gap: 0.85rem;
          padding: 1rem;
        }
      }
      .hero-panel {
        background:
          radial-gradient(circle at top left, rgba(199, 166, 90, 0.14), transparent 28%),
          linear-gradient(135deg, rgba(14, 14, 14, 0.98), rgba(5, 5, 5, 0.96));
      }
    </style>
  </head>

  <body class="bg-neutral-950 text-neutral-100 min-h-screen">
    <nav class="fixed top-0 left-0 right-0 z-50 nav-blur border-b border-neutral-800 h-16">
      <div class="flex items-center justify-between h-full px-6">
        <div class="flex items-center gap-4">
          <button id="sidebar-toggle" class="lg:hidden text-neutral-400 hover:text-white transition-colors p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <a href="index.php" class="text-2xl font-bold font-serif text-white tracking-tight">LensCraft</a>
          <span class="hidden md:inline-block text-sm text-neutral-500 border-l border-neutral-800 pl-4">Dashboard Petugas</span>
        </div>

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

    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    <aside id="sidebar" class="fixed left-0 top-16 bottom-0 w-64 sidebar-blur border-r border-neutral-800 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out overflow-y-auto sidebar-scroll">
      <div class="p-4 space-y-2">
        <a href="index.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          <span>Ringkasan</span>
        </a>

        <a href="borrowings.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Peminjaman</span>
        </a>

        <a href="returns.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          <span>Pengembalian</span>
        </a>

        <a href="stock-price.php" class="nav-item <?= (isset($staff_active_section) && $staff_active_section === 'stock-price') ? 'nav-item-active' : 'text-neutral-400' ?> flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all hover:bg-white/5">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 .895-4 2s1.79 2 4 2 4 .895 4 2-1.79 2-4 2m0-10V6m0 12v2m8-8a8 8 0 11-16 0 8 8 0 0116 0z" />
          </svg>
          <span>Stok & Harga</span>
        </a>

        <a href="reports.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          <span>Laporan & Analitik</span>
        </a>

        <div class="border-t border-neutral-800 my-6"></div>

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

    <main class="lg:ml-64 pt-16 min-h-screen">
      <div class="p-6 md:p-8 space-y-8">
        <section>
          <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
              <div>
                <h1 class="text-3xl md:text-4xl font-serif text-white mb-2">Memantau Pengembalian</h1>
                <p class="text-neutral-400">Pantau dan kelola stok, harga, dan ketersediaan peralatan.</p>
              </div>
              <div class="flex flex-wrap gap-3 items-center">
                <label class="mode-toggle-shell cursor-pointer">
                  <span class="text-sm font-medium text-white">Mode Edit</span>
                  <input type="checkbox" id="edit-mode-toggle" class="discount-toggle sr-only">
                  <span class="discount-switch" aria-hidden="true"></span>
                </label>
                <button class="px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-sm font-medium text-neutral-300 hover:bg-neutral-700 transition-colors flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                  Ekspor
                </button>
              </div>
            </div>
          </div>

          <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 mb-6">
            <div class="flex flex-row items-center gap-4">
              <div class="relative flex-shrink-0">
                <button id="stock-filter-btn" class="flex items-center justify-center w-10 h-10 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-400 hover:bg-neutral-700 hover:text-white transition-colors" aria-label="Toggle filters">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                </button>
                <div id="stock-filter-dropdown" class="absolute left-0 top-full mt-2 w-80 bg-neutral-900 border border-neutral-800 rounded-xl shadow-xl z-20 hidden p-4">
                  <div class="space-y-4">
                    <div>
                      <label class="block text-xs font-medium text-neutral-300 mb-2">Kategori Produk</label>
                      <select id="stock-category-filter" class="w-full px-3 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-700">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($stock_filter_categories as $category): ?>
                          <option value="<?= e((string) ($category['slug'] ?? '')) ?>"><?= e((string) ($category['name'] ?? 'Kategori')) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <div class="min-w-0 flex-1 relative">
                <input type="text" id="stock-search" placeholder="Cari produk berdasarkan nama atau brand..." class="w-full pl-4 pr-12 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-700 focus:border-neutral-600 transition-all" />
                <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-white transition-colors" aria-label="Cari">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </button>
              </div>
            </div>
          </div>
        </section>

        <section id="stock-price-shell" data-stock-loading="true" class="rounded-3xl border border-neutral-800 bg-neutral-900/70 overflow-hidden">
          <div id="stock-price-loading" class="stock-loading-shell" aria-hidden="true">
            <div class="stock-loading-grid">
              <?php for ($stock_skeleton_index = 0; $stock_skeleton_index < 5; $stock_skeleton_index++): ?>
                <div class="stock-loading-row">
                  <div class="stock-loading-product">
                    <div class="skeleton-shimmer h-16 w-16 rounded-2xl"></div>
                    <div class="stock-loading-meta stock-loading-stack">
                      <div class="skeleton-shimmer h-4 w-3/4 rounded-full"></div>
                      <div class="skeleton-shimmer h-3 w-1/2 rounded-full"></div>
                      <div class="skeleton-shimmer h-3 w-1/3 rounded-full"></div>
                    </div>
                  </div>
                  <div class="stock-loading-stack">
                    <div class="skeleton-shimmer h-4 w-20 rounded-full"></div>
                    <div class="skeleton-shimmer stock-loading-pill w-full"></div>
                  </div>
                  <div class="stock-loading-stack">
                    <div class="skeleton-shimmer h-4 w-16 rounded-full"></div>
                    <div class="skeleton-shimmer stock-loading-pill w-full"></div>
                  </div>
                  <div class="stock-loading-stack">
                    <div class="skeleton-shimmer h-4 w-24 rounded-full"></div>
                    <div class="skeleton-shimmer stock-loading-pill w-full"></div>
                  </div>
                  <div class="stock-loading-stack">
                    <div class="skeleton-shimmer h-4 w-24 rounded-full"></div>
                    <div class="skeleton-shimmer stock-loading-pill w-full"></div>
                  </div>
                </div>
              <?php endfor; ?>
            </div>
          </div>
          <form id="stock-price-form" action="../process/staff-stock-price-bulk-update.php" method="POST">
            <div class="overflow-x-auto stock-price-table-wrapper">
              <table class="w-full stock-price-table">
                <thead class="hidden sm:table-header-group">
                  <tr class="border-b border-neutral-800 bg-neutral-800/30">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Produk</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Harga</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Diskon</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Stock Total</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Stock Tersedia</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800">
                  <?php if (empty($inventory_items)): ?>
                    <tr>
                      <td colspan="5" class="px-6 py-10">
                        <div class="flex flex-col items-center justify-center gap-4 rounded-2xl border border-dashed border-neutral-700 bg-neutral-950/60 px-6 py-10 text-center">
                          <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-neutral-700 bg-neutral-900 text-neutral-300">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16m-2 0v10a2 2 0 01-2 2H8a2 2 0 01-2-2V7m3-3h6a2 2 0 012 2v1H7V6a2 2 0 012-2z" />
                            </svg>
                          </div>
                          <div class="space-y-2">
                            <p class="text-base font-semibold text-white">Belum ada produk</p>
                            <p class="mx-auto max-w-md text-sm leading-6 text-neutral-400">Coba ubah filter atau tambah data baru.</p>
                          </div>
                        </div>
                      </td>
                    </tr>
                  <?php else: ?>
                  <?php foreach ($inventory_items as $item): ?>
                    <?php $discount_enabled = $item['discount'] > 0; ?>
                    <tr class="table-row-hover transition-colors block sm:table-row p-4 sm:p-0" data-stock-row data-stock-name="<?= e(strtolower($item['name'])) ?>" data-stock-brand="<?= e(strtolower($item['brand'])) ?>" data-stock-category="<?= e((string) $item['category_slug']) ?>" data-reserved-count="<?= e((string) $item['reserved']) ?>">
                      <td class="block sm:table-cell px-0 pb-4 sm:px-4 sm:py-3 align-top border-b border-neutral-800 sm:border-b-0">
                        <input type="hidden" name="product_ids[]" value="<?= $item['id'] ?>">
                        <div class="flex w-full min-w-0 items-center gap-4">
                          <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>" class="w-16 h-16 rounded-2xl object-cover bg-neutral-800 border border-neutral-700 shrink-0" onerror="this.src='../images/gear-placeholder.svg'">
                          <div class="min-w-0">
                            <div class="text-sm font-semibold text-white mobile-name-ellipsis mobile-card-title-ellipsis"><?= e($item['name']) ?></div>
                            <div class="text-sm text-neutral-400 mt-1"><?= e($item['brand']) ?> • <?= e($item['category']) ?></div>
                            <div class="text-xs text-neutral-500 mt-2"><?= number_format($item['reserved']) ?> unit sedang dipakai</div>
                          </div>
                        </div>
                      </td>
                      <td class="block sm:table-cell px-0 py-3 sm:px-4 sm:py-3 align-top">
                        <div class="flex items-center justify-between gap-4 sm:block">
                          <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-neutral-500 sm:hidden">Harga</span>
                          <div class="flex-1 sm:block">
                        <div data-view-only class="min-w-[10rem] sm:min-w-0 text-sm font-medium text-white mobile-value">
                          <?= e(format_currency($item['price'])) ?> / hari
                        </div>
                        <label data-edit-only class="block min-w-[10rem] sm:min-w-0 hidden mobile-control">
                          <span class="field-shell rounded-2xl px-4 py-3 flex items-center gap-2">
                            <span class="text-neutral-500 text-sm">$</span>
                            <input class="field-input text-sm" type="number" name="price_per_day[<?= $item['id'] ?>]" min="0" step="0.01" value="<?= e(number_format($item['price'], 2, '.', '')) ?>" required data-editable-input data-initial-value="<?= e(number_format($item['price'], 2, '.', '')) ?>" disabled>
                          </span>
                        </label>
                          </div>
                        </div>
                      </td>
                      <td class="block sm:table-cell px-0 py-3 sm:px-4 sm:py-3 align-top">
                        <div class="flex items-start justify-between gap-4 sm:block">
                          <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-neutral-500 sm:hidden pt-2">Diskon</span>
                          <div class="flex-1 sm:block">
                        <div data-view-only class="min-w-[11rem] sm:min-w-0 mobile-value">
                          <div class="text-sm font-medium text-white">
                            <?= $discount_enabled ? e((string) $item['discount']) . '%' : 'Nonaktif' ?>
                          </div>
                          <div class="text-xs text-neutral-500 mt-1">
                            <?= $discount_enabled ? 'Aktif' : 'Nonaktif' ?>
                          </div>
                        </div>
                        <div data-edit-only class="block min-w-[11rem] sm:min-w-0 hidden mobile-control">
                          <div class="flex items-center justify-between gap-3 mb-2">
                            <span class="text-xs uppercase tracking-[0.2em] text-neutral-500 block">Aktif</span>
                            <label class="inline-flex items-center gap-3 cursor-pointer">
                              <input type="checkbox" name="discount_enabled[<?= $item['id'] ?>]" value="1" class="discount-toggle sr-only" <?= $discount_enabled ? 'checked' : '' ?> data-discount-toggle data-initial-checked="<?= $discount_enabled ? '1' : '0' ?>" disabled>
                              <span class="discount-switch" aria-hidden="true"></span>
                            </label>
                          </div>
                          <div class="discount-panel <?= $discount_enabled ? '' : 'is-disabled' ?>" data-discount-panel>
                            <span class="field-shell rounded-2xl px-4 py-3 flex items-center gap-2">
                              <input class="field-input text-sm" type="number" name="discount_percentage[<?= $item['id'] ?>]" min="0" max="100" step="1" value="<?= e((string) $item['discount']) ?>" <?= $discount_enabled ? '' : 'disabled' ?> data-discount-input data-initial-value="<?= e((string) $item['discount']) ?>" disabled>
                              <span class="text-neutral-500 text-sm">%</span>
                            </span>
                          </div>
                        </div>
                          </div>
                        </div>
                      </td>
                      <td class="block sm:table-cell px-0 py-3 sm:px-4 sm:py-3 align-top">
                        <div class="flex items-center justify-between gap-4 sm:block">
                          <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-neutral-500 sm:hidden">Stock Total</span>
                          <div class="flex-1 sm:block">
                        <div data-view-only class="min-w-[11rem] sm:min-w-0 text-sm font-medium text-white mobile-value">
                          <?= number_format($item['total']) ?> unit
                        </div>
                        <label data-edit-only class="block min-w-[11rem] sm:min-w-0 hidden mobile-control">
                          <span class="field-shell rounded-2xl px-4 py-3 flex items-center gap-2">
                            <button type="button" class="stock-stepper-btn" data-stock-adjust data-target="stock_total" data-direction="down" aria-label="Kurangi stock total" disabled>-</button>
                            <input class="field-input text-sm text-center" type="number" name="stock_total[<?= $item['id'] ?>]" min="0" step="1" value="<?= e((string) $item['total']) ?>" required data-stock-total data-initial-value="<?= e((string) $item['total']) ?>" disabled>
                            <button type="button" class="stock-stepper-btn" data-stock-adjust data-target="stock_total" data-direction="up" aria-label="Tambah stock total" disabled>+</button>
                          </span>
                        </label>
                          </div>
                        </div>
                      </td>
                      <td class="block sm:table-cell px-0 pt-3 pb-0 sm:px-4 sm:py-3 align-top">
                        <div class="flex items-center justify-between gap-4 sm:block">
                          <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-neutral-500 sm:hidden">Stock Tersedia</span>
                          <div class="flex-1 sm:block">
                        <div data-view-only class="min-w-[11rem] sm:min-w-0 text-sm font-medium text-white mobile-value" data-stock-available-text>
                          <?= number_format($item['available']) ?> unit
                        </div>
                        <div data-edit-only class="block min-w-[11rem] sm:min-w-0 hidden mobile-control">
                          <div class="field-shell rounded-2xl px-4 py-3 text-sm text-white text-right" data-stock-available-text>
                            <?= number_format($item['available']) ?> unit
                          </div>
                          <p class="text-[11px] text-neutral-500 mt-2 text-right">Auto from total stock</p>
                        </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-6 border-t border-neutral-800">
              <div class="text-sm text-neutral-400">
                Showing <span id="stock-price-shown" class="font-medium text-white">0</span> of <span id="stock-price-total" class="font-medium text-white">0</span> products
              </div>
              <div class="flex items-center gap-2">
                <button id="stock-price-prev" type="button" class="px-4 py-2 text-sm font-medium bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-300 hover:bg-neutral-700 transition-colors disabled:opacity-50" disabled>Sebelumnya</button>
                <div id="stock-price-page-numbers" class="flex items-center gap-1"></div>
                <button id="stock-price-next" type="button" class="px-4 py-2 text-sm font-medium bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-300 hover:bg-neutral-700 transition-colors">Berikutnya</button>
              </div>
            </div>
          </form>
        </section>
      </div>
    </main>

    <nav id="edit-mode-bar" class="floating-nav hidden" role="navigation" aria-label="Edit mode actions">
      <button id="edit-cancel-btn" type="button" class="floating-nav-btn secondary">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span>Batal</span>
      </button>
      <button id="edit-save-btn" type="submit" form="stock-price-form" class="floating-nav-btn primary">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>Save</span>
      </button>
    </nav>

    <script>
      const sidebar = document.getElementById('sidebar');
      const sidebarToggle = document.getElementById('sidebar-toggle');
      const sidebarOverlay = document.getElementById('sidebar-overlay');
      const stockPriceShell = document.getElementById('stock-price-shell');
      const editModeToggle = document.getElementById('edit-mode-toggle');
      const editModeBar = document.getElementById('edit-mode-bar');
      const stockPriceForm = document.getElementById('stock-price-form');
      const editBatalButton = document.getElementById('edit-cancel-btn');
      const stockRows = Array.from(document.querySelectorAll('[data-stock-row]'));
      let filteredStockRows = [...stockRows];
      const stockPrevButton = document.getElementById('stock-price-prev');
      const stockNextButton = document.getElementById('stock-price-next');
      const stockShownLabel = document.getElementById('stock-price-shown');
      const stockTotalLabel = document.getElementById('stock-price-total');
      const stockPageNumbers = document.getElementById('stock-price-page-numbers');
      const stockSearchInput = document.getElementById('stock-search');
      const stockCategoryFilter = document.getElementById('stock-category-filter');
      const stockFilterBtn = document.getElementById('stock-filter-btn');
      const stockFilterDropdown = document.getElementById('stock-filter-dropdown');
      let stockCurrentPage = 1;

      function toggleSidebar() {
        const isClosed = sidebar.classList.contains('-translate-x-full');
        if (isClosed) {
          sidebar.classList.remove('-translate-x-full');
          sidebarOverlay.classList.remove('hidden');
          return;
        }

        sidebar.classList.add('-translate-x-full');
        sidebarOverlay.classList.add('hidden');
      }

      sidebarToggle.addEventListener('click', toggleSidebar);
      sidebarOverlay.addEventListener('click', toggleSidebar);

      function getStockItemsPerPage() {
        return window.innerWidth < 640 ? 4 : 5;
      }

      function renderStockPagination() {
        const perPage = getStockItemsPerPage();
        const totalRows = filteredStockRows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
        if (stockCurrentPage > totalPages) {
          stockCurrentPage = totalPages;
        }

        const start = (stockCurrentPage - 1) * perPage;
        const end = start + perPage;
        const pageRows = filteredStockRows.slice(start, end);
        const pageRowSet = new Set(pageRows);

        stockRows.forEach(function (row, index) {
          row.style.display = pageRowSet.has(row) ? '' : 'none';
        });

        stockShownLabel.textContent = String(pageRows.length);
        stockTotalLabel.textContent = String(totalRows);

        stockPageNumbers.innerHTML = '';
        for (let page = 1; page <= totalPages; page++) {
          const button = document.createElement('button');
          button.type = 'button';
          button.textContent = String(page);
          button.className = page === stockCurrentPage
            ? 'px-3 py-2 text-sm font-medium rounded-lg bg-white text-black'
            : 'px-3 py-2 text-sm font-medium rounded-lg bg-neutral-800 border border-neutral-700 text-neutral-300 hover:bg-neutral-700 transition-colors';
          button.addEventListener('click', function () {
            stockCurrentPage = page;
            renderStockPagination();
          });
          stockPageNumbers.appendChild(button);
        }

        stockPrevButton.disabled = stockCurrentPage <= 1;
        stockNextButton.disabled = stockCurrentPage >= totalPages;
      }

      function filterStockRows() {
        const searchTerm = (stockSearchInput?.value || '').trim().toLowerCase();
        const categoryValue = stockCategoryFilter?.value || '';

        filteredStockRows = stockRows.filter(function (row) {
          const matchesSearch = searchTerm === ''
            || (row.dataset.stockName || '').includes(searchTerm)
            || (row.dataset.stockBrand || '').includes(searchTerm);
          const matchesCategory = categoryValue === '' || (row.dataset.stockCategory || '') === categoryValue;
          return matchesSearch && matchesCategory;
        });

        stockCurrentPage = 1;
        renderStockPagination();
      }

      function syncFloatingBarFooterState() {
        const footer = document.querySelector('footer');
        if (!editModeBar || !footer || editModeBar.classList.contains('hidden')) {
          return;
        }
        const footerRect = footer.getBoundingClientRect();
        const threshold = editModeBar.offsetHeight + 48;
        editModeBar.classList.toggle('footer-near', footerRect.top <= window.innerHeight - threshold);
      }

      function setEditMode(enabled) {
        editModeBar.classList.toggle('hidden', !enabled);

        document.querySelectorAll('[data-view-only]').forEach(function (field) {
          field.classList.toggle('hidden', enabled);
        });

        document.querySelectorAll('[data-edit-only]').forEach(function (field) {
          if (enabled) {
            field.classList.remove('hidden');
          } else {
            field.classList.add('hidden');
            field.classList.remove('sm:inline-block');
          }
        });

        document.querySelectorAll('[data-editable-input]').forEach(function (input) {
          input.disabled = !enabled;
        });

        document.querySelectorAll('[data-stock-total]').forEach(function (input) {
          input.disabled = !enabled;
        });

        document.querySelectorAll('[data-stock-adjust]').forEach(function (button) {
          button.disabled = !enabled;
        });

        document.querySelectorAll('[data-discount-toggle]').forEach(function (toggle) {
          toggle.disabled = !enabled;
        });

        document.querySelectorAll('[data-stock-row]').forEach(function (row) {
          const discountToggle = row.querySelector('[data-discount-toggle]');
          const discountInput = row.querySelector('[data-discount-input]');
          const discountPanel = row.querySelector('[data-discount-panel]');
          const canEditDiscount = enabled && discountToggle.checked;
          discountInput.disabled = !canEditDiscount;
          discountPanel.classList.toggle('is-disabled', !discountToggle.checked);
        });
        syncFloatingBarFooterState();
      }

      function finishStockLoading() {
        if (!stockPriceShell) {
          return;
        }

        stockPriceShell.setAttribute('data-stock-loading', 'false');
      }

      function resetEditState(updateToggle = true) {
        stockPriceForm.reset();
        document.querySelectorAll('[data-editable-input], [data-discount-input], [data-stock-total]').forEach(function (input) {
          const initialValue = input.dataset.initialValue;
          if (typeof initialValue !== 'undefined') {
            input.value = initialValue;
          }
        });
        document.querySelectorAll('[data-discount-toggle]').forEach(function (toggle) {
          toggle.checked = toggle.dataset.initialChecked === '1';
        });
        if (updateToggle) {
          editModeToggle.checked = false;
        }
        setEditMode(false);
        document.querySelectorAll('[data-stock-row]').forEach(syncRowState);
      }

      function syncRowState(row) {
        const totalInput = row.querySelector('[data-stock-total]');
        const discountToggle = row.querySelector('[data-discount-toggle]');
        const discountInput = row.querySelector('[data-discount-input]');
        const discountPanel = row.querySelector('[data-discount-panel]');
        const availableTexts = row.querySelectorAll('[data-stock-available-text]');
        const reservedCount = Math.max(0, Number(row.dataset.reservedCount || 0));

        function syncStockLimits() {
          const total = Math.max(0, Number(totalInput.value || 0));
          const available = Math.max(0, total - reservedCount);
          totalInput.value = String(total);
          availableTexts.forEach(function (node) {
            node.textContent = `${available} unit`;
          });
        }

        function syncDiscountState() {
          const enabled = discountToggle.checked;
          discountInput.disabled = !editModeToggle.checked || !enabled;
          discountPanel.classList.toggle('is-disabled', !enabled);
          if (!enabled) {
            discountInput.value = '0';
            return;
          }

          if (Number(discountInput.value || 0) <= 0) {
            discountInput.value = '10';
          }
        }

        row.querySelectorAll('[data-stock-adjust]').forEach(function (button) {
          button.addEventListener('click', function () {
            if (!editModeToggle.checked) {
              return;
            }
            const direction = button.dataset.direction === 'up' ? 1 : -1;
            const targetName = button.dataset.target;
            if (targetName !== 'stock_total') {
              return;
            }

            const input = totalInput;
            const min = Number(input.min || 0);
            const max = Number(input.max || Number.MAX_SAFE_INTEGER);
            const nextValue = Math.min(max, Math.max(min, Number(input.value || 0) + direction));
            input.value = String(nextValue);

            if (targetName === 'stock_total') {
              syncStockLimits();
            }
          });
        });

        totalInput.addEventListener('input', syncStockLimits);
        discountToggle.addEventListener('change', syncDiscountState);

        syncStockLimits();
        syncDiscountState();
      }

      document.querySelectorAll('[data-stock-row]').forEach(function (row) {
        syncRowState(row);
      });

      stockPrevButton.addEventListener('click', function () {
        if (stockCurrentPage <= 1) {
          return;
        }
        stockCurrentPage -= 1;
        renderStockPagination();
      });

      stockNextButton.addEventListener('click', function () {
        const totalPages = Math.max(1, Math.ceil(filteredStockRows.length / getStockItemsPerPage()));
        if (stockCurrentPage >= totalPages) {
          return;
        }
        stockCurrentPage += 1;
        renderStockPagination();
      });

      editModeToggle.addEventListener('change', function () {
        if (editModeToggle.checked) {
          setEditMode(true);
          return;
        }

        resetEditState(false);
      });

      editBatalButton.addEventListener('click', resetEditState);

      stockPriceForm.addEventListener('submit', function () {
        setEditMode(true);
      });

      if (stockSearchInput) {
        stockSearchInput.addEventListener('input', filterStockRows);
      }

      if (stockCategoryFilter) {
        stockCategoryFilter.addEventListener('change', filterStockRows);
      }

      if (stockFilterBtn && stockFilterDropdown) {
        stockFilterBtn.addEventListener('click', function () {
          stockFilterDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', function (event) {
          if (!stockFilterBtn.contains(event.target) && !stockFilterDropdown.contains(event.target)) {
            stockFilterDropdown.classList.add('hidden');
          }
        });
      }

      setEditMode(false);
      renderStockPagination();
      syncFloatingBarFooterState();
      window.addEventListener('resize', function () {
        renderStockPagination();
        syncFloatingBarFooterState();
      });
      window.addEventListener('scroll', syncFloatingBarFooterState, { passive: true });

      document.querySelectorAll('a.nav-item[href]').forEach(function (link) {
        const active = link.getAttribute('href') === 'stock-price.php';
        link.classList.toggle('nav-item-active', active);
        link.classList.toggle('text-neutral-400', !active);
        link.classList.toggle('text-white', active);
      });

      window.addEventListener('load', function () {
        requestAnimationFrame(finishStockLoading);
      });
    </script>
    <?= page_runtime_bundle($flash_script) ?>
  </body>
</html>
