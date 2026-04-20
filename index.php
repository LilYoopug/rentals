<?php
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/data/products-data.php';

$popular_products = array_slice(get_all_products(), 0, 3);
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LensCraft - Rental Kamera Profesional</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap"
      rel="stylesheet"
    />
    <style>
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
      .neutral-bg {
        background-color: #3b82f6;
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
        background: rgba(0, 0, 0, 0.9);
        backdrop-filter: blur(12px);
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
      @keyframes float {
        0%,
        100% {
          transform: translateY(0);
        }
        50% {
          transform: translateY(-10px);
        }
      }
      .animate-fade-in {
        opacity: 0;
        animation: fadeInUp 0.8s ease-out forwards;
      }
      .animate-float {
        animation: float 6s ease-in-out infinite;
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
    </style>
  </head>

  <body class="bg-neutral-950 text-neutral-100">
    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 nav-blur border-b border-neutral-800">
      <div
        class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center"
      >
        <div class="text-2xl font-bold font-serif text-white tracking-tight">
          LensCraft
        </div>
        <div
          class="hidden md:flex space-x-8 text-sm font-medium text-neutral-400"
        >
          <a href="products.php" class="hover:text-white transition-colors">Kamera</a>
          <a href="products.php" class="hover:text-white transition-colors">Lensa</a>
          <a href="products.php" class="hover:text-white transition-colors"
            >Paket Bundling</a
          >
          <a href="#cara-kerja" class="hover:text-white transition-colors"
            >Cara Kerja</a
          >
          <a href="mailto:hallo@lenscraft.local" class="hover:text-white transition-colors">Kontak</a>
        </div>
        <a
          href="login.php"
          class="px-6 py-2 border border-neutral-700 rounded-full text-sm font-medium hover:bg-neutral-800 transition-colors inline-block"
        >
          Masuk
        </a>
      </div>
    </nav>

    <!-- Hero -->
    <section class="hero-gradient py-20 md:py-32 animate-on-scroll">
      <div class="max-w-7xl mx-auto px-4 md:px-6">
        <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-center">
          <div class="space-y-6 md:space-y-8">
            <div
              class="inline-flex items-center px-3 py-1 border border-neutral-800 rounded-full text-xs font-medium text-neutral-400 bg-neutral-900/50"
            >
              <span class="w-2 h-2 rounded-full neutral-bg mr-2"></span>
              <span>Dipercaya 10.000+ fotografer</span>
            </div>
            <h1
              class="text-5xl md:text-6xl lg:text-7xl font-serif leading-tight"
            >
              Sewa <span class="text-neutral-300">peralatan terbaik</span>
              untuk sesi berikutnya
            </h1>
            <p class="text-lg text-neutral-400 leading-relaxed max-w-xl">
              Kamera dan lensa profesional siap dipakai dengan sewa harian atau
              mingguan, dukungan tim, dan proses pemesanan yang sederhana.
            </p>
            <div class="flex flex-col sm:flex-row gap-4">
              <a
                href="products.php"
                class="px-8 py-4 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-all transform hover:scale-105 text-center"
              >
                Lihat Katalog
              </a>
              <a
                href="#cara-kerja"
                class="px-8 py-4 border border-neutral-700 rounded-lg font-medium hover:bg-neutral-800 transition-colors hover:scale-105 text-center"
              >
                Cara Kerja
              </a>
            </div>
            <div class="flex items-center gap-8 text-sm text-neutral-500">
              <div class="flex items-center gap-2">
                <svg
                  class="w-5 h-5 text-white"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                  />
                </svg>
                <span>Rating 4,9/5</span>
              </div>
              <div class="flex items-center gap-2">
                <svg
                  class="w-5 h-5 text-white"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                    clip-rule="evenodd"
                  />
                </svg>
                <span>Dukungan 24/7</span>
              </div>
            </div>
          </div>
          <div class="relative hidden md:block">
            <div
              class="absolute inset-0 bg-gradient-to-tr from-white/10 to-transparent rounded-3xl blur-3xl"
            ></div>
            <div
              class="relative bg-neutral-900 p-4 rounded-3xl border border-neutral-800 shadow-2xl"
            >
              <div class="h-80 rounded-2xl overflow-hidden">
                <img
                  src="images/hero-camera.jpg"
                  alt="Premium Camera"
                  class="w-full h-full object-cover hero-image hidden md:block"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section id="keunggulan" class="py-24 px-6 animate-on-scroll">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-serif mb-4">
            Kenapa memilih <span class="text-neutral-300">LensCraft</span>?
          </h2>
          <p class="text-neutral-400 max-w-2xl mx-auto">
            Peralatan profesional siap dipakai tanpa biaya investasi besar dan
            tanpa repot pemeliharaan.
          </p>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
          <!-- Feature 1 -->
          <div
            class="bg-neutral-900/50 border border-neutral-800 p-8 rounded-2xl card-hover"
          >
            <div
              class="w-12 h-12 bg-white rounded-xl flex items-center justify-center mb-6"
            >
              <svg
                class="w-6 h-6 text-black"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                ></path>
              </svg>
            </div>
            <h3 class="text-xl font-semibold mb-3">Tarif Harian Terjangkau</h3>
            <p class="text-neutral-400 text-sm leading-relaxed">
              Rent professional gear for a fraction of retail price. Weekly
              discounts available.
            </p>
          </div>
          <!-- Feature 2 -->
          <div
            class="bg-neutral-900/50 border border-neutral-800 p-8 rounded-2xl card-hover"
          >
            <div
              class="w-12 h-12 bg-white rounded-xl flex items-center justify-center mb-6"
            >
              <svg
                class="w-6 h-6 text-black"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                ></path>
              </svg>
            </div>
            <h3 class="text-xl font-semibold mb-3">Asuransi Termasuk</h3>
            <p class="text-neutral-400 text-sm leading-relaxed">
              Every rental comes with comprehensive coverage. Shoot with peace
              of mind.
            </p>
          </div>
          <!-- Feature 3 -->
          <div
            class="bg-neutral-900/50 border border-neutral-800 p-8 rounded-2xl card-hover"
          >
            <div
              class="w-12 h-12 bg-white rounded-xl flex items-center justify-center mb-6"
            >
              <svg
                class="w-6 h-6 text-black"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 10V3L4 14h7v7l9-11h-7z"
                ></path>
              </svg>
            </div>
            <h3 class="text-xl font-semibold mb-3">Pengiriman Cepat</h3>
            <p class="text-neutral-400 text-sm leading-relaxed">
              Free same-day delivery in select areas. Track your gear in
              real-time.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Popular Gear -->
    <section class="py-24 px-6 animate-on-scroll">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-serif mb-3">
            Peralatan <span class="text-neutral-300">Populer</span>
          </h2>
          <p class="text-neutral-400">
            Pilihan favorit untuk produksi foto dan video
          </p>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
          <?php foreach ($popular_products as $product): ?>
          <div
            class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden card-hover"
          >
            <img
              src="<?= e((string) ($product['image'] ?? 'images/gear-placeholder.svg')) ?>"
              alt="<?= e((string) ($product['name'] ?? 'Popular gear')) ?>"
              class="w-full h-48 object-cover"
              onerror="this.src='images/gear-placeholder.svg'"
            />
            <div class="p-6">
              <div class="text-xs font-medium text-neutral-500 mb-1">
                <?= e(ucfirst((string) ($product['category'] ?? 'gear'))) ?>
              </div>
              <h3 class="text-lg font-semibold mb-2"><?= e((string) ($product['name'] ?? 'LensCraft Gear')) ?></h3>
              <div class="flex justify-between items-center">
                <span class="text-xl"
                  ><?= e(format_currency((float) ($product['price'] ?? 0))) ?><span class="text-sm text-neutral-500">/day</span></span
                >
                <a
                  href="product-detail.php?id=<?= e((string) ($product['id'] ?? 0)) ?>"
                  class="px-4 py-2 bg-neutral-800 text-sm rounded hover:bg-neutral-700 inline-block"
                >
                  Lihat
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- How It Works -->
    <section id="cara-kerja" class="py-24 px-6 border-t border-neutral-800 animate-on-scroll">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-serif mb-3">
            Cara <span class="text-neutral-300">Kerja</span>
          </h2>
        </div>
        <div class="grid md:grid-cols-4 gap-8">
          <div class="text-center">
            <div
              class="w-16 h-16 mx-auto mb-4 border border-neutral-700 rounded-full flex items-center justify-center text-2xl font-bold text-neutral-300"
            >
              1
            </div>
            <h3 class="font-semibold mb-2">Pilih</h3>
            <p class="text-neutral-400 text-sm">
              Jelajahi katalog kamera, lensa, dan aksesori yang tersedia.
            </p>
          </div>
          <div class="text-center">
            <div
              class="w-16 h-16 mx-auto mb-4 border border-neutral-700 rounded-full flex items-center justify-center text-2xl font-bold text-neutral-300"
            >
              2
            </div>
            <h3 class="font-semibold mb-2">Atur Durasi</h3>
            <p class="text-neutral-400 text-sm">
              Tentukan tanggal sewa yang sesuai dengan jadwal produksi Anda.
            </p>
          </div>
          <div class="text-center">
            <div
              class="w-16 h-16 mx-auto mb-4 border border-neutral-700 rounded-full flex items-center justify-center text-2xl font-bold text-neutral-300"
            >
              3
            </div>
            <h3 class="font-semibold mb-2">Konfirmasi</h3>
            <p class="text-neutral-400 text-sm">
              Masuk ke akun Anda lalu kirim permintaan rental untuk ditinjau.
            </p>
          </div>
          <div class="text-center">
            <div
              class="w-16 h-16 mx-auto mb-4 border border-neutral-700 rounded-full flex items-center justify-center text-2xl font-bold text-neutral-300"
            >
              4
            </div>
            <h3 class="font-semibold mb-2">Gunakan</h3>
            <p class="text-neutral-400 text-sm">
              Ambil atau terima peralatan, lalu kembalikan setelah selesai.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="py-24 px-6 border-t border-neutral-800 animate-on-scroll">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-serif mb-3">
            Apa Kata <span class="text-neutral-300">Penyewa Kami</span>
          </h2>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
          <div class="bg-neutral-900 border border-neutral-800 p-8 rounded-2xl">
            <p class="text-neutral-300 italic mb-4">
              "LensCraft menyelamatkan sesi wedding saya saat kamera utama
              mendadak bermasalah. Pengirimannya cepat dan alatnya benar-benar
              siap pakai."
            </p>
            <div class="font-semibold">Sarah K.</div>
            <div class="text-sm text-neutral-500">Fotografer Acara</div>
          </div>
          <div class="bg-neutral-900 border border-neutral-800 p-8 rounded-2xl">
            <p class="text-neutral-300 italic mb-4">
              "Layanan rental terbaik yang pernah saya pakai. Prosesnya rapi,
              alatnya terawat, dan saya jadi jauh lebih tenang saat produksi."
            </p>
            <div class="font-semibold">Mike R.</div>
            <div class="text-sm text-neutral-500">Videografer Komersial</div>
          </div>
          <div class="bg-neutral-900 border border-neutral-800 p-8 rounded-2xl">
            <p class="text-neutral-300 italic mb-4">
              "Harganya masuk akal, pilihannya lengkap, dan selalu bisa
              diandalkan. Jadi tempat pertama yang saya cek untuk setiap
              proyek."
            </p>
            <div class="font-semibold">Jenna L.</div>
            <div class="text-sm text-neutral-500">Kreator Konten</div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="py-24 px-6">
      <div
        class="max-w-4xl mx-auto bg-gradient-to-r from-neutral-900 to-neutral-800 border border-neutral-800 rounded-3xl p-12 text-center"
      >
        <h2 class="text-3xl md:text-4xl font-serif mb-6">
          Siap meningkatkan kualitas produksi Anda?
        </h2>
        <p class="text-neutral-400 mb-8 max-w-xl mx-auto">
          Masuk dan pilih peralatan yang Anda butuhkan untuk jadwal berikutnya.
        </p>
        <a
          href="login.php"
          class="inline-block px-8 py-4 bg-white text-black font-bold rounded-lg hover:bg-neutral-200 transition-all text-lg no-underline"
        >
          Mulai Sekarang
        </a>
      </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-neutral-800 py-12 px-6">
      <div
        class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center"
      >
        <div class="flex space-x-6 text-sm text-neutral-500">
          <a href="privacy.php" class="hover:text-white">Privasi</a>
          <a href="terms.php" class="hover:text-white">Syarat</a>
          <a href="mailto:hallo@lenscraft.local" class="hover:text-white">Kontak</a>
        </div>
        <div class="mt-4 md:mt-0 text-sm text-neutral-600">
          © 2026 LensCraft. Seluruh hak cipta dilindungi.
        </div>
      </div>
    </footer>

    <script>
      // Intersection Observer for scroll animations
      const observerOptions = {
        root: null,
        rootMargin: "0px",
        threshold: 0.1,
      };
      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = "1";
            entry.target.style.transform = "translateY(0)";
          }
        });
      }, observerOptions);

      document.querySelectorAll(".animate-on-scroll").forEach((section) => {
        section.style.opacity = "0";
        section.style.transform = "translateY(30px)";
        section.style.transition =
          "opacity 0.8s ease-out, transform 0.8s ease-out";
        observer.observe(section);
      });

      // Hero float animation for image
      const heroImg = document.querySelector(".hero-image");
      if (heroImg) {
        heroImg.classList.add("animate-float");
      }
    </script>
  <?= page_runtime_bundle($flash_script) ?>
</body>
</html>
