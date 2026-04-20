<?php
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/data/products-data.php';

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
    <title>LensCraft - Detail Produk</title>
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
      }
      body { font-family: "Inter", sans-serif; }
       .font-serif { font-family: "Playfair Display", serif; }
       .card-hover {
         transition: transform 0.3s ease, box-shadow 0.3s ease;
       }
       .card-hover:hover {
         transform: translateY(-6px);
         box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
       }
      .nav-blur { background: rgba(5, 5, 5, 0.86); backdrop-filter: blur(18px); }
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
      @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
      .animate-fade-in { opacity: 0; animation: fadeInUp 0.8s ease-out forwards; }
      .delay-100 { animation-delay: 0.1s !important; }
      .delay-200 { animation-delay: 0.2s !important; }
      .image-zoom { transition: transform 0.5s ease; }
      .image-zoom:hover { transform: scale(1.05); }
      .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; border: 1px solid; }
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
        transition: background 0.2s ease, opacity 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
      }
      .floating-nav:hover { background: rgba(17, 17, 17, 0.75); }
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
      .floating-nav-btn:hover { background: rgba(255, 255, 255, 0.08); color: #f3f4f6; }
      .floating-nav-btn.active { background: linear-gradient(135deg, #c7a65a 0%, #8f6421 100%); color: white; }
      .floating-nav-btn svg { width: 1.5rem; height: 1.5rem; }
      .floating-nav-btn span { font-size: 0.58rem; font-weight: 500; letter-spacing: 0.02em; white-space: nowrap; }
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
    <nav class="fixed top-0 w-full z-50 nav-blur border-b border-neutral-800">
      <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <a href="<?= $is_user_catalog ? 'products.php' : 'index.php' ?>" class="text-2xl font-bold font-serif text-white tracking-tight">LensCraft</a>
        <div class="flex items-center gap-6">
          <a href="products.php" class="text-sm text-neutral-400 hover:text-white transition-colors">Jelajahi Peralatan</a>
          <?php if ($is_logged_in): ?>
          <div class="flex items-center gap-3 border-l border-neutral-800 pl-4">
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
          </div>
          <?php else: ?>
          <a href="login.php" class="px-4 py-2 bg-white text-black text-sm font-semibold rounded-xl hover:bg-neutral-200 transition-colors button-primary">Masuk</a>
          <?php endif; ?>
        </div>
      </div>
    </nav>

    <main class="pt-24 pb-20 px-6">
      <div class="max-w-7xl mx-auto" id="product-detail-container"></div>
      <section class="max-w-7xl mx-auto mt-20" id="related-products">
        <h2 class="text-2xl font-serif text-white mb-8">Peralatan Terkait</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6" id="related-grid"></div>
      </section>
    </main>

    <!-- Konfirmasiation Modal -->
    <div id="confirm-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-200">
      <div class="absolute inset-0 bg-neutral-950/80 backdrop-blur-sm" onclick="closeKonfirmasiModal()"></div>
      <div class="relative bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden transform scale-95 opacity-0 transition-all duration-200" id="confirm-modal-content">
        <div class="p-8">
          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-yellow-900/30 border border-yellow-800 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-2xl font-serif text-white mb-2">Konfirmasi permintaan rental</h3>
            <p class="text-neutral-400 text-sm">Periksa detail akhir sebelum mengirim permintaan rental Anda.</p>
          </div>
          
          <div class="bg-neutral-800/50 border border-neutral-700 rounded-xl p-4 mb-6">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-neutral-400">Peralatan</span>
              <span class="text-sm font-medium text-white" id="confirm-product-name"></span>
            </div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-neutral-400">Periode Rental</span>
              <span class="text-sm font-medium text-white" id="confirm-rental-dates"></span>
            </div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-neutral-400">Dikirim</span>
              <span class="text-sm font-medium text-white capitalize" id="confirm-delivery-method"></span>
            </div>
            <div class="flex items-start justify-between gap-4 mb-2 hidden" id="confirm-delivery-address-row">
              <span class="text-sm text-neutral-400">Alamat Pengiriman</span>
              <span class="text-sm font-medium text-white text-right" id="confirm-delivery-address"></span>
            </div>
            <div class="border-t border-neutral-700 pt-2 mt-2">
              <div class="flex items-center justify-between">
                <span class="text-base font-semibold text-white">Total</span>
                <span class="text-lg font-bold text-white" id="confirm-total"></span>
              </div>
            </div>
          </div>
          
          <div class="flex gap-3">
            <button onclick="closeKonfirmasiModal()" class="flex-1 py-3 px-4 bg-neutral-800 border border-neutral-700 hover:bg-neutral-700 text-white text-sm font-medium rounded-xl transition-colors">
              Batal
            </button>
            <button onclick="confirmAndSubmit()" class="flex-1 py-3 px-4 bg-white text-black text-sm font-semibold rounded-xl transition-colors button-primary">
              Konfirmasi & Kirim
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div id="success-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-200">
      <div class="absolute inset-0 bg-neutral-950/80 backdrop-blur-sm" onclick="closeModal()"></div>
      <div class="relative bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden transform scale-95 opacity-0 transition-all duration-200" id="modal-content">
        <div class="p-8">
          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-green-900/30 border border-green-800 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <h3 class="text-2xl font-serif text-white mb-2">Permintaan rental terkirim</h3>
            <p class="text-neutral-400 text-sm">Permintaan Anda berhasil masuk ke sistem dan menunggu peninjauan.</p>
          </div>
          
          <div class="bg-neutral-800/50 border border-neutral-700 rounded-xl p-4 mb-6">
            <div class="flex items-center justify-between mb-3">
              <span class="text-sm text-neutral-400">ID Rental</span>
              <span class="text-sm font-semibold text-white" id="modal-rental-id"></span>
            </div>
            <div class="flex items-center justify-between mb-3">
              <span class="text-sm text-neutral-400">Peralatan</span>
              <span class="text-sm font-medium text-white text-right" id="modal-product-name"></span>
            </div>
            <div class="flex items-center justify-between mb-3">
              <span class="text-sm text-neutral-400">Periode Rental</span>
              <span class="text-sm font-medium text-white" id="modal-rental-dates"></span>
            </div>
            <div class="flex items-center justify-between mb-3">
              <span class="text-sm text-neutral-400">Metode Pengiriman</span>
              <span class="text-sm font-medium text-white capitalize" id="modal-delivery-method"></span>
            </div>
            <div class="border-t border-neutral-700 pt-3 mt-3">
              <div class="flex items-center justify-between">
                <span class="text-base font-semibold text-white">Total</span>
                <span class="text-xl font-bold text-white" id="modal-total"></span>
              </div>
            </div>
          </div>
          
          <p class="text-xs text-neutral-500 text-center mb-6">
            Anda akan diberi tahu setelah permintaan rental disetujui. Pantau pembaruan di halaman rental saya.
          </p>
          
          <div class="flex gap-3">
            <button onclick="closeModal()" class="flex-1 py-3 px-4 bg-neutral-800 border border-neutral-700 hover:bg-neutral-700 text-white text-sm font-medium rounded-xl transition-colors">
              Lanjut jelajah
            </button>
            <button onclick="viewRentals()" class="flex-1 py-3 px-4 bg-white text-black text-sm font-semibold rounded-xl transition-colors button-primary">
              Lihat Rental
            </button>
          </div>
        </div>
      </div>
    </div>

    <footer class="border-t border-neutral-800 py-12 bg-neutral-900/50">
      <div class="max-w-7xl mx-auto px-6 text-center text-sm text-neutral-500">
        <p>© 2026 LensCraft. Sistem Rental Kamera.</p>
        <p class="mt-1">Semua rental memerlukan registrasi akun dan persetujuan admin.</p>
      </div>
    </footer>

    <script>
      // Product dataset (also in products.php)
      const products = <?= $products_json ?>;


      let allProduk = [...products];
      let currentProduct = null;

      function getProductIdFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return parseInt(params.get('id')) || null;
      }

      function getProductById(id) {
        return allProduk.find(p => p.id === id);
      }

      function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      }

      function renderProductDetail(product) {
        const container = document.getElementById('product-detail-container');
        const stockBadge = product.inStock
          ? '<span class="badge bg-green-900/80 text-green-300 border border-green-800 px-3 py-1">Tersedia</span>'
          : '<span class="badge bg-red-900/80 text-red-300 border border-red-800 px-3 py-1">Tidak Tersedia</span>';
        const categoryLabel = product.category.charAt(0).toUpperCase() + product.category.slice(1);

        container.innerHTML = `
          <nav class="mb-8 animate-fade-in">
            <ol class="flex items-center gap-2 text-sm text-neutral-400">
              <li><a href="products.php" class="hover:text-white transition-colors">Produk</a></li>
              <li>/</li>
              <li><span class="text-neutral-200">${escapeHtml(product.name)}</span></li>
            </ol>
          </nav>

          <div class="grid lg:grid-cols-2 gap-12 items-start">
            <div class="animate-fade-in delay-100 lg:sticky lg:top-24">
              <div class="relative aspect-square bg-neutral-900 rounded-2xl overflow-hidden border border-neutral-800 image-zoom">
                <img src="${product.image}" 
                     alt="${product.name.replace(/"/g, '&quot;')}" 
                     class="w-full h-full object-cover"
                     onerror="this.src='images/gear-placeholder.svg'" />
              </div>
            </div>

            <div class="animate-fade-in delay-200 space-y-6">
              <div>
                <div class="flex items-center gap-3 mb-3">
                  <span class="badge bg-neutral-800 text-neutral-300 border border-neutral-700">${categoryLabel}</span>
                  ${stockBadge}
                </div>
                <h1 class="text-4xl md:text-5xl font-serif text-white mb-2">${product.name.replace(/"/g, '&quot;')}</h1>
                <div class="flex items-center gap-3 mb-4">
                  <span class="text-xl text-neutral-400">${product.brand.replace(/"/g, '"')}</span>
                </div>
              </div>

              <div class="border-t border-neutral-800 pt-6">
                <div class="flex items-baseline gap-3 mb-2">
                  ${product.discount > 0 ? `
                    <div class="flex flex-col items-start gap-1 mb-2">
                      <span class="text-xl text-neutral-500 line-through">${window.formatCurrencyIDR(product.price)}</span>
                      <span class="text-3xl font-bold text-green-400">${window.formatCurrencyIDR(Math.round(product.price * (1 - product.discount / 100)))}<span class="text-lg font-normal text-neutral-500">/hari</span></span>
                    </div>
                    <span class="px-3 py-1 text-sm font-medium bg-green-900/50 text-green-300 border border-green-800 rounded-full">-${product.discount}% hemat</span>
                  ` : `
                    <span class="text-3xl font-bold text-white">${window.formatCurrencyIDR(product.price)}<span class="text-lg font-normal text-neutral-500">/hari</span></span>
                  `}
                </div>
              </div>

              <div class="border-t border-neutral-800 pt-6">
                <h3 class="text-lg font-semibold text-white mb-3">Deskripsi</h3>
                <p class="text-neutral-400 leading-relaxed">${product.description.replace(/"/g, '&quot;')}</p>
              </div>

              <div class="border-t border-neutral-800 pt-6">
                <h3 class="text-lg font-semibold text-white mb-3">Spesifikasi</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                  <div class="bg-neutral-900/50 p-4 rounded-lg border border-neutral-800">
                    <div class="text-neutral-500 text-xs uppercase mb-1">Kategori</div>
                    <div class="text-white font-medium">${categoryLabel}</div>
                  </div>
                  <div class="bg-neutral-900/50 p-4 rounded-lg border border-neutral-800">
                    <div class="text-neutral-500 text-xs uppercase mb-1">Merek</div>
                    <div class="text-white font-medium">${product.brand.replace(/"/g, '&quot;')}</div>
                  </div>
                  <div class="bg-neutral-900/50 p-4 rounded-lg border border-neutral-800">
                    <div class="text-neutral-500 text-xs uppercase mb-1">Ketersediaan</div>
                    <div class="text-white font-medium">${product.inStock ? 'Siap Disewa' : 'Belum Tersedia'}</div>
                  </div>
                </div>
              </div>

              <div class="border-t border-neutral-800 pt-6">
                <div id="rental-form-${product.id}" class="space-y-6 ${product.inStock ? '' : 'opacity-50 pointer-events-none'}">
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-neutral-300 mb-2">Tanggal Mulai</label>
                      <input type="date"
                             id="start-date-${product.id}"
                             class="w-full px-4 py-3 bg-neutral-900 border border-neutral-700 rounded-xl text-neutral-100 focus:outline-none control-focus"
                             min="${new Date().toISOString().split('T')[0]}"
                             value="${new Date().toISOString().split('T')[0]}"
                             onchange="calculateTotal(${product.id})">
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-neutral-300 mb-2">Tanggal Selesai</label>
                      <input type="date"
                             id="end-date-${product.id}"
                             class="w-full px-4 py-3 bg-neutral-900 border border-neutral-700 rounded-xl text-neutral-100 focus:outline-none control-focus"
                             min="${new Date().toISOString().split('T')[0]}"
                             value="${new Date(Date.now() + 86400000).toISOString().split('T')[0]}"
                             onchange="calculateTotal(${product.id})">
                    </div>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-3">Metode Pengiriman</label>
                    <div class="grid grid-cols-2 gap-3">
                      <label class="relative flex items-center justify-center p-4 bg-neutral-900 border-2 border-neutral-700 rounded-lg cursor-pointer hover:border-neutral-500 transition-colors">
                        <input type="radio"
                               name="delivery-method-${product.id}"
                               value="ambil_sendiri"
                               class="sr-only"
                               checked
                               onchange="calculateTotal(${product.id})">
                        <div class="text-center">
                          <svg class="w-6 h-6 mx-auto mb-2 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                          </svg>
                          <div class="text-sm font-medium text-neutral-200">Ambil Sendiri</div>
                          <div class="text-xs text-neutral-500 mt-1">Gratis</div>
                        </div>
                        <div class="absolute inset-0 border-2 rounded-lg hidden delivery-method-selected-${product.id}" style="border-color: var(--accent-brass);"></div>
                      </label>
                      <label class="relative flex items-center justify-center p-4 bg-neutral-900 border-2 border-neutral-700 rounded-lg cursor-pointer hover:border-neutral-500 transition-colors">
                        <input type="radio"
                               name="delivery-method-${product.id}"
                               value="diantar"
                               class="sr-only"
                               onchange="calculateTotal(${product.id})">
                        <div class="text-center">
                          <svg class="w-6 h-6 mx-auto mb-2 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                          </svg>
                          <div class="text-sm font-medium text-neutral-200">Dikirim</div>
                          <div class="text-xs text-neutral-500 mt-1">+${window.formatCurrencyIDR(50000)}</div>
                        </div>
                        <div class="absolute inset-0 border-2 rounded-lg hidden delivery-method-selected-${product.id}" style="border-color: var(--accent-brass);"></div>
                      </label>
                    </div>
                  </div>

                  <div class="bg-neutral-900/50 p-4 rounded-lg border border-neutral-800">
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm text-neutral-400">Tarif Harian</span>
                      <span class="text-sm text-neutral-200" id="daily-rate-display-${product.id}">
                        ${product.discount > 0 ? window.formatCurrencyIDR(Math.round(product.price * (1 - product.discount / 100))) : window.formatCurrencyIDR(product.price)}
                      </span>
                    </div>
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm text-neutral-400">Lama Sewa</span>
                      <span class="text-sm text-neutral-200" id="days-display-${product.id}">1 hari</span>
                    </div>
                    <div class="flex items-center justify-between mb-2" id="delivery-row-${product.id}">
                      <span class="text-sm text-neutral-400">Biaya Pengiriman</span>
                      <span class="text-sm text-neutral-200" id="delivery-fee-display-${product.id}">${window.formatCurrencyIDR(0)}</span>
                    </div>
                    ${product.discount > 0 ? `
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm text-neutral-400">Diskon (${product.discount}%)</span>
                      <span class="text-sm text-green-400">-${window.formatCurrencyIDR(product.price * product.discount / 100)}</span>
                    </div>
                    ` : ''}
                    <div class="border-t border-neutral-700 pt-3 mt-3">
                      <div class="flex items-center justify-between">
                        <span class="text-base font-semibold text-white">Total</span>
                        <span class="text-xl font-bold text-white" id="total-display-${product.id}">${window.formatCurrencyIDR(product.discount > 0 ? Math.round(product.price * (1 - product.discount / 100)) : product.price)}</span>
                      </div>
                    </div>
                  </div>

                  <button onclick="showKonfirmasiModal(${product.id})"
                          class="w-full py-4 px-6 bg-white text-black text-center font-semibold rounded-xl transition-colors button-primary flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Ajukan Rental
                  </button>
                </div>
                <p class="text-xs text-neutral-500 text-center mt-3">
                  Anda harus masuk untuk menyelesaikan rental. Akun baru perlu ditinjau admin terlebih dahulu.
                </p>
              </div>

              <div class="border-t border-neutral-800 pt-6">
                <h3 class="text-sm font-semibold text-white mb-3">Syarat Rental</h3>
                <ul class="text-sm text-neutral-400 space-y-2">
                  <li>• Tarif sewa harian tersedia untuk semua peralatan</li>
                  <li>• Semua peralatan diperiksa dan dibersihkan sebelum dikirim</li>
                  <li>• Dukungan operasional tersedia selama masa sewa</li>
                  <li>• Ambil sendiri atau pengiriman sesuai pilihan Anda</li>
                  <li>• Perubahan jadwal dapat diajukan sebelum hari pengambilan</li>
                </ul>
              </div>
            </div>
          </div>
        `;
      }

      function renderRelatedProduk(product) {
        const related = allProduk.filter(p => p.id !== product.id && p.category === product.category).slice(0, 4);
        const grid = document.getElementById('related-grid');
        if (related.length === 0) {
          grid.innerHTML = '<p class="text-neutral-500">Belum ada produk terkait di kategori ini.</p>';
          return;
        }
        grid.innerHTML = related.map(p => {
          const discountBadge = p.discount > 0
            ? `<span class="absolute top-3 right-3 inline-flex items-center px-2 py-1 text-xs font-bold bg-green-900/50 text-green-400 border border-green-800/50 rounded-sm backdrop-blur-sm z-10">-${p.discount}%</span>`
            : '';
          
          const stockBadge = p.inStock
            ? '<span class="absolute top-3 left-3 inline-flex items-center px-2.5 py-1 text-xs font-semibold bg-neutral-900/80 backdrop-blur-sm text-neutral-100 border border-neutral-700/50 rounded-full">Tersedia</span>'
            : '<span class="absolute top-3 left-3 inline-flex items-center px-2.5 py-1 text-xs font-semibold bg-red-900/40 text-red-400 border border-red-800/50 rounded-full backdrop-blur-sm">Tidak Tersedia</span>';
          
          const priceDisplay = p.discount > 0
            ? `
              <div class="flex flex-col items-start gap-1">
                <span class="text-sm text-neutral-600 line-through">${window.formatCurrencyIDR(p.price)}</span>
                <span class="text-xl font-bold text-white">${window.formatCurrencyIDR(Math.round(p.price * (1 - p.discount / 100)))}<span class="text-sm font-normal text-neutral-500 ml-1">/hari</span></span>
              </div>
            `
            : `<span class="text-xl font-bold text-white">${window.formatCurrencyIDR(p.price)}<span class="text-sm font-normal text-neutral-500 ml-1">/hari</span></span>`;
          
          return `
          <div class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden card-hover animate-fade-in flex flex-col cursor-pointer" onclick="window.location.href='product-detail.php?id=${p.id}'">
            <div class="relative aspect-[4/3] overflow-hidden bg-neutral-800 flex-shrink-0">
              <img src="${p.image}"
                   alt="${p.name.replace(/"/g, '"')}"
                   class="w-full h-full object-cover transition-transform duration-500 hover:scale-105"
                   onerror="this.src='images/gear-placeholder.svg'" />
              ${stockBadge}
              ${discountBadge}
            </div>
            <div class="p-5 flex flex-col flex-1 min-h-0">
              <div class="flex-1 flex flex-col min-h-0 space-y-3">
                <div>
                  <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-1.5">${escapeHtml(p.brand)}</p>
                  <h3 class="font-medium text-white leading-snug line-clamp-2 text-base">${escapeHtml(p.name)}</h3>
                </div>
              </div>
              <div class="pt-4 mt-4 border-t border-neutral-800 flex-shrink-0">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    ${priceDisplay}
                  </div>
                </div>
              </div>
            </div>
          </div>
          `;
        }).join('');
      }

      function init() {
        const productId = getProductIdFromUrl();
        const container = document.getElementById('product-detail-container');
        if (!productId) {
          container.innerHTML = `
            <div class="text-center py-20">
              <h1 class="text-2xl font-serif text-white mb-4">Produk Tidak Ditemukan</h1>
              <p class="text-neutral-400 mb-8">Silakan pilih produk dari katalog kami.</p>
              <a href="products.php" class="px-6 py-3 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-colors inline-block">Browse Produk</a>
            </div>`;
          return;
        }

        const product = getProductById(productId);
        if (!product) {
          container.innerHTML = `
            <div class="text-center py-20">
              <h1 class="text-2xl font-serif text-white mb-4">Produk Tidak Ditemukan</h1>
              <p class="text-neutral-400 mb-8">Produk yang diminta tidak tersedia.</p>
              <a href="products.php" class="px-6 py-3 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-colors inline-block">Browse Produk</a>
            </div>`;
          return;
        }

        currentProduct = product;
        renderProductDetail(product);
        renderRelatedProduk(product);
        
        // Initialize rental form after rendering
        setTimeout(() => {
          calculateTotal(product.id);
          initDikirimMethodSelection(product.id);
        }, 0);
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }

      document.addEventListener('DOMContentLoaded', init);

      // Calculate rental total based on dates and delivery method
      function calculateTotal(productId) {
        const product = getProductById(productId);
        if (!product) return;

        const startDateInput = document.getElementById(`start-date-${productId}`);
        const endDateInput = document.getElementById(`end-date-${productId}`);
        
        if (!startDateInput || !endDateInput) return;

        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        // Validate dates
        if (startDate > endDate) {
          endDateInput.value = startDateInput.value;
        }
        
        // Calculate days (inclusive)
        const timeDiff = Math.abs(new Date(endDateInput.value) - new Date(startDateInput.value));
        const days = Math.ceil(timeDiff / (1000 * 60 * 60 * 24)) + 1;
        
        // Get delivery method
        const deliveryMethod = document.querySelector(`input[name="delivery-method-${productId}"]:checked`)?.value || 'ambil_sendiri';
        const deliveryFee = deliveryMethod === 'diantar' ? 50000 : 0;
        
        // Calculate daily rate with discount
        const dailyRate = product.discount > 0
          ? Math.round(product.price * (1 - product.discount / 100))
          : product.price;
        
        const subtotal = dailyRate * days;
        const total = subtotal + deliveryFee;
        
        // Update display
        const daysDisplay = document.getElementById(`days-display-${productId}`);
        const deliveryFeeDisplay = document.getElementById(`delivery-fee-display-${productId}`);
        const deliveryRow = document.getElementById(`delivery-row-${productId}`);
        const totalDisplay = document.getElementById(`total-display-${productId}`);
        
        if (daysDisplay) {
          daysDisplay.textContent = `${days} hari`;
        }
        
        if (deliveryFeeDisplay) {
          deliveryFeeDisplay.textContent = window.formatCurrencyIDR(deliveryFee);
        }
        
        if (deliveryRow) {
          deliveryRow.style.display = deliveryMethod === 'diantar' ? 'flex' : 'none';
        }
        
        if (totalDisplay) {
          totalDisplay.textContent = window.formatCurrencyIDR(total);
        }
        
        // Update delivery method selection visual
        updateDikirimMethodSelection(productId);
      }

      // Update visual selection for delivery method
      function updateDikirimMethodSelection(productId) {
        const deliveryMethods = document.querySelectorAll(`input[name="delivery-method-${productId}"]`);
        deliveryMethods.forEach(method => {
          const label = method.closest('label');
          const selectedIndicator = label.querySelector(`.delivery-method-selected-${productId}`);
          if (selectedIndicator) {
            selectedIndicator.style.display = method.checked ? 'block' : 'none';
          }
        });
      }

      // Store rental data for confirmation
      let pendingRental = null;

      function getCurrentUser() {
        return window.currentUser || null;
      }

      function formatDeliveryAddress(user) {
        if (!user) {
          return 'Alamat belum diisi';
        }

        const parts = [
          user.address_line1,
          user.address_line2,
          user.city,
          user.province,
          user.zip_code,
          user.country
        ]
          .map(value => String(value || '').trim())
          .filter(Boolean);

        return parts.length > 0 ? parts.join(', ') : 'Alamat belum diisi';
      }

      // Show confirmation modal
      function showKonfirmasiModal(productId) {
        const product = getProductById(productId);
        if (!product) return;

        if (!getCurrentUser()) {
          window.location.href = `login.php?product_id=${productId}`;
          return;
        }

        const startDateInput = document.getElementById(`start-date-${productId}`);
        const endDateInput = document.getElementById(`end-date-${productId}`);
        const deliveryMethodInput = document.querySelector(`input[name="delivery-method-${productId}"]:checked`);
        
        if (!startDateInput || !endDateInput || !deliveryMethodInput) {
          alert('Silakan lengkapi semua data yang wajib diisi.');
          return;
        }

        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        // Validate dates
        if (startDate < today) {
          alert('Tanggal mulai tidak boleh sebelum hari ini.');
          return;
        }
        
        if (endDate < startDate) {
          alert('Tanggal selesai harus setelah tanggal mulai.');
          return;
        }

        // Calculate total
        const timeDiff = Math.abs(endDate - startDate);
        const days = Math.ceil(timeDiff / (1000 * 60 * 60 * 24)) + 1;
        const dailyRate = product.discount > 0
          ? Math.round(product.price * (1 - product.discount / 100))
          : product.price;
        const deliveryFee = deliveryMethodInput.value === 'diantar' ? 50000 : 0;
        const total = (dailyRate * days) + deliveryFee;

        // Store pending rental data
        pendingRental = {
          productId: productId,
          startDate: startDateInput.value,
          endDate: endDateInput.value,
          deliveryMethod: deliveryMethodInput.value,
          total: total,
          days: days,
          dailyRate: dailyRate,
          deliveryFee: deliveryFee
        };

        // Populate confirmation modal
        document.getElementById('confirm-product-name').textContent = product.name;
        document.getElementById('confirm-rental-dates').textContent = `${startDateInput.value} sampai ${endDateInput.value}`;
        document.getElementById('confirm-delivery-method').textContent = deliveryMethodInput.value;
        document.getElementById('confirm-total').textContent = window.formatCurrencyIDR(total);

        const confirmDeliveryAddressRow = document.getElementById('confirm-delivery-address-row');
        const confirmDeliveryAddress = document.getElementById('confirm-delivery-address');
        const isDelivery = deliveryMethodInput.value === 'diantar';

        if (confirmDeliveryAddressRow && confirmDeliveryAddress) {
          confirmDeliveryAddress.textContent = formatDeliveryAddress(getCurrentUser());
          confirmDeliveryAddressRow.classList.toggle('hidden', !isDelivery);
        }

        // Show confirmation modal
        const modal = document.getElementById('confirm-modal');
        const modalContent = document.getElementById('confirm-modal-content');
        
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.remove('opacity-0');
        modalContent.classList.remove('opacity-0', 'scale-95');
        modalContent.classList.add('scale-100');
      }

      // Close confirmation modal
      function closeKonfirmasiModal() {
        const modal = document.getElementById('confirm-modal');
        const modalContent = document.getElementById('confirm-modal-content');
        
        modal.classList.add('opacity-0');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
          modal.classList.add('hidden');
        }, 200);
      }

      // Konfirmasi and submit rental
      function confirmAndSubmit() {
        if (!pendingRental) return;

        const payload = new URLSearchParams({
          csrf_token: window.csrfToken,
          product_id: String(pendingRental.productId),
          start_date: pendingRental.startDate,
          end_date: pendingRental.endDate,
          delivery_method: pendingRental.deliveryMethod,
          total_days: String(pendingRental.days)
        });

        fetch('process/rental-create-process.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: payload.toString()
        })
        .then(response => response.json())
        .then(result => {
          if (!result.success) {
            alert(result.message || 'Gagal menyimpan rental.');
            return;
          }

          const rental = result.rental;
          closeKonfirmasiModal();
          showSuccessModal(rental);

          const startDateInput = document.getElementById(`start-date-${pendingRental.productId}`);
          const endDateInput = document.getElementById(`end-date-${pendingRental.productId}`);
          startDateInput.value = new Date().toISOString().split('T')[0];
          endDateInput.value = new Date(Date.now() + 86400000).toISOString().split('T')[0];
          document.querySelector(`input[name="delivery-method-${pendingRental.productId}"][value="ambil_sendiri"]`).checked = true;
          calculateTotal(pendingRental.productId);

          pendingRental = null;
        })
        .catch(() => alert('Gagal menyimpan rental. Silakan coba lagi.'));
      }

      // Show success modal
      function showSuccessModal(rental) {
        const modal = document.getElementById('success-modal');
        const modalContent = document.getElementById('modal-content');
        
        document.getElementById('modal-rental-id').textContent = rental.id;
        document.getElementById('modal-product-name').textContent = rental.product.name;
        document.getElementById('modal-rental-dates').textContent = `${rental.startDate} sampai ${rental.endDate}`;
        document.getElementById('modal-delivery-method').textContent = rental.deliveryMethod;
        document.getElementById('modal-total').textContent = window.formatCurrencyIDR(rental.total);
        
        // Show modal with animation
        modal.classList.remove('hidden');
        // Trigger reflow
        void modal.offsetWidth;
        modal.classList.remove('opacity-0');
        modalContent.classList.remove('opacity-0', 'scale-95');
        modalContent.classList.add('scale-100');
      }

      // Close modal
      function closeModal() {
        const modal = document.getElementById('success-modal');
        const modalContent = document.getElementById('modal-content');
        
        modal.classList.add('opacity-0');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        
        // Wait for transition to complete before hiding
        setTimeout(() => {
          modal.classList.add('hidden');
        }, 200);
      }

      // View rentals page
      function viewRentals() {
        closeModal();
        window.location.href = 'user/rentals.php';
      }

      // Initialize delivery method selection on page load
      function initDikirimMethodSelection(productId) {
        updateDikirimMethodSelection(productId);
      }
    </script>
  <script>window.currentUser = <?= $current_user_json ?>;</script>
  <script>window.csrfToken = <?= json_encode(csrf_token()) ?>;</script>
    <?php if ($is_user_catalog): ?>
    <nav class="floating-nav" role="navigation" aria-label="Quick navigation">
      <button class="floating-nav-btn active" data-nav="home" aria-label="Beranda">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <span>Beranda</span>
      </button>
      <button class="floating-nav-btn" data-nav="rentals" aria-label="Rental">
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
      (function () {
        const floatingNav = document.querySelector('.floating-nav');
        if (!floatingNav) return;
        const footer = document.querySelector('footer');
        const buttons = document.querySelectorAll('.floating-nav-btn');
        function syncFloatingNavFooterState() {
          if (!footer) return;
          const footerRect = footer.getBoundingClientRect();
          const threshold = floatingNav.offsetHeight + 48;
          floatingNav.classList.toggle('footer-near', footerRect.top <= window.innerHeight - threshold);
        }
        buttons.forEach((button) => {
          button.addEventListener('click', function () {
            const pages = { home: 'products.php', rentals: 'user/rentals.php', settings: 'user/profile.php' };
            if (pages[this.dataset.nav]) {
              window.location.href = pages[this.dataset.nav];
            }
          });
        });
        syncFloatingNavFooterState();
        window.addEventListener('scroll', syncFloatingNavFooterState, { passive: true });
        window.addEventListener('resize', syncFloatingNavFooterState);
      })();
    </script>
    <?php endif; ?>
    <?= page_runtime_bundle($flash_script) ?>
  </body>
</html>
