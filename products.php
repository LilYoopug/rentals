<?php
require_once __DIR__ . '/data/products-data.php';
require_once __DIR__ . '/includes/flash.php';
$products_json = json_encode(get_all_products(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$current_user_json = json_encode(current_user(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$is_logged_in = is_logged_in();
$is_user_catalog = $is_logged_in && is_customer_user();
$account_name = (string) (current_user()['fullname'] ?? 'Pengguna');
$avatar_url = $is_logged_in && !empty(current_user()['avatar_path']) ? ltrim((string) current_user()['avatar_path'], '/') : '';
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LensCraft - Jelajahi Peralatan</title>
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
      .card-hover { transition: transform 0.3s ease, box-shadow 0.3s ease; }
      .card-hover:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(255, 255, 255, 0.08); }
        .nav-blur { background: rgba(5, 5, 5, 0.86); backdrop-filter: blur(18px); }
        .filter-btn { @apply flex items-center justify-center w-12 h-12 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-400 hover:bg-neutral-700 hover:text-white transition-colors; }
        .search-input:focus { border-color: var(--accent-brass); box-shadow: 0 0 0 3px var(--accent-brass-soft); }
      @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
      .animate-fade-in { opacity: 0; animation: fadeInUp 0.8s ease-out forwards; }
      .image-zoom { transition: transform 0.5s ease; }
       .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; border: 1px solid; }
       .filter-shell {
         background: linear-gradient(180deg, rgba(18, 18, 18, 0.9), rgba(11, 11, 11, 0.82));
         border: 1px solid var(--panel-border);
         box-shadow: 0 18px 42px rgba(0, 0, 0, 0.24);
       }
       .control-focus:focus {
         border-color: var(--accent-brass);
         box-shadow: 0 0 0 3px var(--accent-brass-soft);
       }

        .line-clamp-2 {
          overflow: hidden;
          display: -webkit-box;
          -webkit-box-orient: vertical;
          -webkit-line-clamp: 2;
        }

        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

        /* Enhanced card hover */
        .card-hover {
          transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
          transform: translateY(-6px);
          box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
        }

        /* Ensure card content stays structured */
        .card-content {
          display: flex;
          flex-direction: column;
          height: 100%;
        }

        /* Floating middle navigation */
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
  <body class="bg-neutral-950 text-neutral-100 min-h-screen">
    <nav class="fixed top-0 left-0 right-0 z-50 nav-blur border-b border-neutral-800 h-16">
      <div class="flex items-center justify-between h-full px-6">
        <div class="flex items-center gap-4">
          <a href="<?= $is_user_catalog ? 'products.php' : 'index.php' ?>" class="text-2xl font-bold font-serif text-white tracking-tight">LensCraft</a>
          <span class="hidden md:inline-block text-sm text-neutral-500 border-l border-neutral-800 pl-4">Jelajahi Peralatan</span>
        </div>
        <div class="flex items-center gap-3 border-l border-neutral-800 pl-4">
          <?php if ($is_logged_in): ?>
          <div class="text-right hidden sm:block">
            <div class="text-sm font-medium text-white"><?= e($account_name) ?></div>
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
          <a href="logout.php" class="text-sm text-neutral-400 hover:text-white transition-colors" title="Logout">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </a>
          <?php else: ?>
          <a href="login.php" class="px-4 py-2 text-sm font-medium hover:text-white transition-colors">Masuk</a>
          <?php endif; ?>
        </div>
      </div>
    </nav>

    <main class="flex-1 pt-24 pb-12 px-6">
      <div class="max-w-7xl mx-auto space-y-8 animate-fade-in">
         <!-- Header -->
        <div class="text-center space-y-4">
          <h1 class="text-4xl md:text-5xl font-serif text-white">Rental kamera & lensa profesional</h1>
          <p class="text-neutral-400 max-w-2xl mx-auto">
            Jelajahi koleksi pilihan kamera dan lensa profesional untuk produksi foto maupun video dengan alur rental yang cepat.
          </p>
        </div>

        <!-- Filters -->
        <div class="flex justify-center">
          <div class="flex items-center gap-2 w-full max-w-2xl filter-shell rounded-[1.6rem] p-3">
            <!-- Filter Dropdown Button -->
            <div class="relative">
              <button id="filter-btn" class="flex items-center justify-center w-12 h-12 bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-400 hover:bg-neutral-700 hover:text-white transition-colors" aria-label="Toggle filters">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
              </button>
              <!-- Filter Dropdown Panel -->
              <div id="filter-dropdown" class="absolute left-0 top-full mt-3 w-72 bg-neutral-900 border border-neutral-800 rounded-2xl shadow-xl z-10 hidden p-4">
                <div class="space-y-4">
                  <div>
                    <label class="block text-xs font-medium text-neutral-300 mb-2">Kategori</label>
                    <select id="category" class="w-full px-3 py-2 bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-100 text-sm focus:outline-none control-focus">
                      <option value="">Semua Kategori</option>
                      <option value="kamera-mirrorless">Mirrorless</option>
                      <option value="lensa">Lensa</option>
                      <option value="video">Video</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-neutral-300 mb-2">Urutkan</label>
                    <select id="sort" class="w-full px-3 py-2 bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-100 text-sm focus:outline-none control-focus">
                      <option value="featured">Unggulan</option>
                      <option value="price-low">Harga: Rendah ke Tinggi</option>
                      <option value="price-high">Harga: Tinggi ke Rendah</option>
                      <option value="name">Nama</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Search Bar -->
            <div class="flex-1 relative">
              <input type="text" id="search" placeholder="Cari peralatan..." class="w-full pl-4 pr-12 py-3 bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-100 placeholder-neutral-500 focus:outline-none control-focus" />
              <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-white transition-colors" aria-label="Search">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
              </button>
              <!-- Search Suggestions -->
              <div id="suggestions" class="hidden absolute z-20 mt-2 w-full bg-neutral-900 border border-neutral-800 rounded-lg shadow-xl max-h-60 overflow-y-auto">
                <div id="suggestions-list"></div>
              </div>
            </div>
          </div>
        </div>

         <!-- Products Grid -->
         <div id="products-grid" class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"></div>

        <!-- Empty state when no products are available -->
        <div id="products-empty" class="hidden p-12 text-center">
          <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-neutral-800 flex items-center justify-center">
            <svg class="w-8 h-8 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9-4 9 4M4 10v6a2 2 0 002 2h2m10-8v6a2 2 0 01-2 2h-2m-6 0h6" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-white mb-2">Tidak Ada Produk</h3>
          <p class="text-neutral-400 text-sm mb-6 max-w-md mx-auto">Belum ada produk tersedia saat ini.</p>
          <?php if (is_admin_user()): ?>
            <a href="admin/products.php" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-colors">Tambahkan Produk</a>
          <?php else: ?>
            <a href="index.php" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-colors">Kembali ke Beranda</a>
          <?php endif; ?>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 py-6 border-t border-neutral-800">
          <!-- Page Numbers -->
          <div class="flex items-center gap-1 overflow-x-auto max-w-full scrollbar-hide" id="pagination-container">
            <button id="prev-btn" class="px-3 py-2 text-sm font-medium bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-300 hover:bg-neutral-700 hover:text-white transition-colors disabled:opacity-50 flex-shrink-0" disabled>Sebelumnya</button>
            <div id="page-numbers" class="flex items-center gap-1"></div>
            <button id="next-btn" class="px-3 py-2 text-sm font-medium bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-300 hover:bg-neutral-700 hover:text-white transition-colors flex-shrink-0">Berikutnya</button>
          </div>

          <!-- Jump to Page -->
          <div class="flex items-center gap-2 text-sm flex-shrink-0">
            <label for="jump-page" class="text-neutral-400">Lompat ke halaman:</label>
            <div class="relative">
              <input type="text" id="jump-page" inputmode="numeric" pattern="[0-9]*" maxlength="3" value="1" class="w-16 py-2 pr-6 bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-100 text-center focus:outline-none control-focus" />
              <button onclick="goToPageFromJump()" class="absolute right-1 top-1/2 -translate-y-1/2 px-1.5 py-0.5 text-[10px] font-medium bg-neutral-700 text-white rounded hover:bg-neutral-600 transition-colors">Go</button>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer class="border-t border-neutral-800 py-12 bg-neutral-900/50">
      <div class="max-w-7xl mx-auto px-6 text-center text-sm text-neutral-500">
        <p>© 2026 LensCraft. Sistem Rental Kamera.</p>
        <p class="mt-1">Semua rental memerlukan registrasi akun dan persetujuan admin.</p>
      </div>
    </footer>

    <?php if ($is_user_catalog): ?>
    <!-- Floating Middle Navigation -->
    <nav class="floating-nav" role="navigation" aria-label="Quick navigation">
      <button class="floating-nav-btn" data-nav="home" aria-label="Beranda">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <span>Beranda</span>
      </button>
      <button class="floating-nav-btn" data-nav="rentals" aria-label="My Rental">
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
    <?php endif; ?>

    <script>
      // Product dataset
      const products = <?= $products_json ?>;

       // State
       let allProducts = [...products];
       let filteredProducts = [...allProducts];
       let currentPage = 1;
       let itemsPerPage = 8;
       let currentSearchTerm = '';
       let currentKategori = '';
       let currentSort = 'featured';

       // Calculate items per page based on screen size
       function getItemsPerPage() {
         if (window.innerWidth >= 1280) return 16; // xl: 4 cols -> 4 rows
         if (window.innerWidth >= 1024) return 12; // lg: 3 cols -> 4 rows
         if (window.innerWidth >= 640) return 8;  // sm: 2 cols -> 4 rows
         return 4; // mobile: 1 col -> 4 rows
       }

      // DOM elements
      const searchInput = document.getElementById('search');
      const categorySelect = document.getElementById('category');
      const sortSelect = document.getElementById('sort');
      const productsGrid = document.getElementById('products-grid');
      const productsEmpty = document.getElementById('products-empty');
      const paginationContainer = document.getElementById('pagination-container');
      const productsPaginationShell = paginationContainer ? paginationContainer.parentElement : null;
      const filterBtn = document.getElementById('filter-btn');
      const filterDropdown = document.getElementById('filter-dropdown');
      const suggestions = document.getElementById('suggestions');
      const suggestionsList = document.getElementById('suggestions-list');

      // Debounce function
      function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
          const later = () => {
            clearTimeout(timeout);
            func(...args);
          };
          clearTimeout(timeout);
          setTimeout(later, wait);
        };
      }

      // Escape HTML for safe rendering
      function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      }

      function getCategoryLabel(category) {
        const labels = {
          'kamera-mirrorless': 'Mirrorless',
          'lensa': 'Lensa',
          'video': 'Video',
        };

        return labels[category] || category;
      }

      // Get filtered and sorted products based on current state
      // Get filtered and sorted products based on current state
      function getFilteredProducts() {
        const searchTerm = currentSearchTerm.toLowerCase();
        const category = currentKategori;
        const sortBy = currentSort;

        let result = allProducts.filter(p => {
          const matchesSearch = searchTerm === '' || p.name.toLowerCase().includes(searchTerm) || p.brand.toLowerCase().includes(searchTerm);
          const matchesKategori = category === '' || p.category === category;
          return matchesSearch && matchesKategori;
        });

        // Sort (use discounted price if discount exists)
        switch (sortBy) {
          case 'price-low':
            result.sort((a, b) => {
              const aFinal = a.price * (1 - (a.discount || 0) / 100);
              const bFinal = b.price * (1 - (b.discount || 0) / 100);
              return aFinal - bFinal;
            });
            break;
          case 'price-high':
            result.sort((a, b) => {
              const aFinal = a.price * (1 - (a.discount || 0) / 100);
              const bFinal = b.price * (1 - (b.discount || 0) / 100);
              return bFinal - aFinal;
            });
            break;
          case 'name':
            result.sort((a, b) => a.name.localeCompare(b.name));
            break;
          // featured: default order
        }
        return result;
      }

       // Render product card
       function renderProductCard(product) {
        const card = document.createElement('div');
        card.className = 'bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden card-hover animate-fade-in flex flex-col';
        card.style.animationDelay = '0.05s';
        
        const discountBadge = product.discount > 0
          ? `<span class="absolute top-3 right-3 inline-flex items-center px-2 py-1 text-xs font-bold bg-green-900/50 text-green-400 border border-green-800/50 rounded-sm backdrop-blur-sm z-10">-${product.discount}%</span>`
          : '';
        
        const stockBadge = product.inStock
          ? '<span class="absolute top-3 left-3 inline-flex items-center px-2.5 py-1 text-xs font-semibold bg-neutral-900/80 backdrop-blur-sm text-neutral-100 border border-neutral-700/50 rounded-full">Tersedia</span>'
          : '<span class="absolute top-3 left-3 inline-flex items-center px-2.5 py-1 text-xs font-semibold bg-red-900/40 text-red-400 border border-red-800/50 rounded-full backdrop-blur-sm">Tidak Tersedia</span>';
        
        const priceDisplay = product.discount > 0
           ? `
             <div class="flex flex-col items-start gap-1">
               <span class="text-xs text-neutral-600 line-through">${window.formatCurrencyIDR(product.price)}</span>
               <span class="text-lg font-bold text-white">${window.formatCurrencyIDR(Math.round(product.price * (1 - product.discount / 100)))}<span class="text-xs font-normal text-neutral-500 ml-1">/hari</span></span>
             </div>
           `
           : `<span class="text-lg font-bold text-white">${window.formatCurrencyIDR(product.price)}<span class="text-xs font-normal text-neutral-500 ml-1">/hari</span></span>`;

        const categoryBadge = `
          <span class="inline-flex flex-shrink-0 items-center px-2.5 py-1 rounded-full border border-neutral-700 bg-neutral-800/80 text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-300">
            ${escapeHtml(getCategoryLabel(product.category))}
          </span>
        `;

        const descriptionPreview = product.description
          ? `<p class="line-clamp-2 leading-6 text-sm text-neutral-400">${escapeHtml(product.description)}</p>`
          : '';
        
        card.innerHTML = `
          <div class="relative aspect-[4/3] overflow-hidden bg-neutral-800 flex-shrink-0">
            <img src="${product.image}" alt="${escapeHtml(product.name)}" class="w-full h-full object-cover transition-transform duration-500 hover:scale-105" onerror="this.src='images/gear-placeholder.svg'">
            ${stockBadge}
            ${discountBadge}
          </div>
          <div class="p-5 flex flex-col flex-1 min-h-0">
            <div class="flex-1 flex flex-col min-h-0 space-y-3">
              <div class="space-y-2">
                <div class="flex items-start justify-between gap-3">
                  <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-1.5">${escapeHtml(product.brand)}</p>
                  ${categoryBadge}
                </div>
                <h3 class="font-medium text-white leading-snug line-clamp-2 text-base">${escapeHtml(product.name)}</h3>
              </div>
              ${descriptionPreview}
            </div>
            <div class="pt-4 mt-4 border-t border-neutral-800 flex-shrink-0">
              <div class="flex items-center justify-between gap-3">
                <div>
                  ${priceDisplay}
                </div>
                <a href="product-detail.php?id=${product.id}" class="px-5 py-2.5 bg-neutral-800 border border-neutral-700 hover:bg-neutral-700 text-white text-sm font-medium rounded-xl transition-colors whitespace-nowrap">Detail</a>
              </div>
            </div>
          </div>
        `;
        return card;
      }

      // Render products grid
      function renderProducts() {
        productsGrid.innerHTML = '';

        const totalItems = filteredProducts.length;
        if (totalItems === 0) {
          if (productsEmpty) productsEmpty.classList.remove('hidden');
          productsGrid.classList.add('hidden');
          if (productsPaginationShell) productsPaginationShell.classList.add('hidden');
          return;
        }

        // Show grid and pagination when there are items
        if (productsEmpty) productsEmpty.classList.add('hidden');
        productsGrid.classList.remove('hidden');
        if (productsPaginationShell) productsPaginationShell.classList.remove('hidden');

        const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const pageProducts = filteredProducts.slice(start, end);
        pageProducts.forEach(product => {
          productsGrid.appendChild(renderProductCard(product));
        });
      }

       // Search action - loads products when triggered
       function performSearch() {
         currentSearchTerm = searchInput.value.trim();
         currentKategori = categorySelect.value;
         currentSort = sortSelect.value;
         filteredProducts = getFilteredProducts();
         itemsPerPage = getItemsPerPage();
         currentPage = 1;
         renderProducts();
         renderPagination();
       }

      // Get pages to show for adaptive pagination
      function getPagesToShow(current, total) {
        const pages = [];
        const windowSize = 5;
        if (total <= windowSize + 2) {
          for (let i = 1; i <= total; i++) pages.push(i);
          return pages;
        }
        pages.push(1);
        let start = Math.max(2, current - Math.floor(windowSize / 2));
        let end = start + windowSize - 1;
        if (end > total - 1) {
          end = total - 1;
          start = end - windowSize + 1;
          if (start < 2) start = 2;
        }
        if (start > 2) pages.push('...');
        for (let i = start; i <= end; i++) pages.push(i);
        if (end < total - 1) pages.push('...');
        pages.push(total);
        return pages;
      }

      // Render pagination
      function renderPagination() {
        const totalPages = Math.ceil(filteredProducts.length / itemsPerPage);
        const container = document.getElementById('page-numbers');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        
        container.innerHTML = '';
        const pages = getPagesToShow(currentPage, totalPages);
        
        pages.forEach(page => {
          if (page === '...') {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'px-2 text-neutral-500 flex-shrink-0 select-none';
            ellipsis.textContent = '...';
            container.appendChild(ellipsis);
          } else {
            const btn = document.createElement('button');
            btn.className = 'px-3 py-2 text-sm font-medium border rounded-lg flex-shrink-0 transition-colors bg-neutral-800 border-neutral-700 text-neutral-300';
            if (page === currentPage) {
              btn.classList.remove('bg-neutral-800', 'text-neutral-300', 'border-neutral-700');
              btn.classList.add('bg-white', 'text-black', 'border-neutral-700');
            }
            btn.textContent = page;
            btn.addEventListener('click', () => goToPage(page));
            container.appendChild(btn);
          }
        });
        
        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === totalPages || totalPages === 0;
        document.getElementById('jump-page').max = totalPages;
      }

      function goToPage(page) {
        const totalPages = Math.ceil(filteredProducts.length / itemsPerPage);
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        renderProducts();
        renderPagination();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }

      // Jump to page from input
      function goToPageFromJump() {
        const input = document.getElementById('jump-page');
        const page = parseInt(input.value);
        if (!isNaN(page)) {
          goToPage(page);
        } else {
          alert('Masukkan nomor halaman yang valid');
        }
      }

      // Search suggestions (shows as user types, but doesn't filter products)
      function updateSuggestions() {
        const term = searchInput.value.trim().toLowerCase();
        if (!term) {
          suggestions.classList.add('hidden');
          return;
        }
        const matches = allProducts.filter(p => 
          p.name.toLowerCase().includes(term) || p.brand.toLowerCase().includes(term)
        ).slice(0, 5);
        suggestionsList.innerHTML = '';
        if (matches.length === 0) {
          suggestionsList.innerHTML = '<div class="px-4 py-2 text-neutral-400 text-sm">Tidak ada hasil</div>';
        } else {
          matches.forEach(p => {
            const div = document.createElement('div');
            div.className = 'px-4 py-2 hover:bg-neutral-800 cursor-pointer text-neutral-100';
            div.textContent = p.name;
            div.addEventListener('click', () => {
              searchInput.value = p.name;
              suggestions.classList.add('hidden');
              performSearch();
            });
            suggestionsList.appendChild(div);
          });
        }
        suggestions.classList.remove('hidden');
      }

      // Event Listeners
      filterBtn.addEventListener('click', () => {
        filterDropdown.classList.toggle('hidden');
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', (e) => {
        if (!filterDropdown.contains(e.target) && !filterBtn.contains(e.target)) {
          filterDropdown.classList.add('hidden');
        }
      });

      // Search when clicking the search button
      document.querySelector('button[aria-label="Search"]').addEventListener('click', performSearch);

      // Also allow Enter key to trigger search
      searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          performSearch();
        }
      });

      // Add keydown listener for suggestions navigation
      searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          suggestions.classList.add('hidden');
        }
      });

      // Update suggestions as user types (for autocomplete)
      searchInput.addEventListener('input', debounce(updateSuggestions, 200));

      // Close suggestions when clicking outside
      document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !suggestions.contains(e.target)) {
          suggestions.classList.add('hidden');
        }
      });

      categorySelect.addEventListener('change', () => {
        filterDropdown.classList.add('hidden');
        performSearch();
      });
      sortSelect.addEventListener('change', () => {
        filterDropdown.classList.add('hidden');
        performSearch();
      });

      document.getElementById('prev-btn').addEventListener('click', () => goToPage(currentPage - 1));
      document.getElementById('next-btn').addEventListener('click', () => goToPage(currentPage + 1));
      document.getElementById('jump-page').addEventListener('input', (e) => {
        const digitsOnly = e.target.value.replace(/\D/g, '').slice(0, 3);
        e.target.value = digitsOnly;
      });

      // Jump to page
      window.goToPage = goToPage;

      // Set active button based on current page
      function syncFloatingNavFooterState() {
        const floatingNav = document.querySelector('.floating-nav');
        const footer = document.querySelector('footer');
        if (!floatingNav || !footer) return;

        const footerRect = footer.getBoundingClientRect();
        const threshold = floatingNav.offsetHeight + 48;
        const isNearFooter = footerRect.top <= window.innerHeight - threshold;
        floatingNav.classList.toggle('footer-near', isNearFooter);
      }

        // Determine and set active floating nav button based on current URL/path.
        function setFloatingNavActiveFromPath() {
          try {
            const path = window.location.pathname.toLowerCase();
            const segments = path.split('/').filter(Boolean);
            const last = segments.length ? segments[segments.length - 1] : '';
            let activeNav = null;

            if (last === '' || last === 'index.php' || last === 'products.php' || path === '/' || path.includes('/products.php')) {
              activeNav = 'home';
            } else if (last === 'rentals.php' || path.includes('/user/rentals.php') || path.includes('/rentals.php')) {
              activeNav = 'rentals';
            } else if (last === 'profile.php' || path.includes('/user/profile.php') || path.includes('/profile.php') || path.includes('/settings.php')) {
              activeNav = 'settings';
            }

            if (activeNav) {
              document.querySelectorAll('.floating-nav-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.nav === activeNav);
              });
            }
          } catch (e) {
            // ignore
          }
        }

        // Run early so the correct button is highlighted before other load-time effects.
        setFloatingNavActiveFromPath();

        document.addEventListener('DOMContentLoaded', () => {
          // Ensure active state is applied after any dynamic routing or DOM updates.
          setFloatingNavActiveFromPath();

          // Initialize products
          itemsPerPage = getItemsPerPage();
          renderProducts();
          renderPagination();

          // Floating navigation functionality
          const floatingNavButtons = document.querySelectorAll('.floating-nav-btn');
          if (floatingNavButtons.length > 0) {
            floatingNavButtons.forEach(btn => {
              btn.addEventListener('click', function() {
                floatingNavButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const navType = this.dataset.nav;
                const pages = {
                  'home': 'products.php',
                  'rentals': 'user/rentals.php',
                  'settings': 'user/profile.php'
                };
                if (pages[navType]) {
                  window.location.href = pages[navType];
                }
              });
            });
          }

          syncFloatingNavFooterState();
          window.addEventListener('scroll', syncFloatingNavFooterState, { passive: true });
          window.addEventListener('resize', syncFloatingNavFooterState);
        });

      // Recalculate items per page on window resize
      let resizeTimeout;
      window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
          const newItemsPerPage = getItemsPerPage();
          if (newItemsPerPage !== itemsPerPage) {
            itemsPerPage = newItemsPerPage;
            currentPage = 1;
            renderProducts();
            renderPagination();
          }
        }, 250);
      });
    </script>
  <script>window.currentUser = <?= $current_user_json ?>;</script>
    <?= page_runtime_bundle($flash_script) ?>
</body>
</html>
