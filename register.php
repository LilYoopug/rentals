<?php
require_once __DIR__ . '/includes/flash.php';

if (is_logged_in()) {
    redirect_logged_in_user_home();
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LensCraft - Buat Akun</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap"
      rel="stylesheet"
    />
    <style>
      :root {
        --bg-page: #050505;
        --bg-panel: rgba(17, 17, 17, 0.84);
        --bg-panel-soft: rgba(17, 17, 17, 0.62);
        --border-soft: rgba(255, 255, 255, 0.08);
        --text-soft: #a3a3a3;
        --accent-brass: #c7a65a;
        --accent-brass-soft: rgba(199, 166, 90, 0.18);
      }
      body {
        font-family: "Inter", sans-serif;
        background:
          radial-gradient(circle at top left, rgba(199, 166, 90, 0.1), transparent 28%),
          radial-gradient(circle at 85% 15%, rgba(255, 255, 255, 0.05), transparent 22%),
          var(--bg-page);
      }
      .font-serif {
        font-family: "Playfair Display", serif;
      }
      .nav-blur {
        background: rgba(5, 5, 5, 0.86);
        backdrop-filter: blur(18px);
      }
      .auth-panel {
        background: linear-gradient(180deg, rgba(22, 22, 22, 0.92), rgba(12, 12, 12, 0.88));
        border: 1px solid var(--border-soft);
        box-shadow:
          0 24px 70px rgba(0, 0, 0, 0.34),
          inset 0 1px 0 rgba(255, 255, 255, 0.04);
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
      .subtle-link {
        color: #e5e5e5;
        text-decoration-color: rgba(199, 166, 90, 0.6);
        text-underline-offset: 0.24em;
      }
      .input-focus {
        transition:
          border-color 0.2s ease,
          box-shadow 0.2s ease,
          background-color 0.2s ease;
      }
      .input-focus:focus {
        border-color: var(--accent-brass);
        box-shadow: 0 0 0 3px var(--accent-brass-soft);
        background-color: rgba(32, 32, 32, 0.96);
      }
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
    </style>
  </head>

  <body class="bg-neutral-950 text-neutral-100 min-h-screen flex flex-col">
    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 nav-blur border-b border-neutral-800">
      <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <a href="index.php" class="text-2xl font-bold font-serif text-white tracking-tight">
          LensCraft
        </a>
        <div class="text-sm text-neutral-400">
          Registrasi akun rental
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center pt-24 pb-12 px-6">
      <div class="max-w-md w-full space-y-8 animate-fade-in">
        <!-- Header -->
        <div class="text-center space-y-4">
          <h1 class="text-4xl font-serif mt-4">Buat akun LensCraft</h1>
          <p class="text-neutral-400 text-sm leading-relaxed max-w-sm mx-auto">
            Daftar untuk menyewa peralatan, mengelola booking, dan menerima pembaruan status rental dalam satu tempat.
          </p>
        </div>

        <!-- Register Form -->
        <form class="space-y-5 auth-panel p-8 rounded-[1.75rem]" action="process/register-process.php" method="POST">
          <!-- Full Name -->
          <div>
            <label for="fullname" class="block text-sm font-medium text-neutral-300 mb-2">
              Nama Lengkap
            </label>
            <input
              type="text"
              id="fullname"
              name="fullname"
              required
              class="w-full px-4 py-3 bg-neutral-800/90 border border-neutral-700 rounded-xl text-neutral-100 placeholder-neutral-500 input-focus outline-none"
              placeholder="Masukkan nama lengkap"
            />
          </div>

          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-neutral-300 mb-2">
              Email
            </label>
            <input
              type="email"
              id="email"
              name="email"
              required
              class="w-full px-4 py-3 bg-neutral-800/90 border border-neutral-700 rounded-xl text-neutral-100 placeholder-neutral-500 input-focus outline-none"
              placeholder="your.email@example.com"
            />
          </div>

          <!-- Username -->
          <div>
            <label for="username" class="block text-sm font-medium text-neutral-300 mb-2">
              Username
            </label>
            <input
              type="text"
              id="username"
              name="username"
              required
              class="w-full px-4 py-3 bg-neutral-800/90 border border-neutral-700 rounded-xl text-neutral-100 placeholder-neutral-500 input-focus outline-none"
              placeholder="Pilih username"
            />
          </div>

          <!-- Password -->
          <div class="relative">
            <label for="password" class="block text-sm font-medium text-neutral-300 mb-2">
              Kata Sandi
            </label>
            <input
              type="password"
              id="password"
              name="password"
              required
              minlength="6"
              class="w-full px-4 pr-12 py-3 bg-neutral-800/90 border border-neutral-700 rounded-xl text-neutral-100 placeholder-neutral-500 input-focus outline-none"
              placeholder="Buat kata sandi minimal 6 karakter"
            />
            <button
              type="button"
              onclick="togglePassword('password', this)"
              class="absolute right-3 top-9 text-neutral-400 hover:text-white transition-colors p-1"
              aria-label="Tampilkan atau sembunyikan kata sandi"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
          </div>

          <!-- Confirm Password -->
          <div class="relative">
            <label for="confirm_password" class="block text-sm font-medium text-neutral-300 mb-2">
              Konfirmasi Kata Sandi
            </label>
            <input
              type="password"
              id="confirm_password"
              name="confirm_password"
              required
              class="w-full px-4 pr-12 py-3 bg-neutral-800/90 border border-neutral-700 rounded-xl text-neutral-100 placeholder-neutral-500 input-focus outline-none"
              placeholder="Masukkan ulang kata sandi"
            />
            <button
              type="button"
              onclick="togglePassword('confirm_password', this)"
              class="absolute right-3 top-9 text-neutral-400 hover:text-white transition-colors p-1"
              aria-label="Tampilkan atau sembunyikan kata sandi"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
          </div>

          <!-- Terms Checkbox -->
          <div>
            <label class="flex items-start gap-2 text-sm text-neutral-400 cursor-pointer">
              <input type="checkbox" name="terms" required class="rounded border-neutral-600 bg-neutral-800 text-[var(--accent-brass)] focus:ring-0 mt-0.5" />
              <span>
                Saya menyetujui <button type="button" onclick="openModal('terms')" class="subtle-link hover:text-white bg-transparent border-none p-0 cursor-pointer">Syarat Layanan</button> dan <button type="button" onclick="openModal('privacy')" class="subtle-link hover:text-white bg-transparent border-none p-0 cursor-pointer">Kebijakan Privasi</button>
              </span>
            </label>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            class="w-full py-3 bg-white text-black font-semibold rounded-xl hover:bg-neutral-200 button-primary active:scale-[0.98]"
          >
            Buat Akun
          </button>

          <!-- Login Link -->
          <div class="text-center text-sm text-neutral-400">
            Sudah punya akun?
            <a href="login.php" class="subtle-link hover:text-white font-medium">Masuk</a>
          </div>
        </form>

        <!-- Footer -->
        <div class="text-center text-xs text-neutral-500 pt-4">
          <p>© 2026 LensCraft. Sistem Rental Kamera.</p>
          <p class="mt-1">Semua akun baru ditinjau dalam 24 jam.</p>
        </div>
      </div>
    </main>

    <!-- TOS/PP Modal -->
    <div id="modal-overlay" class="fixed inset-0 z-50 bg-neutral-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
      <div class="bg-neutral-900 border border-neutral-800 rounded-[1.75rem] w-full max-w-2xl max-h-[80vh] flex flex-col animate-fade-in shadow-2xl">
        <div class="flex items-center justify-between p-6 border-b border-neutral-800">
          <h2 id="modal-title" class="text-xl font-semibold text-white">Title</h2>
          <button onclick="closeModal()" class="text-neutral-400 hover:text-white transition-colors p-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div id="modal-content" class="flex-1 overflow-y-auto p-6 text-sm text-neutral-300 leading-relaxed">
          <!-- Content will be injected here -->
        </div>
        <div class="p-6 border-t border-neutral-800">
          <button onclick="closeModal()" class="w-full py-3 bg-white text-black font-semibold rounded-xl hover:bg-neutral-200 transition-colors button-primary">
            Tutup
          </button>
        </div>
      </div>
    </div>

    <script>
      const modalData = {
        terms: {
          title: 'Syarat Layanan',
          content: `
            <h3 class="text-lg font-semibold text-white mb-4">LensCraft - Syarat Layanan</h3>
            <p class="mb-4">Terakhir diperbarui: Maret 2026</p>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">1. Persetujuan Syarat</h4>
            <p class="mb-4">Dengan mengakses dan menggunakan LensCraft, Anda menyetujui seluruh syarat dan ketentuan layanan ini. Jika Anda tidak menyetujuinya, jangan gunakan platform ini.</p>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">2. Akun Pengguna</h4>
            <p class="mb-4">Untuk menggunakan fitur tertentu di LensCraft, Anda harus membuat akun. Anda setuju memberikan informasi yang akurat, terbaru, dan lengkap saat registrasi serta memperbaruinya bila ada perubahan.</p>
            <p class="mb-4">Anda bertanggung jawab menjaga kredensial akun dan seluruh aktivitas yang terjadi di akun Anda. Segera beri tahu kami jika ada penggunaan akun tanpa izin.</p>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">3. Layanan Rental</h4>
            <p class="mb-4">LensCraft menyediakan platform untuk menyewa peralatan fotografi dan videografi. Semua rental bergantung pada ketersediaan stok dan konfirmasi pembayaran.</p>
            <p class="mb-4">Periode rental ditentukan saat pemesanan. Keterlambatan pengembalian dapat menimbulkan biaya tambahan sesuai tarif harian. Peralatan yang rusak atau hilang akan dikenakan biaya penggantian.</p>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">4. Ketentuan Pembayaran</h4>
            <p class="mb-4">Seluruh biaya rental harus dibayar lunas sebelum peralatan dikirim atau diambil. Metode pembayaran mengikuti opsi yang tersedia saat checkout.</p>
            <p class="mb-4">Pengembalian dana mengikuti kebijakan pembatalan LensCraft dan diproses sesuai peninjauan kami.</p>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">5. Tanggung Jawab Pengguna</h4>
            <p class="mb-4">Anda setuju untuk tidak menggunakan LensCraft untuk tujuan melanggar hukum atau dengan cara yang dapat merusak, membebani, atau mengganggu layanan kami.</p>
            <p class="mb-4">Anda bertanggung jawab menjaga peralatan selama masa rental dan mengembalikannya sesuai syarat yang disepakati.</p>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">6. Batas Tanggung Jawab</h4>
            <p class="mb-4">LensCraft tidak bertanggung jawab atas kerugian tidak langsung, insidental, khusus, atau konsekuensial yang timbul dari penggunaan layanan kami.</p>
            <p class="mb-4">Batas tanggung jawab kami tidak melebihi jumlah yang Anda bayarkan untuk rental terkait.</p>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">7. Changes to Terms</h4>
            <p class="mb-4">Kami berhak memperbarui syarat layanan kapan saja. Penggunaan platform setelah perubahan berarti Anda menerima versi terbaru dari syarat tersebut.</p>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">8. Informasi Kontak</h4>
            <p class="mb-4">Untuk pertanyaan terkait syarat layanan, hubungi kami di support@lenscraft.com</p>
          `
        },
        privacy: {
          title: 'Kebijakan Privasi',
          content: `
            <h3 class="text-lg font-semibold text-white mb-4">LensCraft - Kebijakan Privasi</h3>
            <p class="mb-4">Terakhir diperbarui: Maret 2026</p>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">1. Informasi yang Kami Kumpulkan</h4>
            <p class="mb-4">Kami mengumpulkan informasi yang Anda berikan langsung kepada kami, termasuk namun tidak terbatas pada:</p>
            <ul class="list-disc pl-6 mb-4 space-y-2">
              <li>Nama, alamat email, dan nomor telepon</li>
              <li>Alamat penagihan dan pengiriman</li>
              <li>Kredensial akun</li>
              <li>Riwayat rental dan preferensi</li>
            </ul>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">2. Cara Kami Menggunakan Data Anda</h4>
            <p class="mb-4">Kami menggunakan informasi yang kami kumpulkan untuk:</p>
            <ul class="list-disc pl-6 mb-4 space-y-2">
              <li>Memproses registrasi dan rental Anda</li>
              <li>Mengirim pembaruan transaksi dan pesan dukungan</li>
              <li>Meningkatkan layanan dan pengalaman pengguna</li>
              <li>Memenuhi kewajiban hukum</li>
            </ul>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">3. Berbagi Data</h4>
            <p class="mb-4">Kami tidak menjual data pribadi Anda. Kami dapat membagikan informasi Anda kepada:</p>
            <ul class="list-disc pl-6 mb-4 space-y-2">
              <li>Penyedia layanan yang membantu operasional kami seperti pembayaran dan pengiriman</li>
              <li>Otoritas hukum jika diwajibkan oleh peraturan</li>
              <li>Mitra bisnis dengan persetujuan Anda</li>
            </ul>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">4. Keamanan Data</h4>
            <p class="mb-4">Kami menerapkan langkah teknis dan organisasi yang sesuai untuk melindungi data pribadi Anda. Namun, tidak ada metode transmisi yang sepenuhnya aman dan kami tidak dapat menjamin keamanan absolut.</p>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">5. Hak Anda</h4>
            <p class="mb-4">Anda berhak mengakses, memperbaiki, atau menghapus data pribadi Anda. Anda juga dapat berhenti menerima komunikasi pemasaran kapan saja.</p>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">6. Cookie</h4>
            <p class="mb-4">Kami menggunakan cookie untuk meningkatkan pengalaman Anda, menganalisis lalu lintas situs, dan menyesuaikan konten. Anda dapat mengelola preferensi cookie melalui pengaturan browser Anda.</p>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">7. Changes to Kebijakan Privasi</h4>
            <p class="mb-4">Kami dapat memperbarui kebijakan ini secara berkala. Versi terbaru akan ditandai dengan tanggal pembaruan yang baru.</p>

            <h4 class="text-base font-semibold text-white mt-6 mb-3">8. Kontak</h4>
            <p class="mb-4">Untuk pertanyaan privasi, hubungi tim kami di privacy@lenscraft.com</p>
          `
        }
      };

      function openModal(type) {
        const data = modalData[type];
        if (!data) return;

        document.getElementById('modal-title').textContent = data.title;
        document.getElementById('modal-content').innerHTML = data.content;
        document.getElementById('modal-overlay').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
      }

      function closeModal() {
        document.getElementById('modal-overlay').classList.add('hidden');
        document.body.style.overflow = '';
      }

      // Close modal on overlay click
      document.getElementById('modal-overlay').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
      });

      // Close modal on Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
      });

      function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('svg');

        if (input.type === 'password') {
          input.type = 'text';
          // Change to eye-off icon
          icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
        } else {
          input.type = 'password';
          // Change back to eye icon
          icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
        }
      }
    </script>
  <?= page_runtime_bundle($flash_script) ?>
</body>
</html>
