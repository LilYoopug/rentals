<?php
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/activity-data.php';
require_once __DIR__ . '/../includes/flash.php';

$admin_active_section = 'activity-log';
$admin_active_href = 'activity-log.php';
$admin_active_section_selector = preg_replace('/[^a-z0-9_-]/i', '', $admin_active_section) ?: 'activity-log';

$admin_session_user = current_user();
$admin_avatar_url = !empty($admin_session_user['avatar_path']) ? '../' . ltrim((string) $admin_session_user['avatar_path'], '/') : '';

$admin_activity_log_rows = get_activity_logs();

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

$admin_activities_json = json_encode($admin_activities, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LensCraft - Aktivitas</title>
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
      body { font-family: "Inter", sans-serif; }
      .font-serif { font-family: "Playfair Display", serif; }
      .nav-blur {
        background: rgba(5, 5, 5, 0.86);
        backdrop-filter: blur(18px);
      }
      .sidebar-blur {
        background: rgba(5, 5, 5, 0.94);
        backdrop-filter: blur(18px);
      }
      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(24px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      .animate-fade-in {
        opacity: 0;
        animation: fadeInUp 0.45s ease-out forwards;
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
      .admin-js.admin-page-loading #activity-log {
        display: none !important;
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
      .content-section {
        display: none;
      }
      #<?= e($admin_active_section_selector) ?>.content-section {
        display: block;
      }
      .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        border: 1px solid;
      }
      .badge-info {
        background-color: rgba(59, 130, 246, 0.15);
        color: #60a5fa;
        border-color: rgba(59, 130, 246, 0.3);
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
          <span class="hidden md:inline-block text-sm text-neutral-500 border-l border-neutral-800 pl-4">Aktivitas</span>
        </div>

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

    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    <aside id="sidebar" class="fixed left-0 top-16 bottom-0 w-64 sidebar-blur border-r border-neutral-800 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out overflow-y-auto sidebar-scroll">
      <div class="p-4 space-y-2">
        <a href="index.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-neutral-400 transition-all hover:bg-white/5 hover:text-white" data-section="overview">
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

        <div class="border-t border-neutral-800 my-6"></div>

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

    <main class="lg:ml-64 pt-16 min-h-screen">
      <div class="p-6 md:p-8">
        <div id="content-area">
          <section id="admin-page-skeleton" aria-hidden="true">
            <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
              <div class="space-y-3">
                <div class="skeleton-shell h-10 w-52 rounded-2xl"></div>
                <div class="skeleton-shell h-4 w-80 max-w-full rounded-full"></div>
              </div>
              <div class="skeleton-shell h-10 w-28 rounded-xl"></div>
            </div>
            <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 mb-6">
              <div class="skeleton-shell h-12 w-full rounded-xl"></div>
            </div>
            <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 space-y-4">
              <?php for ($admin_activity_skeleton = 0; $admin_activity_skeleton < 6; $admin_activity_skeleton++): ?>
                <div class="flex items-start gap-4">
                  <div class="skeleton-shell h-12 w-12 rounded-xl flex-shrink-0"></div>
                  <div class="flex-1 space-y-2">
                    <div class="skeleton-shell h-4 w-48 rounded-full"></div>
                    <div class="skeleton-shell h-3 w-32 rounded-full"></div>
                    <div class="skeleton-shell h-3 w-full rounded-full"></div>
                  </div>
                </div>
              <?php endfor; ?>
            </div>
          </section>
          <section id="activity-log" class="content-section">
            <div class="mb-8">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                  <h1 class="text-3xl md:text-4xl font-serif text-white mb-2">Aktivitas</h1>
                  <p class="text-neutral-400">Pantau perubahan data dan aksi pengguna di dalam sistem.</p>
                </div>
                <button class="px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-sm font-medium text-neutral-300 hover:bg-neutral-700 transition-colors flex items-center gap-2" type="button">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                  Ekspor Log
                </button>
              </div>
            </div>

            <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 mb-6">
              <div class="flex flex-row items-center gap-4">
                <div class="relative flex-shrink-0">
                  <button id="activity-filter-btn" class="flex items-center justify-center w-10 h-10 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-400 hover:bg-neutral-700 hover:text-white transition-colors" aria-label="Toggle filters">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                  </button>
                  <div id="activity-filter-dropdown" class="absolute left-0 top-full mt-2 w-80 bg-neutral-900 border border-neutral-800 rounded-xl shadow-xl z-20 hidden p-4">
                    <div class="space-y-4">
                      <div>
                        <label class="block text-xs font-medium text-neutral-300 mb-2">Tipe</label>
                        <select id="activity-type-filter" class="w-full px-3 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-700">
                          <option value="">Semua Tipe</option>
                          <option value="pelanggan">User</option>
                          <option value="transaction">Transaksi</option>
                          <option value="inventory">Inventaris</option>
                          <option value="system">Sistem</option>
                          <option value="security">Keamanan</option>
                        </select>
                      </div>
                      <div>
                        <label class="block text-xs font-medium text-neutral-300 mb-2">Waktu</label>
                        <select id="activity-date-filter" class="w-full px-3 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-700">
                          <option value="">Semua Waktu</option>
                          <option value="today">Hari Ini</option>
                          <option value="week">7 Hari Terakhir</option>
                          <option value="month">Bulan Ini</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="min-w-0 flex-1 relative">
                  <input type="text" id="activity-search" placeholder="Cari aktivitas..." class="w-full pl-4 pr-12 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-700 focus:border-neutral-600 transition-all" />
                  <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-white transition-colors" aria-label="Cari">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                  </button>
                </div>
              </div>
            </div>

            <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6" id="activity-timeline"></div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 pt-6 border-t border-neutral-800">
              <div class="text-sm text-neutral-400">
                Menampilkan <span id="activity-shown" class="font-medium text-white">0</span> dari <span id="activity-total" class="font-medium text-white">0</span> aktivitas
              </div>
              <div class="flex items-center gap-2">
                <button id="activity-prev" class="px-4 py-2 text-sm font-medium bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-300 hover:bg-neutral-700 transition-colors disabled:opacity-50" disabled>Sebelumnya</button>
                <div id="activity-page-numbers" class="flex items-center gap-1"></div>
                <button id="activity-next" class="px-4 py-2 text-sm font-medium bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-300 hover:bg-neutral-700 transition-colors">Berikutnya</button>
              </div>
            </div>
          </section>
        </div>
      </div>
    </main>

    <script>
      const sidebar = document.getElementById('sidebar');
      const sidebarToggle = document.getElementById('sidebar-toggle');
      const sidebarOverlay = document.getElementById('sidebar-overlay');
      const activityFilterBtn = document.getElementById('activity-filter-btn');
      const activityFilterDropdown = document.getElementById('activity-filter-dropdown');
      const activitySearch = document.getElementById('activity-search');
      const activityTypeFilter = document.getElementById('activity-type-filter');
      const activityDateFilter = document.getElementById('activity-date-filter');
      const activityTimeline = document.getElementById('activity-timeline');
      const activityShown = document.getElementById('activity-shown');
      const activityTotal = document.getElementById('activity-total');
      const activityPrev = document.getElementById('activity-prev');
      const activityNext = document.getElementById('activity-next');
      const activityPageNumbers = document.getElementById('activity-page-numbers');

      const activities = <?= $admin_activities_json ?>;
      let filteredActivities = [...activities];
      let activitiesCurrentPage = 1;
      const activitiesPerPage = 10;

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

      const activityColorClasses = {
        blue: 'bg-amber-900/30 text-amber-300 border-amber-800/50',
        green: 'bg-green-900/30 text-green-400 border-green-800/50',
        purple: 'bg-purple-900/30 text-purple-400 border-purple-800/50',
        yellow: 'bg-yellow-900/30 text-yellow-400 border-yellow-800/50',
        orange: 'bg-orange-900/30 text-orange-400 border-orange-800/50',
        red: 'bg-red-900/30 text-red-400 border-red-800/50',
        indigo: 'bg-indigo-900/30 text-indigo-400 border-indigo-800/50'
      };

      function toggleSidebar() {
        if (!sidebar || !sidebarOverlay) return;

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

      function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = String(text ?? '');
        return div.innerHTML;
      }

      function formatDateTime(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        if (Number.isNaN(date.getTime())) return String(dateStr);

        return date.toLocaleString('id-ID', {
          day: '2-digit',
          month: 'short',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      }

      function renderActivityEmptyState(isFiltered) {
        if (!activityTimeline) return;

        const title = isFiltered ? 'Aktivitas tidak ditemukan' : 'Belum ada aktivitas';
        const message = isFiltered
          ? 'Coba ubah kata kunci pencarian atau filter rentang waktu.'
          : 'Aktivitas admin dan pelanggan akan muncul di sini setelah ada perubahan data.';

        activityTimeline.innerHTML = `
          <div class="rounded-2xl border border-dashed border-neutral-700 bg-neutral-950/60 px-6 py-10 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-neutral-700 bg-neutral-900 text-neutral-300">
              <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
              </svg>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-white">${title}</h3>
            <p class="mt-2 text-sm text-neutral-400">${message}</p>
          </div>
        `;
      }

      function renderActivityPagination() {
        if (!activityPageNumbers || !activityPrev || !activityNext) return;

        const totalPages = Math.ceil(filteredActivities.length / activitiesPerPage);
        activityPageNumbers.innerHTML = '';

        for (let page = 1; page <= totalPages; page += 1) {
          const button = document.createElement('button');
          button.className = `px-3 py-2 text-sm font-medium border rounded-lg transition-colors ${page === activitiesCurrentPage ? 'bg-white text-black border-neutral-700' : 'bg-neutral-800 border-neutral-700 text-neutral-300 hover:bg-neutral-700'}`;
          button.textContent = String(page);
          button.addEventListener('click', () => goToActivityPage(page));
          activityPageNumbers.appendChild(button);
        }

        activityPrev.disabled = activitiesCurrentPage === 1 || totalPages === 0;
        activityNext.disabled = activitiesCurrentPage === totalPages || totalPages === 0;
      }

      function renderActivityTimeline() {
        if (!activityTimeline || !activityShown || !activityTotal) return;

        const start = (activitiesCurrentPage - 1) * activitiesPerPage;
        const end = start + activitiesPerPage;
        const pageActivities = filteredActivities.slice(start, end);

        if (pageActivities.length === 0) {
          renderActivityEmptyState(filteredActivities.length !== activities.length);
          activityShown.textContent = '0';
          activityTotal.textContent = String(filteredActivities.length);
          renderActivityPagination();
          return;
        }

        activityTimeline.innerHTML = pageActivities.map((activity) => `
          <div class="flex gap-4 pb-6 border-b border-neutral-800 last:border-b-0 animate-fade-in">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 rounded-full ${activityColorClasses[activity.color] || activityColorClasses.indigo} flex items-center justify-center border">
                ${activityIcons[activity.icon] || activityIcons.alert}
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2 mb-1">
                <div>
                  <p class="text-sm font-medium text-white">${escapeHtml(activity.action)}</p>
                  <p class="text-xs text-neutral-500">oleh ${escapeHtml(activity.actorName)} • ${escapeHtml(formatDateTime(activity.timestamp))}</p>
                </div>
                <span class="badge badge-info text-xs capitalize">${escapeHtml(activity.type)}</span>
              </div>
              <div class="bg-neutral-800/50 rounded-lg p-3 mt-2">
                <p class="text-sm text-neutral-300"><span class="font-medium">Target:</span> ${escapeHtml(activity.target)}</p>
                <p class="text-sm text-neutral-400 mt-1">${escapeHtml(activity.details)}</p>
              </div>
            </div>
          </div>
        `).join('');

        activityShown.textContent = String(pageActivities.length);
        activityTotal.textContent = String(filteredActivities.length);
        renderActivityPagination();
      }

      function goToActivityPage(page) {
        const totalPages = Math.ceil(filteredActivities.length / activitiesPerPage);
        if (page < 1 || page > totalPages) return;

        activitiesCurrentPage = page;
        renderActivityTimeline();
      }

      function filterActivities() {
        const searchTerm = (activitySearch?.value || '').toLowerCase();
        const typeFilter = activityTypeFilter?.value || '';
        const dateFilter = activityDateFilter?.value || '';

        filteredActivities = activities.filter((activity) => {
          const matchesCari = activity.action.toLowerCase().includes(searchTerm) ||
            activity.actorName.toLowerCase().includes(searchTerm) ||
            activity.actorRole.toLowerCase().includes(searchTerm) ||
            activity.target.toLowerCase().includes(searchTerm) ||
            activity.details.toLowerCase().includes(searchTerm);

          const matchesType = typeFilter === '' || activity.type === typeFilter;

          let matchesDate = true;
          if (dateFilter !== '') {
            const activityDate = new Date(activity.timestamp);
            if (Number.isNaN(activityDate.getTime())) {
              return false;
            }

            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

            switch (dateFilter) {
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

      if (activitySearch) {
        activitySearch.addEventListener('input', filterActivities);
      }
      if (activityTypeFilter) {
        activityTypeFilter.addEventListener('change', filterActivities);
      }
      if (activityDateFilter) {
        activityDateFilter.addEventListener('change', filterActivities);
      }
      if (activityPrev) {
        activityPrev.addEventListener('click', () => goToActivityPage(activitiesCurrentPage - 1));
      }
      if (activityNext) {
        activityNext.addEventListener('click', () => goToActivityPage(activitiesCurrentPage + 1));
      }

      if (activityFilterBtn && activityFilterDropdown) {
        activityFilterBtn.addEventListener('click', () => {
          activityFilterDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', (event) => {
          if (!activityFilterDropdown.contains(event.target) && !activityFilterBtn.contains(event.target)) {
            activityFilterDropdown.classList.add('hidden');
          }
        });
      }

      document.addEventListener('DOMContentLoaded', function () {
        renderActivityTimeline();

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
