<?php
require_once __DIR__ . '/../includes/customer-check.php';
require_once __DIR__ . '/../data/products-data.php';
require_once __DIR__ . '/../data/rentals-data.php';
require_once __DIR__ . '/../includes/flash.php';
$customer_session_user = current_user();
$avatar_url = !empty($customer_session_user['avatar_path']) ? '../' . ltrim((string) $customer_session_user['avatar_path'], '/') : '';
$products_json = json_encode(get_all_products(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$rentals_json = json_encode(get_customer_rentals((int) current_user()['id']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$current_user_json = json_encode(current_user(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LensCraft - Rental Saya</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap"
      rel="stylesheet"
    />
    <style>
      :root {
        --accent-brass: #c7a65a;
        --accent-brass-deep: #8f6421;
        --accent-brass-soft: rgba(199, 166, 90, 0.18);
        --panel-border: rgba(255, 255, 255, 0.08);
      }
      body { font-family: "Inter", sans-serif; }
      .font-serif { font-family: "Playfair Display", serif; }
      .nav-blur { background: rgba(5, 5, 5, 0.86); backdrop-filter: blur(18px); }
      .sidebar-blur { background: rgba(5, 5, 5, 0.94); backdrop-filter: blur(18px); }
      .filter-shell {
        background: linear-gradient(180deg, rgba(18, 18, 18, 0.9), rgba(11, 11, 11, 0.82));
        border: 1px solid var(--panel-border);
        box-shadow: 0 18px 42px rgba(0, 0, 0, 0.24);
      }
      .button-primary {
        transition:
          transform 0.25s ease,
          box-shadow 0.25s ease,
          background-color 0.25s ease;
      }
      .button-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 28px rgba(0, 0, 0, 0.24);
      }
      .control-focus:focus {
        border-color: var(--accent-brass);
        box-shadow: 0 0 0 3px var(--accent-brass-soft);
      }
      
      @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
      }
      .animate-fade-in { opacity: 0; animation: fadeInUp 0.8s ease-out forwards; }
      
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
      #modal-status {
        border: none;
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
      .badge-info {
        background-color: rgba(59, 130, 246, 0.15);
        color: #60a5fa;
        border-color: rgba(59, 130, 246, 0.3);
      }
      .badge-neutral {
        background-color: rgba(107, 114, 128, 0.15);
        color: #9ca3af;
        border-color: rgba(107, 114, 128, 0.3);
      }
      
      .card-hover {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
      }
      .card-hover:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
      }

      .sidebar-scroll::-webkit-scrollbar { width: 6px; }
      .sidebar-scroll::-webkit-scrollbar-thumb { background-color: #333; border-radius: 3px; }
      .nav-item-active {
        background-color: rgba(255, 255, 255, 0.1);
        border-left: 3px solid #fff;
        color: #fff;
      }

      /* Tab styles */
      .tab-btn {
        @apply px-6 py-3 text-sm font-medium border-b-2 border-transparent transition-colors;
      }
      .tab-btn.active {
        border-bottom-color: var(--accent-brass);
        color: #fff;
      }
      .tab-btn:not(.active) {
        color: #9ca3af;
      }
      .tab-btn:not(.active):hover {
        color: #d1d5db;
      }

      /* Filter dropdown */
      .filter-dropdown {
        min-width: 200px;
      }

      /* Floating middle navigation */
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
        z-index: 40;
        transition:
          background 0.2s ease,
          opacity 0.2s ease,
          border-color 0.2s ease,
          box-shadow 0.2s ease;
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
        min-width: 3.75rem;
        width: auto;
        height: 3.75rem;
        border-radius: 1.05rem;
        padding: 0 0.55rem;
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

      .floating-nav-btn.active {
        background: linear-gradient(135deg, #c7a65a 0%, #8f6421 100%);
        color: white;
      }

      .floating-nav-btn svg {
        width: 1.5rem;
        height: 1.5rem;
      }

      .floating-nav-btn span {
        font-size: 0.58rem;
        font-weight: 500;
        text-transform: none;
        letter-spacing: 0.02em;
        white-space: nowrap;
      }

      /* Responsive adjustments */
      @media (max-width: 640px) {
        .floating-nav {
          bottom: 1rem;
          padding: 0.6rem 1rem;
          gap: 0.55rem;
        }
        .floating-nav-btn {
          min-width: 3.1rem;
          width: auto;
          height: 3.1rem;
          padding: 0 0.45rem;
        }
        .floating-nav-btn svg {
          width: 1.18rem;
          height: 1.18rem;
        }
        .floating-nav-btn span {
          font-size: 0.49rem;
        }
      }
    </style>
  </head>

  <body class="bg-neutral-950 text-neutral-100 min-h-screen flex flex-col">
    <!-- Top Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-50 nav-blur border-b border-neutral-800 h-16">
      <div class="flex items-center justify-between h-full px-6">
        <!-- Logo and Toggle -->
        <div class="flex items-center gap-4">
          <a href="index.php" class="text-2xl font-bold font-serif text-white tracking-tight">LensCraft</a>
          <span class="hidden md:inline-block text-sm text-neutral-500 border-l border-neutral-800 pl-4">Rental Saya</span>
        </div>

        <!-- Right side -->
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-3 border-l border-neutral-800 pl-4">
            <div class="text-right hidden sm:block">
              <div class="text-sm font-medium text-white"><?= e((string) ($customer_session_user['fullname'] ?? 'User Name')) ?></div>
              <div class="text-xs text-neutral-500">Sudah masuk</div>
            </div>
            <div class="w-10 h-10 bg-neutral-800 rounded-full flex items-center justify-center border border-neutral-700 overflow-hidden">
              <?php if ($avatar_url !== ''): ?>
                <img src="<?= e($avatar_url) ?>" alt="Profile avatar" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
              <?php endif; ?>
              <svg class="w-5 h-5 text-neutral-400" style="<?= $avatar_url !== '' ? 'display:none;' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

    <!-- Main Content -->
    <main class="pt-20 pb-12 px-6 flex-1">
      <div class="max-w-7xl mx-auto animate-fade-in">
        <!-- Page Header -->
        <div class="mb-8 space-y-3">
          <h1 class="text-3xl md:text-4xl font-serif text-white mb-2">Rental Saya</h1>
          <p class="text-neutral-400">Kelola rental aktif dan riwayat rental Anda</p>
        </div>

        <!-- Tabs -->
        <div class="border-b border-neutral-800 mb-8">
          <div class="flex gap-8 overflow-x-auto scrollbar-hide">
            <button class="tab-btn active" data-tab="all">Semua</button>
            <button class="tab-btn" data-tab="aktif">Aktif</button>
            <button class="tab-btn" data-tab="menunggu">Menunggu</button>
            <button class="tab-btn" data-tab="complete">Selesai</button>
          </div>
        </div>

        <!-- Filters & Actions Bar -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 filter-shell rounded-[1.6rem] p-4">
          <div class="flex items-center gap-3 w-full sm:w-auto">
            <!-- Filter Button -->
            <div class="relative">
              <button id="filter-btn" class="flex items-center gap-2 px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-xl text-sm text-neutral-300 hover:bg-neutral-700 hover:text-white transition-colors" aria-label="Filter rentals">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span>Filter</span>
              </button>
              <!-- Filter Dropdown -->
              <div id="filter-dropdown" class="absolute left-0 top-full mt-3 w-64 bg-neutral-900 border border-neutral-800 rounded-2xl shadow-xl z-10 hidden p-4 filter-dropdown">
                <div class="space-y-4">
                  <div>
                    <label class="block text-xs font-medium text-neutral-300 mb-2">Rentang Tanggal</label>
                    <select id="date-range" class="w-full px-3 py-2 bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-100 text-sm focus:outline-none control-focus">
                      <option value="all">Semua Waktu</option>
                      <option value="30">30 hari terakhir</option>
                      <option value="90">90 hari terakhir</option>
                      <option value="365">1 tahun terakhir</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-neutral-300 mb-2">Jenis Peralatan</label>
                    <select id="equipment-type" class="w-full px-3 py-2 bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-100 text-sm focus:outline-none control-focus">
                      <option value="all">Semua Peralatan</option>
                      <option value="kamera-mirrorless">Kamera Mirrorless</option>
                      <option value="lensa">Lensa</option>
                      <option value="video">Peralatan Video</option>
                    </select>
                  </div>
                  <div class="pt-2 border-t border-neutral-800">
                    <button id="clear-filters" class="w-full px-3 py-2 text-sm text-neutral-400 hover:text-white transition-colors">Reset Filter</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Search -->
            <div class="flex-1 relative max-w-md">
              <input type="text" id="rental-search" placeholder="Cari rental..." class="w-full pl-4 pr-12 py-2 bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-100 placeholder-neutral-500 text-sm focus:outline-none control-focus" />
              <button class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-white transition-colors" aria-label="Search">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </button>
            </div>
          </div>

          <div class="text-sm text-neutral-400">
            <span id="rental-count">0</span> rentals
          </div>
        </div>

        <!-- Semua Rentals Tab Content -->
        <div id="tab-all" class="tab-content">
          <div class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden">
            <!-- Table Header (hidden on mobile) -->
            <div class="hidden sm:grid grid-cols-12 gap-4 p-4 bg-neutral-800/50 border-b border-neutral-800 text-xs font-medium text-neutral-400 uppercase tracking-wider">
              <div class="col-span-5">Peralatan</div>
              <div class="col-span-2">Periode Sewa</div>
              <div class="col-span-2">Total</div>
              <div class="col-span-2">Status</div>
              <div class="col-span-1 text-right">Aksi</div>
            </div>

            <!-- Rental Items -->
            <div id="all-rentals-list" class="divide-y divide-neutral-800">
              <!-- Rental items will be populated by JavaScript -->
            </div>

            <!-- Empty State -->
            <div id="all-empty" class="hidden p-12 text-center">
              <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-neutral-800 flex items-center justify-center">
                <svg class="w-8 h-8 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
              </div>
              <h3 class="text-lg font-semibold text-white mb-2">Belum Ada Rental</h3>
              <p class="text-neutral-400 text-sm mb-6 max-w-md mx-auto">Belum ada rental di akun Anda saat ini.</p>
              <a href="../products.php" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Jelajahi Peralatan
              </a>
            </div>
          </div>
        </div>

        <!-- Aktif Tab Content -->
        <div id="tab-active" class="tab-content hidden">
          <div class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden">
            <!-- Table Header (hidden on mobile) -->
            <div class="hidden sm:grid grid-cols-12 gap-4 p-4 bg-neutral-800/50 border-b border-neutral-800 text-xs font-medium text-neutral-400 uppercase tracking-wider">
              <div class="col-span-5">Peralatan</div>
              <div class="col-span-2">Periode Sewa</div>
              <div class="col-span-2">Total</div>
              <div class="col-span-2">Status</div>
              <div class="col-span-1 text-right">Aksi</div>
            </div>
            <div id="active-rentals-list" class="divide-y divide-neutral-800">
              <!-- Populated by JS -->
            </div>
            <div id="active-empty" class="hidden p-12 text-center">
              <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-neutral-800 flex items-center justify-center">
                <svg class="w-8 h-8 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
              </div>
              <h3 class="text-lg font-semibold text-white mb-2">Tidak Ada Rental Aktif</h3>
              <p class="text-neutral-400 text-sm mb-6 max-w-md mx-auto">Belum ada rental dengan status aktif saat ini.</p>
            </div>
          </div>
        </div>

        <!-- Menunggu Tab Content -->
        <div id="tab-pending" class="tab-content hidden">
          <div class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden">
            <!-- Table Header (hidden on mobile) -->
            <div class="hidden sm:grid grid-cols-12 gap-4 p-4 bg-neutral-800/50 border-b border-neutral-800 text-xs font-medium text-neutral-400 uppercase tracking-wider">
              <div class="col-span-5">Peralatan</div>
              <div class="col-span-2">Periode Sewa</div>
              <div class="col-span-2">Total</div>
              <div class="col-span-2">Status</div>
              <div class="col-span-1 text-right">Aksi</div>
            </div>
            <div id="pending-rentals-list" class="divide-y divide-neutral-800">
              <!-- Populated by JS -->
            </div>
            <div id="pending-empty" class="hidden p-12 text-center">
              <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-neutral-800 flex items-center justify-center">
                <svg class="w-8 h-8 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 class="text-lg font-semibold text-white mb-2">Tidak Ada Rental Menunggu</h3>
              <p class="text-neutral-400 text-sm">Belum ada rental dengan status menunggu saat ini.</p>
            </div>
          </div>
        </div>

        <!-- Selesai Tab Content -->
        <div id="tab-complete" class="tab-content hidden">
          <div class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden">
            <!-- Table Header (hidden on mobile) -->
            <div class="hidden sm:grid grid-cols-12 gap-4 p-4 bg-neutral-800/50 border-b border-neutral-800 text-xs font-medium text-neutral-400 uppercase tracking-wider">
              <div class="col-span-5">Peralatan</div>
              <div class="col-span-2">Periode Sewa</div>
              <div class="col-span-2">Total</div>
              <div class="col-span-2">Status</div>
              <div class="col-span-1 text-right">Aksi</div>
            </div>
            <div id="complete-rentals-list" class="divide-y divide-neutral-800">
              <!-- Populated by JS -->
            </div>
            <div id="complete-empty" class="hidden p-12 text-center">
              <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-neutral-800 flex items-center justify-center">
                <svg class="w-8 h-8 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 class="text-lg font-semibold text-white mb-2">Belum Ada Rental Selesai</h3>
              <p class="text-neutral-400 text-sm">Belum ada rental dengan status selesai atau dibatalkan saat ini.</p>
            </div>
          </div>
        </div>

        <div id="rentals-pagination" class="hidden mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 p-6 border border-neutral-800 rounded-2xl bg-neutral-900">
          <div class="text-sm text-neutral-400">
            Menampilkan <span id="rentals-shown" class="font-medium text-white">0</span> dari <span id="rentals-total" class="font-medium text-white">0</span> rental
          </div>
          <div class="flex items-center gap-2">
            <button id="rentals-prev" class="px-4 py-2 text-sm font-medium bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-300 hover:bg-neutral-700 transition-colors disabled:opacity-50" disabled>Sebelumnya</button>
            <div id="rentals-page-numbers" class="flex items-center gap-1"></div>
            <button id="rentals-next" class="px-4 py-2 text-sm font-medium bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-300 hover:bg-neutral-700 transition-colors">Berikutnya</button>
          </div>
        </div>
      </div>
    </main>

    <!-- Return Confirmation Modal -->
    <div id="return-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-200">
      <div class="absolute inset-0 bg-neutral-950/80 backdrop-blur-sm" onclick="closeReturnModal()"></div>
      <div class="relative bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden transform scale-95 opacity-0 transition-all duration-200" id="return-modal-content">
        <div class="p-8">
          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-yellow-900/30 border border-yellow-800 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-2xl font-serif text-white mb-2">Kembalikan Peralatan</h3>
            <p class="text-neutral-400 text-sm">Are you sure you want to return this rental?</p>
          </div>
          
          <div class="bg-neutral-800/50 border border-neutral-700 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-12 h-12 bg-neutral-800 rounded-lg overflow-hidden flex-shrink-0">
                <img id="return-product-image" src="../images/gear-placeholder.svg" alt="" class="w-full h-full object-cover" onerror="this.src='../images/gear-placeholder.svg'">
              </div>
              <div class="min-w-0">
                <h4 class="text-sm font-semibold text-white truncate" id="return-product-name"></h4>
                <p class="text-xs text-neutral-400" id="return-product-brand"></p>
                <p class="text-xs text-neutral-500 mt-0.5" id="return-rental-id"></p>
              </div>
            </div>
            <div class="space-y-2 text-xs">
              <div class="flex justify-between">
                <span class="text-neutral-500">Periode Sewa</span>
                <span class="text-neutral-200" id="return-rental-dates"></span>
              </div>
              <div class="flex justify-between">
                <span class="text-neutral-500">Total</span>
                <span class="text-white font-semibold" id="return-total"></span>
              </div>
            </div>
          </div>
          
          <p class="text-xs text-neutral-500 text-center mb-6">
            Please ensure the equipment is packed properly and ready for pickup/drop-off.
          </p>
          
          <div class="flex gap-3">
            <button onclick="closeReturnModal()" class="flex-1 py-3 px-4 bg-neutral-800 border border-neutral-700 hover:bg-neutral-700 text-white text-sm font-medium rounded-xl transition-colors">
              Cancel
            </button>
            <button onclick="confirmReturn()" class="flex-1 py-3 px-4 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors">
              Konfirmasi Pengembalian
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Rental Details Modal -->
    <div id="rental-details-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-200">
      <div class="absolute inset-0 bg-neutral-950/80 backdrop-blur-sm" onclick="closeRentalDetailsModal()"></div>
      <div class="relative bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl max-w-2xl w-full mx-4 overflow-hidden transform scale-95 opacity-0 transition-all duration-200" id="rental-details-modal-content">
        <div class="p-8">
          <!-- Modal Header -->
          <div class="flex items-start justify-between mb-6">
            <div>
              <h3 class="text-2xl font-serif text-white mb-1">Rental Details</h3>
              <p class="text-sm text-neutral-400" id="modal-rental-id"></p>
            </div>
            <button onclick="closeRentalDetailsModal()" class="text-neutral-400 hover:text-white transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Modal Content -->
          <div class="space-y-4">
            <!-- Product Header -->
            <div class="flex gap-4">
              <div class="w-20 h-20 sm:w-24 sm:h-24 bg-neutral-800 rounded-lg overflow-hidden border border-neutral-700 flex-shrink-0">
                <img id="modal-product-image" src="../images/gear-placeholder.svg" alt="" class="w-full h-full object-cover" onerror="this.src='../images/gear-placeholder.svg'">
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2 mb-1">
                  <div>
                    <h4 class="text-base sm:text-lg font-semibold text-white mb-1" id="modal-product-name"></h4>
                    <p class="text-xs sm:text-sm text-neutral-400" id="modal-product-brand"></p>
                  </div>
                  <span id="modal-status" class="badge flex-shrink-0 text-xs"></span>
                </div>
                <p class="text-xs sm:text-sm text-neutral-400 leading-relaxed line-clamp-2" id="modal-description"></p>
              </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
              <!-- Rental Info -->
              <div class="bg-neutral-800/50 border border-neutral-700 rounded-xl p-3 sm:p-4">
                <h5 class="text-xs sm:text-sm font-semibold text-neutral-300 mb-2 sm:mb-3">Periode Sewa</h5>
                <div class="space-y-2 text-xs sm:text-sm">
                  <div class="flex justify-between">
                    <span class="text-neutral-400">From</span>
                    <span class="text-white" id="modal-rental-dates"></span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-neutral-400">Durasi</span>
                    <span class="text-white" id="modal-duration"></span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-neutral-400">Delivery</span>
                    <span class="text-white capitalize" id="modal-delivery"></span>
                  </div>
                </div>
              </div>

              <!-- Pricing Summary -->
              <div class="bg-neutral-800/50 border border-neutral-700 rounded-xl p-3 sm:p-4">
                <h5 class="text-xs sm:text-sm font-semibold text-neutral-300 mb-2 sm:mb-3">Pricing</h5>
                <div class="space-y-2 text-xs sm:text-sm">
                  <div class="flex justify-between">
                    <span class="text-neutral-400">Daily rate</span>
                    <span class="text-white" id="modal-daily-rate"></span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-neutral-400">Days</span>
                    <span class="text-white" id="modal-days"></span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-neutral-400">Delivery</span>
                    <span class="text-white" id="modal-delivery-fee"></span>
                  </div>
                  <div id="modal-discount-row" class="flex justify-between hidden">
                    <span class="text-neutral-400">Discount</span>
                    <span class="text-green-400" id="modal-discount"></span>
                  </div>
                  <div class="border-t border-neutral-700 pt-2 mt-2 flex justify-between">
                    <span class="font-semibold text-white">Total</span>
                    <span class="text-lg sm:text-xl font-bold text-white" id="modal-total"></span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Timeline -->
            <div class="bg-neutral-800/50 border border-neutral-700 rounded-xl p-3 sm:p-4">
              <h5 class="text-xs sm:text-sm font-semibold text-neutral-300 mb-2 sm:mb-3">Timeline</h5>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 text-xs sm:text-sm">
                <div>
                  <div class="text-neutral-500 text-[10px] sm:text-xs uppercase mb-1">Dibuat</div>
                  <div class="text-white" id="modal-created-at"></div>
                </div>
                <div id="modal-approved-container">
                  <div class="text-neutral-500 text-[10px] sm:text-xs uppercase mb-1">Disetujui</div>
                  <div class="text-white" id="modal-approved-at"></div>
                </div>
                <div id="modal-completed-container">
                  <div class="text-neutral-500 text-[10px] sm:text-xs uppercase mb-1">Selesai</div>
                  <div class="text-white" id="modal-completed-at"></div>
                </div>
                <div id="modal-cancelled-container">
                  <div class="text-neutral-500 text-[10px] sm:text-xs uppercase mb-1">Dibatalkan</div>
                  <div class="text-white" id="modal-cancelled-at"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="mt-6 pt-4 border-t border-neutral-800 flex justify-end gap-3">
            <button onclick="closeRentalDetailsModal()" class="px-6 py-2.5 bg-neutral-800 border border-neutral-700 hover:bg-neutral-700 text-white text-sm font-medium rounded-xl transition-colors">
              Tutup
            </button>
            <button id="modal-action-btn" class="px-6 py-2.5 bg-white text-black hover:bg-neutral-200 text-sm font-semibold rounded-xl transition-colors button-primary hidden">
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Return Success Modal -->
    <div id="return-success-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-200">
      <div class="absolute inset-0 bg-neutral-950/80 backdrop-blur-sm" onclick="closeReturnSuccessModal()"></div>
      <div class="relative bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden transform scale-95 opacity-0 transition-all duration-200" id="return-success-modal-content">
        <div class="p-8">
          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-green-900/30 border border-green-800 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <h3 class="text-2xl font-serif text-white mb-2">Permintaan Terkirim!</h3>
            <p class="text-neutral-400 text-sm">Pengembalian sedang menunggu persetujuan petugas.</p>
          </div>
          
          <div class="bg-neutral-800/50 border border-neutral-700 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-3 mb-2">
              <div class="w-10 h-10 bg-neutral-800 rounded-lg overflow-hidden flex-shrink-0">
                <img id="success-product-image" src="../images/gear-placeholder.svg" alt="" class="w-full h-full object-cover" onerror="this.src='../images/gear-placeholder.svg'">
              </div>
              <div class="min-w-0">
                <h4 class="text-sm font-semibold text-white truncate" id="success-product-name"></h4>
                <p class="text-xs text-neutral-500" id="success-rental-id"></p>
              </div>
            </div>
          </div>
          
          <div class="flex gap-3">
            <button onclick="closeReturnSuccessModal()" class="flex-1 py-3 px-4 bg-neutral-800 border border-neutral-700 hover:bg-neutral-700 text-white text-sm font-medium rounded-xl transition-colors">
              Tutup
            </button>
            <button onclick="closeReturnSuccessModal(); window.location.href='rentals.php';" class="flex-1 py-3 px-4 bg-white text-black hover:bg-neutral-200 text-sm font-semibold rounded-xl transition-colors button-primary">
              Lihat Rental
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Error Modal -->
    <div id="error-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-200">
      <div class="absolute inset-0 bg-neutral-950/80 backdrop-blur-sm" onclick="closeErrorModal()"></div>
      <div class="relative bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden transform scale-95 opacity-0 transition-all duration-200" id="error-modal-content">
        <div class="p-8">
          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-red-900/30 border border-red-800 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
            <h3 class="text-2xl font-serif text-white mb-2">Terjadi Kesalahan</h3>
            <p class="text-neutral-400 text-sm" id="error-message"></p>
          </div>

          <div class="flex gap-3">
            <button onclick="closeErrorModal()" class="flex-1 py-3 px-4 bg-neutral-800 border border-neutral-700 hover:bg-neutral-700 text-white text-sm font-medium rounded-xl transition-colors">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-neutral-800 py-12 bg-neutral-900/50">
      <div class="max-w-7xl mx-auto px-6 text-center text-sm text-neutral-500">
        <p>© 2026 LensCraft. Sistem Rental Kamera.</p>
        <p class="mt-1">Butuh bantuan? Hubungi tim dukungan kami.</p>
      </div>
    </footer>

    <!-- Floating Middle Navigation -->
    <nav class="floating-nav" role="navigation" aria-label="Quick navigation">
      <button class="floating-nav-btn" data-nav="home" aria-label="Beranda">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <span>Beranda</span>
      </button>
      <button class="floating-nav-btn" data-nav="rentals" aria-label="Rental Saya">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
        </svg>
        <span>Rental</span>
      </button>
      <button class="floating-nav-btn" data-nav="settings" aria-label="Pengaturan">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span>Pengaturan</span>
      </button>
    </nav>

    <script>
      const sampleRentals = <?= $rentals_json ?>;

      // Product dataset (same as in product-detail.php)
      const products = <?= $products_json ?>;

      function loadRentals() {
        return sampleRentals;
      }

      const rentals = loadRentals();

      // State
      let currentTab = 'all';
      let filteredRentals = [...rentals];
      let rentalsCurrentPage = 1;
      const rentalsPerPage = 8;
      const tabTargets = {
        all: { listId: 'all-rentals-list', emptyId: 'all-empty' },
        aktif: { listId: 'active-rentals-list', emptyId: 'active-empty' },
        menunggu: { listId: 'pending-rentals-list', emptyId: 'pending-empty' },
        complete: { listId: 'complete-rentals-list', emptyId: 'complete-empty' }
      };
      const tabContentTargets = {
        all: 'tab-all',
        aktif: 'tab-active',
        menunggu: 'tab-pending',
        complete: 'tab-complete'
      };

      // DOM Elements
      const tabButtons = document.querySelectorAll('.tab-btn');
      const tabContents = document.querySelectorAll('.tab-content');
      const filterBtn = document.getElementById('filter-btn');
      const filterDropdown = document.getElementById('filter-dropdown');
      const searchInput = document.getElementById('rental-search');
      const dateRangeSelect = document.getElementById('date-range');
      const equipmentTypeSelect = document.getElementById('equipment-type');
      const clearFiltersBtn = document.getElementById('clear-filters');

      // Tab switching
      tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          const tab = btn.dataset.tab;
          switchTab(tab);
        });
      });

      function switchTab(tab) {
        currentTab = tab;
        rentalsCurrentPage = 1;
        
        // Update button states
        tabButtons.forEach(btn => {
          btn.classList.toggle('active', btn.dataset.tab === tab);
        });
        
        // Update content visibility
        const activeContentId = tabContentTargets[tab] || tabContentTargets.all;
        tabContents.forEach(content => {
          content.classList.toggle('hidden', content.id !== activeContentId);
        });
        
        // Filter and render
        filterAndRenderRentals();
      }

      // Get rentals for a specific tab
      function getRentalsForTab(status) {
        switch(status) {
          case 'all':
            return rentals;
          case 'aktif':
            return rentals.filter(r => r.status === 'aktif');
          case 'menunggu':
            return rentals.filter(r => r.status === 'menunggu' || r.status === 'mendatang' || r.status === 'disetujui');
          case 'complete':
            return rentals.filter(r => r.status === 'selesai' || r.status === 'dibatalkan');
          default:
            return rentals;
        }
      }

      // Filter dropdown toggle
      filterBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        filterDropdown.classList.toggle('hidden');
      });

      document.addEventListener('click', () => {
        filterDropdown.classList.add('hidden');
      });

      // Filter functionality
      function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase();
        const dateRange = dateRangeSelect.value;
        const equipmentType = equipmentTypeSelect.value;
        
        // Get base rentals for current tab
        let baseRentals = getRentalsForTab(currentTab);
        
        filteredRentals = baseRentals.filter(rental => {
          // Search filter
          if (searchTerm) {
            const matchesSearch =
              rental.id.toLowerCase().includes(searchTerm) ||
              rental.product.name.toLowerCase().includes(searchTerm) ||
              rental.product.brand.toLowerCase().includes(searchTerm);
            if (!matchesSearch) return false;
          }
          
          // Date range filter
          if (dateRange !== 'all') {
            const daysAgo = (Date.now() - new Date(rental.createdAt)) / (1000 * 60 * 60 * 24);
            if (daysAgo > parseInt(dateRange)) return false;
          }
          
          // Peralatan type filter
          if (equipmentType !== 'all' && rental.product.category !== equipmentType) {
            return false;
          }
          
          return true;
        });

        rentalsCurrentPage = 1;
        renderRentals();
      }

      searchInput.addEventListener('input', applyFilters);
      dateRangeSelect.addEventListener('change', applyFilters);
      equipmentTypeSelect.addEventListener('change', applyFilters);
      clearFiltersBtn.addEventListener('click', () => {
        searchInput.value = '';
        dateRangeSelect.value = 'all';
        equipmentTypeSelect.value = 'all';
        applyFilters();
      });
      document.getElementById('rentals-prev').addEventListener('click', () => goToRentalsPage(rentalsCurrentPage - 1));
      document.getElementById('rentals-next').addEventListener('click', () => goToRentalsPage(rentalsCurrentPage + 1));
      document.getElementById('rentals-prev').addEventListener('click', () => goToRentalsPage(rentalsCurrentPage - 1));
      document.getElementById('rentals-next').addEventListener('click', () => goToRentalsPage(rentalsCurrentPage + 1));

      function filterAndRenderRentals() {
        applyFilters();
      }

      function renderRentalsPagination() {
        const totalPages = Math.ceil(filteredRentals.length / rentalsPerPage);
        const container = document.getElementById('rentals-page-numbers');
        const prevBtn = document.getElementById('rentals-prev');
        const nextBtn = document.getElementById('rentals-next');

        container.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
          const btn = document.createElement('button');
          btn.className = `px-3 py-2 text-sm font-medium border rounded-lg transition-colors ${i === rentalsCurrentPage ? 'bg-white text-black border-neutral-700' : 'bg-neutral-800 border-neutral-700 text-neutral-300 hover:bg-neutral-700'}`;
          btn.textContent = i;
          btn.addEventListener('click', () => goToRentalsPage(i));
          container.appendChild(btn);
        }

        prevBtn.disabled = rentalsCurrentPage === 1;
        nextBtn.disabled = rentalsCurrentPage === totalPages || totalPages === 0;
      }

      function goToRentalsPage(page) {
        const totalPages = Math.ceil(filteredRentals.length / rentalsPerPage);
        if (page < 1 || page > totalPages) return;
        rentalsCurrentPage = page;
        renderRentals();
      }

      // Render rentals for current tab
      function renderRentals() {
        const filtered = filteredRentals;
        
        const currentTabTargets = tabTargets[currentTab] || tabTargets.all;
        const container = document.getElementById(currentTabTargets.listId);
        const emptyState = document.getElementById(currentTabTargets.emptyId);
        const paginationShell = document.getElementById('rentals-pagination');
        const shownCount = document.getElementById('rentals-shown');
        const totalCount = document.getElementById('rentals-total');
        
        if (filtered.length === 0) {
          container.classList.add('hidden');
          emptyState.classList.remove('hidden');
          paginationShell.classList.add('hidden');
          shownCount.textContent = '0';
          totalCount.textContent = '0';
        } else {
          const totalPages = Math.ceil(filtered.length / rentalsPerPage);
          if (rentalsCurrentPage > totalPages) {
            rentalsCurrentPage = totalPages;
          }

          const start = (rentalsCurrentPage - 1) * rentalsPerPage;
          const end = start + rentalsPerPage;
          const pageItems = filtered.slice(start, end);

          container.classList.remove('hidden');
          emptyState.classList.add('hidden');
          container.innerHTML = pageItems.map(rental => createRentalRow(rental)).join('');
          shownCount.textContent = String(pageItems.length);
          totalCount.textContent = String(filtered.length);
          paginationShell.classList.remove('hidden');
          renderRentalsPagination();
        }
        
        // Update count - show all rentals count for "Semua" tab, otherwise show filtered count
        const countElement = document.getElementById('rental-count');
        countElement.textContent = filtered.length;
      }

      function createRentalRow(rental) {
        const startDate = new Date(rental.startDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        const endDate = new Date(rental.endDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        
        const statusBadge = getStatusBadge(rental.status);
        const originalDailyRate = getOriginalDailyRate(rental);
        const discountDisplay = rental.discount > 0 && originalDailyRate > rental.dailyRate
          ? `<span class="text-xs text-neutral-500 line-through ml-1">${window.formatCurrencyIDR(originalDailyRate)}/hari</span>`
          : '';
        
        let actions = '';
        if (rental.status === 'aktif') {
          actions = `
            <div class="flex gap-2 justify-end">
              <button onclick="viewRentalDetails('${rental.id}')" class="p-2 text-neutral-400 hover:text-white transition-colors" title="Lihat Detail">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
              <button onclick="returnRental('${rental.id}')" class="p-2 text-neutral-400 hover:text-green-400 transition-colors" title="Kembalikan Peralatan">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                </svg>
              </button>
            </div>
          `;
        } else if (rental.status === 'disetujui' && (rental.payment?.status || '') !== 'paid') {
          actions = `
            <div class="flex gap-2 justify-end items-center">
              <button onclick="viewRentalDetails('${rental.id}')" class="p-2 text-neutral-400 hover:text-white transition-colors" title="Lihat Detail">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
              <a href="payment.php?rental=${rental.id}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white text-black hover:bg-neutral-200 text-xs font-semibold transition-colors button-primary" title="Bayar Sekarang">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2m-2 0h14a1 1 0 011 1v8a2 2 0 01-2 2H6a2 2 0 01-2-2v-8a1 1 0 011-1z" />
                </svg>
                Bayar Sekarang
              </a>
            </div>
          `;
        } else {
          // Semua status lain hanya menampilkan detail
          actions = `
            <div class="flex gap-2 justify-end">
              <button onclick="viewRentalDetails('${rental.id}')" class="p-2 text-neutral-400 hover:text-white transition-colors" title="Lihat Detail">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
            </div>
          `;
        }

        return `
          <div class="card-hover group">
            <!-- Mobile Layout (stacked) -->
            <div class="p-4 sm:hidden">
              <div class="flex items-start justify-between gap-3 mb-3">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                  <div class="w-12 h-12 bg-neutral-800 rounded-lg overflow-hidden flex-shrink-0">
                    <img src="${rental.product.image}" alt="${rental.product.name}" class="w-full h-full object-cover" onerror="this.src='../images/gear-placeholder.svg'">
                  </div>
                  <div class="min-w-0">
                    <h4 class="text-sm font-semibold text-white truncate">${rental.product.name}</h4>
                    <p class="text-xs text-neutral-400">${rental.product.brand} • ${capitalizeFirst(rental.product.category)}</p>
                    <p class="text-xs text-neutral-500 mt-0.5">${rental.id}</p>
                  </div>
                </div>
                <div class="flex-shrink-0">${statusBadge}</div>
              </div>
              
              <div class="space-y-2 text-xs mb-3">
                <div class="flex justify-between">
                  <span class="text-neutral-500">Periode Sewa</span>
                  <span class="text-neutral-200">${startDate} - ${endDate}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-neutral-500">Durasi</span>
                  <span class="text-neutral-200">${rental.totalDays} hari</span>
                </div>
                ${rental.discount > 0 ? `
                <div class="flex justify-between">
                  <span class="text-neutral-500">Diskon</span>
                  <span class="text-green-400">${rental.discount}%</span>
                </div>
                ` : ''}
                <div class="flex justify-between">
                  <span class="text-neutral-500">Total</span>
                  <span class="text-white font-semibold">${window.formatCurrencyIDR(rental.total)}</span>
                </div>
              </div>
              
              <div class="flex justify-end gap-2 pt-2 border-t border-neutral-800">
                ${actions.replace(/justify-end/g, '')}
              </div>
            </div>

            <!-- Desktop Layout (table-like) -->
            <div class="hidden sm:grid grid-cols-12 gap-4 p-4 items-center">
              <!-- Peralatan -->
              <div class="col-span-5 flex items-center gap-4">
                <div class="w-16 h-16 bg-neutral-800 rounded-lg overflow-hidden flex-shrink-0">
                  <img src="${rental.product.image}" alt="${rental.product.name}" class="w-full h-full object-cover" onerror="this.src='../images/gear-placeholder.svg'">
                </div>
                <div class="min-w-0">
                  <h4 class="text-sm font-semibold text-white truncate">${rental.product.name}</h4>
                  <p class="text-xs text-neutral-400">${rental.product.brand} • ${capitalizeFirst(rental.product.category)}</p>
                  <p class="text-xs text-neutral-500 mt-1">${rental.id}</p>
                </div>
              </div>

              <!-- Periode Sewa -->
              <div class="col-span-2 text-sm">
                <div class="text-neutral-200">${startDate}</div>
                <div class="text-neutral-500 text-xs">sampai ${endDate}</div>
                <div class="text-neutral-400 text-xs mt-1">${rental.totalDays} hari</div>
              </div>

              <!-- Total -->
              <div class="col-span-2">
                <div class="text-sm font-semibold text-white">${window.formatCurrencyIDR(rental.total)}</div>
                ${rental.discount > 0 ? `<div class="text-xs text-neutral-500">${rental.discount}% diskon diterapkan</div>` : ''}
              </div>

              <!-- Status -->
              <div class="col-span-2">
                ${statusBadge}
              </div>

              <!-- Aksi -->
              <div class="col-span-1">
                ${actions}
              </div>
            </div>
          </div>
        `;
      }

      function getStatusBadge(status) {
        const badges = {
          disetujui: '<span class="badge badge-info">Disetujui</span>',
          aktif: '<span class="badge badge-success">Aktif</span>',
          mendatang: '<span class="badge badge-info">Mendatang</span>',
          selesai: '<span class="badge badge-neutral">Selesai</span>',
          dibatalkan: '<span class="badge badge-danger">Dibatalkan</span>',
          menunggu: '<span class="badge badge-warning">Menunggu</span>'
        };
        return badges[status] || badges.menunggu;
      }

      function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
      }

      function getOriginalDailyRate(rental, productPrice = 0) {
        const explicitOriginal = Number(productPrice || 0);
        if (explicitOriginal > 0) {
          return explicitOriginal;
        }

        const discountedRate = Number(rental.dailyRate || 0);
        const discountPercent = Number(rental.discount || 0);
        if (discountPercent > 0 && discountPercent < 100 && discountedRate > 0) {
          return Math.round(discountedRate / (1 - (discountPercent / 100)));
        }

        return discountedRate;
      }

      function getDiscountAmount(rental, productPrice = 0) {
        const originalDailyRate = getOriginalDailyRate(rental, productPrice);
        return Math.max(0, originalDailyRate - Number(rental.dailyRate || 0));
      }

      // Action handlers
      function viewRentalDetails(rentalId) {
        const rental = rentals.find(r => r.id === rentalId);
        if (!rental) return;

        // Get product details
        const product = products.find(p => p.id === rental.product.id);
        if (!product) return;

        // Populate modal with rental data
        document.getElementById('modal-rental-id').textContent = rental.id;
        document.getElementById('modal-product-name').textContent = rental.product.name;
        document.getElementById('modal-product-brand').textContent = `${rental.product.brand} • ${capitalizeFirst(rental.product.category)}`;
        document.getElementById('modal-product-image').src = rental.product.image;
        document.getElementById('modal-description').textContent = product.description;
      
        // Dates
        const startDate = new Date(rental.startDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        const endDate = new Date(rental.endDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        document.getElementById('modal-rental-dates').textContent = `${startDate} - ${endDate}`;
        document.getElementById('modal-duration').textContent = `${rental.totalDays} hari`;
      
        // Status
        document.getElementById('modal-status').innerHTML = getStatusBadge(rental.status);
      
        // Pricing
        const originalDailyRate = getOriginalDailyRate(rental, product.price);
        const discountAmount = getDiscountAmount(rental, product.price);
        const dailyRateDisplay = rental.discount > 0 && originalDailyRate > rental.dailyRate
          ? `<span class="text-sm text-neutral-500 line-through ml-1">${window.formatCurrencyIDR(originalDailyRate)}/hari</span>`
          : '';
        document.getElementById('modal-daily-rate').innerHTML = `<span class="text-white">${window.formatCurrencyIDR(rental.dailyRate)}/hari</span> ${dailyRateDisplay}`;
        document.getElementById('modal-days').textContent = rental.totalDays;
        document.getElementById('modal-delivery-fee').textContent = window.formatCurrencyIDR(rental.deliveryFee || 0);
      
        if (rental.discount > 0) {
          document.getElementById('modal-discount-row').classList.remove('hidden');
          document.getElementById('modal-discount').textContent = `${rental.discount}% (${window.formatCurrencyIDR(discountAmount)} hemat)`;
        } else {
          document.getElementById('modal-discount-row').classList.add('hidden');
        }
      
        document.getElementById('modal-total').textContent = window.formatCurrencyIDR(rental.total);
        document.getElementById('modal-delivery').textContent = rental.deliveryMethod || 'ambil_sendiri';
      
        // Timeline
        document.getElementById('modal-created-at').textContent = new Date(rental.createdAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
      
        // Show/hide timeline rows based on status
        const approvedContainer = document.getElementById('modal-approved-container');
        const completedContainer = document.getElementById('modal-completed-container');
        const cancelledContainer = document.getElementById('modal-cancelled-container');
      
        if (rental.approvedAt) {
          approvedContainer.classList.remove('hidden');
          document.getElementById('modal-approved-at').textContent = new Date(rental.approvedAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        } else {
          approvedContainer.classList.add('hidden');
        }
      
        if (rental.completedAt) {
          completedContainer.classList.remove('hidden');
          document.getElementById('modal-completed-at').textContent = new Date(rental.completedAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        } else {
          completedContainer.classList.add('hidden');
        }
      
        if (rental.cancelledAt) {
          cancelledContainer.classList.remove('hidden');
          document.getElementById('modal-cancelled-at').textContent = new Date(rental.cancelledAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        } else {
          cancelledContainer.classList.add('hidden');
        }
      
        // Show modal with animation
        const modal = document.getElementById('rental-details-modal');
        const modalContent = document.getElementById('rental-details-modal-content');
      
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.remove('opacity-0');
        modalContent.classList.remove('opacity-0', 'scale-95');
        modalContent.classList.add('scale-100');
      }

      function closeRentalDetailsModal() {
        const modal = document.getElementById('rental-details-modal');
        const modalContent = document.getElementById('rental-details-modal-content');
      
        modal.classList.add('opacity-0');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95', 'opacity-0');
      
        setTimeout(() => {
          modal.classList.add('hidden');
        }, 200);
      }

      // Return rental functionality
      let pendingReturnRentalId = null;

      function returnRental(rentalId) {
        const rental = rentals.find(r => r.id === rentalId);
        if (!rental) return;

        // Store pending rental ID
        pendingReturnRentalId = rentalId;

        // Populate return modal
        document.getElementById('return-product-name').textContent = rental.product.name;
        document.getElementById('return-product-brand').textContent = `${rental.product.brand} • ${capitalizeFirst(rental.product.category)}`;
        document.getElementById('return-product-image').src = rental.product.image;
        document.getElementById('return-rental-id').textContent = rental.id;
      
        const startDate = new Date(rental.startDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        const endDate = new Date(rental.endDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        document.getElementById('return-rental-dates').textContent = `${startDate} - ${endDate}`;
        document.getElementById('return-total').textContent = window.formatCurrencyIDR(rental.total);
      
        // Show return modal
        const modal = document.getElementById('return-modal');
        const modalContent = document.getElementById('return-modal-content');
      
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.remove('opacity-0');
        modalContent.classList.remove('opacity-0', 'scale-95');
        modalContent.classList.add('scale-100');
      }

      function closeReturnModal() {
        const modal = document.getElementById('return-modal');
        const modalContent = document.getElementById('return-modal-content');
      
        modal.classList.add('opacity-0');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95', 'opacity-0');
      
        setTimeout(() => {
          modal.classList.add('hidden');
          pendingReturnRentalId = null;
        }, 200);
      }

      function closeReturnSuccessModal() {
        const modal = document.getElementById('return-success-modal');
        const modalContent = document.getElementById('return-success-modal-content');
      
        modal.classList.add('opacity-0');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95', 'opacity-0');
      
        setTimeout(() => {
          modal.classList.add('hidden');
        }, 200);
      }

      function closeErrorModal() {
        const modal = document.getElementById('error-modal');
        const modalContent = document.getElementById('error-modal-content');
      
        modal.classList.add('opacity-0');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95', 'opacity-0');
      
        setTimeout(() => {
          modal.classList.add('hidden');
        }, 200);
      }

      function confirmReturn() {
        if (!pendingReturnRentalId) return;

        fetch('../process/rental-return-process.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ csrf_token: window.csrfToken, rental_code: pendingReturnRentalId }).toString()
        })
        .then(response => response.json())
        .then(result => {
          if (!result.success) {
            showErrorModal(result.message || 'Gagal memproses pengembalian.');
            return;
          }

          closeReturnModal();
          showReturnSuccessModal(result.rental);
          setTimeout(() => {
            window.location.href = 'rentals.php';
          }, 600);
        })
        .catch(() => showErrorModal('Gagal memproses pengembalian.'));
      }

      // Show return success modal
      function showReturnSuccessModal(rental) {
        const modal = document.getElementById('return-success-modal');
        const modalContent = document.getElementById('return-success-modal-content');
        
        document.getElementById('success-rental-id').textContent = rental.id;
        document.getElementById('success-product-name').textContent = rental.product.name;
        
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.remove('opacity-0');
        modalContent.classList.remove('opacity-0', 'scale-95');
        modalContent.classList.add('scale-100');
      }

      // Close return success modal
      function closeReturnSuccessModal() {
        const modal = document.getElementById('return-success-modal');
        const modalContent = document.getElementById('return-success-modal-content');
      
        modal.classList.add('opacity-0');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95', 'opacity-0');
      
        setTimeout(() => {
          modal.classList.add('hidden');
        }, 200);
      }

      // Show error modal
      function showErrorModal(message) {
        const modal = document.getElementById('error-modal');
        const modalContent = document.getElementById('error-modal-content');
        document.getElementById('error-message').textContent = message;
        
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.remove('opacity-0');
        modalContent.classList.remove('opacity-0', 'scale-95');
        modalContent.classList.add('scale-100');
      }

      // Tutup error modal
      function closeErrorModal() {
        const modal = document.getElementById('error-modal');
        const modalContent = document.getElementById('error-modal-content');
      
        modal.classList.add('opacity-0');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95', 'opacity-0');
      
        setTimeout(() => {
          modal.classList.add('hidden');
        }, 200);
      }

      function cancelRental(rentalId) {
        if (!confirm('Batalkan rental ini?')) {
          return;
        }

        fetch('../process/rental-cancel-process.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ csrf_token: window.csrfToken, rental_code: rentalId }).toString()
        })
        .then(response => response.json())
        .then(result => {
          if (!result.success) {
            showErrorModal(result.message || 'Gagal membatalkan rental.');
            return;
          }

          window.location.href = 'rentals.php';
        })
        .catch(() => showErrorModal('Gagal membatalkan rental.'));
      }

      function extendRental(rentalId) {
        const rental = rentals.find(r => r.id === rentalId);
        if (rental) {
          window.location.href = `../product-detail.php?id=${encodeURIComponent(rental.product.id)}`;
        }
      }

      function reorderRental(rentalId) {
        const rental = rentals.find(r => r.id === rentalId);
        if (rental) {
          window.location.href = `../product-detail.php?id=${encodeURIComponent(rental.product.id)}`;
        }
      }

      // Set active button based on current page and handle navigation
      function syncFloatingNavFooterState() {
        const floatingNav = document.querySelector('.floating-nav');
        const footer = document.querySelector('footer');
        if (!floatingNav || !footer) return;

        const footerRect = footer.getBoundingClientRect();
        const threshold = floatingNav.offsetHeight + 48;
        const isNearFooter = footerRect.top <= window.innerHeight - threshold;
        floatingNav.classList.toggle('footer-near', isNearFooter);
      }

      document.addEventListener('DOMContentLoaded', () => {
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        const pageMap = {
          'index.php': 'home',
          'rentals.php': 'rentals',
          'index.php': 'home',
          'profile.php': 'settings'
        };
        
        const activeNav = pageMap[currentPage];
        if (activeNav) {
          document.querySelectorAll('.floating-nav-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.nav === activeNav) {
              btn.classList.add('active');
            }
          });
        }
        
        // Initialize rentals
        renderRentals();
        
        // Floating navigation functionality
        const floatingNavButtons = document.querySelectorAll('.floating-nav-btn');
        floatingNavButtons.forEach(btn => {
          btn.addEventListener('click', function() {
            // Remove active class from all buttons
            floatingNavButtons.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            // Navigate based on data-nav attribute
            const navType = this.dataset.nav;
            const pages = {
              'home': 'index.php',
              'rentals': 'rentals.php',
              'settings': 'profile.php'
            };
            if (pages[navType]) {
              window.location.href = pages[navType];
            }
          });
        });

        syncFloatingNavFooterState();
        window.addEventListener('scroll', syncFloatingNavFooterState, { passive: true });
        window.addEventListener('resize', syncFloatingNavFooterState);
      });
    </script>
  <script>window.currentUser = <?= $current_user_json ?>;</script>
  <script>window.csrfToken = <?= json_encode(csrf_token()) ?>;</script>
    <?= page_runtime_bundle($flash_script) ?>
  </body>
</html>
